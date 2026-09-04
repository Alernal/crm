<x-app-layout>
<x-slot name="title">Nuevo Estado Financiero</x-slot>

@php
    $periodTypes = \App\Models\Budget::PERIOD_TYPES;
    $inputClass = 'w-full bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 h-10 text-[14px] text-[var(--text-700)] outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]';
    $labelClass = 'block text-[13px] font-medium text-[var(--text-700)] mb-1.5';
@endphp

<a href="{{ $preClient ? route('financial.statements.client', $preClient) : route('financial.statements.index') }}"
   class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)] mb-5">
    <x-lucide-arrow-left class="w-4 h-4" />
    Cancelar
</a>

<form method="POST" action="{{ route('financial.statements.store') }}"
      x-data="statementPairForm()"
      x-init="init()">
@csrf

{{-- Botón flotante de guardar — siempre visible sin importar el scroll,
     para no tener que bajar hasta el final del formulario cada vez que se
     quiere guardar (mismo criterio que `financial/statements/edit.blade.php`,
     a pedido explícito del usuario). Es un botón submit normal dentro del
     propio <form>, solo posicionado `fixed`. --}}
<button type="submit"
        class="fixed bottom-6 left-6 lg:left-[244px] z-40 w-14 h-14 rounded-full bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white shadow-[var(--shadow-card-hover)] flex items-center justify-center transition-transform hover:scale-105"
        title="Guardar estados financieros">
    <x-lucide-save class="w-6 h-6" />
</button>

@if($errors->any())
<div class="mb-4 bg-[var(--color-danger-bg)] border border-[var(--color-danger)]/20 rounded-[var(--radius-control)] px-4 py-3 text-[14px] text-[var(--color-danger-text)]">
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

{{-- ── BLOQUE 1: Parametrización (compartida por ambos estados) ──────────── --}}
<div class="mb-6">

    <div class="flex items-start justify-between gap-4 mb-4">
        <div>
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Parametrización</p>
            <p class="text-[12px] text-[var(--text-400)] mt-0.5">Un Estado de Situación Financiera y un Estado de Resultados, siempre vinculados y con el mismo período.</p>
        </div>
        <button type="button" @click="parametrizacionModal = true"
                class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[13px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] flex-shrink-0">
            <x-lucide-settings-2 class="w-3.5 h-3.5" />
            Parametrización
        </button>
    </div>

    <div class="lg:max-w-sm">
        <label class="{{ $labelClass }}">
            Cliente <span class="text-[var(--color-danger)]">*</span>
        </label>
        <select name="client_id" required class="{{ $inputClass }} w-full">
            <option value="">Seleccionar…</option>
            @foreach($clients as $c)
            <option value="{{ $c->id }}" {{ (old('client_id', $preClient?->id)) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>

</div>

{{-- ══ MODAL: Parametrización (Período, N°, Fecha de corte, Períodos a registrar) ══ --}}
<div x-show="parametrizacionModal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="display:none"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="absolute inset-0 bg-gray-900/50" @click="parametrizacionModal = false"></div>

    <div class="relative bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-2xl z-10 max-h-[90vh] overflow-y-auto"
         @click.stop>

        <div class="flex items-center justify-between px-6 py-5 border-b border-[var(--border-default)] sticky top-0 bg-[var(--surface-card)]">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-[var(--color-primary-light)] rounded-[var(--radius-control)] flex items-center justify-center">
                    <x-lucide-settings-2 class="w-5 h-5 text-[var(--color-primary)]" />
                </div>
                <div>
                    <h2 class="text-[16px] font-bold text-[var(--text-900)]">Parametrización</h2>
                    <p class="text-[12px] text-[var(--text-400)] mt-0.5">Periodicidad, cantidad de períodos y fecha de corte</p>
                </div>
            </div>
            <button type="button" @click="parametrizacionModal = false"
                    class="p-1.5 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                <x-lucide-x class="w-4 h-4" />
            </button>
        </div>

        <div class="px-6 py-5 space-y-5">

            <div class="flex flex-col lg:flex-row gap-4">
                <div class="lg:flex-1">
                    <label class="{{ $labelClass }}">
                        Período <span class="text-[var(--color-danger)]">*</span>
                    </label>
                    <select name="period_type" x-model="periodType" @change="onPeriodTypeChange()" required class="{{ $inputClass }} w-full">
                        @foreach($periodTypes as $val => $label)
                        <option value="{{ $val }}" {{ old('period_type', 'annual') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:w-24 lg:flex-none">
                    <label class="{{ $labelClass }}">
                        N° <span class="text-[var(--color-danger)]">*</span>
                    </label>
                    <input type="number" name="periods_count" x-model.number="periodsCount" @input="clampPeriodsCount()"
                           required min="0" max="10"
                           class="{{ $inputClass }} w-full text-center tabular-nums"/>
                </div>

                <div class="lg:flex-1">
                    <label class="{{ $labelClass }}">Fecha de corte</label>
                    <input type="date" name="cutoff_date" x-model="cutoffDate"
                           class="{{ $inputClass }} w-full tabular-nums"/>
                </div>
            </div>

            {{-- Períodos a registrar: solo para anual (años, con posible
                 comparativo no consecutivo). El resto de periodicidades nombra
                 cada período directamente en el encabezado de su columna, en la
                 tabla de estructura de abajo. --}}
            <template x-if="periodType === 'annual'">
                <div>
                    <div class="flex items-center gap-1.5 mb-2.5">
                        <label class="text-[13px] font-medium text-[var(--text-700)]">Períodos a registrar</label>
                        <x-help-icon title="Períodos a registrar">
                            Al aumentar "N°" se agregan los siguientes años en orden — cada año es editable si necesitas una comparación no consecutiva.
                        </x-help-icon>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <template x-for="(year, p) in periodYears" :key="p">
                            <div class="flex-shrink-0 w-[116px] rounded-[var(--radius-card)] border border-[var(--border-default)] bg-[var(--surface-card)] overflow-hidden transition-shadow hover:shadow-[var(--shadow-card)]">
                                <div class="flex items-center justify-center gap-1.5 pt-2.5">
                                    <span class="w-[5px] h-[5px] rounded-full flex-shrink-0 bg-[var(--border-strong)]"></span>
                                    <span class="text-[10px] font-semibold uppercase tracking-[0.06em] text-[var(--text-400)]"
                                          x-text="'Período ' + (p + 1)"></span>
                                </div>
                                <input type="number" x-model.number="periodYears[p]"
                                       :name="`period_years[${p}]`"
                                       min="1900" max="2200" required
                                       class="w-full bg-transparent border-0 px-2 pt-1 pb-2.5 text-[18px] font-bold text-center text-[var(--text-900)] outline-none tabular-nums focus:ring-2 focus:ring-inset focus:ring-[var(--color-primary-light)]"/>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

        </div>

        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-[var(--border-default)] sticky bottom-0 bg-[var(--surface-card)]">
            <button type="button" @click="parametrizacionModal = false"
                    class="h-9 px-4 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[13px] font-medium">
                Listo
            </button>
        </div>
    </div>
</div>

{{-- ── BLOQUE 2: Selector de pestañas + estructura ────────────────────────── --}}
<div class="mb-5">

    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-1.5">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Estructura (rubros NIIF)</p>
            <x-help-icon title="Estructura (rubros NIIF)">
                Las secciones siguen la estructura NIIF y no se pueden renombrar ni reclasificar — agrega o quita rubros libremente dentro de cada una. Cambiar de pestaña no borra lo digitado en la otra.
                <template x-if="periodType !== 'annual'"><span> El nombre de cada período se edita directamente en el encabezado de su columna.</span></template>
            </x-help-icon>
        </div>
        <div class="inline-flex rounded-[var(--radius-control)] border border-[var(--border-default)] bg-[var(--surface-subtle)] p-1">
            <button type="button" @click="activeTab = 'esf'"
                    class="px-4 h-8 rounded-[var(--radius-control)] text-[13px] font-medium transition-colors"
                    :class="activeTab === 'esf' ? 'bg-[var(--surface-card)] shadow-[var(--shadow-card)] text-[var(--color-primary)]' : 'text-[var(--text-500)] hover:text-[var(--text-700)]'">
                Situación Financiera
            </button>
            <button type="button" @click="activeTab = 'eri'"
                    class="px-4 h-8 rounded-[var(--radius-control)] text-[13px] font-medium transition-colors"
                    :class="activeTab === 'eri' ? 'bg-[var(--surface-card)] shadow-[var(--shadow-card)] text-[var(--color-primary)]' : 'text-[var(--text-500)] hover:text-[var(--text-700)]'">
                Resultados
            </button>
        </div>
    </div>

    <div x-show="activeTab === 'esf'">
        @include('financial.statements._structure-editor', ['prefix' => 'esf'])
    </div>
    <div x-show="activeTab === 'eri'" style="display:none">
        @include('financial.statements._structure-editor', ['prefix' => 'eri'])
    </div>

</div>

{{-- ── Acciones ────────────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between gap-3">
    <a href="{{ $preClient ? route('financial.statements.client', $preClient) : route('financial.statements.index') }}"
       class="h-10 flex items-center px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)]">
        Cancelar
    </a>
</div>

</form>

<script>
function statementPairForm() {
    const defaults = { esf: @json($defaultSections['esf']), eri: @json($defaultSections['eri']) };

    return {
        activeTab: 'esf',
        parametrizacionModal: false,
        periodType: '{{ old('period_type', 'annual') }}',
        periodsCount: {{ (int) old('periods_count', 0) }},
        periodYears: @json(old('period_years', [])),
        periodLabels: @json(old('period_labels', [])),
        cutoffDate: '{{ old('cutoff_date', '') }}',
        periodLabelWords: @json(\App\Models\Budget::PERIOD_LABEL_WORDS),
        esfSections: [],
        eriSections: [],
        esfRoles: @json(\App\Models\Budget::ESF_SECTION_ROLES),
        eriRoles: @json(\App\Models\Budget::ERI_SECTION_ROLES),
        autoLines: [@json(\App\Models\Budget::ESF_UTILIDAD_LINE), @json(\App\Models\Budget::ERI_DEPRECIACION_GASTO_LINE)],
        esfUtilidadLineName: @json(\App\Models\Budget::ESF_UTILIDAD_LINE),
        eriDeprecGastoLineName: @json(\App\Models\Budget::ERI_DEPRECIACION_GASTO_LINE),
        esfDeprecLineName: @json(\App\Models\Budget::ESF_DEPRECIACION_LINE),

        init() {
            this.esfSections = this.buildSections('esf');
            this.eriSections = this.buildSections('eri');
            this.resizePeriodYears();
            this.resizePeriodLabels();
        },

        buildSections(which) {
            return defaults[which].map(s => ({
                name: s.name,
                statementRole: s.statement_role || null,
                lines: s.lines.map(l => ({
                    name: l.name,
                    signNegative: l.sign_negative || false,
                    values: {},
                    valuesDisplay: {},
                })),
            }));
        },

        sectionsFor(which) {
            return which === 'esf' ? this.esfSections : this.eriSections;
        },

        periodsArray() {
            return Array.from({ length: (this.periodsCount || 0) + 1 }, (_, i) => i);
        },

        maxPeriodsCount() { return 10; },

        clampPeriodsCount() {
            if (this.periodsCount > this.maxPeriodsCount()) this.periodsCount = this.maxPeriodsCount();
            this.resizePeriodYears();
            this.resizePeriodLabels();
        },

        // Cambiar la periodicidad cambia lo que significa cada período, así
        // que las etiquetas por defecto se regeneran desde cero (a
        // diferencia de resizePeriodLabels(), que preserva ediciones al solo
        // cambiar "N°").
        onPeriodTypeChange() {
            this.clampPeriodsCount();
            if (this.periodType !== 'annual') {
                this.periodLabels = this.periodsArray().map((_, p) => this.defaultPeriodLabel(p));
            }
        },

        defaultPeriodLabel(p) {
            const word = this.periodLabelWords[this.periodType] || 'Período';
            return `${word} ${p + 1}`;
        },

        // Mantiene `periodYears` alineado con `periodsCount`. Agrega años
        // consecutivos al final o recorta desde el final — nunca toca años
        // ya editados en medio del arreglo.
        resizePeriodYears() {
            const n = (this.periodsCount || 0) + 1;
            if (this.periodYears.length === 0) {
                const start = new Date().getFullYear();
                this.periodYears = Array.from({ length: n }, (_, i) => start + i);
                return;
            }
            while (this.periodYears.length < n) {
                const last = this.periodYears[this.periodYears.length - 1];
                this.periodYears.push(last + 1);
            }
            if (this.periodYears.length > n) this.periodYears.length = n;
        },

        // Igual que resizePeriodYears() pero para las etiquetas de texto de
        // períodos no anuales — agrega/recorta al final, preservando lo ya
        // editado en medio del arreglo.
        resizePeriodLabels() {
            const n = (this.periodsCount || 0) + 1;
            while (this.periodLabels.length < n) {
                this.periodLabels.push(this.defaultPeriodLabel(this.periodLabels.length));
            }
            if (this.periodLabels.length > n) this.periodLabels.length = n;
        },

        // ESF: totales en vivo (misma lógica que Budget::roleTotals() en el backend).
        // Valor "efectivo" de un rubro para sumas en vivo: el digitado, salvo
        // que sea una celda "Auto" (Utilidad del período/Depreciaciones), en
        // cuyo caso usa la misma fórmula de `autoCellDisplayValue()` que ya
        // se muestra en pantalla — sin esto, TOTAL PATRIMONIO/EBIT no
        // reflejaban la utilidad/depreciación recién calculada en vivo
        // aunque la celda sí la mostrara (bug real reportado por el
        // usuario: "Patrimonio" mostraba la Utilidad del período correcta
        // en su celda, pero TOTAL PATRIMONIO seguía sumando solo el resto).
        lineValueForPeriod(line, p) {
            if (this.isAutoCell(line.name, p)) {
                return this.autoCellDisplayValue(line.name, p);
            }
            const raw = line.values[p];
            return (raw === '' || raw === null || raw === undefined) ? 0 : (parseFloat(raw) || 0);
        },

        sectionTotalForPeriod(which, section, p) {
            let sum = 0;
            section.lines.forEach(line => {
                sum += line.signNegative ? -this.lineValueForPeriod(line, p) : this.lineValueForPeriod(line, p);
            });
            return sum;
        },

        roleTotalForPeriod(which, role, p) {
            let sum = 0;
            this.sectionsFor(which).filter(s => s.statementRole === role).forEach(s => sum += this.sectionTotalForPeriod(which, s, p));
            return sum;
        },

        totalActivoForPeriod(p) {
            return this.roleTotalForPeriod('esf', 'activo_corriente', p) + this.roleTotalForPeriod('esf', 'activo_no_corriente', p);
        },

        totalPasivoForPeriod(p) {
            return this.roleTotalForPeriod('esf', 'pasivo_corriente', p) + this.roleTotalForPeriod('esf', 'pasivo_no_corriente', p);
        },

        totalPasivoPatrimonioForPeriod(p) {
            return this.totalPasivoForPeriod(p) + this.roleTotalForPeriod('esf', 'patrimonio', p);
        },

        diferenciaForPeriod(p) {
            return this.totalActivoForPeriod(p) - this.totalPasivoPatrimonioForPeriod(p);
        },

        // ERI: cascada Ventas Netas → Costo de Ventas → Utilidad Bruta →
        // Gastos Operacionales → EBIT → EBITDA → No Operacionales → UAI →
        // Impuestos → Utilidad Neta → ORI → Resultado Integral, replicando
        // Budget::buildEriReport() (incl. el "add-back" de depreciación para EBITDA).
        depreciacionForPeriod(p) {
            let sum = 0;
            this.eriSections.forEach(s => s.lines.forEach(l => {
                if (l.name === this.eriDeprecGastoLineName) {
                    sum += this.lineValueForPeriod(l, p);
                }
            }));
            return sum;
        },

        utilidadBrutaForPeriod(p) {
            return this.roleTotalForPeriod('eri', 'ingresos_operacionales', p) + this.roleTotalForPeriod('eri', 'costo_ventas', p);
        },

        totalGastosOpForPeriod(p) {
            return this.roleTotalForPeriod('eri', 'gastos_administracion', p) + this.roleTotalForPeriod('eri', 'gastos_ventas', p);
        },

        ebitForPeriod(p) {
            return this.utilidadBrutaForPeriod(p) + this.totalGastosOpForPeriod(p);
        },

        ebitdaForPeriod(p) {
            return this.ebitForPeriod(p) + this.depreciacionForPeriod(p);
        },

        uaiForPeriod(p) {
            return this.ebitForPeriod(p) + this.roleTotalForPeriod('eri', 'ingresos_no_operacionales', p) + this.roleTotalForPeriod('eri', 'gastos_no_operacionales', p);
        },

        utilidadNetaForPeriod(p) {
            return this.uaiForPeriod(p) + this.roleTotalForPeriod('eri', 'impuestos', p);
        },

        resultadoIntegralForPeriod(p) {
            return this.utilidadNetaForPeriod(p) + this.roleTotalForPeriod('eri', 'ori', p);
        },

        isAutoCell(lineName, period) {
            if (this.autoLines.includes(lineName)) return true;
            return false;
        },

        esfLineValueForPeriod(lineName, p) {
            let val = 0;
            this.esfSections.forEach(s => s.lines.forEach(l => {
                if (l.name === lineName) {
                    const raw = l.values[p];
                    val = (raw === '' || raw === null || raw === undefined) ? 0 : (parseFloat(raw) || 0);
                }
            }));
            return val;
        },

        // Vista previa en vivo de las 2 celdas "Auto" mientras se digita —
        // antes se veían en blanco hasta guardar, aunque el vínculo
        // ESF<->ERI ya está resuelto del lado del servidor al recalcular
        // (`projectEsfLinks()`/`projectEriLinks()`); esto solo refleja esa
        // misma fórmula en el navegador para que el usuario vea el efecto
        // de lo que está digitando sin tener que guardar primero.
        autoCellDisplayValue(lineName, p) {
            if (lineName === this.esfUtilidadLineName) {
                return this.utilidadNetaForPeriod(p);
            }
            if (lineName === this.eriDeprecGastoLineName) {
                const current = this.esfLineValueForPeriod(this.esfDeprecLineName, p);
                const prior   = p > 0 ? this.esfLineValueForPeriod(this.esfDeprecLineName, p - 1) : 0;
                return Math.abs(current - prior);
            }
            return 0;
        },

        parseCOP(str) {
            if (!str && str !== 0) return 0;
            return parseFloat(String(str).replace(/\s/g, '').replace(/\$/g, '').replace(/\./g, '').replace(',', '.')) || 0;
        },

        formatGridNumber(val) {
            if (val === '' || val === null || val === undefined) return '';
            const num = Math.round(parseFloat(val) * 100) / 100;
            if (isNaN(num)) return '';
            return num.toLocaleString('es-CO');
        },

        onCellFocus(line, p, event) {
            event.target.select();
        },

        // Formatea con separador de miles MIENTRAS SE ESCRIBE (no solo al
        // perder el foco), conservando la posición del cursor a la misma
        // distancia del final del texto — igual criterio que la directiva
        // global `x-money` (`resources/js/app.js`), reimplementado aquí en
        // vez de usarla directamente porque esta grilla ya tiene su propio
        // manejo de celdas "Auto"/vacías (`isAutoCell`, blanco real vs. 0)
        // que x-money no conoce.
        onCellInput(line, p, event) {
            const raw = event.target.value;
            const distanceFromEnd = raw.length - event.target.selectionStart;
            const trimmed = raw.trim();
            const parsed = trimmed === '' ? '' : this.parseCOP(trimmed);
            line.values[p] = parsed;
            const formatted = parsed === '' ? '' : this.formatGridNumber(parsed);
            line.valuesDisplay[p] = formatted;
            this.$nextTick(() => {
                const pos = Math.max(0, formatted.length - distanceFromEnd);
                event.target.setSelectionRange(pos, pos);
            });
        },

        onCellBlur(line, p, event) {
            line.valuesDisplay[p] = this.formatGridNumber(line.values[p]);
        },

        addLine(which, sIdx) {
            this.sectionsFor(which)[sIdx].lines.push({
                name: '', signNegative: false, values: {}, valuesDisplay: {},
            });
        },

        removeLine(which, sIdx, lIdx) {
            this.sectionsFor(which)[sIdx].lines.splice(lIdx, 1);
        },
    };
}
</script>

</x-app-layout>
