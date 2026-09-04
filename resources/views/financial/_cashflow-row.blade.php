@php
    $type = $row['type'];
    $isSectionHeader = $type === 'section';
    $isTotal   = $type === 'total';
    $isFinal   = $type === 'final';
    $isHighlight = in_array($type, ['highlight', 'final'], true);
    $editable  = $row['editable'] ?? false;
    $editableZeroOnly = $row['editable_zero_only'] ?? false;
    $isOutflow = $row['is_outflow'] ?? false;
    $conceptTdBg = $isFinal
        ? 'bg-[var(--color-primary-light)]'
        : (($isTotal || $isHighlight) ? 'bg-[var(--surface-subtle)]' : 'bg-[var(--surface-card)] group-hover:bg-[var(--surface-subtle)]');
@endphp

@if($isSectionHeader)
<tr>
    {{-- Celda propia (no colspan) para el título — así puede ser "sticky"
         de verdad, igual que la columna Concepto del resto de las filas.
         Una segunda celda colspan solo continúa el fondo detrás de los
         períodos, sin contenido. --}}
    <td class="sticky left-0 z-[1] px-4 pt-5 pb-1.5 bg-[var(--surface-card)]">
        <span class="text-[11px] font-semibold text-[var(--text-400)] uppercase tracking-[0.06em] whitespace-nowrap">{{ $row['label'] }}</span>
    </td>
    <td colspan="{{ count($periodLabels) * 3 }}" class="pt-5 pb-1.5 bg-[var(--surface-card)]"></td>
</tr>
@else
<tr class="group {{ $isTotal || $isHighlight ? 'bg-[var(--surface-subtle)] border-b border-[var(--border-default)]' : 'border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]' }}">
    <td class="sticky left-0 z-[1] px-4 py-2.5 border-r border-[var(--border-strong)] shadow-[1px_0_0_0_var(--border-strong)] {{ $conceptTdBg }}">
        @if($isTotal || $isHighlight)
        <span class="text-[11px] font-semibold {{ $isFinal ? 'text-[var(--color-primary-dark)]' : 'text-[var(--text-700)]' }} uppercase tracking-[0.06em]">{{ $row['label'] }}</span>
        @else
        <div class="border-l-2 border-[var(--border-strong)] pl-3 text-[14px] text-[var(--text-700)] truncate" style="max-width:260px" title="{{ $row['label'] }}">{{ $row['label'] }}</div>
        @endif
    </td>

    @foreach($periodLabels as $idx => $label)
    @php
        $ppto = $row['values'][$idx]['ppto'] ?? 0.0;
        $real = $row['values'][$idx]['real'] ?? 0.0;
        $var  = $ppto != 0.0 ? (($real - $ppto) / abs($ppto)) * 100 : null;
        $canEditThisPeriod = $editable && (!$editableZeroOnly || $idx === 0);
        $moneyFmt = fn ($v) => $v == 0 ? '—' : ($isOutflow ? '-$' . number_format(abs($v), 0, ',', '.') : '$' . number_format($v, 0, ',', '.'));
        $pptoColor = $isOutflow ? 'text-[var(--color-danger)]' : 'text-[var(--text-700)]';
        $realColor = $isOutflow ? 'text-[var(--color-danger)]' : 'text-[var(--color-success-text)]';
    @endphp

    {{-- Ppto --}}
    <td class="px-2 py-2.5 text-right border-l border-[var(--surface-muted)] {{ $isFinal ? 'bg-[var(--color-primary-light)]' : '' }}">
        @if($canEditThisPeriod)
        <div x-data="{ editing: false, valDisplay: '' }"
             @dblclick="editing = true; valDisplay = formatGridNumber({{ $ppto }}); $nextTick(() => $el.querySelector('input')?.select())"
             @click.outside="if(editing){ editing=false; saveValue({{ $row['line_id'] }}, {{ $idx }}, parseGridNumber(valDisplay), 'budgeted') }">
            <template x-if="!editing">
                <span class="tabular-nums text-[13px] {{ $pptoColor }}">{{ $moneyFmt($ppto) }}</span>
            </template>
            <template x-if="editing">
                <input type="text" inputmode="decimal" x-model="valDisplay"
                       @input="valDisplay = reformatGridInput($event)"
                       @keydown.enter.prevent="saveValue({{ $row['line_id'] }}, {{ $idx }}, parseGridNumber(valDisplay), 'budgeted'); editing=false"
                       @keydown.escape="editing=false"
                       x-init="$nextTick(() => $el.focus())"
                       class="w-24 text-right border border-[var(--color-primary)] rounded-[var(--radius-control)] px-1.5 py-0.5 text-[13px] outline-none ring-2 ring-[var(--color-primary-light)] bg-[var(--surface-card)] tabular-nums" />
            </template>
        </div>
        @else
        <span class="tabular-nums text-[13px] {{ $isTotal || $isHighlight ? 'font-bold' : '' }} {{ $isTotal || $isHighlight ? ($isOutflow ? 'text-[var(--color-danger)]' : 'text-[var(--text-900)]') : $pptoColor }}">{{ $moneyFmt($ppto) }}</span>
        @endif
    </td>

    {{-- Real: en vez de editarse directo, abre el modal de movimientos (trazabilidad) --}}
    <td class="px-2 py-2.5 text-right {{ $isFinal ? 'bg-[var(--color-primary-light)]' : '' }}">
        @if($editable)
        <span @dblclick="openEntriesModal({{ $row['line_id'] }}, {{ $idx }})"
              class="tabular-nums text-[13px] {{ $realColor }} cursor-pointer hover:underline decoration-dotted underline-offset-2"
              title="Doble clic para ver/editar los movimientos">{{ $moneyFmt($real) }}</span>
        @else
        <span class="tabular-nums text-[13px] {{ $isTotal || $isHighlight ? 'font-bold' : '' }} {{ $realColor }}">{{ $moneyFmt($real) }}</span>
        @endif
    </td>

    {{-- Var% --}}
    <td class="px-2 py-2.5 text-right {{ $isFinal ? 'bg-[var(--color-primary-light)]' : '' }}">
        @if($var !== null)
        <x-status-badge :variant="$var >= 0 ? 'success' : 'danger'" class="!text-[10px] !px-[6px] !py-0">
            {{ $var >= 0 ? '+' : '' }}{{ number_format($var, 1) }}%
        </x-status-badge>
        @else
        <span class="text-[var(--border-strong)] text-[12px]">—</span>
        @endif
    </td>
    @endforeach
</tr>
@endif
