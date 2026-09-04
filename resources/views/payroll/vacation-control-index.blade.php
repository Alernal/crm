<x-app-layout>
<x-slot name="title">Control de Vacaciones</x-slot>

<div class="flex items-start justify-between mb-6">
    <p class="text-[14px] text-[var(--text-500)] max-w-2xl">
        Saldo de vacaciones pendientes por empleado, calculado en vivo — 15 días hábiles por cada año cumplido desde el ingreso, menos los días ya disfrutados. Haz clic en un empleado para registrar un período o ajustar su saldo inicial.
    </p>
    <a href="{{ route('vacation-control.calendar', [], false) }}"
       class="inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium flex-shrink-0">
        <x-lucide-calendar-days class="w-4 h-4" />
        Ver cronograma
    </a>
</div>

@if(session('success'))
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="mb-5 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[15px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

{{-- Filtros --}}
<form method="GET" action="{{ route('vacation-control.index', [], false) }}" class="flex flex-col sm:flex-row gap-3 mb-5">
    <select name="client_id" class="h-10 px-3.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[15px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-1 focus:ring-[var(--color-primary-light)] focus:border-[var(--border-default)] outline-none sm:max-w-[220px]">
        <option value="">Todos los clientes</option>
        @foreach($clients as $c)
        <option value="{{ $c->id }}" {{ (string) request('client_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
        @endforeach
    </select>
    <button type="submit" class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
        Buscar
    </button>
    @if(request()->hasAny(['client_id']))
    <a href="{{ route('vacation-control.index', [], false) }}" class="h-10 flex items-center px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[15px] font-medium hover:bg-[var(--surface-muted)]">
        Limpiar
    </a>
    @endif
</form>

{{-- Tabla --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
    @if($rows->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-16 h-16 rounded-[var(--radius-card)] bg-[var(--surface-muted)] flex items-center justify-center mb-4">
            <x-lucide-calendar-days class="w-8 h-8 text-[var(--text-400)]" />
        </div>
        <p class="text-[15px] font-semibold text-[var(--text-700)]">No hay empleados activos para mostrar</p>
        <p class="text-[13px] text-[var(--text-400)] mt-1">El control de vacaciones aplica solo a empleados con estado activo</p>
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
                    <th class="{{ $thClass }} text-left">Cliente</th>
                    <th class="{{ $thClass }} text-right">Saldo pendiente</th>
                    <th class="{{ $thClass }} text-left hidden sm:table-cell">Próxima acumulación</th>
                    <th class="{{ $thClass }} text-center">Mínimo legal (año)</th>
                    <th class="{{ $thClass }} text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                @php $balance = $row['balance']; $employee = $row['employee']; @endphp
                <tr class="border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">
                    <td class="px-6 py-[14px]">
                        <a href="{{ route('vacation-control.show', $employee, false) }}" class="text-[14px] text-[var(--text-500)] hover:text-[var(--color-primary)]">
                            {{ $employee->full_name }}
                        </a>
                    </td>
                    <td class="px-6 py-[14px] text-[14px] text-[var(--text-500)]">{{ $employee->client->name }}</td>
                    <td class="px-6 py-[14px] text-right text-[14px] font-semibold text-[var(--text-500)] tabular-nums">
                        {{ number_format($balance['pending_balance'], 1) }} días
                    </td>
                    <td class="px-6 py-[14px] hidden sm:table-cell text-[14px] text-[var(--text-500)]">
                        {{ $balance['next_accrual_date']?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="px-6 py-[14px] text-center">
                        @if($balance['complies_minimum_current_year'])
                        <x-status-badge variant="success">Cumple</x-status-badge>
                        @else
                        <x-status-badge variant="warning">Pendiente</x-status-badge>
                        @endif
                    </td>
                    <td class="px-6 py-[14px] text-right">
                        <a href="{{ route('vacation-control.show', $employee, false) }}" class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Ver">
                            <x-lucide-eye class="w-4 h-4" />
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
    @endif
</div>

</x-app-layout>
