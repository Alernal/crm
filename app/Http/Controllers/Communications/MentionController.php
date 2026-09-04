<?php

namespace App\Http\Controllers\Communications;

use App\Http\Controllers\Communications\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Models\MessageMention;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MentionController extends Controller
{
    use ResolvesActor;

    public function index(Request $request): JsonResponse
    {
        $actor = $this->actor($request);

        $mentions = $actor->mentions()
            ->whereNull('read_at')
            ->with('message.channel')
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'count' => $mentions->count(),
            'mentions' => $mentions->map(fn (MessageMention $mention) => [
                'id' => $mention->id,
                'channel_id' => $mention->message->channel_id,
                'channel_name' => $mention->message->channel->name,
                'excerpt' => str($mention->message->content)->limit(80)->toString(),
            ]),
        ]);
    }

    public function markRead(Request $request, MessageMention $mention): Response
    {
        $actor = $this->actor($request);

        abort_unless(
            $mention->mentioned_type === $actor::class && $mention->mentioned_id === $actor->getKey(),
            403
        );

        $mention->markAsRead();

        return response()->noContent();
    }
}
