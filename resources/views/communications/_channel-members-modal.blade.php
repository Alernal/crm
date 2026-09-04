<x-modal name="miembros-canal" :show="false" maxWidth="md">
    <div class="p-6">
        <h2 class="text-[16px] font-bold text-[var(--text-900)] mb-4">Miembros de #{{ $channel->name }}</h2>

        <div class="space-y-2 mb-6 max-h-56 overflow-y-auto">
            @foreach($members as $channelMember)
                <div class="flex items-center justify-between px-3 py-2 rounded-[var(--radius-control)] bg-[var(--surface-subtle)]">
                    <div>
                        <p class="text-[13px] font-medium text-[var(--text-900)]">{{ $channelMember->member ? \Illuminate\Support\Str::title($channelMember->member->name) : 'Usuario eliminado' }}</p>
                        <p class="text-[11px] text-[var(--text-400)]">{{ $channelMember->role === 'admin' ? 'Administrador' : 'Miembro' }}</p>
                    </div>
                    @if($channelMember->member_type !== \App\Models\User::class)
                        <form method="POST" action="{{ route('communications.members.destroy', [$channel, $channelMember]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--color-danger)]">
                                <x-lucide-x class="w-4 h-4" />
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>

        @if($availableTeamMembers->isNotEmpty())
            <form method="POST" action="{{ route('communications.members.store', $channel) }}" class="flex items-end gap-2">
                @csrf
                <div class="flex-1">
                    <x-input-label for="team_member_id" value="Agregar miembro" />
                    <select id="team_member_id" name="team_member_id"
                            class="mt-1 block w-full rounded-[var(--radius-control)] border-[var(--border-strong)] text-[14px] focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]">
                        @foreach($availableTeamMembers as $teamMember)
                            <option value="{{ $teamMember->id }}">{{ \Illuminate\Support\Str::title($teamMember->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                        class="h-10 px-4 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white rounded-[var(--radius-control)] text-[14px] font-medium">
                    Agregar
                </button>
            </form>
        @else
            <p class="text-[13px] text-[var(--text-400)]">No hay más miembros de equipo disponibles para agregar.</p>
        @endif

        <div class="flex justify-end pt-4">
            <x-secondary-button type="button" @click="$dispatch('close-modal', 'miembros-canal')">Cerrar</x-secondary-button>
        </div>
    </div>
</x-modal>
