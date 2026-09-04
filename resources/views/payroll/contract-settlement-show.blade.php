<x-app-layout>
<x-slot name="title">Liquidación de Contratos</x-slot>

@php
    $rowClass = 'flex items-center justify-between py-2.5 border-b border-[var(--surface-muted)] last:border-b-0';
    $labelClass = 'text-[14px] text-[var(--text-700)]';
    $valueClass = 'text-[14px] text-[var(--text-900)] tabular-nums';
@endphp

<div x-data="{ isPrinting: false, printUrl: '' }">

<a href="{{ route('contract-settlements.index', [], false) }}"
   class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)] mb-4">
    <x-lucide-arrow-left class="w-4 h-4" />
    Volver
</a>

@if(session('success'))
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="mb-5 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

{{-- Cabecera --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] p-6 mb-5">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <p class="text-[22px] font-bold text-[var(--text-900)]">
                Liquidación de contrato — {{ $contractSettlement->employee->full_name }}
            </p>
            <p class="text-[14px] text-[var(--text-500)] mt-0.5">
                {{ $contractSettlement->client->name }}
                &bull; {{ \App\Models\Employee::CONTRACT_TYPES[$contractSettlement->contract_type] }}
                &bull; Retiro: {{ $contractSettlement->contract_end_date->format('d/m/Y') }}
            </p>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('contract-settlements.pdf', $contractSettlement, false) }}"
               class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                <x-lucide-download class="w-4 h-4" />
                Descargar PDF
            </a>
            <button @click="printUrl = '{{ route('contract-settlements.print', $contractSettlement, false) }}'; isPrinting = true"
               class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                <x-lucide-printer class="w-4 h-4" />
                Imprimir
            </button>
            <form method="POST" action="{{ route('contract-settlements.destroy', $contractSettlement, false) }}"
                  x-data="" x-on:submit.prevent="if(confirm('¿Eliminar esta liquidación de contrato? Esta acción no se puede deshacer.')) $el.submit()">
                @csrf @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] border bg-[var(--color-danger-bg)]/50 border-[var(--color-danger)]/30 text-[var(--color-danger)] text-[14px] font-medium hover:bg-[var(--color-danger-bg)]">
                    <x-lucide-trash-2 class="w-4 h-4" />
                    Eliminar
                </button>
            </form>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">

        {{-- Prestaciones sociales --}}
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-6">
            <h3 class="flex items-center gap-2 text-[13px] font-semibold text-[var(--text-900)] uppercase tracking-[0.06em] mb-3">
                Prestaciones sociales pendientes
                <x-help-icon title="Prestaciones sociales">
                    Días contados desde la fecha base de cada prestación (definida en el wizard) hasta la fecha de retiro, bajo la convención legal de mes de 30 días.
                </x-help-icon>
            </h3>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Prima de servicios ({{ number_format($contractSettlement->prima_days, 0) }} días)</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->prima_value, 0, ',', '.') }}</span></div>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Cesantías ({{ number_format($contractSettlement->cesantias_days, 0) }} días)</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->cesantias_value, 0, ',', '.') }}</span></div>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Intereses a las cesantías</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->interest_cesantias_value, 0, ',', '.') }}</span></div>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Vacaciones ({{ number_format($contractSettlement->vacation_days, 0) }} días)</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->vacation_value, 0, ',', '.') }}</span></div>
        </div>

        {{-- Pagos del último período --}}
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-6">
            <h3 class="flex items-center gap-2 text-[13px] font-semibold text-[var(--text-900)] uppercase tracking-[0.06em] mb-3">
                Pagos del último período laborado
                <x-help-icon title="Pagos del último período">
                    Valores devengados en el mes de retiro, digitados manualmente en el wizard — solo se muestran los conceptos con valor mayor a cero.
                </x-help-icon>
            </h3>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Salario días laborados</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->basic_salary_pay, 0, ',', '.') }}</span></div>
            @if($contractSettlement->overtime_value > 0)<div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Horas extra</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->overtime_value, 0, ',', '.') }}</span></div>@endif
            @if($contractSettlement->recargos_value > 0)<div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Recargos</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->recargos_value, 0, ',', '.') }}</span></div>@endif
            @if($contractSettlement->commissions > 0)<div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Comisiones</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->commissions, 0, ',', '.') }}</span></div>@endif
            @if($contractSettlement->bonuses_salarial > 0)<div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Bonificaciones salariales</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->bonuses_salarial, 0, ',', '.') }}</span></div>@endif
            @if($contractSettlement->per_diem_salarial > 0)<div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Viáticos permanentes</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->per_diem_salarial, 0, ',', '.') }}</span></div>@endif
            @if($contractSettlement->other_salarial > 0)<div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Otros pagos salariales</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->other_salarial, 0, ',', '.') }}</span></div>@endif
            @if($contractSettlement->occasional_bonuses > 0)<div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Bonificaciones ocasionales</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->occasional_bonuses, 0, ',', '.') }}</span></div>@endif
            @if($contractSettlement->extralegal_premiums > 0)<div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Primas, beneficios o auxilios extralegales</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->extralegal_premiums, 0, ',', '.') }}</span></div>@endif
            @if($contractSettlement->per_diem_no_salarial > 0)<div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Viáticos (no salariales)</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->per_diem_no_salarial, 0, ',', '.') }}</span></div>@endif
            @if($contractSettlement->transport_allowance_value > 0)<div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Auxilio de transporte</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->transport_allowance_value, 0, ',', '.') }}</span></div>@endif
            @if($contractSettlement->other_no_salarial > 0)<div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Otros pagos no salariales</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->other_no_salarial, 0, ',', '.') }}</span></div>@endif
        </div>

        @if($contractSettlement->indemnification_value > 0)
        <div class="bg-[var(--color-warning-bg)] border border-[var(--color-warning)]/30 rounded-[var(--radius-card)] p-6 flex items-center justify-between">
            <div>
                <h3 class="text-[13px] font-semibold text-[var(--color-warning-text)] uppercase tracking-[0.06em]">Indemnización por despido sin justa causa</h3>
                <p class="text-[12px] text-[var(--color-warning-text)]/80 mt-0.5">Calculada según el tipo de contrato y el tiempo laborado</p>
            </div>
            <span class="text-[20px] font-bold text-[var(--color-warning-text)] tabular-nums">$ {{ number_format($contractSettlement->indemnification_value, 0, ',', '.') }}</span>
        </div>
        @endif

        {{-- Deducciones --}}
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-6">
            <h3 class="flex items-center gap-2 text-[13px] font-semibold text-[var(--text-900)] uppercase tracking-[0.06em] mb-3">
                Deducciones
                <x-help-icon title="Deducciones">
                    Salud, pensión y fondo de solidaridad se calculan automáticamente sobre el IBC del último período; retención en la fuente y otros valores son los digitados manualmente en el wizard.
                </x-help-icon>
            </h3>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Salud</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->health_employee, 0, ',', '.') }}</span></div>
            @if($contractSettlement->pension_employee > 0)<div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Pensión</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->pension_employee, 0, ',', '.') }}</span></div>@endif
            @if($contractSettlement->fsp_employee > 0)<div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Fondo de solidaridad pensional</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->fsp_employee, 0, ',', '.') }}</span></div>@endif
            @if($contractSettlement->withholding_tax > 0)<div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Retención en la fuente</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->withholding_tax, 0, ',', '.') }}</span></div>@endif
            @if($contractSettlement->other_deductions > 0)<div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Otros valores a deducir</span><span class="{{ $valueClass }}">$ {{ number_format($contractSettlement->other_deductions, 0, ',', '.') }}</span></div>@endif
        </div>
    </div>

    {{-- Resumen --}}
    <div class="space-y-5">
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-6">
            <h3 class="flex items-center gap-2 text-[13px] font-semibold text-[var(--text-900)] uppercase tracking-[0.06em] mb-4">
                Resumen
                <x-help-icon title="Resumen">
                    Total a pagar = prestaciones + pagos del período + indemnización (si aplica). Neto a pagar = total a pagar menos el total de deducciones.
                </x-help-icon>
            </h3>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Total a pagar</span><span class="text-[14px] font-semibold text-[var(--color-success-text)] tabular-nums">$ {{ number_format($contractSettlement->total_to_pay, 0, ',', '.') }}</span></div>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Total deducciones</span><span class="text-[14px] font-semibold text-[var(--color-danger)] tabular-nums">$ {{ number_format($contractSettlement->total_deductions, 0, ',', '.') }}</span></div>
            <div class="mt-4 bg-[#1E3A8A] rounded-[var(--radius-control)] px-4 py-4 flex items-center justify-between">
                <span class="text-[13px] font-semibold uppercase tracking-[0.06em] text-blue-100">Neto a pagar</span>
                <span class="text-[20px] font-bold text-white tabular-nums">$ {{ number_format($contractSettlement->net_pay, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-6">
            <h3 class="text-[13px] font-semibold text-[var(--text-900)] uppercase tracking-[0.06em] mb-3">Datos del empleado</h3>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Documento</span><span class="{{ $valueClass }}">{{ $contractSettlement->employee->document_type }} {{ $contractSettlement->employee->document_number }}</span></div>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Cargo</span><span class="{{ $valueClass }}">{{ $contractSettlement->employee->position ?? '—' }}</span></div>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Ingreso</span><span class="{{ $valueClass }}">{{ $contractSettlement->employee->hire_date?->format('d/m/Y') ?? '—' }}</span></div>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Retiro</span><span class="{{ $valueClass }}">{{ $contractSettlement->contract_end_date->format('d/m/Y') }}</span></div>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Motivo</span><span class="{{ $valueClass }}">{{ \App\Models\Employee::TERMINATION_REASONS[$contractSettlement->employee->termination_reason] ?? '—' }}</span></div>
        </div>
    </div>
</div>

{{-- iframe oculto — carga la vista de impresión y dispara print() automáticamente --}}
<iframe
    x-ref="printFrame"
    :src="isPrinting ? printUrl : ''"
    @load="if (isPrinting) { $refs.printFrame.contentWindow.print(); isPrinting = false }"
    style="position:fixed;top:-9999px;left:-9999px;width:0;height:0;border:0"
    title="Vista de impresión">
</iframe>

</div>
</x-app-layout>
