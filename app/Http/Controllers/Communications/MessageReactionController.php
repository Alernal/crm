<?php

namespace App\Http\Controllers\Communications;

use App\Http\Controllers\Communications\Concerns\FormatsMessages;
use App\Http\Controllers\Communications\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Message;
use App\Models\MessageReaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageReactionController extends Controller
{
    use ResolvesActor, FormatsMessages;

    public function store(Request $request, Channel $channel, Message $message): JsonResponse
    {
        $actor = $this->actor($request);
        abort_if($channel->owner_id !== $actor->communicationOwnerId(), 403);
        abort_if($message->channel_id !== $channel->id, 404);

        $isMember = $channel->members()
            ->where('member_type', $actor::class)
            ->where('member_id', $actor->getKey())
            ->exists();

        abort_unless($isMember, 403);

        $data = $request->validate([
            'emoji' => ['required', 'string', 'max:8', 'in:'.implode(',', MessageReaction::QUICK_EMOJIS)],
        ]);

        $existing = $message->reactions()
            ->where('actor_type', $actor::class)
            ->where('actor_id', $actor->getKey())
            ->where('emoji', $data['emoji'])
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            $message->reactions()->create([
                'actor_type' => $actor::class,
                'actor_id' => $actor->getKey(),
                'emoji' => $data['emoji'],
            ]);
        }

        return response()->json([
            'reactions' => $this->formatReactions($message->load('reactions'), $actor),
        ]);
    }
}
