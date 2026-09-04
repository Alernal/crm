<?php

namespace App\Http\Controllers\Communications;

use App\Http\Controllers\Communications\Concerns\AuthorizesChannelManagement;
use App\Http\Controllers\Communications\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageThemeRequest;
use App\Models\Channel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessageThemeController extends Controller
{
    use ResolvesActor, AuthorizesChannelManagement;

    public function store(StoreMessageThemeRequest $request, Channel $channel): RedirectResponse
    {
        $actor = $this->actor($request);
        abort_if($channel->owner_id !== $actor->communicationOwnerId(), 403);
        abort_unless($channel->isContextual(), 404);
        $this->authorizeManagement($request, $channel, $actor);

        $channel->themes()->create([
            ...$request->validated(),
            'order' => $channel->themes()->max('order') + 1,
            'created_by_type' => $actor::class,
            'created_by_id' => $actor->getKey(),
        ]);

        return redirect()->route('communications.show', $channel)
            ->with('success', 'Tema creado.');
    }
}
