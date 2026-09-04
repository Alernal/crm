<?php

namespace App\Http\Controllers\Communications;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamMemberRequest;
use App\Http\Requests\UpdateTeamMemberRequest;
use App\Models\Channel;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TeamMemberController extends Controller
{
    public function index(Request $request): View
    {
        $teamMembers = $request->user()->teamMembers()->orderBy('name')->get();
        $channels = Channel::where('owner_id', $request->user()->id)->orderBy('name')->get();

        return view('communications.team.index', compact('teamMembers', 'channels'));
    }

    public function store(StoreTeamMemberRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $channelId = $data['channel_id'] ?? null;
        unset($data['channel_id']);

        $teamMember = $request->user()->teamMembers()->create([
            ...$data,
            'password' => Hash::make($data['password']),
            'invited_at' => now(),
        ]);

        if ($channelId) {
            // Se valida contra los canales del propio dueño para no permitir asignar,
            // vía un id manipulado, un canal que no le pertenece.
            $channel = Channel::where('owner_id', $request->user()->id)->find($channelId);

            $channel?->members()->firstOrCreate(
                ['member_type' => TeamMember::class, 'member_id' => $teamMember->id],
                ['role' => 'member', 'joined_at' => now()]
            );
        }

        return redirect()->route('communications.team.index')
            ->with('success', 'Miembro de equipo creado. Comparte sus credenciales para que ingrese en /equipo/login.');
    }

    public function update(UpdateTeamMemberRequest $request, TeamMember $teamMember): RedirectResponse
    {
        abort_if($teamMember->owner_id !== $request->user()->id, 403);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $teamMember->update($data);

        return redirect()->route('communications.team.index')
            ->with('success', 'Miembro de equipo actualizado.');
    }

    public function destroy(Request $request, TeamMember $teamMember): RedirectResponse
    {
        abort_if($teamMember->owner_id !== $request->user()->id, 403);

        // Se desactiva en vez de borrar: conserva su autoría en el historial de mensajes.
        $teamMember->update(['is_active' => false]);

        return redirect()->route('communications.team.index')
            ->with('success', 'Miembro de equipo desactivado. Ya no puede iniciar sesión.');
    }
}
