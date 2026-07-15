<x-app-layout>
<x-slot name="title">Empleados</x-slot>

<div class="max-w-5xl mx-auto space-y-5">

    <nav class="flex items-center gap-1.5 text-[14px] text-[var(--text-400)] mb-1">
        <a href="{{ route('employees.index', [], false) }}" class="hover:text-[var(--color-primary)]">Empleados</a>
        <x-lucide-chevron-right class="w-3.5 h-3.5" />
        <span class="text-[var(--text-700)] font-medium truncate">{{ $employee->full_name }}</span>
    </nav>

    @if(session('success'))
    <div class="flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
        <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
        {{ session('success') }}
    </div>
    @endif

    {{-- ===== CABECERA ===== --}}
    <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-[var(--color-primary-light)] flex items-center justify-center text-[var(--color-primary)] font-semibold text-[18px] flex-shrink-0">
                    {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-[22px] font-semibold text-[var(--text-900)]">{{ $employee->full_name }}</h1>
                    <p class="text-[14px] text-[var(--text-500)] mt-0.5">
                        {{ $employee->document_type }} {{ $employee->document_number }}
                        &bull; {{ $employee->position ?? 'Sin cargo asignado' }}
                        &bull; <a href="{{ route('clients.show', $employee->client, false) }}" class="text-[var(--color-primary)] hover:underline">{{ $employee->client->name }}</a>
                    </p>
                    <div class="flex items-center gap-2 mt-2">
                        <x-status-badge :variant="$employee->status === 'active' ? 'success' : 'neutral'">
                            {{ $employee->status === 'active' ? 'Activo' : 'Inactivo' }}
                        </x-status-badge>
                        <x-status-badge variant="info">{{ \App\Models\Employee::CONTRACT_TYPES[$employee->contract_type] }}</x-status-badge>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('employees.edit', $employee, false) }}"
                   class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                    <x-lucide-edit-2 class="w-4 h-4" />
                    Editar
                </a>
                <form method="POST" action="{{ route('employees.destroy', $employee, false) }}"
                      x-data=""
                      x-on:submit.prevent="if(confirm('¿Eliminar a {{ addslashes($employee->full_name) }}? Esta acción no se puede deshacer.')) $el.submit()">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] border border-[var(--color-danger)]/30 text-[var(--color-danger)] text-[14px] font-medium hover:bg-[var(--color-danger-bg)]">
                        <x-lucide-trash-2 class="w-4 h-4" />
                        Eliminar
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-5 pt-5 border-t border-[var(--border-default)] grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
                <p class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em]">Salario básico</p>
                <p class="text-[14px] text-[var(--text-700)] mt-0.5">$ {{ number_format($employee->base_salary, 0, ',', '.') }}</p>
            </div>
            <div>
                <p class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em]">Ingreso</p>
                <p class="text-[14px] text-[var(--text-700)] mt-0.5">{{ $employee->hire_date->format('d/m/Y') }}</p>
            </div>
            @if($employee->termination_date)
            <div>
                <p class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em]">Retiro</p>
                <p class="text-[14px] text-[var(--text-700)] mt-0.5">
                    {{ $employee->termination_date->format('d/m/Y') }}
                    <span class="text-[12px] text-[var(--text-400)]">— {{ \App\Models\Employee::TERMINATION_REASONS[$employee->termination_reason] }}</span>
                </p>
            </div>
            @endif
            @if($employee->email)
            <div>
                <p class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em]">Correo</p>
                <a href="mailto:{{ $employee->email }}" class="text-[14px] text-[var(--color-primary)] hover:underline mt-0.5 block truncate">{{ $employee->email }}</a>
            </div>
            @endif
            @if($employee->phone)
            <div>
                <p class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em]">Teléfono</p>
                <p class="text-[14px] text-[var(--text-700)] mt-0.5">{{ $employee->phone }}</p>
            </div>
            @endif
        </div>

        <div class="mt-4 pt-4 border-t border-[var(--border-default)] grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
                <p class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em]">Auxilio de transporte</p>
                <p class="text-[14px] text-[var(--text-700)] mt-0.5">{{ \App\Models\Employee::TRANSPORT_ALLOWANCE_MODES[$employee->transport_allowance_mode] }}</p>
                @if($employee->transport_allowance_note)
                <p class="text-[12px] text-[var(--text-400)]">{{ $employee->transport_allowance_note }}</p>
                @endif
            </div>
            <div>
                <p class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em]">EPS</p>
                <p class="text-[14px] text-[var(--text-700)] mt-0.5">{{ $employee->eps ?? '—' }}</p>
            </div>
            <div>
                <p class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em]">Fondo de pensiones</p>
                <p class="text-[14px] text-[var(--text-700)] mt-0.5">
                    {{ $employee->pension_exempt ? 'No cotiza a pensión' : ($employee->afp ?? '—') }}
                </p>
            </div>
            <div>
                <p class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em]">Riesgo ARL</p>
                <p class="text-[14px] text-[var(--text-700)] mt-0.5">{{ \App\Models\Employee::ARL_RISK_LEVELS[$employee->arl_risk_level] }}</p>
            </div>
        </div>
    </div>

    {{-- ===== NÓMINAS RECIENTES ===== --}}
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
        <div class="px-6 py-4 border-b border-[var(--border-default)] flex items-center justify-between">
            <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Desprendibles recientes</h2>
            <a href="{{ route('payroll-periods.index', ['client_id' => $employee->client_id], false) }}" class="text-[13px] text-[var(--color-primary)] hover:underline font-medium">
                Ver períodos de nómina
            </a>
        </div>
        @if($recentPayrolls->isEmpty())
        <div class="px-6 py-10 text-center">
            <p class="text-[14px] text-[var(--text-500)]">Este empleado aún no tiene nóminas generadas.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-[var(--border-default)]">
                        <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-left">Período</th>
                        <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-right">Devengado</th>
                        <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-right">Neto pagado</th>
                        <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentPayrolls as $payroll)
                    <tr class="border-b border-[var(--surface-muted)] hover:bg-[var(--surface-subtle)]">
                        <td class="px-6 py-[14px]">
                            <a href="{{ route('payroll-periods.show', $payroll->payrollPeriod, false) }}" class="text-[14px] font-medium text-[var(--text-900)] hover:text-[var(--color-primary)]">
                                {{ $payroll->payrollPeriod->number }}
                            </a>
                            <p class="text-[12px] text-[var(--text-400)]">{{ $payroll->payrollPeriod->start_date->format('d/m/Y') }} – {{ $payroll->payrollPeriod->end_date->format('d/m/Y') }}</p>
                        </td>
                        <td class="px-6 py-[14px] text-right text-[14px] text-[var(--text-700)]">$ {{ number_format($payroll->total_earned, 0, ',', '.') }}</td>
                        <td class="px-6 py-[14px] text-right text-[14px] font-semibold text-[var(--text-900)]">$ {{ number_format($payroll->net_pay, 0, ',', '.') }}</td>
                        <td class="px-6 py-[14px] text-center">
                            <x-status-badge :variant="match($payroll->status) { 'pagada' => 'success', 'emitida' => 'info', 'anulada' => 'neutral', default => 'warning' }">
                                {{ \App\Models\Payroll::STATUSES[$payroll->status] }}
                            </x-status-badge>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
</x-app-layout>
