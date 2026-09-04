<?php

namespace App\Http\Controllers\Communications\Concerns;

use App\Models\HasCommunicationOwner;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * The Comunicaciones module is reachable by two actor types (User = el
 * contador, TeamMember = su equipo invitado), authenticated through
 * "auth:web,team_member". This resolves whichever one is logged in for the
 * current request, since the guard middleware already picked the right one.
 */
trait ResolvesActor
{
    protected function actor(Request $request): Model&HasCommunicationOwner
    {
        return $request->user();
    }

    protected function isOwner(Request $request): bool
    {
        return $this->actor($request) instanceof User;
    }
}
