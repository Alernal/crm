<x-app-layout>
<x-slot name="title">Períodos de Nómina</x-slot>

@php
    $fieldClass = 'w-full h-10 px-3.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none';
    $th = 'text-[10px] font-semibold text-[var(--text-400)] uppercase tracking-[0.04em] px-3 py-2.5 text-right whitespace-nowrap';
    $thGroup = 'text-[10px] font-semibold uppercase tracking-[0.04em] px-3 py-2 text-center whitespace-nowrap border-b border-[var(--border-default)]';
    $td = 'px-3 py-2.5 text-right text-[13px] text-[var(--text-700)] whitespace-nowrap tabular-nums';
    $editable = $payrollPeriod->status === 'borrador';
    $canEditConcepts = in_array($payrollPeriod->status, ['borrador', 'procesada'], true);

    $rowsData = $payrollPeriod->payrolls->map(function ($p) use ($overtimeRatesByPayroll) {
        return [
            'id' => $p->id,
            'employee_name' => $p->employee->full_name,
            'employee_email' => $p->employee->email,
            'worked_days' => (float) $p->worked_days,
            'commissions' => (float) $p->commissions,
            'bonuses_salarial' => (float) $p->bonuses_salarial,
            'per_diem_salarial' => (float) $p->per_diem_salarial,
            'other_salarial' => (float) $p->other_salarial,
            'occasional_bonuses' => (float) $p->occasional_bonuses,
            'extralegal_premiums' => (float) $p->extralegal_premiums,
            'per_diem_no_salarial' => (float) $p->per_diem_no_salarial,
            'other_no_salarial' => (float) $p->other_no_salarial,
            'loans_deduction' => (float) $p->loans_deduction,
            'withholding_tax' => (float) $p->withholding_tax,
            'other_deductions' => (float) $p->other_deductions,
            'overtime_items' => $p->overtimeItems->map(fn ($i) => ['type' => $i->type, 'hours' => (float) $i->hours])->values(),
            'rates' => $overtimeRatesByPayroll[$p->id] ?? [],
            'update_url' => route('payrolls.update', $p, false),
            'overtime_url' => route('payrolls.overtime.update', $p, false),
            'email_url' => route('payrolls.send_email', $p, false),
            'pdf_url' => route('payrolls.pdf', $p, false),
            'print_url' => route('payrolls.print', $p, false),
        ];
    });
@endphp

<div x-data="payrollShow(@js($rowsData))" @keydown.escape.window="closeModals()">

<a href="{{ route('payroll-periods.index', [], false) }}"
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
            <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $payrollPeriod->number }} — {{ $payrollPeriod->client->name }}</p>
            <p class="text-[14px] text-[var(--text-500)] mt-0.5">
                {{ \App\Models\PayrollPeriod::PERIOD_TYPES[$payrollPeriod->period_type] }}
                &bull; {{ $payrollPeriod->start_date->format('d/m/Y') }} – {{ $payrollPeriod->end_date->format('d/m/Y') }}
                &bull; Pago: {{ $payrollPeriod->payment_date->format('d/m/Y') }}
            </p>
            <div class="mt-2">
                <x-status-badge :variant="match($payrollPeriod->status) { 'pagada' => 'success', 'procesada' => 'info', 'anulada' => 'neutral', default => 'warning' }">
                    {{ \App\Models\PayrollPeriod::STATUSES[$payrollPeriod->status] }}
                </x-status-badge>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('payroll-periods.index', ['client_id' => $payrollPeriod->client_id, 'duplicate_from' => $payrollPeriod->id], false) }}"
               class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                <x-lucide-copy class="w-4 h-4" />
                Duplicar
            </a>
            <a href="{{ route('payroll-periods.pdf', $payrollPeriod, false) }}"
               class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                <x-lucide-download class="w-4 h-4" />
                Descargar PDF
            </a>
            <button @click="printUrl = '{{ route('payroll-periods.print', $payrollPeriod, false) }}'; isPrinting = true"
               class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                <x-lucide-printer class="w-4 h-4" />
                Imprimir
            </button>
            @if($editable)
            <form method="POST" action="{{ route('payroll-periods.close', $payrollPeriod, false) }}"
                  x-data="" x-on:submit.prevent="if(confirm('¿Procesar este período? Los desprendibles quedarán marcados como emitidos.')) $el.submit()">
                @csrf @method('PATCH')
                <button type="submit" class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">
                    <x-lucide-check class="w-4 h-4" />
                    Procesar período
                </button>
            </form>
            @endif
            @if(in_array($payrollPeriod->status, ['borrador', 'procesada'], true))
            <form method="POST" action="{{ route('payroll-periods.destroy', $payrollPeriod, false) }}"
                  x-data="" x-on:submit.prevent="if(confirm('¿Eliminar este período de nómina? Esta acción no se puede deshacer.')) $el.submit()">
                @csrf @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] border bg-[var(--color-danger-bg)]/50 border-[var(--color-danger)]/30 text-[var(--color-danger)] text-[14px] font-medium hover:bg-[var(--color-danger-bg)]">
                    <x-lucide-trash-2 class="w-4 h-4" />
                    Eliminar
                </button>
            </form>
            @endif
        </div>
    </div>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="rounded-[var(--radius-card)] p-5 text-white" style="background: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%);">
        <p class="text-[11px] font-medium text-white/75 uppercase tracking-[0.06em]">Neto total a pagar</p>
        <p class="text-[22px] font-bold mt-2">$ {{ number_format($payrollPeriod->total_net_pay, 0, ',', '.') }}</p>
        <p class="text-[12px] text-white/60 mt-1">{{ $payrollPeriod->payrolls->count() }} empleados</p>
    </div>
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-5 shadow-[var(--shadow-card)]">
        <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Total devengado</p>
        <p class="text-[22px] font-bold text-[var(--text-900)] mt-2">$ {{ number_format($payrollPeriod->total_earned, 0, ',', '.') }}</p>
    </div>
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-5 shadow-[var(--shadow-card)]">
        <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Total deducciones</p>
        <p class="text-[22px] font-bold text-[var(--text-900)] mt-2">$ {{ number_format($payrollPeriod->total_deductions, 0, ',', '.') }}</p>
    </div>
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-5 shadow-[var(--shadow-card)]">
        <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Aportes + provisiones patronales</p>
        <p class="text-[22px] font-bold text-[var(--text-900)] mt-2">$ {{ number_format($payrollPeriod->total_employer_contributions, 0, ',', '.') }}</p>
    </div>
</div>

{{-- Tabla de nómina --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
    <div class="px-4 py-3 border-b border-[var(--border-default)] flex items-center gap-2">
        <h3 class="text-[13px] font-semibold text-[var(--text-900)]">Detalle por empleado</h3>
        <x-help-icon title="Detalle por empleado">
            Usa "Editar conceptos" en cada fila para ajustar comisiones, bonificaciones, viáticos y deducciones manuales, y "Horas extra" para registrar recargos/horas extra por tipo legal. Los días laborados, la seguridad social y las prestaciones se recalculan automáticamente al guardar.
        </x-help-icon>
    </div>
    <div class="overflow-x-auto p-3">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-[var(--surface-subtle)]">
                    <th rowspan="2" class="{{ $th }} text-left sticky left-0 bg-[var(--surface-card)] z-10">Empleado</th>
                    <th rowspan="2" class="{{ $th }}">Días</th>
                    <th colspan="7" class="{{ $thGroup }} text-[var(--text-700)] bg-[var(--surface-subtle)]">Devengado salarial</th>
                    <th colspan="6" class="{{ $thGroup }} text-[var(--text-700)] bg-[var(--surface-subtle)]">Devengado no salarial</th>
                    <th rowspan="2" class="{{ $th }}">Total<br>devengado</th>
                    <th colspan="7" class="{{ $thGroup }} text-[var(--color-danger-text)] bg-[var(--color-danger-bg)]">Deducciones</th>
                    <th rowspan="2" class="{{ $th }} bg-[var(--color-primary-light)]">Neto a<br>pagar</th>
                    <th colspan="7" class="{{ $thGroup }} text-[var(--text-700)] bg-[var(--surface-subtle)]">Aportes empleador</th>
                    <th colspan="4" class="{{ $thGroup }} text-[var(--text-700)] bg-[var(--surface-subtle)]">Provisión prestaciones sociales</th>
                    <th rowspan="2" class="{{ $th }} text-center">Acciones</th>
                </tr>
                <tr class="bg-[var(--surface-subtle)] border-b border-[var(--border-default)]">
                    <th class="{{ $th }}">Básico</th>
                    <th class="{{ $th }}">H. Extra</th>
                    <th class="{{ $th }}">Comisiones</th>
                    <th class="{{ $th }}">Bonif.</th>
                    <th class="{{ $th }}">Viáticos</th>
                    <th class="{{ $th }}">Otros</th>
                    <th class="{{ $th }} font-semibold">Subtotal</th>
                    <th class="{{ $th }}">Aux. Transp.</th>
                    <th class="{{ $th }}">Bonif. Ocas.</th>
                    <th class="{{ $th }}">Primas Extraleg.</th>
                    <th class="{{ $th }}">Viáticos</th>
                    <th class="{{ $th }}">Otros</th>
                    <th class="{{ $th }} font-semibold">Subtotal</th>
                    <th class="{{ $th }}">Salud</th>
                    <th class="{{ $th }}">Pensión</th>
                    <th class="{{ $th }}">F.S.P.</th>
                    <th class="{{ $th }}">Préstamos</th>
                    <th class="{{ $th }}">Retefuente</th>
                    <th class="{{ $th }}">Otros</th>
                    <th class="{{ $th }} font-semibold">Total</th>
                    <th class="{{ $th }}">Salud</th>
                    <th class="{{ $th }}">Pensión</th>
                    <th class="{{ $th }}">ARL</th>
                    <th class="{{ $th }}">Caja Comp.</th>
                    <th class="{{ $th }}">ICBF</th>
                    <th class="{{ $th }}">SENA</th>
                    <th class="{{ $th }} font-semibold">Total</th>
                    <th class="{{ $th }}">Prima</th>
                    <th class="{{ $th }}">Cesantías</th>
                    <th class="{{ $th }}">Int. Cesant.</th>
                    <th class="{{ $th }}">Vacaciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payrollPeriod->payrolls as $payroll)
                <tr class="border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">
                    <td class="px-3 py-2.5 text-[13px] font-medium text-[var(--text-900)] whitespace-nowrap sticky left-0 bg-[var(--surface-card)]">
                        {{ $payroll->employee->full_name }}
                    </td>
                    <td class="{{ $td }}">{{ number_format($payroll->worked_days, 0) }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->basic_salary_pay, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->overtime_pay, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->commissions, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->bonuses_salarial, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->per_diem_salarial, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->other_salarial, 0, ',', '.') }}</td>
                    <td class="{{ $td }} font-semibold">{{ number_format($payroll->subtotal_salarial, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->transport_allowance, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->occasional_bonuses, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->extralegal_premiums, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->per_diem_no_salarial, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->other_no_salarial, 0, ',', '.') }}</td>
                    <td class="{{ $td }} font-semibold">{{ number_format($payroll->subtotal_no_salarial, 0, ',', '.') }}</td>
                    <td class="{{ $td }} font-semibold text-[var(--text-900)]">{{ number_format($payroll->total_earned, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->health_employee, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->pension_employee, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->fsp_employee, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->loans_deduction, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->withholding_tax, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->other_deductions, 0, ',', '.') }}</td>
                    <td class="{{ $td }} font-semibold text-[var(--color-danger-text)]">{{ number_format($payroll->total_deductions, 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right text-[14px] font-bold text-[var(--text-900)] whitespace-nowrap bg-[var(--color-primary-light)]/40 tabular-nums">
                        {{ number_format($payroll->net_pay, 0, ',', '.') }}
                    </td>
                    <td class="{{ $td }}">{{ number_format($payroll->health_employer, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->pension_employer, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->arl_employer, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->caja_compensacion, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->icbf, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->sena, 0, ',', '.') }}</td>
                    <td class="{{ $td }} font-semibold">{{ number_format($payroll->total_employer_contributions, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->prima_provision, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->cesantias_provision, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->interest_cesantias_provision, 0, ',', '.') }}</td>
                    <td class="{{ $td }}">{{ number_format($payroll->vacation_provision, 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5">
                        <div class="flex items-center justify-center gap-2">
                            @if($canEditConcepts)
                            <button @click="openConceptos({{ $payroll->id }})" title="Editar conceptos" class="text-[var(--text-400)] hover:text-[var(--color-primary)]">
                                <x-lucide-pencil class="w-4 h-4" />
                            </button>
                            <button @click="openOvertime({{ $payroll->id }})" title="Horas extra" class="text-[var(--text-400)] hover:text-[var(--color-primary)]">
                                <x-lucide-clock class="w-4 h-4" />
                            </button>
                            @endif
                            <a href="{{ route('payrolls.pdf', $payroll, false) }}" title="Descargar PDF" class="text-[var(--text-400)] hover:text-[var(--text-900)]">
                                <x-lucide-download class="w-4 h-4" />
                            </a>
                            <button @click="printUrl = '{{ route('payrolls.print', $payroll, false) }}'; isPrinting = true" title="Imprimir" class="text-[var(--text-400)] hover:text-[var(--text-900)]">
                                <x-lucide-printer class="w-4 h-4" />
                            </button>
                            <button @click="openEmail({{ $payroll->id }})" title="Enviar por correo" class="text-[var(--text-400)] hover:text-[var(--text-900)]">
                                <x-lucide-mail class="w-4 h-4" />
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@include('payroll._modals', ['fieldClass' => $fieldClass])

{{-- iframe oculto — carga la vista de impresión y dispara print() automáticamente --}}
<iframe
    x-ref="printFrame"
    :src="isPrinting ? printUrl : ''"
    @load="if (isPrinting) { $refs.printFrame.contentWindow.print(); isPrinting = false }"
    style="position:fixed;top:-9999px;left:-9999px;width:0;height:0;border:0"
    title="Vista de impresión">
</iframe>

</div>

<script>
function payrollShow(rows) {
    return {
        rows: rows,
        conceptos: { open: false, row: null, form: {} },
        overtime: { open: false, row: null, items: [] },
        email: { open: false, row: null, email: '', message: '' },
        isPrinting: false,
        printUrl: '',

        findRow(id) { return this.rows.find(r => r.id === id); },

        openConceptos(id) {
            const row = this.findRow(id);
            this.conceptos.row = row;
            this.conceptos.form = {
                worked_days: row.worked_days,
                commissions: row.commissions,
                bonuses_salarial: row.bonuses_salarial,
                per_diem_salarial: row.per_diem_salarial,
                other_salarial: row.other_salarial,
                occasional_bonuses: row.occasional_bonuses,
                extralegal_premiums: row.extralegal_premiums,
                per_diem_no_salarial: row.per_diem_no_salarial,
                other_no_salarial: row.other_no_salarial,
                loans_deduction: row.loans_deduction,
                withholding_tax: row.withholding_tax,
                other_deductions: row.other_deductions,
            };
            this.conceptos.open = true;
        },

        openOvertime(id) {
            const row = this.findRow(id);
            this.overtime.row = row;
            this.overtime.items = row.overtime_items.length
                ? row.overtime_items.map(i => ({ type: i.type, hours: i.hours }))
                : [];
            this.overtime.open = true;
        },
        addOvertimeItem() {
            this.overtime.items.push({ type: 'extra_diurna', hours: 1 });
        },
        removeOvertimeItem(index) {
            this.overtime.items.splice(index, 1);
        },
        overtimeLineTotal(item) {
            const rate = (this.overtime.row?.rates?.[item.type]) || 0;
            return rate * (parseFloat(item.hours) || 0);
        },
        get overtimeTotal() {
            return this.overtime.items.reduce((sum, item) => sum + this.overtimeLineTotal(item), 0);
        },

        openEmail(id) {
            const row = this.findRow(id);
            this.email.row = row;
            this.email.email = row.employee_email || '';
            this.email.message = '';
            this.email.open = true;
        },

        closeModals() {
            this.conceptos.open = false;
            this.overtime.open = false;
            this.email.open = false;
        },

        fmt(v) {
            return Number(v || 0).toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        },
    };
}
</script>
</x-app-layout>
