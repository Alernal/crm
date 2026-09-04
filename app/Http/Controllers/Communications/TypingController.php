<?php

namespace App\Http\Controllers\Communications;

use App\Http\Controllers\Communications\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Services\Communications\TypingPresence;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TypingController extends Controller
{
    use ResolvesActor;

    public function heartbeat(Request $request, Channel $channel): Response
    {
        $actor = $this->actor($request);
        abort_if($channel->owner_id !== $actor->communicationOwnerId(), 403);

        $isMember = $channel->members()
            ->where('member_type', $actor::class)
            ->where('member_id', $actor->getKey())
            ->exists();

        abort_unless($isMember, 403);

        TypingPresence::heartbeat($channel, $actor);

        return response()->noContent();
    }
}
