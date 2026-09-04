<x-app-layout>
<x-slot name="title">Datos</x-slot>

{{-- Volver --}}
<a href="{{ route('financial.client', $client) }}"
   class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)] mb-5">
    <x-lucide-arrow-left class="w-4 h-4" />
    Volver
</a>

{{-- Flash --}}
@if(session('success'))
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="mb-4 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="mb-4 bg-[var(--color-danger-bg)] border border-[var(--color-danger)]/20 rounded-[var(--radius-control)] px-4 py-3 text-[14px] text-[var(--color-danger-text)]">
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('financial.save_data', $client) }}"
      x-data="datosForm({{ json_encode($years) }})">
@csrf

{{-- ── Indicadores por año ─────────────────────────────────────────────── --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] p-6 mb-5">
    <div class="flex items-center justify-between gap-3 mb-1">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-[var(--radius-control)] bg-[var(--color-primary-light)] flex items-center justify-center">
                <x-lucide-calendar-range class="w-4 h-4 text-[var(--color-primary)]" />
            </div>
            <div>
                <h2 class="text-[16px] font-bold text-[var(--text-900)]">Indicadores por año</h2>
                <p class="text-[12px] text-[var(--text-400)] mt-0.5">
                    Cliente: <span class="font-medium text-[var(--text-700)]">{{ $client->name }}</span>
                    · El SMMLV guía la proyección salarial de Nómina; la inflación guía precios de Ventas y renglones sin driver legal
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <button type="button" @click="fillMissingWithInflation()"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[12px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)]">
                <x-lucide-wand-2 class="w-3.5 h-3.5" />
                Proyectar años faltantes con inflación
            </button>
            <button type="button" @click="addYear()"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] hover:bg-[var(--surface-muted)] text-[12px] font-medium text-[var(--text-700)]">
                <x-lucide-plus class="w-3.5 h-3.5" />
                Agregar año
            </button>
        </div>
    </div>

    <div class="overflow-x-auto mt-4">
        <table class="w-full border-collapse">
            <thead>
                <tr class="border-b border-[var(--border-default)]">
                    <th class="text-left px-2 py-2 text-[11px] font-semibold text-[var(--text-400)] uppercase tracking-[0.06em]" style="width:180px">Indicador</th>
                    <template x-for="year in years" :key="year">
                        <th class="text-right px-2 py-2 text-[12px] font-semibold text-[var(--text-700)] tabular-nums" x-text="year"></th>
                    </template>
                </tr>
            </thead>
            <tbody>
                @foreach(\App\Models\ClientBudgetYearlyData::INDICATORS as $key => $label)
                <tr class="border-b border-[var(--surface-muted)]">
                    <td class="px-2 py-2.5 text-[13px] text-[var(--text-700)]">{{ $label }}</td>
                    <template x-for="year in years" :key="'{{ $key }}-'+year">
                        <td class="px-2 py-1.5 text-right">
                            @if($key === 'inflacion')
                            <input type="number" step="0.01" min="0"
                                   :name="`indicators[{{ $key }}][${year}]`"
                                   x-model="values['{{ $key }}'][year]"
                                   placeholder="—"
                                   class="w-28 h-9 text-right bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] px-2 text-[13px] tabular-nums outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]"/>
                            @else
                            <input type="hidden" :name="`indicators[{{ $key }}][${year}]`" x-model="values['{{ $key }}'][year]"/>
                            <input type="text" x-money="values['{{ $key }}'][year]"
                                   placeholder="—"
                                   class="w-28 h-9 text-right bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] px-2 text-[13px] tabular-nums outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]"/>
                            @endif
                        </td>
                    </template>
                </tr>
                @endforeach
            </tbody>
        </table>
        <template x-for="year in years" :key="'hidden-'+year">
            <input type="hidden" name="years[]" :value="year"/>
        </template>
    </div>
</div>

{{-- ── Políticas planas ─────────────────────────────────────────────────── --}}
@php
    $inputClass = 'w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3 text-[14px] font-semibold bg-[var(--surface-card)] text-[var(--text-900)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none tabular-nums';
    $fields = [
        [
            'title' => 'Política comercial y de cartera', 'icon' => 'credit-card', 'bg' => 'var(--color-success-bg)', 'fg' => 'var(--color-success)',
            'items' => [
                ['key' => 'credit_sales_pct', 'label' => '% Ventas a crédito', 'suffix' => '%', 'help' => 'El resto se asume de contado'],
                ['key' => 'collection_days',  'label' => 'Política de cobro de cartera', 'suffix' => 'días', 'help' => 'Rotación usada por el Presupuesto financiero para el recaudo'],
            ],
        ],
        [
            'title' => 'Política de proveedores', 'icon' => 'truck', 'bg' => 'var(--color-warning-bg)', 'fg' => 'var(--color-warning)',
            'items' => [
                ['key' => 'supplier_payment_days', 'label' => 'Política de pago a proveedores', 'suffix' => 'días', 'help' => 'Referencia informativa, no automatiza el Ppto aún'],
            ],
        ],
        [
            'title' => 'Parámetros financieros', 'icon' => 'landmark', 'bg' => 'var(--color-primary-light)', 'fg' => 'var(--color-primary)',
            'items' => [
                ['key' => 'partner_contributions', 'label' => 'Aportes de socios (sugerido)', 'suffix' => '$', 'help' => 'Valor por defecto al crear un presupuesto financiero'],
                ['key' => 'interest_rate',         'label' => 'Costo de la obligación financiera', 'suffix' => '%', 'help' => 'Tasa anual del plan de pagos'],
                ['key' => 'income_tax_rate',       'label' => 'Tarifa de impuesto de renta', 'suffix' => '%', 'help' => ''],
                ['key' => 'legal_reserve_pct',     'label' => 'Reserva legal', 'suffix' => '%', 'help' => ''],
            ],
        ],
    ];
@endphp

<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] p-6 mb-5">
    <div class="space-y-6">
        @foreach($fields as $group)
        <div>
            <div class="flex items-center gap-2 mb-3">
                <div class="w-6 h-6 rounded-[var(--radius-control)] flex items-center justify-center" style="background: {{ $group['bg'] }};">
                    @svg('lucide-' . $group['icon'], 'w-3.5 h-3.5', ['style' => 'color: ' . $group['fg'] . ';'])
                </div>
                <h2 class="text-[14px] font-bold text-[var(--text-700)]">{{ $group['title'] }}</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($group['items'] as $f)
                <div class="bg-[var(--surface-subtle)] rounded-[var(--radius-control)] p-4 border border-[var(--border-default)]">
                    <label class="block text-[12px] font-medium text-[var(--text-700)] mb-2">{{ $f['label'] }}</label>
                    <div class="relative" @if($f['suffix'] === '$') x-data="{ fieldVal: {{ old($f['key'], $clientData->{$f['key']} ?? 'null') }} }" @endif>
                        @if($f['suffix'] === '$')
                        <input type="text" name="{{ $f['key'] }}" x-money="fieldVal"
                               required
                               class="{{ $inputClass }} pr-10"/>
                        @else
                        <input type="number" step="{{ $f['suffix'] === 'días' ? '1' : '0.01' }}" min="0" name="{{ $f['key'] }}"
                               value="{{ old($f['key'], $clientData->{$f['key']} ?? '') }}"
                               required
                               class="{{ $inputClass }} pr-10"/>
                        @endif
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] text-[var(--text-400)]">{{ $f['suffix'] }}</span>
                    </div>
                    @if($f['help'])
                    <p class="text-[11px] text-[var(--text-400)] mt-1.5 leading-tight">{{ $f['help'] }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

<div class="flex items-center justify-between gap-3">
    <a href="{{ route('financial.client', $client) }}"
       class="h-10 flex items-center px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)]">
        Cancelar
    </a>
    <button type="submit"
            class="inline-flex items-center gap-[6px] h-10 px-6 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium rounded-[var(--radius-control)]">
        <x-lucide-check-circle class="w-4 h-4" />
        Guardar datos
    </button>
</div>

</form>

{{-- ── Parámetros legales de nómina (solo lectura) ─────────────────────── --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] p-6 mt-5">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-9 h-9 rounded-[var(--radius-control)] bg-[var(--surface-muted)] flex items-center justify-center">
            <x-lucide-shield-check class="w-4 h-4 text-[var(--text-500)]" />
        </div>
        <div>
            <h2 class="text-[15px] font-bold text-[var(--text-900)]">Parámetros legales de nómina (vigentes)</h2>
            <p class="text-[12px] text-[var(--text-400)] mt-0.5">
                Se gestionan en el módulo Nómina — el presupuesto de Nómina los usa automáticamente, sin duplicarlos aquí.
            </p>
        </div>
    </div>

    @if($legalSettings)
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach([
            'pct_cesantias' => 'Cesantías', 'pct_prima' => 'Prima', 'pct_vacaciones' => 'Vacaciones',
            'pct_health_employer' => 'Salud empleador', 'pct_pension_employer' => 'Pensión empleador',
            'pct_caja_compensacion' => 'Caja de compensación', 'pct_health_employee' => 'Salud empleado',
            'pct_pension_employee' => 'Pensión empleado', 'pct_sena' => 'SENA', 'pct_icbf' => 'ICBF',
        ] as $key => $label)
        <div class="bg-[var(--surface-subtle)] rounded-[var(--radius-control)] px-3 py-2.5 border border-[var(--border-default)]">
            <p class="text-[10px] text-[var(--text-400)] uppercase tracking-[0.04em]">{{ $label }}</p>
            <p class="text-[14px] font-semibold text-[var(--text-900)] tabular-nums">{{ number_format($legalSettings->{$key} * 100, 2) }}%</p>
        </div>
        @endforeach
        <div class="bg-[var(--surface-subtle)] rounded-[var(--radius-control)] px-3 py-2.5 border border-[var(--border-default)]">
            <p class="text-[10px] text-[var(--text-400)] uppercase tracking-[0.04em]">ARL (nivel I)</p>
            <p class="text-[14px] font-semibold text-[var(--text-900)] tabular-nums">{{ number_format($legalSettings->arlRateFor('I') * 100, 3) }}%</p>
        </div>
    </div>
    <p class="text-[11px] text-[var(--text-400)] mt-3">Vigentes desde {{ $legalSettings->effective_from->translatedFormat('d \d\e F \d\e Y') }}.</p>
    @else
    <p class="text-[13px] text-[var(--text-400)]">No hay parámetros legales sembrados todavía.</p>
    @endif
</div>

<script>
function datosForm(initialYears) {
    return {
        years: initialYears,
        values: @json($yearly),

        addYear() {
            const next = this.years.length ? Math.max(...this.years) + 1 : new Date().getFullYear();
            this.years.push(next);
            this.years.sort((a, b) => a - b);
            for (const key of Object.keys(this.values)) {
                if (this.values[key][next] === undefined) this.values[key][next] = null;
            }
        },

        fillMissingWithInflation() {
            const sortedYears = [...this.years].sort((a, b) => a - b);
            for (const indicator of ['smmlv', 'auxilio_transporte']) {
                let lastValue = null;
                for (const year of sortedYears) {
                    const current = this.values[indicator][year];
                    if (current !== null && current !== '' && current !== undefined) {
                        lastValue = parseFloat(current);
                        continue;
                    }
                    if (lastValue === null) continue;
                    const rate = parseFloat(this.values['inflacion'][year]) || 0;
                    lastValue = Math.round(lastValue * (1 + rate / 100) * 100) / 100;
                    this.values[indicator][year] = lastValue;
                }
            }
        },
    };
}
</script>

</x-app-layout>
