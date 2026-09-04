@php
    $type = $row['type'];
    $isGroup     = $type === 'group';
    $isSection   = $type === 'section';
    $isTotal     = $type === 'total';
    $isHighlight = $type === 'highlight';
    $isBalance   = $type === 'balance';
    $isData      = $type === 'data';

    $groupIcons = [
        'INGRESOS OPERACIONALES'               => 'trending-up',
        'COSTO DE VENTAS'                       => 'package',
        'GASTOS OPERACIONALES DE ADMINISTRACIÓN' => 'briefcase',
        'GASTOS OPERACIONALES DE VENTAS'         => 'megaphone',
        'INGRESOS NO OPERACIONALES'              => 'landmark',
        'GASTOS NO OPERACIONALES'                 => 'banknote',
        'PROVISIÓN PARA IMPUESTOS'                => 'receipt',
        'OTRO RESULTADO INTEGRAL (ORI)'           => 'layers',
        'Activos'                                 => 'wallet',
        'Pasivos'                                 => 'scale',
        'Patrimonio'                              => 'landmark',
    ];
@endphp

@if($isGroup)
<tr>
    <td colspan="{{ 1 + count($periodLabels) }}" class="px-4 pt-5 pb-1.5 bg-[var(--surface-card)]">
        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-[var(--text-900)] uppercase tracking-[0.06em] whitespace-nowrap">
            @svg('lucide-' . ($groupIcons[$row['label']] ?? 'circle'), 'w-3.5 h-3.5 text-[var(--text-400)] flex-shrink-0')
            {{ $row['label'] }}
        </span>
    </td>
</tr>
@elseif($isSection)
<tr>
    <td colspan="{{ 1 + count($periodLabels) }}" class="px-4 pt-2 pb-1 bg-[var(--surface-card)]">
        <span class="text-[11px] font-semibold text-[var(--text-400)] uppercase tracking-[0.05em] whitespace-nowrap">{{ $row['label'] }}</span>
    </td>
</tr>
@elseif($isBalance)
<tr class="border-t-2 border-[var(--border-strong)]">
    <td class="px-4 py-3">
        <span class="text-[11px] font-bold uppercase tracking-[0.04em] text-[var(--text-700)] whitespace-nowrap">{{ $row['label'] }}</span>
    </td>
    @foreach($periodLabels as $idx => $label)
    @php $v = round($row['values'][$idx] ?? 0, 2); $ok = abs($v) < 1; @endphp
    <td class="px-3 py-3 text-right">
        <x-status-badge :variant="$ok ? 'success' : 'danger'">
            {{ $ok ? 'Cuadra' : ($v < 0 ? '-$' : '+$') . number_format(abs($v), 0, ',', '.') }}
        </x-status-badge>
    </td>
    @endforeach
</tr>
@else
<tr class="{{ $isTotal || $isHighlight ? 'bg-[var(--surface-subtle)] border-b border-[var(--border-default)]' : 'border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]' }}">
    <td class="px-4 {{ $isHighlight ? 'py-3' : 'py-2.5' }} {{ $isHighlight ? 'bg-[var(--color-primary-light)]/30' : '' }}">
        @if($isTotal || $isHighlight)
        <span class="text-[12px] font-bold {{ $isHighlight ? 'text-[var(--color-primary-dark)]' : 'text-[var(--text-700)]' }} uppercase tracking-[0.05em] whitespace-nowrap">{{ $row['label'] }}</span>
        @else
        <div class="border-l-2 border-[var(--border-strong)] pl-3 text-[13px] text-[var(--text-700)] whitespace-nowrap">{{ $row['label'] }}</div>
        @endif
    </td>
    @foreach($periodLabels as $idx => $label)
    @php
        $val     = $row['values'][$idx] ?? 0;
        $signNeg = $row['sign_negative'] ?? false;

        if ($isData) {
            $num = (float) $val;
            if ($num == 0.0) {
                $formattedVal = '—';
            } else {
                if ($signNeg) { $num = -abs($num); }
                $formattedVal = ($num < 0 ? '-' : '') . '$' . number_format(round(abs($num)), 0, ',', '.');
            }
        }
    @endphp
    <td class="px-4 {{ $isHighlight ? 'py-3' : 'py-2.5' }} text-right {{ $isHighlight ? 'bg-[var(--color-primary-light)]/30' : '' }}">
        @if($isData)
        <div x-data="{ editing: false, val: {{ $val }} }"
             @dblclick="editing = true"
             @click.outside="if(editing){ editing=false; saveValue({{ $row['line_id'] }}, {{ $idx }}, val) }">
            <template x-if="!editing">
                <span class="tabular-nums text-[13px] {{ $signNeg ? 'text-[var(--color-danger)]' : 'text-[var(--text-500)]' }}"
                      x-text="formatSignedCOP(val, {{ $signNeg ? 'true' : 'false' }})">{{ $formattedVal }}</span>
            </template>
            <template x-if="editing">
                <input type="text" x-money="val"
                       @keydown.enter="editing=false; saveValue({{ $row['line_id'] }}, {{ $idx }}, val)"
                       @keydown.escape="editing=false"
                       x-init="$nextTick(() => $el.focus())"
                       class="w-28 text-right border border-[var(--color-primary)] rounded-[var(--radius-control)] px-2 py-1 text-[13px] outline-none ring-2 ring-[var(--color-primary-light)] bg-[var(--surface-card)]"/>
            </template>
        </div>
        @else
        <span class="tabular-nums text-[14px] font-bold {{ $val < 0 ? 'text-[var(--color-danger)]' : 'text-[var(--text-900)]' }} {{ $isHighlight ? '!text-[15px]' : '' }}">
            {{ $val < 0 ? '-$' : '$' }}{{ number_format(abs($val), 0, ',', '.') }}
        </span>
        @endif
    </td>
    @endforeach
</tr>
@endif
