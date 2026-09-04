<?php

namespace App\Http\Controllers\Communications;

use App\Http\Controllers\Communications\Concerns\AuthorizesChannelManagement;
use App\Http\Controllers\Communications\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChannelMemberController extends Controller
{
    use ResolvesActor, AuthorizesChannelManagement;

    public function store(Request $request, Channel $channel): RedirectResponse
    {
        $actor = $this->actor($request);
        abort_if($channel->owner_id !== $actor->communicationOwnerId(), 403);
        $this->authorizeManagement($request, $channel, $actor);

        $data = $request->validate([
            'team_member_id' => ['required', 'exists:team_members,id'],
        ]);

        $teamMember = TeamMember::where('id', $data['team_member_id'])
            ->where('owner_id', $channel->owner_id)
            ->firstOrFail();

        $channel->members()->firstOrCreate(
            ['member_type' => TeamMember::class, 'member_id' => $teamMember->id],
            ['role' => 'member', 'joined_at' => now()]
        );

        return redirect()->route('communications.show', $channel)
            ->with('success', 'Miembro agregado al canal.');
    }

    public function destroy(Request $request, Channel $channel, ChannelMember $channelMember): RedirectResponse
    {
        $actor = $this->actor($request);
        abort_if($channel->owner_id !== $actor->communicationOwnerId(), 403);
        abort_if($channelMember->channel_id !== $channel->id, 404);
        $this->authorizeManagement($request, $channel, $actor);

        $channelMember->delete();

        return redirect()->route('communications.show', $channel)
            ->with('success', 'Miembro eliminado del canal.');
    }
}
