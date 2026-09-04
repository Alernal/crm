<x-app-layout>
<x-slot name="title">Servicios</x-slot>

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-end gap-4 mb-6">
    <a href="{{ route('services.create') }}"
       class="inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
        <x-lucide-plus class="w-4 h-4" />
        Nuevo servicio
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
<form method="GET" action="{{ route('services.index') }}" class="mb-5" x-data="{ filtersOpen: false }">
    @php
        $exceptQuery = fn(array $keys) => array_filter(
            request()->except(array_merge($keys, ['page'])),
            fn($v) => $v !== null && $v !== ''
        );
    @endphp
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="relative flex-1">
            <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-400)]" />
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Buscar por nombre o descripción..."
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

        {{-- Más filtros: panel flotante --}}
        <div class="relative">
            <button type="button" @click="filtersOpen = !filtersOpen"
                    class="h-10 inline-flex items-center gap-2 px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] bg-[var(--surface-subtle)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)]">
                <x-lucide-sliders-horizontal class="w-4 h-4" />
                Más filtros
                @if(request()->filled('vat'))
                <span class="w-[18px] h-[18px] rounded-full bg-[var(--color-primary)] text-white text-[11px] font-semibold flex items-center justify-center">1</span>
                @endif
            </button>

            <div x-show="filtersOpen" x-cloak @click.away="filtersOpen = false"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-72 bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] z-50 p-4">
                <label class="block text-[12.5px] font-semibold text-[var(--text-700)] mb-1.5">IVA</label>
                <select name="vat"
                        class="w-full h-10 rounded-[var(--radius-control)] border border-[var(--border-default)] bg-[var(--surface-subtle)] px-3 text-[14px] text-[var(--text-700)] focus:border-[var(--border-default)] focus:ring-1 focus:ring-[var(--color-primary-light)] outline-none">
                    <option value="">Todos</option>
                    <option value="1" {{ request('vat') === '1' ? 'selected' : '' }}>Aplica IVA</option>
                    <option value="0" {{ request('vat') === '0' ? 'selected' : '' }}>Sin IVA</option>
                </select>
                <button type="submit" class="mt-3 w-full h-9 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[13.5px] font-medium">
                    Aplicar
                </button>
            </div>
        </div>

        <button type="submit" class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
            Buscar
        </button>
    </div>

    {{-- Filtros activos --}}
    @if(request()->filled('search') || request()->filled('status') || request()->filled('vat'))
    <div class="flex flex-wrap items-center gap-2 mt-3">
        <span class="text-[12.5px] text-[var(--text-400)]">Filtros activos:</span>

        @if(request()->filled('search'))
        <a href="{{ route('services.index', $exceptQuery(['search'])) }}"
           class="inline-flex items-center gap-1.5 h-7 pl-3 pr-2 rounded-full bg-[var(--color-primary-light)] text-[var(--color-primary)] text-[12.5px] font-medium hover:opacity-80">
            &ldquo;{{ request('search') }}&rdquo;
            <x-lucide-x class="w-3 h-3" />
        </a>
        @endif

        @if(request()->filled('status'))
        <a href="{{ route('services.index', $exceptQuery(['status'])) }}"
           class="inline-flex items-center gap-1.5 h-7 pl-3 pr-2 rounded-full bg-[var(--color-primary-light)] text-[var(--color-primary)] text-[12.5px] font-medium hover:opacity-80">
            {{ request('status') === 'active' ? 'Activos' : 'Inactivos' }}
            <x-lucide-x class="w-3 h-3" />
        </a>
        @endif

        @if(request()->filled('vat'))
        <a href="{{ route('services.index', $exceptQuery(['vat'])) }}"
           class="inline-flex items-center gap-1.5 h-7 pl-3 pr-2 rounded-full bg-[var(--color-primary-light)] text-[var(--color-primary)] text-[12.5px] font-medium hover:opacity-80">
            {{ request('vat') === '1' ? 'Aplica IVA' : 'Sin IVA' }}
            <x-lucide-x class="w-3 h-3" />
        </a>
        @endif

        <a href="{{ route('services.index') }}" class="text-[12.5px] text-[var(--text-400)] hover:text-[var(--text-900)] underline">
            Limpiar todo
        </a>
    </div>
    @endif
</form>

{{-- Tabla --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
    @if($services->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-16 h-16 rounded-[var(--radius-card)] bg-[var(--surface-muted)] flex items-center justify-center mb-4">
            <x-lucide-briefcase class="w-8 h-8 text-[var(--text-400)]" />
        </div>
        @if(request()->filled('search') || request()->filled('status') || request()->filled('vat'))
        <p class="text-[15px] font-semibold text-[var(--text-700)]">No se encontraron servicios con esos filtros</p>
        <a href="{{ route('services.index') }}" class="mt-2 text-[14px] text-[var(--color-primary)] hover:underline font-medium">Limpiar filtros</a>
        @else
        <p class="text-[15px] font-semibold text-[var(--text-700)]">Aún no tienes servicios registrados</p>
        <p class="text-[13px] text-[var(--text-400)] mt-1">Agrega tu primer servicio para comenzar</p>
        <a href="{{ route('services.create') }}" class="mt-4 inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
            <x-lucide-plus class="w-4 h-4" />
            Agregar primer servicio
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
                    <th class="{{ $thClass }} text-left">Descripción</th>
                    <th class="{{ $thClass }} text-left hidden md:table-cell">Categoría</th>
                    <th class="{{ $thClass }} text-left hidden md:table-cell">Unidad</th>
                    <th class="{{ $thClass }} text-right">Precio</th>
                    <th class="{{ $thClass }} text-center hidden sm:table-cell">IVA</th>
                    <th class="{{ $thClass }} text-center">Estado</th>
                    <th class="{{ $thClass }} text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($services as $service)
                <tr class="border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">
                    <td class="px-6 py-[14px] text-right text-[14px] text-[var(--text-400)] tabular-nums">
                        {{ $service->consecutive_number ? str_pad($service->consecutive_number, 2, '0', STR_PAD_LEFT) : '—' }}
                    </td>
                    <td class="px-6 py-[14px] max-w-0 w-full">
                        <a href="{{ route('services.show', $service) }}" class="block truncate text-[14px] text-[var(--text-500)] hover:text-[var(--color-primary)]">
                            {{ $service->name }}
                        </a>
                        @if($service->description)
                        <p class="truncate text-[13px] text-[var(--text-400)] mt-0.5" title="{{ $service->description }}">{{ $service->description }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-[14px] hidden md:table-cell text-[14px] text-[var(--text-500)]">
                        {{ $service->category?->name ?? '—' }}
                    </td>
                    <td class="px-6 py-[14px] hidden md:table-cell text-[14px] text-[var(--text-500)] capitalize">
                        {{ $service->unit }}
                    </td>
                    <td class="px-6 py-[14px] text-right text-[14px] text-[var(--text-500)] tabular-nums">
                        ${{ number_format($service->base_price, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-[14px] text-center hidden sm:table-cell">
                        @if($service->applies_vat)
                        <x-status-badge variant="warning">19%</x-status-badge>
                        @else
                        <span class="text-[var(--border-strong)] text-[14px]">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-[14px] text-center">
                        <x-status-badge :variant="$service->status === 'active' ? 'success' : 'neutral'">
                            {{ $service->status === 'active' ? 'Activo' : 'Inactivo' }}
                        </x-status-badge>
                    </td>
                    <td class="px-6 py-[14px] text-right">
                        <div class="flex items-center justify-end gap-[10px]">
                            <a href="{{ route('services.show', $service) }}"
                               class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Ver detalle">
                                <x-lucide-eye class="w-4 h-4" />
                            </a>
                            <a href="{{ route('services.edit', $service) }}"
                               class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Editar">
                                <x-lucide-edit-2 class="w-4 h-4" />
                            </a>
                            <form method="POST" action="{{ route('services.destroy', $service) }}"
                                  x-data=""
                                  x-on:submit.prevent="if(confirm('¿Eliminar «{{ addslashes($service->name) }}»? Esta acción no se puede deshacer.')) $el.submit()">
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

    @if($services->hasPages())
    <div class="px-6 py-4 border-t border-[var(--border-default)]">
        {{ $services->links() }}
    </div>
    @endif
    @endif
</div>

</x-app-layout>
