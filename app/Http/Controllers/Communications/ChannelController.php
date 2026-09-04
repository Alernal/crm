<?php

namespace App\Http\Controllers\Communications;

use App\Http\Controllers\Communications\Concerns\FormatsMessages;
use App\Http\Controllers\Communications\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChannelRequest;
use App\Models\Channel;
use App\Models\Client;
use App\Models\GeneratedDocument;
use App\Models\HasCommunicationOwner;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\MessageMention;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChannelController extends Controller
{
    use ResolvesActor, FormatsMessages;

    private const CONTEXT_TYPES = [
        'cliente' => Client::class,
        'factura' => Invoice::class,
        'contrato' => GeneratedDocument::class,
    ];

    public function index(Request $request): View
    {
        $actor = $this->actor($request);
        $this->ensureDefaultGeneralChannel($actor);

        return view('communications.index', [
            'channels' => $this->visibleChannels($actor),
            'activeChannel' => null,
        ]);
    }

    public function store(StoreChannelRequest $request): RedirectResponse
    {
        $actor = $this->actor($request);

        abort_unless($this->isOwner($request) || ($actor instanceof TeamMember && $actor->isAdmin()), 403);

        $channel = Channel::create([
            'owner_id' => $actor->communicationOwnerId(),
            'name' => $request->validated('name'),
            'slug' => Channel::generateUniqueSlug($actor->communicationOwnerId(), $request->validated('name')),
            'description' => $request->validated('description'),
            'created_by_type' => $actor::class,
            'created_by_id' => $actor->getKey(),
        ]);

        $channel->members()->create([
            'member_type' => $actor::class,
            'member_id' => $actor->getKey(),
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        return redirect()->route('communications.show', $channel)
            ->with('success', 'Canal creado correctamente.');
    }

    public function openContext(Request $request, string $type, int $id): RedirectResponse
    {
        $modelClass = self::CONTEXT_TYPES[$type] ?? abort(404);
        $owner = $request->user();
        abort_unless($owner instanceof User, 403);

        $context = $modelClass::findOrFail($id);
        abort_if($context->user_id !== $owner->id, 403);

        $name = match ($type) {
            'cliente' => $context->name,
            'factura' => "Cuenta de cobro {$context->number}",
            'contrato' => trim("{$context->documentType->default_prefix} {$context->full_number}"),
        };

        $channel = Channel::findOrCreateForContext($owner, $context, $name);

        return redirect()->route('communications.show', $channel);
    }

    public function show(Request $request, Channel $channel): View
    {
        $actor = $this->actor($request);
        abort_if($channel->owner_id !== $actor->communicationOwnerId(), 403);
        $this->ensureDefaultGeneralChannel($actor);

        $membership = $channel->members()
            ->where('member_type', $actor::class)
            ->where('member_id', $actor->getKey())
            ->first();

        if (! $membership) {
            abort_unless($this->isOwner($request), 403);

            $membership = $channel->members()->create([
                'member_type' => $actor::class,
                'member_id' => $actor->getKey(),
                'role' => 'admin',
                'joined_at' => now(),
            ]);
        }

        $membership->markAsRead();

        MessageMention::where('mentioned_type', $actor::class)
            ->where('mentioned_id', $actor->getKey())
            ->whereNull('read_at')
            ->whereHas('message', fn ($q) => $q->where('channel_id', $channel->id))
            ->update(['read_at' => now()]);

        $messages = $channel->messages()->with(['author', 'reactions', 'attachments', 'thread.author', 'thread.attachments'])->latest('id')->limit(200)->get()->reverse()->values();
        $members = $channel->members()->with('member')->get();

        $initialMessages = $messages->map(fn (Message $message) => $this->formatMessage($message, $actor));

        $mentionable = $members
            ->filter(fn ($channelMember) => $channelMember->member)
            ->map(fn ($channelMember) => [
                'handle' => $channelMember->member->mentionHandle(),
                'name' => Str::title($channelMember->member->name),
            ])
            ->values();

        $memberTeamMemberIds = $members->where('member_type', TeamMember::class)->pluck('member_id');

        $availableTeamMembers = TeamMember::where('owner_id', $channel->owner_id)
            ->where('is_active', true)
            ->whereNotIn('id', $memberTeamMemberIds)
            ->orderBy('name')
            ->get();

        $viewData = [
            'channels' => $this->visibleChannels($actor),
            'activeChannel' => $channel,
            'channel' => $channel,
            'initialMessages' => $initialMessages,
            'members' => $members,
            'mentionable' => $mentionable,
            'availableTeamMembers' => $availableTeamMembers,
            'isOwner' => $this->isOwner($request),
            'canManage' => $this->isOwner($request) || ($actor instanceof TeamMember
                && $channel->members()->where('member_type', TeamMember::class)->where('member_id', $actor->id)->where('role', 'admin')->exists()),
        ];

        if ($channel->isContextual()) {
            [$contextLabel, $contextUrl] = match ($channel->context_type) {
                Client::class => ['Cliente: '.$channel->context->name, route('clients.show', $channel->context)],
                Invoice::class => ['Cuenta de cobro: '.$channel->context->number, route('invoices.show', $channel->context)],
                GeneratedDocument::class => ['Contrato: '.$channel->context->full_number, route('documents.contracts.show', $channel->context)],
                default => [null, null],
            };

            return view('communications.show-contextual', [
                ...$viewData,
                'themes' => $channel->themes,
                'contextLabel' => $contextLabel,
                'contextUrl' => $contextUrl,
            ]);
        }

        return view('communications.show', $viewData);
    }

    /**
     * Todo contador tiene un canal #General siempre disponible desde el arranque
     * (como #general en Slack), sin necesidad de crearlo a mano. Se resuelve de
     * forma perezosa (find-or-create) en vez de sembrarlo en un evento de
     * registro, para que también aparezca en cuentas que ya existían antes de
     * este cambio.
     */
    private function ensureDefaultGeneralChannel(Model&HasCommunicationOwner $actor): void
    {
        $ownerId = $actor->communicationOwnerId();

        $general = Channel::firstOrCreate(
            ['owner_id' => $ownerId, 'slug' => 'general'],
            [
                'name' => 'General',
                'channel_type' => 'general',
                'created_by_type' => User::class,
                'created_by_id' => $ownerId,
            ]
        );

        $general->members()->firstOrCreate(
            ['member_type' => $actor::class, 'member_id' => $actor->getKey()],
            ['role' => $actor instanceof User ? 'admin' : 'member', 'joined_at' => now()]
        );
    }

    private function visibleChannels(Model&HasCommunicationOwner $actor): Collection
    {
        $query = Channel::where('owner_id', $actor->communicationOwnerId())->where('is_active', true);

        if (! $actor instanceof User) {
            $query->whereHas('members', function ($q) use ($actor) {
                $q->where('member_type', $actor::class)->where('member_id', $actor->getKey());
            });
        }

        return $query->orderByRaw("CASE WHEN slug = 'general' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get()
            ->each(function (Channel $channel) use ($actor) {
                $channel->setAttribute('unread_count', $channel->unreadCountFor($actor));
            });
    }
}
