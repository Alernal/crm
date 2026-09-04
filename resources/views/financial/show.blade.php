<x-app-layout>
<x-slot name="title">{{ $budget->name }}</x-slot>

@php
    $typeLabels  = \App\Models\Budget::TYPES;
    $statusLabels= \App\Models\Budget::STATUS_LABELS;
    $driverLabels= \App\Models\Budget::DRIVERS;
    $statusVariant = ['draft' => 'neutral', 'final' => 'success'];
    $sl = $statusLabels[$budget->status] ?? $statusLabels['draft'];
    $sv = $statusVariant[$budget->status] ?? 'neutral';
    $allPeriods  = range(0, $budget->periods_count);
    $isDraft     = $budget->status === 'draft';
    $isStatement = in_array($budget->type, \App\Models\Budget::ESTADO_FINANCIERO_TYPES, true);
    $backClientRoute = $isStatement ? 'financial.statements.client' : 'financial.client';
@endphp

{{-- Envoltura raíz: comparte isPrinting/printUrl/ratiosModal entre
     la barra de acciones, la tabla (con su propio x-data anidado budgetTable) y los modales --}}
<div x-data="{ isPrinting: false, printUrl: '', ratiosModal: {{ session('open_ratios_modal') ? 'true' : 'false' }} }">

{{-- Volver --}}
<a href="{{ route($backClientRoute, $budget->client) }}"
   class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)] mb-3">
    <x-lucide-arrow-left class="w-4 h-4" />
    Volver
</a>

{{-- Barra de acciones --}}
<div class="flex items-center justify-end gap-4 mb-6 flex-wrap">
    <div class="flex items-center gap-2 flex-wrap">
        @if($budget->type === 'flujo_caja')
        <a href="{{ route('financial.dashboard', $budget) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-bar-chart-2 class="w-3.5 h-3.5" />
            Ver dashboard
        </a>
        @endif

        <button @click="printUrl = '{{ route('financial.print', $budget) }}'; isPrinting = true"
                class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-printer class="w-3.5 h-3.5" />
            Imprimir
        </button>

        <a href="{{ route('financial.pdf', $budget) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-download class="w-3.5 h-3.5" />
            PDF
        </a>

        @unless($isStatement)
        @if(!$data)
        <a href="{{ route('financial.data', $budget->client) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--color-warning-bg)] border border-[#FCD34D] text-[var(--color-warning-text)] text-[13px] font-medium hover:opacity-90">
            <x-lucide-alert-triangle class="w-3.5 h-3.5" />
            Datos
        </a>
        @else
        <a href="{{ route('financial.data', $budget->client) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-database class="w-3.5 h-3.5" />
            Datos
        </a>
        @endif
        @endunless

        @if($isStatement && !empty($financialRatios))
        <button @click="ratiosModal = true"
                class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-activity class="w-3.5 h-3.5" />
            Indicadores financieros
        </button>
        @endif

        @unless($isStatement)
        @if($budget->status !== 'final')
        <form method="POST" action="{{ route('financial.approve', $budget) }}"
              x-data=""
              x-on:submit.prevent="if(confirm('¿Aprobar «{{ addslashes($budget->name) }}»? Márcalo como aprobado una vez se haya socializado y esté listo para seguimiento.')) $el.submit()">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--color-success)] hover:opacity-90 text-white text-[13px] font-medium">
                <x-lucide-check-circle class="w-3.5 h-3.5" />
                Aprobar
            </button>
        </form>
        @endif
        @endunless

        <a href="{{ route('financial.edit', $budget) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-edit-2 class="w-3.5 h-3.5" />
            Editar
        </a>

        <form method="POST" action="{{ route('financial.destroy', $budget) }}"
              x-data=""
              x-on:submit.prevent="if(confirm('¿Eliminar «{{ addslashes($budget->name) }}»? Esta acción no se puede deshacer.')) $el.submit()">
            @csrf @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] border bg-[var(--color-danger-bg)] border-[var(--color-danger)] text-[var(--color-danger)] text-[13px] font-medium hover:bg-[var(--color-danger)] hover:text-white">
                <x-lucide-trash-2 class="w-3.5 h-3.5" />
                Eliminar
            </button>
        </form>
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

{{-- Flash --}}
@if(session('success'))
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="mb-5 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

{{-- Encabezado del reporte --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-t-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
    <div class="px-6 py-5 flex items-center justify-between gap-4 border-b border-[var(--border-default)]">
        <div>
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] mb-0.5">
                {{ $typeLabels[$budget->type] ?? '' }} · {{ $budget->client->name }}
            </p>
            <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $budget->name }}</p>
        </div>
        <div class="flex items-center gap-3 flex-shrink-0 text-right">
            <div class="text-right">
                <p class="text-[11px] text-[var(--text-400)] uppercase tracking-[0.06em]">Estado</p>
                <x-status-badge :variant="$sv">{{ $sl['label'] }}</x-status-badge>
            </div>
            @unless($isStatement)
            <div class="w-px h-8 bg-[var(--border-default)]"></div>
            <div class="text-right">
                <p class="text-[11px] text-[var(--text-400)] uppercase tracking-[0.06em]">Período base</p>
                <p class="text-[14px] font-semibold text-[var(--text-700)] tabular-nums">{{ $budget->base_year }}</p>
            </div>
            @endunless
            <div class="w-px h-8 bg-[var(--border-default)]"></div>
            <div class="text-right">
                <p class="text-[11px] text-[var(--text-400)] uppercase tracking-[0.06em]">Períodos</p>
                <p class="text-[14px] font-semibold text-[var(--text-700)] tabular-nums">{{ $budget->periods_count }}</p>
            </div>
        </div>
    </div>
    @if($budget->notes)
    <div class="px-6 py-2.5 bg-[var(--surface-subtle)] text-[13px] text-[var(--text-500)] italic border-b border-[var(--border-default)]">
        <span class="font-medium not-italic text-[var(--text-700)]">Notas:</span> {{ $budget->notes }}
    </div>
    @endif
    @if(in_array($budget->type, ['esf', 'eri'], true) && $budget->linkedCounterpart)
    <div class="px-6 py-2.5 bg-[var(--surface-subtle)] text-[13px] text-[var(--text-500)] border-b border-[var(--border-default)] flex items-center gap-1.5">
        <x-lucide-link class="w-3.5 h-3.5 flex-shrink-0" />
        Vinculado con
        <a href="{{ route('financial.show', $budget->linkedCounterpart) }}" class="font-medium text-[var(--color-primary)] hover:underline">{{ $budget->linkedCounterpart->name }}</a>
    </div>
    @endif
</div>

@php
    $isCashFlow = $budget->type === 'flujo_caja' && $cashFlowReport !== null;
    $isEsf      = $budget->type === 'esf' && $esfReport !== null;
    $isEri      = $budget->type === 'eri' && $eriReport !== null;
@endphp

@if($isCashFlow)
@php
    $rows              = $cashFlowReport['rows'];
    $chartLabels       = $cashFlowReport['chartLabels'];
    $chartPpto         = $cashFlowReport['chartPpto'];
    $chartReal         = $cashFlowReport['chartReal'];
    $chartCumplimiento = $cashFlowReport['chartCumplimiento'];
@endphp

<div class="bg-[var(--surface-card)] border border-t-0 border-[var(--border-default)] rounded-b-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden mb-5"
     x-data="budgetTable('{{ route('financial.update_value', $budget) }}', '{{ csrf_token() }}', true, '{{ route('financial.value_entries.index', $budget) }}')">

    {{-- Scroll horizontal superior, sincronizado con el de la tabla — para no
         tener que bajar hasta el final de la tabla solo para desplazarse a los
         últimos períodos --}}
    <div class="overflow-x-auto" x-ref="topScroll" @scroll="$refs.bottomScroll.scrollLeft = $refs.topScroll.scrollLeft">
        <div :style="{ height: '1px', width: tableScrollWidth + 'px' }"></div>
    </div>

    <div class="overflow-x-auto" x-ref="bottomScroll" @scroll="$refs.topScroll.scrollLeft = $refs.bottomScroll.scrollLeft">
        <table class="w-full min-w-[900px] border-separate" style="border-spacing:0">
            <thead>
                <tr class="bg-[var(--surface-subtle)]">
                    <th rowspan="2" class="sticky left-0 z-[3] px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.06em] text-[var(--text-400)] align-bottom border-r border-[var(--border-strong)] shadow-[1px_0_0_0_var(--border-strong)]" style="width:280px; background-color: var(--surface-subtle)">
                        Concepto
                    </th>
                    @foreach($periodLabels as $idx => $label)
                    <th colspan="3" class="px-3 py-2 text-center text-[12px] font-semibold text-[var(--text-700)] border-b border-l border-[var(--border-default)]">
                        {{ $label }}
                        @if($idx === 0)
                        <span class="block text-[10px] font-normal text-[var(--text-400)] normal-case tracking-normal">Período base</span>
                        @endif
                    </th>
                    @endforeach
                </tr>
                <tr class="bg-[var(--surface-subtle)] border-b border-[var(--border-default)]">
                    @foreach($periodLabels as $idx => $label)
                    <th class="px-2 py-1.5 text-[10px] font-semibold text-[var(--text-400)] uppercase tracking-[0.04em] text-right border-l border-[var(--border-default)]">Ppto</th>
                    <th class="px-2 py-1.5 text-[10px] font-semibold text-[var(--text-400)] uppercase tracking-[0.04em] text-right">Real</th>
                    <th class="px-2 py-1.5 text-[10px] font-semibold text-[var(--text-400)] uppercase tracking-[0.04em] text-right">Var%</th>
                    @endforeach
                </tr>
            </thead>
            <tbody x-ref="tbody">
                @include('financial._cashflow-body', ['rows' => $rows, 'periodLabels' => $periodLabels])
            </tbody>
        </table>
    </div>

    {{-- Pie: instrucción discreta --}}
    <div class="px-4 py-2.5 border-t border-[var(--border-default)] flex items-center gap-2 text-[12px] text-[var(--text-400)]">
        <x-lucide-edit-2 class="w-3.5 h-3.5 flex-shrink-0" />
        Doble clic en una celda <strong class="text-[var(--text-500)] mx-0.5">Ppto</strong> para editarla, o en <strong class="text-[var(--color-success-text)] mx-0.5">Real</strong> para registrar los movimientos que la componen. Los saldos y la Var% se recalculan automáticamente.
    </div>

    @include('financial._value-entries-modal')
</div>


@elseif($isEsf || $isEri)
@php
    $statementReport = $isEsf ? $esfReport : $eriReport;
    $periodLabels    = $statementReport['periodLabels'];
    $lastIdx         = array_key_last($periodLabels);
    $lastLabel       = $periodLabels[$lastIdx];

    if ($isEsf) {
        $kpiBalanced = abs($statementReport['diferencia'][$lastIdx] ?? 0) < 1;
        $kpis = [
            ['icon' => 'wallet',   'label' => 'Total Activo',     'value' => $statementReport['totalActivo'][$lastIdx] ?? 0],
            ['icon' => 'scale',    'label' => 'Total Pasivo',     'value' => $statementReport['totalPasivo'][$lastIdx] ?? 0],
            ['icon' => 'landmark', 'label' => 'Total Patrimonio', 'value' => $statementReport['totalPatrimonio'][$lastIdx] ?? 0],
        ];
    } else {
        $ventasNetas   = $statementReport['ventasNetas'][$lastIdx] ?? 0;
        $utilidadNeta  = $statementReport['utilidadNeta'][$lastIdx] ?? 0;
        $margen        = $ventasNetas != 0 ? ($utilidadNeta / $ventasNetas) * 100 : null;
        $kpis = [
            ['icon' => 'trending-up',    'label' => 'Ventas Netas',   'value' => $ventasNetas],
            ['icon' => 'package',        'label' => 'Utilidad Bruta', 'value' => $statementReport['utilidadBruta'][$lastIdx] ?? 0],
            ['icon' => 'activity',       'label' => 'EBITDA',         'value' => $statementReport['ebitda'][$lastIdx] ?? 0],
        ];
    }
@endphp

{{-- Franja de KPIs del estado financiero (último período registrado) --}}
<div class="mb-5">
    <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] mb-2.5">Resumen · {{ $lastLabel }}</p>
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach($kpis as $k)
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

        @if($isEsf)
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-[var(--radius-control)] bg-[var(--color-primary-light)] flex items-center justify-center flex-shrink-0">
                    <x-lucide-check-circle class="w-4 h-4 text-[var(--color-primary)]" />
                </div>
                <p class="text-[12px] font-medium text-[var(--text-500)]">Estado del balance</p>
            </div>
            <x-status-badge :variant="$kpiBalanced ? 'success' : 'danger'">{{ $kpiBalanced ? 'Cuadra' : 'Descuadre' }}</x-status-badge>
        </div>
        @else
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
        @endif
    </div>
</div>

<div x-data="statementTable('{{ route('financial.update_value', $budget) }}', '{{ csrf_token() }}')">
    <div x-ref="body">
        @include('financial._statement-report-body', ['isEsf' => $isEsf, 'statementReport' => $statementReport, 'periodLabels' => $periodLabels])
    </div>
</div>

@else
{{-- Tabla principal (presupuestos genéricos: ventas, gastos, compras, nómina) --}}
<div class="bg-[var(--surface-card)] border border-t-0 border-[var(--border-default)] rounded-b-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden mb-5"
     x-data="budgetTable('{{ route('financial.update_value', $budget) }}', '{{ csrf_token() }}')">

    {{-- Scroll horizontal superior, sincronizado con el de la tabla --}}
    <div class="overflow-x-auto" x-ref="topScroll" @scroll="$refs.bottomScroll.scrollLeft = $refs.topScroll.scrollLeft">
        <div :style="{ height: '1px', width: tableScrollWidth + 'px' }"></div>
    </div>

    <div class="overflow-x-auto" x-ref="bottomScroll" @scroll="$refs.topScroll.scrollLeft = $refs.bottomScroll.scrollLeft">
        <table class="w-full min-w-[680px] border-separate" style="border-spacing:0">

            {{-- Cabecera de columnas --}}
            <thead>
                <tr class="bg-[var(--surface-subtle)] border-b border-[var(--border-default)]">
                    <th class="sticky left-0 z-[3] px-4 py-3.5 text-left text-[11px] font-semibold uppercase tracking-[0.06em] text-[var(--text-400)] border-r border-[var(--border-strong)] shadow-[1px_0_0_0_var(--border-strong)]" style="width:280px; background-color: var(--surface-subtle)">
                        Concepto
                    </th>
                    @foreach($periodLabels as $idx => $label)
                    <th class="px-4 py-3.5 text-right text-[11px] font-semibold uppercase tracking-[0.06em] min-w-[130px]
                               {{ $idx === 0 ? 'text-[var(--text-900)]' : 'text-[var(--text-400)]' }}">
                        {{ $label }}
                        @if($idx === 0)
                        <span class="block text-[10px] font-normal text-[var(--text-400)] mt-0.5 normal-case tracking-normal">Período base</span>
                        @endif
                    </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
            @php $grandTotals = array_fill(0, count($periodLabels), 0); @endphp
            @foreach($budget->sections as $section)

                {{-- Cabecera de sección: celda propia (no colspan) para que el título
                     pueda ser "sticky" de verdad, igual que la columna Concepto --}}
                <tr>
                    <td class="sticky left-0 z-[1] px-4 pt-5 pb-1.5 bg-[var(--surface-card)]">
                        <span class="text-[11px] font-semibold text-[var(--text-400)] uppercase tracking-[0.06em] whitespace-nowrap">
                            {{ $section->name }}
                        </span>
                    </td>
                    <td colspan="{{ count($periodLabels) }}" class="pt-5 pb-1.5 bg-[var(--surface-card)]"></td>
                </tr>

                @php $sectionTotals = array_fill(0, count($periodLabels), 0); @endphp

                @foreach($section->lines as $line)
                @php
                    $driverLabel = $driverLabels[$line->projection_driver] ?? $line->projection_driver;
                    if ($line->projection_driver === 'custom_pct' && $line->custom_rate !== null) {
                        $driverLabel = number_format($line->custom_rate, 1) . '%';
                    }
                @endphp
                <tr class="group border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">
                    <td class="sticky left-0 z-[1] px-4 py-2.5 text-[14px] text-[var(--text-700)] bg-[var(--surface-card)] group-hover:bg-[var(--surface-subtle)] border-r border-[var(--border-strong)] shadow-[1px_0_0_0_var(--border-strong)]">
                        <div class="border-l-2 border-[var(--border-strong)] pl-3 truncate" style="max-width:260px" title="{{ $line->name }}">
                            {{ $line->name }}
                        </div>
                    </td>
                    @foreach($periodLabels as $idx => $label)
                    @php
                        $val = $line->getValueForPeriod($idx);
                        $sectionTotals[$idx] += ($line->sign_negative ? -$val : $val);
                        $isBase      = ($idx === 0);
                        $isProjected = ($idx > 0) && !$isDraft;
                        $pctChange   = ($idx > 0 && $line->getValueForPeriod($idx - 1) != 0)
                            ? (($val - $line->getValueForPeriod($idx - 1)) / abs($line->getValueForPeriod($idx - 1))) * 100
                            : null;
                    @endphp
                    <td class="px-4 py-2.5 text-right {{ $isBase ? '' : 'bg-[var(--surface-subtle)]' }}">
                        <div x-data="{ editing: false, val: {{ $val }} }"
                             @dblclick="editing = true"
                             @click.outside="if(editing){ editing=false; saveValue({{ $line->id }}, {{ $idx }}, val) }">
                            <template x-if="!editing">
                                <div>
                                    <span class="tabular-nums text-[14px]
                                                 {{ $line->sign_negative ? ($isBase ? 'font-semibold text-[var(--color-danger)]' : 'font-medium text-[var(--color-danger)]') : ($isBase ? 'font-semibold text-[var(--text-900)]' : ($isProjected ? 'font-medium text-[var(--color-primary)]' : 'text-[var(--border-strong)]')) }}"
                                          x-text="formatSignedCOP(val, {{ $line->sign_negative ? 'true' : 'false' }})">
                                    </span>
                                    @if($idx > 0 && $pctChange !== null && !$isDraft)
                                    <x-status-badge :variant="$pctChange >= 0 ? 'success' : 'danger'" class="mt-1 !text-[10px] !px-[6px] !py-0">
                                        {{ $pctChange >= 0 ? '+' : '' }}{{ number_format($pctChange, 1) }}%
                                    </x-status-badge>
                                    @endif
                                </div>
                            </template>
                            <template x-if="editing">
                                <input type="text"
                                       x-money="val"
                                       @keydown.enter="editing=false; saveValue({{ $line->id }}, {{ $idx }}, val)"
                                       @keydown.escape="editing=false"
                                       x-init="$nextTick(() => $el.focus())"
                                       class="w-28 text-right border border-[var(--color-primary)] rounded-[var(--radius-control)] px-2 py-1 text-[14px] outline-none ring-2 ring-[var(--color-primary-light)] bg-[var(--surface-card)]"
                                       placeholder="0"/>
                            </template>
                        </div>
                    </td>
                    @endforeach
                </tr>
                @endforeach

                {{-- Total de sección --}}
                <tr class="bg-[var(--surface-subtle)] border-b border-[var(--border-default)]">
                    <td class="sticky left-0 z-[1] px-4 py-3 bg-[var(--surface-subtle)] border-r border-[var(--border-strong)] shadow-[1px_0_0_0_var(--border-strong)]">
                        <span class="text-[11px] font-semibold text-[var(--text-700)] uppercase tracking-[0.06em]">
                            Total {{ $section->name }}
                        </span>
                    </td>
                    @foreach($periodLabels as $idx => $label)
                    @php $grandTotals[$idx] += $sectionTotals[$idx]; @endphp
                    <td class="px-4 py-3 text-right">
                        <span class="text-[15px] font-bold tabular-nums {{ $sectionTotals[$idx] < 0 ? 'text-[var(--color-danger)]' : ($idx === 0 ? 'text-[var(--text-900)]' : 'text-[var(--color-primary-dark)]') }}">
                            {{ $sectionTotals[$idx] < 0 ? '-' : '' }}${{ number_format(abs($sectionTotals[$idx]), 0, ',', '.') }}
                        </span>
                    </td>
                    @endforeach
                </tr>

            @endforeach

                {{-- Total General --}}
                <tr class="bg-[var(--color-primary-light)]/30 border-t-2 border-[var(--border-strong)]">
                    <td class="px-4 py-3.5">
                        <span class="text-[12px] font-bold text-[var(--text-900)] uppercase tracking-[0.06em]">
                            Total General
                        </span>
                    </td>
                    @foreach($periodLabels as $idx => $label)
                    <td class="px-4 py-3.5 text-right">
                        <span class="text-[16px] font-bold tabular-nums {{ $grandTotals[$idx] < 0 ? 'text-[var(--color-danger)]' : 'text-[var(--text-900)]' }}">
                            {{ $grandTotals[$idx] < 0 ? '-' : '' }}${{ number_format(abs($grandTotals[$idx]), 0, ',', '.') }}
                        </span>
                    </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Pie: instrucción discreta --}}
    <div class="px-4 py-2.5 border-t border-[var(--border-default)] flex items-center gap-2 text-[12px] text-[var(--text-400)]">
        <x-lucide-edit-2 class="w-3.5 h-3.5 flex-shrink-0" />
        Los períodos se proyectan automáticamente a partir de los valores base. Doble clic en una celda para editarla manualmente.
    </div>
</div>
@endif

<script>
function budgetTable(updateUrl, csrfToken, reloadOnSave, entriesUrl) {
    return {
        tableScrollWidth: 0,
        init() {
            const measure = () => { this.tableScrollWidth = this.$refs.bottomScroll ? this.$refs.bottomScroll.scrollWidth : 0; };
            this.$nextTick(measure);
            window.addEventListener('resize', measure);
        },

        // ── Trazabilidad de "Real": modal con movimientos (fecha/tercero/
        // descripción/valor) por línea+período. La suma de los movimientos
        // reemplaza el valor agregado de la celda al guardar.
        entriesModal: {
            open: false,
            lineId: null,
            periodIndex: null,
            loading: false,
            saving: false,
            entries: [],
            draftDate: '',
            draftTercero: '',
            draftDescription: '',
            draftValueDisplay: '',
        },
        openEntriesModal(lineId, periodIndex) {
            this.entriesModal.open = true;
            this.entriesModal.lineId = lineId;
            this.entriesModal.periodIndex = periodIndex;
            this.entriesModal.entries = [];
            this.entriesModal.loading = true;
            this.entriesModal.draftDate = new Date().toISOString().slice(0, 10);
            this.entriesModal.draftTercero = '';
            this.entriesModal.draftDescription = '';
            this.entriesModal.draftValueDisplay = '';

            fetch(`${entriesUrl}?line_id=${lineId}&period_index=${periodIndex}`, {
                headers: { Accept: 'application/json' },
            }).then(res => res.json()).then(data => {
                this.entriesModal.entries = (data.entries || []).map(e => ({
                    entry_date: e.entry_date,
                    tercero: e.tercero || '',
                    description: e.description || '',
                    value: parseFloat(e.value) || 0,
                }));
            }).catch(() => {}).finally(() => { this.entriesModal.loading = false; });
        },
        addEntryDraft() {
            const value = this.parseGridNumber(this.entriesModal.draftValueDisplay);
            if (!this.entriesModal.draftDate || !value) return;
            this.entriesModal.entries.push({
                entry_date: this.entriesModal.draftDate,
                tercero: this.entriesModal.draftTercero,
                description: this.entriesModal.draftDescription,
                value,
            });
            this.entriesModal.draftTercero = '';
            this.entriesModal.draftDescription = '';
            this.entriesModal.draftValueDisplay = '';
        },
        entriesModalTotal() {
            return this.entriesModal.entries.reduce((sum, e) => sum + (parseFloat(e.value) || 0), 0);
        },
        saveEntriesModal() {
            this.entriesModal.saving = true;
            fetch(entriesUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                body: JSON.stringify({
                    line_id: this.entriesModal.lineId,
                    period_index: this.entriesModal.periodIndex,
                    entries: this.entriesModal.entries,
                }),
            }).then(res => res.json()).then(data => {
                if (data.html && this.$refs.tbody) {
                    this.$refs.tbody.innerHTML = data.html;
                    window.Alpine.initTree(this.$refs.tbody);
                }
                this.entriesModal.open = false;
            }).catch(() => {}).finally(() => { this.entriesModal.saving = false; });
        },
        saveValue(lineId, periodIndex, value, valueType) {
            fetch(updateUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                body: JSON.stringify({ line_id: lineId, period_index: periodIndex, value: value, value_type: valueType || 'budgeted' }),
            }).then(res => res.json()).then(data => {
                // Presupuestos de flujo de caja: el servidor devuelve el <tbody> ya
                // recalculado (saldos/Var%) y lo reemplazamos en el sitio — así no
                // hay que recargar toda la página ni se pierde la posición del scroll.
                if (data.html && this.$refs.tbody) {
                    this.$refs.tbody.innerHTML = data.html;
                    window.Alpine.initTree(this.$refs.tbody);
                } else if (reloadOnSave) {
                    window.location.reload();
                }
            }).catch(() => {});
        },
        // Formato "1.200.000" con separador de miles mientras se escribe —
        // conserva la posición del cursor contando dígitos desde el inicio en
        // vez de reformatear a ciegas (que movería el cursor al final).
        formatGridNumber(val) {
            const num = parseFloat(val) || 0;
            if (num === 0) return '';
            return num.toLocaleString('es-CO', { maximumFractionDigits: 2 });
        },
        parseGridNumber(str) {
            if (!str && str !== 0) return 0;
            return parseFloat(String(str).replace(/\s/g, '').replace(/\./g, '').replace(',', '.')) || 0;
        },
        reformatGridInput(event) {
            const oldVal = event.target.value;
            const oldPos = event.target.selectionStart ?? oldVal.length;
            const digitsBeforeCursor = oldVal.slice(0, oldPos).replace(/[^\d]/g, '').length;
            const formatted = this.formatGridNumber(this.parseGridNumber(oldVal));

            this.$nextTick(() => {
                let digitsSeen = 0, newPos = formatted.length;
                for (let i = 0; i < formatted.length; i++) {
                    if (/\d/.test(formatted[i])) digitsSeen++;
                    if (digitsSeen === digitsBeforeCursor) { newPos = i + 1; break; }
                }
                event.target.setSelectionRange(newPos, newPos);
            });

            return formatted;
        },
        saveQuantity(lineId, periodIndex, quantity) {
            fetch(updateUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ line_id: lineId, period_index: periodIndex, quantity: quantity, value_type: 'budgeted' }),
            }).then(res => {
                if (res.ok && reloadOnSave) window.location.reload();
            }).catch(() => {});
        },
        saveUnitPrice(lineId, periodIndex, unitPrice) {
            fetch(updateUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ line_id: lineId, period_index: periodIndex, unit_price: unitPrice, value_type: 'budgeted' }),
            }).then(res => {
                if (res.ok && reloadOnSave) window.location.reload();
            }).catch(() => {});
        },
        formatCOP(val) {
            if (!val && val !== 0) return '—';
            const num = parseFloat(val);
            if (isNaN(num) || num === 0) return '—';
            return '$' + Math.round(num).toLocaleString('es-CO');
        },
        formatSignedCOP(val, forceNegative) {
            if (!val && val !== 0) return '—';
            let num = parseFloat(val);
            if (isNaN(num) || num === 0) return '—';
            if (forceNegative) num = -Math.abs(num);
            const sign = num < 0 ? '-' : '';
            return sign + '$' + Math.round(Math.abs(num)).toLocaleString('es-CO');
        },
    };
}

// Estados Financieros (ESF/ERI): guarda la celda vía AJAX y reemplaza solo el
// contenido de la tabla con el HTML recalculado (subtotales + vínculos
// automáticos con la contraparte) que devuelve el servidor — nunca recarga
// la página completa, así el usuario no pierde el scroll ni el punto donde
// estaba editando.
function statementTable(updateUrl, csrfToken) {
    return {
        saving: false,
        formatSignedCOP(val, forceNegative) {
            if (!val && val !== 0) return '—';
            let num = parseFloat(val);
            if (isNaN(num) || num === 0) return '—';
            if (forceNegative) num = -Math.abs(num);
            const sign = num < 0 ? '-' : '';
            return sign + '$' + Math.round(Math.abs(num)).toLocaleString('es-CO');
        },
        saveValue(lineId, periodIndex, value) {
            this.saving = true;
            fetch(updateUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ line_id: lineId, period_index: periodIndex, value: value, value_type: 'budgeted' }),
            })
                .then(res => res.json())
                .then(data => {
                    if (data.html) {
                        this.$refs.body.innerHTML = data.html;
                        window.Alpine.initTree(this.$refs.body);
                    }
                })
                .catch(() => {})
                .finally(() => { this.saving = false; });
        },
    };
}
</script>


@if($isStatement && !empty($financialRatios))
{{-- ══ MODAL: Indicadores financieros ══════════════════════════════════ --}}
@php
    $ratioRows = [
        ['key' => 'liquidez',      'icon' => 'droplets'],
        ['key' => 'endeudamiento', 'icon' => 'landmark'],
        ['key' => 'cobertura',     'icon' => 'shield'],
        ['key' => 'roe',           'icon' => 'trending-up'],
        ['key' => 'roa',           'icon' => 'trending-up'],
        ['key' => 'kt',            'icon' => 'wallet'],
    ];
    $firstRow = reset($financialRatios);
@endphp
<div x-show="ratiosModal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="display:none"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="absolute inset-0 bg-gray-900/50" @click="ratiosModal = false"></div>

    <div class="relative bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-4xl z-10 max-h-[90vh] overflow-y-auto"
         @click.stop>

        <div class="flex items-center justify-between px-6 py-5 border-b border-[var(--border-default)] sticky top-0 bg-[var(--surface-card)]">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-[var(--color-primary-light)] rounded-[var(--radius-control)] flex items-center justify-center">
                    <x-lucide-activity class="w-5 h-5 text-[var(--color-primary)]" />
                </div>
                <div>
                    <h2 class="text-[16px] font-bold text-[var(--text-900)]">Indicadores financieros</h2>
                    <p class="text-[12px] text-[var(--text-400)] mt-0.5">Calculados en vivo a partir del ESF y ERI vinculados — no se guardan, cambian al editar las cifras</p>
                </div>
            </div>
            <button @click="ratiosModal = false"
                    class="p-1.5 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                <x-lucide-x class="w-4 h-4" />
            </button>
        </div>

        <div class="px-6 py-5 space-y-5">

            <div class="overflow-x-auto border border-[var(--border-default)] rounded-[var(--radius-control)]">
                <table class="w-full text-[13px] border-collapse">
                    <thead>
                        <tr class="bg-[var(--surface-subtle)]">
                            <th class="text-left px-3 py-2.5 text-[11px] font-semibold uppercase tracking-[0.04em] text-[var(--text-400)]" style="width:220px">Indicador</th>
                            <th class="text-right px-3 py-2.5 text-[11px] font-semibold uppercase tracking-[0.04em] text-[var(--text-400)]">Óptimo</th>
                            @foreach($financialRatios as $r)
                            <th class="text-right px-3 py-2.5 text-[11px] font-semibold uppercase tracking-[0.04em] text-[var(--text-400)] min-w-[110px]">{{ $r['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ratioRows as $rr)
                        @php $first = $firstRow[$rr['key']]; @endphp
                        <tr class="border-t border-[var(--surface-muted)]">
                            <td class="px-3 py-2.5 font-medium text-[var(--text-700)] flex items-center gap-1.5">
                                @svg('lucide-' . $rr['icon'], 'w-3.5 h-3.5 text-[var(--text-400)] flex-shrink-0')
                                {{ $first['label'] }}
                            </td>
                            <td class="px-3 py-2.5 text-right text-[var(--text-400)] tabular-nums">
                                {{ $first['suffix'] === '%' ? number_format($first['target'] * 100, 1) . '%' : ($first['suffix'] === '$' ? '$' . number_format($first['target'], 0, ',', '.') : number_format($first['target'], 2) . $first['suffix']) }}
                            </td>
                            @foreach($financialRatios as $r)
                            @php $ind = $r[$rr['key']]; @endphp
                            <td class="px-3 py-2.5 text-right tabular-nums">
                                @if($ind['value'] === null)
                                <span class="text-[var(--text-400)]">—</span>
                                @else
                                <div class="flex items-center justify-end gap-1.5">
                                    <span class="font-semibold text-[var(--text-900)]">
                                        {{ $ind['suffix'] === '%' ? number_format($ind['value'] * 100, 1) . '%' : ($ind['suffix'] === '$' ? '$' . number_format($ind['value'], 0, ',', '.') : number_format($ind['value'], 2) . $ind['suffix']) }}
                                    </span>
                                    <x-status-badge :variant="$ind['ok'] ? 'success' : 'danger'">{{ $ind['ok'] ? 'Cumple' : 'No cumple' }}</x-status-badge>
                                </div>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Editar criterios --}}
            <div class="border-t border-[var(--border-default)] pt-4">
                <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] mb-2.5">Editar criterios (niveles óptimos)</p>
                <form method="POST" action="{{ route('financial.ratio_targets.update', $budget->client) }}" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 items-end">
                    @csrf @method('PATCH')
                    <input type="hidden" name="redirect_budget_id" value="{{ $budget->id }}"/>
                    <div>
                        <label class="block text-[11px] font-medium text-[var(--text-700)] mb-1">Liquidez ≥</label>
                        <input type="number" step="0.01" min="0" name="ratio_liquidity_target" value="{{ $data->ratio_liquidity_target ?? 2 }}"
                               class="w-full h-9 border border-[var(--border-default)] rounded-[var(--radius-control)] px-2.5 text-[13px] tabular-nums outline-none focus:ring-2 focus:ring-[var(--color-primary-light)]"/>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-[var(--text-700)] mb-1">Endeudamiento &lt;</label>
                        <input type="number" step="0.01" min="0" max="1" name="ratio_debt_target" value="{{ $data->ratio_debt_target ?? 0.40 }}"
                               class="w-full h-9 border border-[var(--border-default)] rounded-[var(--radius-control)] px-2.5 text-[13px] tabular-nums outline-none focus:ring-2 focus:ring-[var(--color-primary-light)]"/>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-[var(--text-700)] mb-1">Cobertura ≥</label>
                        <input type="number" step="0.01" min="0" name="ratio_interest_coverage_target" value="{{ $data->ratio_interest_coverage_target ?? 14 }}"
                               class="w-full h-9 border border-[var(--border-default)] rounded-[var(--radius-control)] px-2.5 text-[13px] tabular-nums outline-none focus:ring-2 focus:ring-[var(--color-primary-light)]"/>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-[var(--text-700)] mb-1">ROE ≥</label>
                        <input type="number" step="0.01" min="0" max="1" name="ratio_roe_target" value="{{ $data->ratio_roe_target ?? 0.14 }}"
                               class="w-full h-9 border border-[var(--border-default)] rounded-[var(--radius-control)] px-2.5 text-[13px] tabular-nums outline-none focus:ring-2 focus:ring-[var(--color-primary-light)]"/>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-[var(--text-700)] mb-1">ROA ≥</label>
                        <input type="number" step="0.01" min="0" max="1" name="ratio_roa_target" value="{{ $data->ratio_roa_target ?? 0.14 }}"
                               class="w-full h-9 border border-[var(--border-default)] rounded-[var(--radius-control)] px-2.5 text-[13px] tabular-nums outline-none focus:ring-2 focus:ring-[var(--color-primary-light)]"/>
                    </div>
                    <div class="flex gap-2" x-data="{ workingCapitalTarget: {{ $data->ratio_working_capital_target ?? 0 }} }">
                        <div class="flex-1">
                            <label class="block text-[11px] font-medium text-[var(--text-700)] mb-1">Capital trab. ≥</label>
                            <input type="text" name="ratio_working_capital_target" x-money="workingCapitalTarget"
                                   class="w-full h-9 border border-[var(--border-default)] rounded-[var(--radius-control)] px-2.5 text-[13px] tabular-nums outline-none focus:ring-2 focus:ring-[var(--color-primary-light)]"/>
                        </div>
                        <button type="submit" class="h-9 px-3 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[12px] font-medium flex-shrink-0">
                            <x-lucide-check class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
@endif

</div>

</x-app-layout>
