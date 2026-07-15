<x-app-layout>
<x-slot name="title">Servicios</x-slot>

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-end gap-4 mb-6">
    <a href="{{ route('services.create') }}"
       class="inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">
        <x-lucide-plus class="w-4 h-4" />
        Nuevo servicio
    </a>
</div>

{{-- Flash --}}
@if(session('success'))
<div class="mb-4 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

{{-- Filtros --}}
<form method="GET" action="{{ route('services.index') }}" class="mb-5">
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-400)]" />
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Buscar por nombre o descripción..."
                   class="w-full h-10 pl-9 pr-4 rounded-[var(--radius-control)] border border-[var(--border-default)] bg-[var(--surface-subtle)] text-[14px] text-[var(--text-700)] focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary-light)] outline-none" />
        </div>
        <select name="status" class="h-10 rounded-[var(--radius-control)] border border-[var(--border-default)] bg-[var(--surface-subtle)] px-3.5 text-[14px] text-[var(--text-700)] focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary-light)] outline-none min-w-[140px]">
            <option value="">Todos los estados</option>
            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Activo</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
        </select>
        <select name="vat" class="h-10 rounded-[var(--radius-control)] border border-[var(--border-default)] bg-[var(--surface-subtle)] px-3.5 text-[14px] text-[var(--text-700)] focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary-light)] outline-none min-w-[140px]">
            <option value="">Con/sin IVA</option>
            <option value="1" {{ request('vat') === '1' ? 'selected' : '' }}>Aplica IVA</option>
            <option value="0" {{ request('vat') === '0' ? 'selected' : '' }}>Sin IVA</option>
        </select>
        <button type="submit" class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">
            Buscar
        </button>
        @if(request()->hasAny(['search','status','vat']))
        <a href="{{ route('services.index') }}" class="h-10 flex items-center px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
            Limpiar
        </a>
        @endif
    </div>
</form>

{{-- Tabla --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
    @if($services->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-16 h-16 rounded-[var(--radius-card)] bg-[var(--surface-muted)] flex items-center justify-center mb-4">
            <x-lucide-briefcase class="w-8 h-8 text-[var(--text-400)]" />
        </div>
        @if(request()->hasAny(['search','status','vat']))
        <p class="text-[14px] font-semibold text-[var(--text-700)]">No se encontraron servicios con esos filtros</p>
        <a href="{{ route('services.index') }}" class="mt-2 text-[13px] text-[var(--color-primary)] hover:underline font-medium">Limpiar filtros</a>
        @else
        <p class="text-[14px] font-semibold text-[var(--text-700)]">Aún no tienes servicios registrados</p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">Agrega tu primer servicio para comenzar</p>
        <a href="{{ route('services.create') }}" class="mt-4 inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">
            <x-lucide-plus class="w-4 h-4" />
            Agregar primer servicio
        </a>
        @endif
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-[var(--border-default)]">
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-left">Nombre</th>
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-left hidden md:table-cell">Unidad</th>
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-right">Precio base</th>
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-center hidden sm:table-cell">IVA</th>
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-center">Estado</th>
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($services as $service)
                <tr class="border-b border-[var(--surface-muted)] hover:bg-[var(--surface-subtle)]">
                    <td class="px-6 py-[14px]">
                        <a href="{{ route('services.show', $service) }}" class="font-semibold text-[14px] text-[var(--text-900)] hover:text-[var(--color-primary)]">
                            {{ $service->name }}
                        </a>
                        @if($service->description)
                        <p class="text-[12px] text-[var(--text-400)] mt-0.5 truncate max-w-xs">{{ $service->description }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-[14px] hidden md:table-cell text-[14px] text-[var(--text-700)] capitalize">
                        {{ $service->unit }}
                    </td>
                    <td class="px-6 py-[14px] text-right font-semibold text-[14px] text-[var(--text-900)]">
                        ${{ number_format($service->base_price, 0, ',', '.') }}
                        <span class="text-[12px] font-normal text-[var(--text-400)] block">COP</span>
                    </td>
                    <td class="px-6 py-[14px] text-center hidden sm:table-cell">
                        @if($service->applies_vat)
                        <x-status-badge variant="warning">19%</x-status-badge>
                        @else
                        <span class="text-[var(--border-strong)] text-[13px]">—</span>
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
                                        class="text-[var(--color-danger)]/60 hover:text-[var(--color-danger)]" title="Eliminar">
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

    @if($services->hasPages())
    <div class="px-6 py-4 border-t border-[var(--border-default)]">
        {{ $services->links() }}
    </div>
    @endif
    @endif
</div>

</x-app-layout>
