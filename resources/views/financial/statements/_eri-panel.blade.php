{{--
    Panel completo del ERI (tarjetas KPI + tabla de estructura) — contraparte
    de `_esf-panel.blade.php`, mismo motivo: re-renderizable entero vía AJAX
    para que "Utilidad Neta" y el resto de KPIs queden al día en tiempo real.
--}}
@php
    $lastEriIdx = array_key_last($eriPeriodLabels);
@endphp
<div x-data="statementTable('{{ route('financial.update_value', $eri) }}', '{{ csrf_token() }}', {{ $eri->id }})">
    <div x-ref="body">

        <div class="mb-5">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] mb-2.5">Resumen · {{ $eriPeriodLabels[$lastEriIdx] }}</p>
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
                @foreach([
                    ['icon' => 'trending-up', 'label' => 'Ventas Netas',   'value' => $ventasNetas],
                    ['icon' => 'package',     'label' => 'Utilidad Bruta', 'value' => $eriReport['utilidadBruta'][$lastEriIdx] ?? 0],
                    ['icon' => 'activity',    'label' => 'EBITDA',         'value' => $eriReport['ebitda'][$lastEriIdx] ?? 0],
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
                            <x-lucide-trending-up class="w-4 h-4 text-[var(--color-primary)]" />
                        </div>
                        <p class="text-[12px] font-medium text-[var(--text-500)]">Utilidad Neta</p>
                    </div>
                    <p class="text-[20px] font-bold tabular-nums {{ $utilidadNeta < 0 ? 'text-[var(--color-danger)]' : 'text-[var(--text-900)]' }}">
                        {{ $utilidadNeta < 0 ? '-$' : '$' }}{{ number_format(abs($utilidadNeta), 0, ',', '.') }}
                    </p>
                    @if($margen !== null)
                    <p class="text-[11px] text-[var(--text-400)] mt-0.5">Margen neto {{ number_format($margen, 1) }}%</p>
                    @endif
                </div>
            </div>
        </div>

        @include('financial._statement-report-body', ['isEsf' => false, 'statementReport' => $eriReport, 'periodLabels' => $eriPeriodLabels])

    </div>
</div>
