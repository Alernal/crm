{{--
    Panel completo del ESF (tarjetas KPI + tabla de estructura) — partial
    propio para poder re-renderizarlo entero vía AJAX cuando se edita una
    celda en cualquiera de los dos estados (ver `saveValue()` más abajo en
    `show.blade.php` y `BudgetController::renderStatementPanel()`), de forma
    que "Utilidad del período"/"Resultados acumulados" y el badge
    Cuadra/Descuadre queden siempre al día sin depender de recargar la
    página completa.
--}}
@php
    $lastEsfIdx = array_key_last($esfPeriodLabels);
@endphp
<div x-data="statementTable('{{ route('financial.update_value', $esf) }}', '{{ csrf_token() }}', {{ $esf->id }})">
    <div x-ref="body">

        <div class="mb-5">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] mb-2.5">Resumen · {{ $esfPeriodLabels[$lastEsfIdx] }}</p>
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
                @foreach([
                    ['icon' => 'wallet',   'label' => 'Total Activo',     'value' => $totalActivo],
                    ['icon' => 'scale',    'label' => 'Total Pasivo',     'value' => $totalPasivo],
                    ['icon' => 'landmark', 'label' => 'Total Patrimonio', 'value' => $totalPatrim],
                ] as $k)
                <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 rounded-[var(--radius-control)] bg-[var(--color-primary-light)] flex items-center justify-center flex-shrink-0">
                            @svg('lucide-' . $k['icon'], 'w-4 h-4 text-[var(--color-primary)]')
                        </div>
                        <p class="text-[12px] font-medium text-[var(--text-500)]">{{ $k['label'] }}</p>
                    </div>
                    <p class="text-[20px] font-bold text-[var(--text-900)] tabular-nums {{ $k['value'] < 0 ? 'text-[var(--color-danger)]' : '' }}">
                        {{ $k['value'] < 0 ? '-$' : '$' }}{{ number_format(abs($k['value']), 0, ',', '.') }}
                    </p>
                </div>
                @endforeach
                <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 rounded-[var(--radius-control)] bg-[var(--color-primary-light)] flex items-center justify-center flex-shrink-0">
                            <x-lucide-check-circle class="w-4 h-4 text-[var(--color-primary)]" />
                        </div>
                        <p class="text-[12px] font-medium text-[var(--text-500)]">Estado del balance</p>
                    </div>
                    <x-status-badge :variant="$kpiBalanced ? 'success' : 'danger'">{{ $kpiBalanced ? 'Cuadra' : 'Descuadre' }}</x-status-badge>
                </div>
            </div>
        </div>

        @include('financial._statement-report-body', ['isEsf' => true, 'statementReport' => $esfReport, 'periodLabels' => $esfPeriodLabels])

    </div>
</div>
