<x-app-layout>
<x-slot name="title">Estados Financieros — {{ $esf->client->name }}</x-slot>

@php
    $statusLabels  = \App\Models\Budget::STATUS_LABELS;
    $statusVariant = ['draft' => 'neutral', 'final' => 'success'];
    $sl = $statusLabels[$esf->status] ?? $statusLabels['draft'];
    $sv = $statusVariant[$esf->status] ?? 'neutral';

    $esfPeriodLabels = $esf->getPeriodLabels();
    $eriPeriodLabels = $eri->getPeriodLabels();
    $lastEsfIdx = array_key_last($esfPeriodLabels);
    $lastEriIdx = array_key_last($eriPeriodLabels);

    $kpiBalanced  = abs($esfReport['diferencia'][$lastEsfIdx] ?? 0) < 1;
    $totalActivo  = $esfReport['totalActivo'][$lastEsfIdx] ?? 0;
    $totalPasivo  = $esfReport['totalPasivo'][$lastEsfIdx] ?? 0;
    $totalPatrim  = $esfReport['totalPatrimonio'][$lastEsfIdx] ?? 0;

    $ventasNetas  = $eriReport['ventasNetas'][$lastEriIdx] ?? 0;
    $utilidadNeta = $eriReport['utilidadNeta'][$lastEriIdx] ?? 0;
    $margen       = $ventasNetas != 0 ? ($utilidadNeta / $ventasNetas) * 100 : null;
@endphp

<div x-data="{ isPrinting: false, printUrl: '', ratiosModal: {{ session('open_ratios_modal') ? 'true' : 'false' }}, activeTab: '{{ $activeTab }}' }">

{{-- Volver --}}
<a href="{{ route('financial.statements.client', $esf->client) }}"
   class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)] mb-3">
    <x-lucide-arrow-left class="w-4 h-4" />
    Volver
</a>

{{-- Barra de acciones --}}
<div class="flex items-center justify-end gap-4 mb-6 flex-wrap">
    <div class="flex items-center gap-2 flex-wrap">
        <button @click="printUrl = (activeTab === 'esf' ? '{{ route('financial.print', $esf) }}' : '{{ route('financial.print', $eri) }}'); isPrinting = true"
                class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-printer class="w-3.5 h-3.5" />
            Imprimir
        </button>

        <a :href="activeTab === 'esf' ? '{{ route('financial.pdf', $esf) }}' : '{{ route('financial.pdf', $eri) }}'"
           class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-download class="w-3.5 h-3.5" />
            PDF
        </a>

        @if(!empty($financialRatios))
        <button @click="ratiosModal = true"
                class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-activity class="w-3.5 h-3.5" />
            Indicadores financieros
        </button>
        @endif

        <a href="{{ route('financial.statements.edit', $esf) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-edit-2 class="w-3.5 h-3.5" />
            Editar
        </a>

        <form method="POST" action="{{ route('financial.statements.destroy', $esf) }}"
              x-data=""
              x-on:submit.prevent="if(confirm('¿Eliminar el Estado de Situación Financiera y el Estado de Resultados de «{{ addslashes($esf->client->name) }}»? Esta acción no se puede deshacer.')) $el.submit()">
            @csrf @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] border bg-[var(--color-danger-bg)] border-[var(--color-danger)] text-[var(--color-danger)] text-[13px] font-medium hover:bg-[var(--color-danger)] hover:text-white">
                <x-lucide-trash-2 class="w-3.5 h-3.5" />
                Eliminar
            </button>
        </form>
    </div>

    <iframe
        x-ref="printFrame"
        :src="isPrinting ? printUrl : ''"
        @load="if (isPrinting) { $refs.printFrame.contentWindow.print(); isPrinting = false }"
        style="position:fixed;top:-9999px;left:-9999px;width:0;height:0;border:0"
        title="Vista de impresión">
    </iframe>
</div>

{{-- Flash — desaparece solo a los pocos segundos, sin necesidad de recargar
     ni cerrarlo a mano (a pedido explícito del usuario). --}}
@if(session('success'))
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="mb-5 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

{{-- Encabezado del par --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden mb-5">
    <div class="px-6 py-5 flex items-center justify-between gap-4">
        <div>
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] mb-0.5">
                Estados Financieros · {{ $esf->client->name }}
            </p>
            <p class="text-[22px] font-bold text-[var(--text-900)]">
                {{ $esfPeriodLabels[0] ?? $esf->base_year }}{{ count($esfPeriodLabels) > 1 ? ' – ' . end($esfPeriodLabels) : '' }}
            </p>
        </div>
        <div class="flex items-center gap-3 flex-shrink-0 text-right">
            <div class="text-right">
                <p class="text-[11px] text-[var(--text-400)] uppercase tracking-[0.06em]">Estado</p>
                <x-status-badge :variant="$sv">{{ $sl['label'] }}</x-status-badge>
            </div>
            <div class="w-px h-8 bg-[var(--border-default)]"></div>
            <div class="text-right">
                <p class="text-[11px] text-[var(--text-400)] uppercase tracking-[0.06em]">Períodos</p>
                <p class="text-[14px] font-semibold text-[var(--text-700)] tabular-nums">{{ $esf->periods_count + 1 }}</p>
            </div>
            @if($esf->cutoff_date)
            <div class="w-px h-8 bg-[var(--border-default)]"></div>
            <div class="text-right">
                <p class="text-[11px] text-[var(--text-400)] uppercase tracking-[0.06em]">Fecha de corte</p>
                <p class="text-[14px] font-semibold text-[var(--text-700)] tabular-nums">{{ $esf->cutoff_date->locale('es')->isoFormat('D MMM YYYY') }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Selector de pestañas --}}
    <div class="border-t border-[var(--border-default)] px-6 flex items-center gap-1">
        <button type="button" @click="activeTab = 'esf'"
                class="px-4 h-11 text-[13px] font-medium border-b-2 -mb-px transition-colors"
                :class="activeTab === 'esf' ? 'border-[var(--color-primary)] text-[var(--color-primary)]' : 'border-transparent text-[var(--text-500)] hover:text-[var(--text-700)]'">
            Situación Financiera
        </button>
        <button type="button" @click="activeTab = 'eri'"
                class="px-4 h-11 text-[13px] font-medium border-b-2 -mb-px transition-colors"
                :class="activeTab === 'eri' ? 'border-[var(--color-primary)] text-[var(--color-primary)]' : 'border-transparent text-[var(--text-500)] hover:text-[var(--text-700)]'">
            Resultados
        </button>
    </div>
</div>

{{-- ── Panel: Estado de Situación Financiera ──────────────────────────────── --}}
<div x-show="activeTab === 'esf'">
    @include('financial.statements._esf-panel')
</div>

{{-- ── Panel: Estado de Resultados ───────────────────────────────── --}}
<div x-show="activeTab === 'eri'" style="display:none">
    @include('financial.statements._eri-panel')
</div>

<script>
function statementTable(updateUrl, csrfToken, budgetId) {
    return {
        saving: false,
        budgetId: budgetId,
        init() {
            window.addEventListener('statement-counterpart-updated', (e) => {
                if (e.detail.budgetId === this.budgetId) {
                    this.$refs.body.innerHTML = e.detail.html;
                    window.Alpine.initTree(this.$refs.body);
                }
            });
        },
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
                    if (data.counterpart_id && data.counterpart_html) {
                        window.dispatchEvent(new CustomEvent('statement-counterpart-updated', {
                            detail: { budgetId: data.counterpart_id, html: data.counterpart_html },
                        }));
                    }
                })
                .catch(() => {})
                .finally(() => { this.saving = false; });
        },
    };
}
</script>

@if(!empty($financialRatios))
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
                <form method="POST" action="{{ route('financial.ratio_targets.update', $esf->client) }}" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 items-end">
                    @csrf @method('PATCH')
                    <input type="hidden" name="redirect_budget_id" value="{{ $esf->id }}"/>
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
