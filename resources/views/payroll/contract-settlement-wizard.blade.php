<x-app-layout>
<x-slot name="title">Liquidación de Contratos</x-slot>

@php
    $fieldClass = 'w-full h-10 px-3.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none';
    $labelClass = 'block text-[13px] font-medium text-[var(--text-700)] mb-1';
    $helpClass = 'mt-1.5 text-[12px] text-[var(--text-400)]';
    $fmtDate = fn ($d) => $d ? $d->format('Y-m-d') : '';
    $isFijo = $employee->contract_type === 'fijo';
@endphp

<a href="{{ route('employees.show', $employee, false) }}"
   class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)] mb-4">
    <x-lucide-arrow-left class="w-4 h-4" />
    Volver a {{ $employee->full_name }}
</a>

@if($errors->any())
<div class="mb-5 flex items-start gap-2 bg-[var(--color-danger-bg)] border border-[var(--color-danger)]/20 text-[var(--color-danger-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-alert-triangle class="w-4 h-4 flex-shrink-0 mt-0.5" />
    <div>
        @foreach($errors->all() as $error)
        <p>{{ $error }}</p>
        @endforeach
    </div>
</div>
@endif

<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] p-6 mb-5">
    <div class="flex items-center gap-3">
        <div class="w-11 h-11 bg-[var(--color-primary-light)] rounded-[var(--radius-control)] flex items-center justify-center flex-shrink-0">
            <x-lucide-file-minus class="w-5 h-5 text-[var(--color-primary)]" />
        </div>
        <div>
            <p class="text-[22px] font-bold text-[var(--text-900)]">Liquidar contrato — {{ $employee->full_name }}</p>
            <p class="text-[14px] text-[var(--text-500)] mt-0.5">
                {{ \App\Models\Employee::CONTRACT_TYPES[$employee->contract_type] }}
                &bull; Ingreso: {{ $employee->hire_date?->format('d/m/Y') ?? '—' }}
                &bull; Retiro: {{ $employee->termination_date->format('d/m/Y') }}
                &bull; {{ \App\Models\Employee::TERMINATION_REASONS[$employee->termination_reason] }}
            </p>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('contract-settlements.store', [], false) }}" class="space-y-5"
      x-data="{
          smlv: {{ old('smlv', $defaults['smlv']) }},
          last_salary: {{ old('last_salary', $defaults['last_salary']) }},
          basic_salary: {{ old('basic_salary', $defaults['basic_salary']) }},
          transport_allowance_input: {{ old('transport_allowance_input', $defaults['transport_allowance_input']) }},
          overtime_value: {{ old('overtime_value', 0) }},
          recargos_value: {{ old('recargos_value', 0) }},
          commissions: {{ old('commissions', 0) }},
          bonuses_salarial: {{ old('bonuses_salarial', 0) }},
          per_diem_salarial: {{ old('per_diem_salarial', 0) }},
          other_salarial: {{ old('other_salarial', 0) }},
          occasional_bonuses: {{ old('occasional_bonuses', 0) }},
          extralegal_premiums: {{ old('extralegal_premiums', 0) }},
          per_diem_no_salarial: {{ old('per_diem_no_salarial', 0) }},
          other_no_salarial: {{ old('other_no_salarial', 0) }},
          withholding_tax: {{ old('withholding_tax', 0) }},
          other_deductions: {{ old('other_deductions', 0) }},
      }">
    @csrf
    <input type="hidden" name="employee_id" value="{{ $employee->id }}">
    <input type="hidden" name="contract_type" value="{{ $employee->contract_type }}">

    {{-- Datos iniciales --}}
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-6">
        <h3 class="flex items-center gap-2 text-[13px] font-semibold text-[var(--text-900)] uppercase tracking-[0.06em] mb-4">
            Datos iniciales
            <x-help-icon title="Datos iniciales">
                Precargados desde la ficha del empleado y el retiro registrado — revísalos y ajústalos si el salario a liquidar difiere del básico actual (ej. salario variable) o si el retiro no da lugar a indemnización.
            </x-help-icon>
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="{{ $labelClass }}">SMMLV vigente <span class="text-[var(--color-danger)]">*</span></label>
                <input type="text" name="smlv" x-money="smlv" class="{{ $fieldClass }}" required>
            </div>
            <div>
                <label class="{{ $labelClass }}">Último salario devengado <span class="text-[var(--color-danger)]">*</span></label>
                <input type="text" name="last_salary" x-money="last_salary" class="{{ $fieldClass }}" required>
                <p class="{{ $helpClass }}">Si el salario es variable, usa el promedio de los últimos 12 meses.</p>
            </div>
            <div>
                <label class="{{ $labelClass }}">Sueldo básico mensual <span class="text-[var(--color-danger)]">*</span></label>
                <input type="text" name="basic_salary" x-money="basic_salary" class="{{ $fieldClass }}" required>
            </div>
            <div>
                <label class="{{ $labelClass }}">Auxilio de transporte (para prestaciones) <span class="text-[var(--color-danger)]">*</span></label>
                <input type="text" name="transport_allowance_input" x-money="transport_allowance_input" class="{{ $fieldClass }}" required>
            </div>
            <div>
                <label class="{{ $labelClass }}">Días laborados en el mes de liquidación <span class="text-[var(--color-danger)]">*</span></label>
                <input type="number" step="0.01" min="0" max="30" name="worked_days_month" value="{{ old('worked_days_month', $defaults['worked_days_month']) }}" class="{{ $fieldClass }}" required>
            </div>
            <div>
                <label class="{{ $labelClass }}">¿Hay lugar a indemnización? <span class="text-[var(--color-danger)]">*</span></label>
                <select name="indemnification_applies" class="{{ $fieldClass }}" required>
                    <option value="1" {{ old('indemnification_applies', $defaults['indemnification_applies'] ? '1' : '0') == '1' ? 'selected' : '' }}>Sí — despido sin justa causa</option>
                    <option value="0" {{ old('indemnification_applies', $defaults['indemnification_applies'] ? '1' : '0') == '0' ? 'selected' : '' }}>No</option>
                </select>
                <p class="{{ $helpClass }}">Sugerido según el motivo de retiro registrado, puedes ajustarlo.</p>
            </div>
        </div>
    </div>

    {{-- Fechas base --}}
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-6">
        <h3 class="flex items-center gap-2 text-[13px] font-semibold text-[var(--text-900)] uppercase tracking-[0.06em] mb-1">
            Fechas requeridas en los cálculos
            <x-help-icon title="Fechas base">
                Cada fecha marca desde cuándo se cuentan los días pendientes de esa prestación. Si el empleado ya recibió prima/cesantías/vacaciones antes, usa la fecha del último pago en vez del inicio del año — así no se le liquida dos veces el mismo período.
            </x-help-icon>
        </h3>
        <p class="text-[12px] text-[var(--text-400)] mb-4">Ajusta estas fechas si el empleado ya tenía prestaciones liquidadas o vacaciones disfrutadas antes de este período.</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="{{ $labelClass }}">Inicio del año en curso <span class="text-[var(--color-danger)]">*</span></label>
                <input type="date" name="year_start_date" value="{{ old('year_start_date', $fmtDate($defaults['year_start_date'])) }}" class="{{ $fieldClass }}" required>
                <p class="{{ $helpClass }}">Base para cesantías.</p>
            </div>
            <div>
                <label class="{{ $labelClass }}">Inicio del período base de prima <span class="text-[var(--color-danger)]">*</span></label>
                <input type="date" name="prima_period_start" value="{{ old('prima_period_start', $fmtDate($defaults['prima_period_start'])) }}" class="{{ $fieldClass }}" required>
            </div>
            <div>
                <label class="{{ $labelClass }}">Inicio del período base de vacaciones <span class="text-[var(--color-danger)]">*</span></label>
                <input type="date" name="vacation_period_start" value="{{ old('vacation_period_start', $fmtDate($defaults['vacation_period_start'])) }}" class="{{ $fieldClass }}" required>
                <p class="{{ $helpClass }}">Desde la última vez que disfrutó vacaciones (el sistema no lleva ese historial todavía).</p>
            </div>
            <div>
                <label class="{{ $labelClass }}">Fecha de finalización de contrato <span class="text-[var(--color-danger)]">*</span></label>
                <input type="date" name="contract_end_date" value="{{ old('contract_end_date', $fmtDate($defaults['contract_end_date'])) }}" class="{{ $fieldClass }} bg-[var(--surface-subtle)]" readonly required>
            </div>
            <div class="sm:col-span-2">
                @if($isFijo)
                <label class="{{ $labelClass }}">Fecha de finalización pactada inicialmente <span class="text-[var(--color-danger)]">*</span></label>
                <input type="date" name="contract_reference_date" value="{{ old('contract_reference_date', $fmtDate($defaults['contract_reference_date'])) }}" class="{{ $fieldClass }}" required>
                <p class="{{ $helpClass }}">Necesaria para calcular la indemnización de un contrato a término fijo (días pendientes del plazo pactado).</p>
                @else
                <label class="{{ $labelClass }}">Fecha de inicio de contrato <span class="text-[var(--color-danger)]">*</span></label>
                <input type="date" name="contract_reference_date" value="{{ old('contract_reference_date', $fmtDate($defaults['contract_reference_date'])) }}" class="{{ $fieldClass }}" required>
                @endif
            </div>
        </div>
    </div>

    {{-- Pagos salariales del último período --}}
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-6">
        <h3 class="flex items-center gap-2 text-[13px] font-semibold text-[var(--text-900)] uppercase tracking-[0.06em] mb-1">
            Pagos que constituyen salario en el último período
            <x-help-icon title="Pagos salariales">
                Horas extra y recargos se digitan aparte de comisiones/bonificaciones porque afectan distinto el cálculo del auxilio de transporte — todos suman al IBC de seguridad social.
            </x-help-icon>
        </h3>
        <p class="text-[12px] text-[var(--text-400)] mb-4">Valores ya causados en el mes de retiro. Deja en 0 los que no apliquen.</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div><label class="{{ $labelClass }}">Horas extra</label><input type="text" name="overtime_value" x-money="overtime_value" class="{{ $fieldClass }}"></div>
            <div><label class="{{ $labelClass }}">Recargos</label><input type="text" name="recargos_value" x-money="recargos_value" class="{{ $fieldClass }}"></div>
            <div><label class="{{ $labelClass }}">Comisiones</label><input type="text" name="commissions" x-money="commissions" class="{{ $fieldClass }}"></div>
            <div><label class="{{ $labelClass }}">Bonificaciones salariales</label><input type="text" name="bonuses_salarial" x-money="bonuses_salarial" class="{{ $fieldClass }}"></div>
            <div><label class="{{ $labelClass }}">Viáticos permanentes</label><input type="text" name="per_diem_salarial" x-money="per_diem_salarial" class="{{ $fieldClass }}"></div>
            <div><label class="{{ $labelClass }}">Otros pagos salariales</label><input type="text" name="other_salarial" x-money="other_salarial" class="{{ $fieldClass }}"></div>
        </div>
    </div>

    {{-- Pagos no salariales del último período --}}
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-6">
        <h3 class="flex items-center gap-2 text-[13px] font-semibold text-[var(--text-900)] uppercase tracking-[0.06em] mb-1">
            Pagos que no constituyen salario en el último período
            <x-help-icon title="Pagos no salariales">
                No entran al IBC de seguridad social, salvo que su suma supere el 40% del total devengado (excedente del art. 30 de la Ley 1393/2010) — el sistema aplica esa regla automáticamente.
            </x-help-icon>
        </h3>
        <p class="text-[12px] text-[var(--text-400)] mb-4">El auxilio de transporte se calcula automáticamente si aplica.</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div><label class="{{ $labelClass }}">Bonificaciones ocasionales</label><input type="text" name="occasional_bonuses" x-money="occasional_bonuses" class="{{ $fieldClass }}"></div>
            <div><label class="{{ $labelClass }}">Primas, beneficios o auxilios extralegales</label><input type="text" name="extralegal_premiums" x-money="extralegal_premiums" class="{{ $fieldClass }}"></div>
            <div><label class="{{ $labelClass }}">Viáticos (medios de transporte o representación)</label><input type="text" name="per_diem_no_salarial" x-money="per_diem_no_salarial" class="{{ $fieldClass }}"></div>
            <div><label class="{{ $labelClass }}">Otros pagos no salariales</label><input type="text" name="other_no_salarial" x-money="other_no_salarial" class="{{ $fieldClass }}"></div>
        </div>
    </div>

    {{-- Deducciones adicionales --}}
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-6">
        <h3 class="flex items-center gap-2 text-[13px] font-semibold text-[var(--text-900)] uppercase tracking-[0.06em] mb-1">
            Deducciones adicionales
            <x-help-icon title="Deducciones adicionales">
                La retención en la fuente no se calcula automáticamente en este liquidador — digita el valor que ya calculaste aparte, si aplica.
            </x-help-icon>
        </h3>
        <p class="text-[12px] text-[var(--text-400)] mb-4">Salud, pensión y fondo de solidaridad se calculan automáticamente sobre el IBC.</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div><label class="{{ $labelClass }}">Retención en la fuente</label><input type="text" name="withholding_tax" x-money="withholding_tax" class="{{ $fieldClass }}"></div>
            <div><label class="{{ $labelClass }}">Otros valores a deducir (préstamos, libranza...)</label><input type="text" name="other_deductions" x-money="other_deductions" class="{{ $fieldClass }}"></div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="inline-flex items-center gap-[6px] h-11 px-6 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
            <x-lucide-calculator class="w-4 h-4" />
            Calcular y generar liquidación
        </button>
        <a href="{{ route('employees.show', $employee, false) }}" class="h-11 flex items-center px-5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[15px] font-medium hover:bg-[var(--surface-muted)]">
            Cancelar
        </a>
    </div>
</form>

</x-app-layout>
