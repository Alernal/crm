<x-app-layout>
<x-slot name="title">Variables</x-slot>

{{-- Breadcrumb --}}
<nav class="flex items-center gap-1.5 text-[14px] text-[var(--text-400)] mb-5">
    <a href="{{ route('financial.index') }}" class="hover:text-[var(--color-primary)]">Financiero</a>
    <x-lucide-chevron-right class="w-3.5 h-3.5" />
    <a href="{{ route('financial.client', $client) }}" class="hover:text-[var(--color-primary)]">{{ $client->name }}</a>
    <x-lucide-chevron-right class="w-3.5 h-3.5" />
    <span class="text-[var(--text-700)] font-medium">Variables</span>
</nav>

{{-- Flash --}}
@if(session('success'))
<div class="mb-4 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

<form method="POST" action="{{ route('financial.save_variables', $client) }}">
@csrf

<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] p-6 mb-5">
    <div class="flex items-center gap-3 mb-6">
        <div class="w-9 h-9 rounded-[var(--radius-control)] bg-[var(--color-primary-light)] flex items-center justify-center">
            <x-lucide-settings class="w-4 h-4 text-[var(--color-primary)]" />
        </div>
        <div>
            <h1 class="text-[16px] font-semibold text-[var(--text-900)]">Variables macroeconómicas</h1>
            <p class="text-[12px] text-[var(--text-400)] mt-0.5">
                Cliente: <span class="font-medium text-[var(--text-700)]">{{ $client->name }}</span>
                · Estas tasas impulsan la proyección automática de cada rubro
            </p>
        </div>
    </div>

    @if($errors->any())
    <div class="mb-4 bg-[var(--color-danger-bg)] border border-[var(--color-danger)]/20 rounded-[var(--radius-control)] px-4 py-3 text-[14px] text-[var(--color-danger-text)]">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    @php
        $groups = [
            [
                'title' => 'Indicadores Macroeconómicos Colombia',
                'icon'  => 'bar-chart-2',
                'bg'    => 'var(--color-primary-light)',
                'fg'    => 'var(--color-primary)',
                'fields' => [
                    ['key' => 'ipc',            'label' => 'IPC proyectado',          'help' => 'Índice de Precios al Consumidor. Referencia: 6.77% (2025)'],
                    ['key' => 'inflation',       'label' => 'Inflación proyectada',     'help' => 'Meta inflación Banco de la República'],
                    ['key' => 'smmlv_increase',  'label' => 'Incremento SMMLV (%)',     'help' => 'Porcentaje de aumento del Salario Mínimo. Referencia: 9.54% (2025)'],
                    ['key' => 'interest_rate',   'label' => 'Tasa de interés bancaria', 'help' => 'Tasa de referencia Banco de la República + spread'],
                ],
            ],
            [
                'title' => 'Metas Comerciales',
                'icon'  => 'trending-up',
                'bg'    => 'var(--color-success-bg)',
                'fg'    => 'var(--color-success)',
                'fields' => [
                    ['key' => 'sales_growth',         'label' => 'Meta crecimiento ventas anual (%)',   'help' => 'Porcentaje de crecimiento esperado en ingresos por año'],
                    ['key' => 'sales_growth_monthly',  'label' => 'Meta crecimiento ventas mensual (%)', 'help' => 'Para presupuestos mensuales. Ej: 0.80% mensual ≈ 10% anual'],
                    ['key' => 'new_clients_pct',       'label' => 'Meta nuevos clientes (%)',           'help' => 'Porcentaje de crecimiento en base de clientes'],
                    ['key' => 'services_growth',       'label' => 'Incremento tarifas de servicios (%)', 'help' => 'Ajuste anual de tarifas profesionales'],
                ],
            ],
            [
                'title' => 'Costos y Gastos Operativos',
                'icon'  => 'credit-card',
                'bg'    => 'var(--color-warning-bg)',
                'fg'    => 'var(--color-warning)',
                'fields' => [
                    ['key' => 'payroll_growth',   'label' => 'Incremento nómina (%)',             'help' => 'Usualmente igual o superior al SMMLV'],
                    ['key' => 'rent_growth',      'label' => 'Incremento arrendamientos (%)',     'help' => 'Legalmente ligado al IPC del año anterior'],
                    ['key' => 'utilities_growth', 'label' => 'Incremento servicios públicos (%)', 'help' => 'Energía, agua, gas, internet'],
                    ['key' => 'purchases_growth', 'label' => 'Incremento compras / insumos (%)', 'help' => 'Variación en precios de proveedores'],
                ],
            ],
        ];
    @endphp

    <div class="space-y-6">
        @foreach($groups as $group)
        <div>
            <div class="flex items-center gap-2 mb-3">
                <div class="w-6 h-6 rounded-[var(--radius-control)] flex items-center justify-center" style="background: {{ $group['bg'] }};">
                    @svg('lucide-' . $group['icon'], 'w-3.5 h-3.5', ['style' => 'color: ' . $group['fg'] . ';'])
                </div>
                <h2 class="text-[14px] font-semibold text-[var(--text-700)]">{{ $group['title'] }}</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($group['fields'] as $f)
                @php $initVal = old($f['key'], $vars->{$f['key']} ?? ''); @endphp
                <div class="bg-[var(--surface-subtle)] rounded-[var(--radius-control)] p-4 border border-[var(--border-default)]"
                     x-data="{ raw: '{{ $initVal }}' }"
                     x-init="$refs.display.value = raw !== '' ? raw + '%' : ''">
                    <label class="block text-[12px] font-medium text-[var(--text-700)] mb-2">{{ $f['label'] }}</label>
                    <input type="text" x-ref="display"
                           @focus="$event.target.value = raw; $event.target.select()"
                           @blur="raw = $event.target.value.replace(/[^0-9.]/g, ''); $event.target.value = raw !== '' ? raw + '%' : ''"
                           placeholder="0.00%"
                           class="w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3 text-[14px] font-semibold bg-[var(--surface-card)] text-[var(--text-900)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none tabular-nums"/>
                    <input type="hidden" name="{{ $f['key'] }}" :value="raw"/>
                    <p class="text-[11px] text-[var(--text-400)] mt-1.5 leading-tight">{{ $f['help'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

<div class="flex items-center justify-between gap-3">
    <a href="{{ route('financial.client', $client) }}"
       class="h-10 flex items-center px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)]">
        Cancelar
    </a>
    <button type="submit"
            class="inline-flex items-center gap-[6px] h-10 px-6 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium rounded-[var(--radius-control)]">
        <x-lucide-check-circle class="w-4 h-4" />
        Guardar variables
    </button>
</div>

</form>

</x-app-layout>
