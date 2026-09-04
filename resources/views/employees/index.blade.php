<x-app-layout>
<x-slot name="title">Empleados</x-slot>

<div class="flex flex-col sm:flex-row sm:items-start sm:justify-end gap-4 mb-6">
    <a href="{{ route('employees.create', [], false) }}"
       class="inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
        <x-lucide-plus class="w-4 h-4" />
        Nuevo empleado
    </a>
</div>

@if(session('success'))
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="mb-4 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[15px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

{{-- KPIs --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-5 shadow-[var(--shadow-card)]">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Empleados activos</p>
            <x-lucide-users class="w-5 h-5 text-[var(--color-primary)]" />
        </div>
        <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $totalActive }}</p>
    </div>
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-5 shadow-[var(--shadow-card)]">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Nómina mensual estimada</p>
            <x-lucide-banknote class="w-5 h-5 text-[var(--color-primary)]" />
        </div>
        <p class="text-[22px] font-bold text-[var(--text-900)]">$ {{ number_format($totalPayroll, 0, ',', '.') }}</p>
        <p class="text-[13px] text-[var(--text-400)] mt-1">Suma de salarios básicos activos, sin auxilios ni prestaciones</p>
    </div>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('employees.index', [], false) }}" class="mb-5">
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-400)]" />
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Buscar por nombre o documento..."
                   class="w-full h-10 pl-9 pr-4 rounded-[var(--radius-control)] border border-[var(--border-default)] bg-[var(--surface-subtle)] text-[15px] text-[var(--text-700)] outline-none transition-all focus:bg-[var(--surface-card)] focus:border-[var(--border-strong)] focus:ring-2 focus:ring-[var(--color-primary-light)]" />
        </div>
        <select name="client_id" class="h-10 rounded-[var(--radius-control)] border border-[var(--border-default)] bg-[var(--surface-subtle)] px-3.5 text-[15px] text-[var(--text-700)] focus:border-[var(--border-default)] focus:ring-1 focus:ring-[var(--color-primary-light)] outline-none min-w-[180px]">
            <option value="">Todos los clientes</option>
            @foreach($clients as $c)
            <option value="{{ $c->id }}" {{ (string) request('client_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="status" class="h-10 rounded-[var(--radius-control)] border border-[var(--border-default)] bg-[var(--surface-subtle)] px-3.5 text-[15px] text-[var(--text-700)] focus:border-[var(--border-default)] focus:ring-1 focus:ring-[var(--color-primary-light)] outline-none min-w-[140px]">
            <option value="">Todos los estados</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activo</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
        </select>
        <button type="submit" class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
            Buscar
        </button>
        @if(request()->hasAny(['search','client_id','status']))
        <a href="{{ route('employees.index', [], false) }}" class="h-10 flex items-center px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[15px] font-medium hover:bg-[var(--surface-muted)]">
            Limpiar
        </a>
        @endif
    </div>
</form>

{{-- Tabla --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
    @if($employees->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-16 h-16 rounded-[var(--radius-card)] bg-[var(--surface-muted)] flex items-center justify-center mb-4">
            <x-lucide-users class="w-8 h-8 text-[var(--text-400)]" />
        </div>
        @if(request()->hasAny(['search','client_id','status']))
        <p class="text-[15px] font-semibold text-[var(--text-700)]">No se encontraron empleados con esos filtros</p>
        <a href="{{ route('employees.index', [], false) }}" class="mt-2 text-[14px] text-[var(--color-primary)] hover:underline font-medium">Limpiar filtros</a>
        @else
        <p class="text-[15px] font-semibold text-[var(--text-700)]">Aún no tienes empleados registrados</p>
        <p class="text-[13px] text-[var(--text-400)] mt-1">Agrega el primer empleado de un cliente para comenzar</p>
        <a href="{{ route('employees.create', [], false) }}" class="mt-4 inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
            <x-lucide-plus class="w-4 h-4" />
            Agregar primer empleado
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
                    <th class="{{ $thClass }} text-left">Empleado</th>
                    <th class="{{ $thClass }} text-left hidden md:table-cell">Identificación</th>
                    <th class="{{ $thClass }} text-left hidden md:table-cell">Cliente</th>
                    <th class="{{ $thClass }} text-left hidden lg:table-cell">Cargo</th>
                    <th class="{{ $thClass }} text-right hidden sm:table-cell">Salario</th>
                    <th class="{{ $thClass }} text-center">Estado</th>
                    <th class="{{ $thClass }} text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $employee)
                <tr class="border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">
                    <td class="px-6 py-[14px]">
                        <div class="flex items-center gap-3">
                            <x-avatar-chip :name="$employee->full_name" />
                            <div class="min-w-0">
                                <a href="{{ route('employees.show', $employee, false) }}" class="text-[14px] text-[var(--text-500)] hover:text-[var(--color-primary)]">
                                    {{ $employee->full_name }}
                                </a>
                                <p class="text-[13px] text-[var(--text-400)] mt-0.5 md:hidden">
                                    {{ $employee->document_number }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-[14px] hidden md:table-cell text-[14px] text-[var(--text-500)]">{{ $employee->document_number }}</td>
                    <td class="px-6 py-[14px] hidden md:table-cell text-[14px] text-[var(--text-500)]">{{ $employee->client->name }}</td>
                    <td class="px-6 py-[14px] hidden lg:table-cell text-[14px] text-[var(--text-500)]">{{ $employee->position ?? '—' }}</td>
                    <td class="px-6 py-[14px] hidden sm:table-cell text-right text-[14px] text-[var(--text-500)] tabular-nums">$ {{ number_format($employee->base_salary, 0, ',', '.') }}</td>
                    <td class="px-6 py-[14px] text-center">
                        <x-status-badge :variant="$employee->status === 'active' ? 'success' : 'neutral'">
                            {{ $employee->status === 'active' ? 'Activo' : 'Inactivo' }}
                        </x-status-badge>
                    </td>
                    <td class="px-6 py-[14px] text-right">
                        <div class="flex items-center justify-end gap-[10px]">
                            <a href="{{ route('employees.show', $employee, false) }}" class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Ver detalle">
                                <x-lucide-eye class="w-4 h-4" />
                            </a>
                            <a href="{{ route('employees.edit', $employee, false) }}" class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Editar">
                                <x-lucide-edit-2 class="w-4 h-4" />
                            </a>
                            <form method="POST" action="{{ route('employees.destroy', $employee, false) }}"
                                  x-data=""
                                  x-on:submit.prevent="if(confirm('¿Eliminar a {{ addslashes($employee->full_name) }}? Esta acción no se puede deshacer.')) $el.submit()">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Eliminar">
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

    @if($employees->hasPages())
    <div class="px-6 py-4 border-t border-[var(--border-default)]">
        {{ $employees->links() }}
    </div>
    @endif
    @endif
</div>

</x-app-layout>
