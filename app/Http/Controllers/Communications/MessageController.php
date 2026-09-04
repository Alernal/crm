<?php

namespace App\Http\Controllers\Communications;

use App\Http\Controllers\Communications\Concerns\FormatsMessages;
use App\Http\Controllers\Communications\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Channel;
use App\Models\Message;
use App\Services\Communications\TypingPresence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    use ResolvesActor, FormatsMessages;

    public function index(Request $request, Channel $channel): JsonResponse
    {
        $actor = $this->actor($request);
        $membership = $this->requireMembership($channel, $actor);

        $query = $channel->messages()->with(['author', 'reactions', 'attachments', 'thread.author', 'thread.attachments'])->orderBy('id');

        if ($after = $request->integer('after')) {
            $query->where('id', '>', $after);
        } else {
            $query->latest('id')->limit(50);
        }

        $messages = $query->get()->sortBy('id')->values();

        $membership->markAsRead();

        return response()->json([
            'messages' => $messages->map(fn (Message $message) => $this->formatMessage($message, $actor)),
            'typing' => TypingPresence::activeLabelsFor($channel, $actor),
        ]);
    }

    public function store(StoreMessageRequest $request, Channel $channel): JsonResponse
    {
        $actor = $this->actor($request);
        $this->requireMembership($channel, $actor);

        $themeId = $request->validated('theme_id');
        $threadId = $request->validated('thread_id');

        if ($threadId !== null) {
            // Estilo WhatsApp: la cita apunta directo al mensaje que se está respondiendo
            // (aunque ese mensaje sea a su vez una respuesta), no se colapsa a su raíz.
            $target = $channel->messages()->whereKey($threadId)->first();
            abort_if(! $target, 422, 'El mensaje al que respondes no existe en este canal.');

            $themeId = $target->message_theme_id;
        } elseif ($themeId !== null) {
            abort_unless($channel->themes()->whereKey($themeId)->exists(), 422, 'El tema no pertenece a este canal.');
        }

        $message = $channel->messages()->create([
            'message_theme_id' => $themeId,
            'thread_id' => $threadId,
            'author_type' => $actor::class,
            'author_id' => $actor->getKey(),
            'content' => $request->validated('content') ?? '',
        ]);

        $this->createMentions($channel, $message, $actor);
        $this->storeAttachmentIfPresent($request, $channel, $message);

        return response()->json([
            'message' => $this->formatMessage($message->load(['author', 'reactions', 'attachments', 'thread.author', 'thread.attachments']), $actor),
        ], 201);
    }

    private function requireMembership(Channel $channel, $actor)
    {
        abort_if($channel->owner_id !== $actor->communicationOwnerId(), 403);

        $membership = $channel->members()
            ->where('member_type', $actor::class)
            ->where('member_id', $actor->getKey())
            ->first();

        abort_if(! $membership, 403, 'No perteneces a este canal.');

        return $membership;
    }

    private function createMentions(Channel $channel, Message $message, $author): void
    {
        if (! preg_match_all('/@([a-z0-9.\-]+)/i', $message->content, $matches)) {
            return;
        }

        $handles = array_unique(array_map('mb_strtolower', $matches[1]));

        if (empty($handles)) {
            return;
        }

        $channel->members()->with('member')->get()
            ->filter(fn ($channelMember) => $channelMember->member
                && ! ($channelMember->member_type === $author::class && $channelMember->member_id === $author->getKey())
                && in_array(mb_strtolower($channelMember->member->mentionHandle()), $handles, true))
            ->each(fn ($channelMember) => $message->mentions()->create([
                'mentioned_type' => $channelMember->member_type,
                'mentioned_id' => $channelMember->member_id,
            ]));
    }

    private function storeAttachmentIfPresent(StoreMessageRequest $request, Channel $channel, Message $message): void
    {
        if (! $request->hasFile('file')) {
            return;
        }

        $file = $request->file('file');
        $path = $file->store("communications/{$channel->owner_id}/{$channel->id}", 'local');

        $message->attachments()->create([
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }
}
