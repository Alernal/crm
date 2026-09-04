<x-app-layout>
<x-slot name="title">Clientes</x-slot>

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-end gap-4 mb-6">
    <a href="{{ route('clients.create') }}"
       class="inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
        <x-lucide-plus class="w-4 h-4" />
        Nuevo cliente
    </a>
</div>

{{-- Flash --}}
@if(session('success'))
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="mb-4 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[15px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

{{-- Filtros --}}
<form method="GET" action="{{ route('clients.index') }}" class="mb-5">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="relative flex-1">
            <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-400)]" />
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Buscar por nombre o número de documento..."
                   class="w-full h-10 pl-9 pr-4 rounded-[var(--radius-control)] border border-[var(--border-default)] bg-[var(--surface-subtle)] text-[15px] text-[var(--text-700)] outline-none transition-all focus:bg-[var(--surface-card)] focus:border-[var(--border-strong)] focus:ring-2 focus:ring-[var(--color-primary-light)]" />
        </div>

        {{-- Estado: píldoras --}}
        <div class="inline-flex items-center gap-1 p-1 rounded-full bg-[var(--surface-subtle)] border border-[var(--border-default)] w-fit">
            @php
                $pillClass = fn(bool $active) => 'h-8 px-3.5 rounded-full text-[13.5px] font-medium whitespace-nowrap transition-colors '
                    . ($active ? 'bg-[var(--color-primary)] text-white' : 'text-[var(--text-500)] hover:text-[var(--text-900)]');
            @endphp
            <button type="submit" name="status" value="" class="{{ $pillClass(!request()->filled('status')) }}">Todos</button>
            <button type="submit" name="status" value="active" class="{{ $pillClass(request('status') === 'active') }}">Activos</button>
            <button type="submit" name="status" value="inactive" class="{{ $pillClass(request('status') === 'inactive') }}">Inactivos</button>
        </div>

        <button type="submit" class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
            Buscar
        </button>
    </div>

    {{-- Filtros activos --}}
    @if(request()->filled('search') || request()->filled('status'))
    <div class="flex flex-wrap items-center gap-2 mt-3">
        <span class="text-[12.5px] text-[var(--text-400)]">Filtros activos:</span>

        @if(request()->filled('search'))
        <a href="{{ route('clients.index', array_filter(request()->except(['search', 'page']))) }}"
           class="inline-flex items-center gap-1.5 h-7 pl-3 pr-2 rounded-full bg-[var(--color-primary-light)] text-[var(--color-primary)] text-[12.5px] font-medium hover:opacity-80">
            &ldquo;{{ request('search') }}&rdquo;
            <x-lucide-x class="w-3 h-3" />
        </a>
        @endif

        @if(request()->filled('status'))
        <a href="{{ route('clients.index', array_filter(request()->except(['status', 'page']))) }}"
           class="inline-flex items-center gap-1.5 h-7 pl-3 pr-2 rounded-full bg-[var(--color-primary-light)] text-[var(--color-primary)] text-[12.5px] font-medium hover:opacity-80">
            {{ request('status') === 'active' ? 'Activos' : 'Inactivos' }}
            <x-lucide-x class="w-3 h-3" />
        </a>
        @endif

        <a href="{{ route('clients.index') }}" class="text-[12.5px] text-[var(--text-400)] hover:text-[var(--text-900)] underline">
            Limpiar todo
        </a>
    </div>
    @endif
</form>

{{-- Tabla --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
    @if($clients->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-16 h-16 rounded-[var(--radius-card)] bg-[var(--surface-muted)] flex items-center justify-center mb-4">
            <x-lucide-users class="w-8 h-8 text-[var(--text-400)]" />
        </div>
        @if(request()->filled('search') || request()->filled('status'))
        <p class="text-[15px] font-semibold text-[var(--text-700)]">No se encontraron clientes con esos filtros</p>
        <a href="{{ route('clients.index') }}" class="mt-2 text-[14px] text-[var(--color-primary)] hover:underline font-medium">Limpiar filtros</a>
        @else
        <p class="text-[15px] font-semibold text-[var(--text-700)]">Aún no tienes clientes registrados</p>
        <p class="text-[13px] text-[var(--text-400)] mt-1">Agrega tu primer cliente para comenzar</p>
        <a href="{{ route('clients.create') }}" class="mt-4 inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
            <x-lucide-plus class="w-4 h-4" />
            Agregar primer cliente
        </a>
        @endif
    </div>
    @else
    <div class="overflow-x-auto p-3">
    <div class="overflow-y-auto max-h-[65vh]">
        <table class="w-full">
            <thead>
                <tr>
                    @php
                        $thClass = 'sticky top-0 z-[1] bg-[var(--surface-card)] border-b border-[var(--border-default)] text-[13px] font-bold text-[var(--text-900)] px-6 py-3.5';
                    @endphp
                    <th class="{{ $thClass }} text-right w-16">N°</th>
                    <th class="{{ $thClass }} text-left">Nombre / razón social</th>
                    <th class="{{ $thClass }} text-left hidden md:table-cell">Identificación</th>
                    <th class="{{ $thClass }} text-left hidden lg:table-cell">Correo</th>
                    <th class="{{ $thClass }} text-left hidden lg:table-cell">Teléfono</th>
                    <th class="{{ $thClass }} text-left hidden xl:table-cell">Ciudad</th>
                    <th class="{{ $thClass }} text-center">Estado</th>
                    <th class="{{ $thClass }} text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clients as $client)
                <tr class="border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">
                    <td class="px-6 py-[14px] text-right text-[14px] text-[var(--text-400)] tabular-nums">
                        {{ $client->consecutive_number ? str_pad($client->consecutive_number, 2, '0', STR_PAD_LEFT) : '—' }}
                    </td>
                    <td class="px-6 py-[14px]">
                        <div class="min-w-0">
                            <a href="{{ route('clients.show', $client) }}" class="text-[14px] text-[var(--text-500)] hover:text-[var(--color-primary)]">
                                {{ $client->name }}
                            </a>
                            <p class="text-[13px] text-[var(--text-400)] mt-0.5 md:hidden">
                                {{ $client->document_number }}
                            </p>
                        </div>
                    </td>
                    <td class="px-6 py-[14px] hidden md:table-cell text-[14px] text-[var(--text-500)]">
                        {{ $client->document_number }}
                    </td>
                    <td class="px-6 py-[14px] hidden lg:table-cell text-[14px] text-[var(--text-500)] truncate max-w-[180px]">
                        {{ $client->email ?? '—' }}
                    </td>
                    <td class="px-6 py-[14px] hidden lg:table-cell text-[14px] text-[var(--text-500)]">
                        {{ $client->phone ?? '—' }}
                    </td>
                    <td class="px-6 py-[14px] hidden xl:table-cell text-[14px] text-[var(--text-500)]">
                        {{ $client->city ?? '—' }}
                    </td>
                    <td class="px-6 py-[14px] text-center">
                        <x-status-badge :variant="$client->status === 'active' ? 'success' : 'neutral'">
                            {{ $client->status === 'active' ? 'Activo' : 'Inactivo' }}
                        </x-status-badge>
                    </td>
                    <td class="px-6 py-[14px] text-right">
                        <div class="flex items-center justify-end gap-[10px]">
                            <a href="{{ route('clients.show', $client) }}"
                               class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Ver detalle">
                                <x-lucide-eye class="w-4 h-4" />
                            </a>
                            <a href="{{ route('clients.edit', $client) }}"
                               class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Editar">
                                <x-lucide-edit-2 class="w-4 h-4" />
                            </a>
                            <form method="POST" action="{{ route('clients.destroy', $client) }}"
                                  x-data=""
                                  x-on:submit.prevent="if(confirm('¿Eliminar a {{ addslashes($client->name) }}? Esta acción no se puede deshacer.')) $el.submit()">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Eliminar">
                                    <x-lucide-trash-2 class="w-4 h-4" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>

    @if($clients->hasPages())
    <div class="px-6 py-4 border-t border-[var(--border-default)]">
        {{ $clients->links() }}
    </div>
    @endif
    @endif
</div>

</x-app-layout>
