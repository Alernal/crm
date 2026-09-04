<x-app-layout>
<x-slot name="title">Equipo de trabajo</x-slot>

<a href="{{ route('communications.index') }}"
   class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)] mb-4">
    <x-lucide-arrow-left class="w-4 h-4" />
    Volver a Comunicación
</a>

<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
    <p class="text-[13px] text-[var(--text-500)] max-w-xl">Invita a las personas que colaboran contigo. Solo pueden acceder al módulo de Comunicación, en <a href="{{ route('team-member.login') }}" target="_blank" class="font-medium text-[var(--color-primary)] hover:underline">{{ route('team-member.login') }}</a>.</p>
    <button type="button" @click="$dispatch('open-modal', 'nuevo-miembro')"
            class="inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium flex-shrink-0">
        <x-lucide-plus class="w-4 h-4" />
        Nuevo miembro
    </button>
</div>

@if($teamMembers->isNotEmpty())
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-5 shadow-[var(--shadow-card)]">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Miembros de equipo</p>
            <x-lucide-users class="w-5 h-5 text-[var(--color-primary)]" />
        </div>
        <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $teamMembers->count() }}</p>
    </div>
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-5 shadow-[var(--shadow-card)]">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Activos</p>
            <x-lucide-user-check class="w-5 h-5 text-[var(--color-success)]" />
        </div>
        <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $teamMembers->where('is_active', true)->count() }}</p>
    </div>
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-5 shadow-[var(--shadow-card)]">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Canales disponibles</p>
            <x-lucide-hash class="w-5 h-5 text-[var(--color-primary)]" />
        </div>
        <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $channels->count() }}</p>
    </div>
</div>
@endif

<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
    @if($teamMembers->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-16 h-16 rounded-full bg-[var(--surface-page)] shadow-[var(--shadow-card)] flex items-center justify-center mb-4">
            <x-lucide-users class="w-8 h-8 text-[var(--color-primary)]" />
        </div>
        <p class="text-[14px] font-semibold text-[var(--text-700)]">Aún no has invitado a nadie a tu equipo</p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">Agrega tu primer miembro para empezar a colaborar</p>
    </div>
    @else
    <div class="overflow-x-auto p-3">
    <table class="w-full text-left">
        <thead>
            @php
                $thClass = 'text-[13px] font-bold text-[var(--text-900)] px-6 py-3.5';
            @endphp
            <tr class="border-b border-[var(--border-default)]">
                <th class="{{ $thClass }} text-left">Nombre</th>
                <th class="{{ $thClass }} text-left hidden md:table-cell">Correo</th>
                <th class="{{ $thClass }} text-left hidden sm:table-cell">Rol</th>
                <th class="{{ $thClass }} text-center">Estado</th>
                <th class="{{ $thClass }} text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($teamMembers as $teamMember)
                <tr class="border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">
                    <td class="px-6 py-[14px]">
                        <div class="flex items-center gap-3">
                            <x-avatar-chip :name="$teamMember->name" />
                            <div class="min-w-0">
                                <p class="font-semibold text-[14px] text-[var(--text-500)]">{{ $teamMember->name }}</p>
                                <p class="text-[12px] text-[var(--text-400)] mt-0.5 md:hidden">{{ $teamMember->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-[14px] hidden md:table-cell text-[14px] text-[var(--text-500)]">{{ $teamMember->email }}</td>
                    <td class="px-6 py-[14px] hidden sm:table-cell text-[14px] text-[var(--text-500)]">{{ $teamMember->role === 'admin' ? 'Administrador' : 'Miembro' }}</td>
                    <td class="px-6 py-[14px] text-center">
                        <x-status-badge :variant="$teamMember->is_active ? 'success' : 'neutral'">
                            {{ $teamMember->is_active ? 'Activo' : 'Desactivado' }}
                        </x-status-badge>
                    </td>
                    <td class="px-6 py-[14px] text-right">
                        <div class="flex items-center justify-end gap-[10px]">
                            <button type="button" @click="$dispatch('open-modal', 'editar-{{ $teamMember->id }}')"
                                    class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Editar">
                                <x-lucide-pencil class="w-4 h-4" />
                            </button>
                            @if($teamMember->is_active)
                                <form method="POST" action="{{ route('communications.team.destroy', $teamMember) }}"
                                      onsubmit="return confirm('¿Desactivar a {{ $teamMember->name }}? No podrá volver a iniciar sesión.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[var(--color-danger)]/60 hover:text-[var(--color-danger)]" title="Desactivar">
                                        <x-lucide-user-x class="w-4 h-4" />
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>

                <x-modal :name="'editar-'.$teamMember->id" :show="false" maxWidth="md">
                    <form method="POST" action="{{ route('communications.team.update', $teamMember) }}" class="p-6 space-y-4">
                        @csrf
                        @method('PATCH')
                        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Editar {{ $teamMember->name }}</h2>

                        <div>
                            <x-input-label value="Nombre" />
                            <x-text-input name="name" class="mt-1 block w-full" required :value="$teamMember->name" />
                        </div>
                        <div>
                            <x-input-label value="Correo electrónico" />
                            <x-text-input name="email" type="email" class="mt-1 block w-full" required :value="$teamMember->email" />
                        </div>
                        <div>
                            <x-input-label value="Nueva contraseña (opcional)" />
                            <x-text-input name="password" type="password" class="mt-1 block w-full" placeholder="Dejar en blanco para no cambiarla" />
                        </div>
                        <div>
                            <x-input-label value="Rol" />
                            <select name="role" class="mt-1 block w-full rounded-[var(--radius-control)] border-[var(--border-strong)] text-[14px] focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]">
                                <option value="member" @selected($teamMember->role === 'member')>Miembro</option>
                                <option value="admin" @selected($teamMember->role === 'admin')>Administrador</option>
                            </select>
                        </div>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" @checked($teamMember->is_active)
                                   class="rounded border-[var(--border-strong)] text-[var(--color-primary)] focus:ring-[var(--color-primary-light)] w-4 h-4">
                            <span class="text-[14px] text-[var(--text-700)]">Cuenta activa</span>
                        </label>

                        <div class="flex justify-end gap-2 pt-2">
                            <x-secondary-button type="button" @click="$dispatch('close-modal', 'editar-{{ $teamMember->id }}')">Cancelar</x-secondary-button>
                            <button type="submit" class="inline-flex items-center gap-[6px] h-10 px-5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white rounded-[var(--radius-control)] text-[14px] font-medium">
                                Guardar
                            </button>
                        </div>
                    </form>
                </x-modal>
            @endforeach
        </tbody>
    </table>
    </div>
    @endif
</div>

<x-modal name="nuevo-miembro" :show="$errors->any()" maxWidth="md">
    <form method="POST" action="{{ route('communications.team.store') }}" class="p-6 space-y-4">
        @csrf
        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Nuevo miembro de equipo</h2>

        <div>
            <x-input-label for="name" value="Nombre" />
            <x-text-input id="name" name="name" class="mt-1 block w-full" required :value="old('name')" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" required :value="old('email')" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="role" value="Rol" />
            <select id="role" name="role" class="mt-1 block w-full rounded-[var(--radius-control)] border-[var(--border-strong)] text-[14px] focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]">
                <option value="member">Miembro</option>
                <option value="admin">Administrador</option>
            </select>
            <p class="text-[12px] text-[var(--text-400)] mt-1">Los administradores pueden crear canales y gestionar sus miembros.</p>
        </div>
        <div>
            <x-input-label for="channel_id" value="Canal (opcional)" />
            <select id="channel_id" name="channel_id" class="mt-1 block w-full rounded-[var(--radius-control)] border-[var(--border-strong)] text-[14px] focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]">
                <option value="">Sin asignar por ahora</option>
                @foreach($channels as $channel)
                    <option value="{{ $channel->id }}">#{{ $channel->name }}</option>
                @endforeach
            </select>
            <p class="text-[12px] text-[var(--text-400)] mt-1">Lo agrega directamente a ese canal. Puedes agregarlo a más canales después desde "Miembros" en cada uno.</p>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <x-secondary-button type="button" @click="$dispatch('close-modal', 'nuevo-miembro')">Cancelar</x-secondary-button>
            <button type="submit" class="inline-flex items-center gap-[6px] h-10 px-5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white rounded-[var(--radius-control)] text-[14px] font-medium">
                Crear
            </button>
        </div>
    </form>
</x-modal>

</x-app-layout>
