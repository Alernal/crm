<?php

namespace App\Http\Controllers\Communications\Concerns;

use App\Models\Channel;
use Illuminate\Http\Request;

/**
 * Shared by controllers that let the contador or a channel admin manage a
 * channel (members, temas) — everyone else is a plain member.
 */
trait AuthorizesChannelManagement
{
    private function authorizeManagement(Request $request, Channel $channel, $actor): void
    {
        if ($this->isOwner($request)) {
            return;
        }

        $isChannelAdmin = $channel->members()
            ->where('member_type', $actor::class)
            ->where('member_id', $actor->getKey())
            ->where('role', 'admin')
            ->exists();

        abort_unless($isChannelAdmin, 403);
    }
}
