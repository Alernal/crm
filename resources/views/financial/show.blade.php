<x-app-layout>
<x-slot name="title">Presupuesto</x-slot>

@php
    $typeLabels  = \App\Models\Budget::TYPES;
    $statusLabels= \App\Models\Budget::STATUS_LABELS;
    $driverLabels= \App\Models\Budget::DRIVERS;
    $statusVariant = ['draft' => 'neutral', 'projected' => 'info', 'final' => 'success'];
    $sl = $statusLabels[$budget->status] ?? $statusLabels['draft'];
    $sv = $statusVariant[$budget->status] ?? 'neutral';
    $allPeriods  = range(0, $budget->periods_count);
    $isDraft     = $budget->status === 'draft';
@endphp

{{-- Breadcrumb --}}
<nav class="flex items-center gap-1.5 text-[14px] text-[var(--text-400)] mb-2">
    <a href="{{ route('financial.index') }}" class="hover:text-[var(--color-primary)]">Financiero</a>
    <x-lucide-chevron-right class="w-3.5 h-3.5" />
    <a href="{{ route('financial.client', $budget->client) }}" class="hover:text-[var(--color-primary)]">{{ $budget->client->name }}</a>
    <x-lucide-chevron-right class="w-3.5 h-3.5" />
    <span class="text-[var(--text-700)] font-medium truncate">{{ $budget->name }}</span>
</nav>

{{-- Barra de acciones --}}
<div class="flex items-center justify-end gap-4 mb-6 flex-wrap"
     x-data="{ isPrinting: false, printUrl: '' }">
    <div class="flex items-center gap-2 flex-wrap">
        <button @click="printUrl = '{{ route('financial.print', $budget) }}'; isPrinting = true"
                class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-printer class="w-3.5 h-3.5" />
            Imprimir
        </button>

        <a href="{{ route('financial.pdf', $budget) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-download class="w-3.5 h-3.5" />
            PDF
        </a>

        @if(!$variables)
        <a href="{{ route('financial.variables', $budget->client) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--color-warning-bg)] border border-[#FCD34D] text-[var(--color-warning-text)] text-[13px] font-medium hover:opacity-90">
            <x-lucide-alert-triangle class="w-3.5 h-3.5" />
            Variables
        </a>
        @else
        <a href="{{ route('financial.variables', $budget->client) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-settings class="w-3.5 h-3.5" />
            Variables
        </a>
        @endif

        <form method="POST" action="{{ route('financial.project', $budget) }}">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-1.5 h-9 px-4 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[13px] font-medium">
                <x-lucide-trending-up class="w-3.5 h-3.5" />
                Proyectar
            </button>
        </form>

        <a href="{{ route('financial.edit', $budget) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-edit-2 class="w-3.5 h-3.5" />
            Editar
        </a>
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
<div class="mb-5 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
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
            <h1 class="text-[20px] font-semibold text-[var(--text-900)]">{{ $budget->name }}</h1>
        </div>
        <div class="flex items-center gap-3 flex-shrink-0 text-right">
            <div class="text-right">
                <p class="text-[11px] text-[var(--text-400)] uppercase tracking-[0.06em]">Estado</p>
                <x-status-badge :variant="$sv">{{ $sl['label'] }}</x-status-badge>
            </div>
            <div class="w-px h-8 bg-[var(--border-default)]"></div>
            <div class="text-right">
                <p class="text-[11px] text-[var(--text-400)] uppercase tracking-[0.06em]">Año base</p>
                <p class="text-[14px] font-semibold text-[var(--text-700)] tabular-nums">{{ $budget->base_year }}</p>
            </div>
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
</div>

{{-- Tabla principal --}}
<div class="bg-[var(--surface-card)] border border-t-0 border-[var(--border-default)] rounded-b-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden mb-5"
     x-data="budgetTable('{{ route('financial.update_value', $budget) }}', '{{ csrf_token() }}')">

    <div class="overflow-x-auto">
        <table class="w-full min-w-[680px] border-collapse">

            {{-- Cabecera de columnas --}}
            <thead>
                <tr class="border-b border-[var(--border-default)]">
                    <th class="px-4 py-3 text-left text-[11px] font-medium uppercase tracking-[0.06em] text-[var(--text-400)]" style="width:240px">
                        Concepto
                    </th>
                    @foreach($periodLabels as $idx => $label)
                    <th class="px-4 py-3 text-right text-[11px] font-medium uppercase tracking-[0.06em] min-w-[130px]
                               {{ $idx === 0 ? 'text-[var(--text-900)]' : 'text-[var(--text-400)]' }}">
                        {{ $label }}
                        @if($idx === 0)
                        <span class="block text-[10px] font-normal text-[var(--text-400)] mt-0.5 normal-case tracking-normal">Año base</span>
                        @endif
                    </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
            @foreach($budget->sections as $section)

                {{-- Cabecera de sección --}}
                <tr>
                    <td colspan="{{ 1 + count($periodLabels) }}"
                        class="px-4 pt-5 pb-1.5 bg-[var(--surface-card)]">
                        <span class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">
                            {{ $section->name }}
                        </span>
                    </td>
                </tr>

                @php $sectionTotals = array_fill(0, count($periodLabels), 0); @endphp

                @foreach($section->lines as $line)
                @php
                    $driverLabel = $driverLabels[$line->projection_driver] ?? $line->projection_driver;
                    if ($line->projection_driver === 'custom_pct' && $line->custom_rate !== null) {
                        $driverLabel = number_format($line->custom_rate, 1) . '%';
                    }
                @endphp
                <tr class="border-b border-[var(--surface-muted)] hover:bg-[var(--surface-subtle)]">
                    <td class="px-4 py-3 text-[14px] text-[var(--text-700)]">
                        <div class="border-l-2 border-[var(--border-strong)] pl-3">
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
                    <td class="px-4 py-3 text-right {{ $isBase ? '' : 'bg-[var(--surface-subtle)]' }}">
                        <div x-data="{ editing: false, val: {{ $val }} }"
                             @dblclick="editing = true"
                             @click.outside="if(editing){ editing=false; saveValue({{ $line->id }}, {{ $idx }}, val) }">
                            <template x-if="!editing">
                                <div>
                                    <span class="tabular-nums text-[14px]
                                                 {{ $isBase ? 'font-semibold text-[var(--text-900)]' : ($isProjected ? 'font-medium text-[var(--color-primary)]' : 'text-[var(--border-strong)]') }}"
                                          x-text="formatCOP(val)">
                                    </span>
                                    @if($idx > 0 && $pctChange !== null && !$isDraft)
                                    <span class="text-[11px] font-medium block leading-none mt-0.5
                                                 {{ $pctChange >= 0 ? 'text-[var(--color-success)]' : 'text-[var(--color-danger)]' }}">
                                        {{ $pctChange >= 0 ? '+' : '' }}{{ number_format($pctChange, 1) }}%
                                    </span>
                                    @endif
                                </div>
                            </template>
                            <template x-if="editing">
                                <input type="number"
                                       x-model="val"
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
                    <td class="px-4 py-3">
                        <span class="text-[11px] font-semibold text-[var(--text-700)] uppercase tracking-[0.06em]">
                            Total {{ $section->name }}
                        </span>
                    </td>
                    @foreach($periodLabels as $idx => $label)
                    <td class="px-4 py-3 text-right">
                        <span class="text-[15px] font-bold tabular-nums {{ $idx === 0 ? 'text-[var(--text-900)]' : 'text-[var(--color-primary-dark)]' }}">
                            ${{ number_format($sectionTotals[$idx], 0, ',', '.') }}
                        </span>
                    </td>
                    @endforeach
                </tr>

            @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pie: instrucción discreta --}}
    <div class="px-4 py-2.5 border-t border-[var(--border-default)] flex items-center gap-2 text-[12px] text-[var(--text-400)]">
        <x-lucide-edit-2 class="w-3.5 h-3.5 flex-shrink-0" />
        @if($isDraft)
        Ingresa los valores base y presiona <strong class="text-[var(--text-500)] mx-0.5">Proyectar</strong> para calcular los períodos. Doble clic en una celda para editarla.
        @else
        Doble clic en cualquier celda para editar manualmente. Los cambios se guardan automáticamente.
        @endif
    </div>
</div>

<script>
function budgetTable(updateUrl, csrfToken) {
    return {
        saveValue(lineId, periodIndex, value) {
            fetch(updateUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ line_id: lineId, period_index: periodIndex, value: value }),
            }).catch(() => {});
        },
        formatCOP(val) {
            if (!val && val !== 0) return '—';
            const num = parseFloat(val);
            if (isNaN(num) || num === 0) return '—';
            return '$' + Math.round(num).toLocaleString('es-CO');
        },
    };
}
</script>

</x-app-layout>
