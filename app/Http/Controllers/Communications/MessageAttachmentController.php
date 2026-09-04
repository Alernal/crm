<?php

namespace App\Http\Controllers\Communications;

use App\Http\Controllers\Communications\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Models\MessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageAttachmentController extends Controller
{
    use ResolvesActor;

    public function show(Request $request, MessageAttachment $attachment): StreamedResponse
    {
        $actor = $this->actor($request);
        $channel = $attachment->message->channel;

        abort_if($channel->owner_id !== $actor->communicationOwnerId(), 403);

        $isMember = $channel->members()
            ->where('member_type', $actor::class)
            ->where('member_id', $actor->getKey())
            ->exists();

        abort_unless($isMember, 403);

        return Storage::disk('local')->response(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type]
        );
    }
}
