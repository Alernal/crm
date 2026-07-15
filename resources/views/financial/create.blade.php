<x-app-layout>
<x-slot name="title">Nuevo Presupuesto</x-slot>

@php
    $types       = \App\Models\Budget::TYPES;
    $periodTypes = \App\Models\Budget::PERIOD_TYPES;
    $driverShort = [
        'ipc'                  => 'IPC',
        'inflation'            => 'Inflación',
        'smmlv'                => 'SMMLV',
        'sales_growth'         => 'Meta ventas',
        'sales_growth_monthly' => 'Meta ventas (mes)',
        'payroll_growth'       => 'Nómina',
        'rent_growth'          => 'Arriendos',
        'utilities_growth'     => 'Serv. públicos',
        'purchases_growth'     => 'Compras',
        'interest_rate'        => 'Interés bancario',
        'services_growth'      => 'Tarifas servicios',
        'fixed'                => 'Fijo',
        'manual'               => 'Manual',
        'custom_pct'           => '% Personalizado',
    ];
    $inputClass = 'w-full bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 h-10 text-[14px] text-[var(--text-700)] outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]';
    $labelClass = 'block text-[13px] font-medium text-[var(--text-700)] mb-1.5';
@endphp

<form method="POST" action="{{ route('financial.store') }}"
      x-data="budgetForm()"
      x-init="init()">
@csrf

@if($errors->any())
<div class="mb-4 bg-[var(--color-danger-bg)] border border-[var(--color-danger)]/20 rounded-[var(--radius-control)] px-4 py-3 text-[14px] text-[var(--color-danger-text)]">
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

{{-- ── BLOQUE 1: Parametrización ──────────────────────────────────────────── --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] overflow-hidden mb-4">

    <div class="px-6 py-4 border-b border-[var(--border-default)]">
        <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Parametrización</h2>
    </div>

    <div class="px-6 pt-5 pb-4">

        {{-- Fila 1: Cliente · Tipo · Año base · Período / N° --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

            <div class="col-span-2 lg:col-span-1">
                <label class="{{ $labelClass }}">
                    Cliente <span class="text-[var(--color-danger)]">*</span>
                </label>
                <select name="client_id" required class="{{ $inputClass }}">
                    <option value="">Seleccionar…</option>
                    @foreach($clients as $c)
                    <option value="{{ $c->id }}" {{ (old('client_id', $preClient?->id)) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="{{ $labelClass }}">
                    Tipo <span class="text-[var(--color-danger)]">*</span>
                </label>
                <select name="type" x-model="selectedType" @change="loadDefaults()" required class="{{ $inputClass }}">
                    <option value="">Seleccionar…</option>
                    @foreach($types as $val => $label)
                    <option value="{{ $val }}" {{ old('type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="{{ $labelClass }}">
                    Año base <span class="text-[var(--color-danger)]">*</span>
                </label>
                <input type="number" name="base_year" value="{{ old('base_year', date('Y')) }}"
                       required min="2000" max="2100"
                       class="{{ $inputClass }} tabular-nums"/>
            </div>

            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="{{ $labelClass }}">
                        Período <span class="text-[var(--color-danger)]">*</span>
                    </label>
                    <select name="period_type" required class="{{ $inputClass }}">
                        @foreach($periodTypes as $val => $label)
                        <option value="{{ $val }}" {{ old('period_type', 'annual') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-20">
                    <label class="{{ $labelClass }}">
                        N° <span class="text-[var(--color-danger)]">*</span>
                    </label>
                    <input type="number" name="periods_count" value="{{ old('periods_count', 3) }}"
                           required min="1" max="10"
                           class="{{ $inputClass }} text-center tabular-nums"/>
                </div>
            </div>
        </div>

        {{-- Fila 2: Nombre · Notas --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 pt-4 border-t border-[var(--border-default)]">
            <div>
                <label class="{{ $labelClass }}">
                    Nombre del presupuesto <span class="text-[var(--color-danger)]">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="200"
                       placeholder="Ej: Presupuesto de Ventas 2026"
                       class="{{ $inputClass }}"/>
            </div>
            <div class="lg:col-span-2">
                <label class="{{ $labelClass }}">
                    Supuestos y notas
                </label>
                <input type="text" name="notes" value="{{ old('notes') }}" maxlength="500"
                       placeholder="Supuestos del presupuesto, fuentes de información…"
                       class="{{ $inputClass }}"/>
            </div>
        </div>

    </div>
</div>

{{-- ── BLOQUE 2: Secciones y rubros ─────────────────────────────────────── --}}
<div class="mb-5">

    <div class="flex items-center justify-between mb-3">
        <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Estructura del presupuesto</p>
        <button type="button" @click="addSection()"
                class="inline-flex items-center gap-1.5 px-3 h-8 bg-[var(--surface-card)] border border-[var(--border-default)] hover:bg-[var(--surface-muted)] text-[var(--text-700)] text-[12px] font-medium rounded-[var(--radius-control)]">
            <x-lucide-plus class="w-3 h-3" />
            Nueva sección
        </button>
    </div>

    {{-- Estado vacío --}}
    <template x-if="sections.length === 0">
        <div class="flex flex-col items-center justify-center py-14 bg-[var(--surface-card)] border border-dashed border-[var(--border-strong)] rounded-[var(--radius-card)] text-center">
            <div class="w-10 h-10 rounded-[var(--radius-control)] bg-[var(--surface-muted)] flex items-center justify-center mb-3">
                <x-lucide-bar-chart-2 class="w-5 h-5 text-[var(--text-400)]" />
            </div>
            <p class="text-[14px] font-semibold text-[var(--text-500)]">Selecciona el tipo de presupuesto arriba</p>
            <p class="text-[12px] text-[var(--text-400)] mt-1">La plantilla se carga automáticamente con todos los rubros</p>
        </div>
    </template>

    {{-- Una tarjeta por sección --}}
    <template x-for="(section, sIdx) in sections" :key="sIdx">
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] overflow-hidden mb-3 shadow-[var(--shadow-card)]">

            {{-- Cabecera de sección --}}
            <div class="bg-[var(--surface-subtle)] border-b border-[var(--border-default)] px-5 py-3 flex items-center justify-between gap-4">
                <input type="text"
                       :name="`sections[${sIdx}][name]`"
                       x-model="section.name"
                       placeholder="Nombre de la sección"
                       class="flex-1 bg-transparent text-[14px] font-semibold text-[var(--text-700)] uppercase tracking-wide border-none outline-none placeholder-[var(--text-400)] min-w-0"/>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button type="button" @click="addLine(sIdx)"
                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-[var(--surface-card)] border border-[var(--border-default)] hover:bg-[var(--surface-muted)] text-[var(--text-700)] text-[11px] font-medium rounded-[var(--radius-control)]">
                        <x-lucide-plus class="w-2.5 h-2.5" />
                        Rubro
                    </button>
                    <button type="button" @click="removeSection(sIdx)"
                            class="w-6 h-6 flex items-center justify-center text-[var(--text-400)] hover:text-[var(--color-danger)] hover:bg-[var(--color-danger-bg)] rounded-[var(--radius-control)]">
                        <x-lucide-x class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>

            {{-- Cabecera de columnas (solo cuando hay rubros) --}}
            <template x-if="section.lines.length > 0">
                <div class="grid gap-3 px-5 py-2 border-b border-[var(--border-default)] bg-[var(--surface-subtle)]"
                     style="grid-template-columns: 1fr 190px 148px 28px">
                    <span class="text-[10px] font-medium text-[var(--text-400)] uppercase tracking-wide">Rubro / Concepto</span>
                    <span class="text-[10px] font-medium text-[var(--text-400)] uppercase tracking-wide text-center">Proyección</span>
                    <span class="text-[10px] font-medium text-[var(--text-400)] uppercase tracking-wide text-right">Valor base</span>
                    <span></span>
                </div>
            </template>

            {{-- Filas de rubros --}}
            <template x-for="(line, lIdx) in section.lines" :key="lIdx">
                <div class="grid gap-3 px-5 py-2.5 border-b border-[var(--surface-muted)] hover:bg-[var(--surface-subtle)] group items-center"
                     style="grid-template-columns: 1fr 190px 148px 28px">

                    {{-- Nombre del rubro --}}
                    <input type="text"
                           :name="`sections[${sIdx}][lines][${lIdx}][name]`"
                           x-model="line.name"
                           placeholder="Nombre del concepto"
                           class="w-full min-w-0 bg-transparent text-[14px] text-[var(--text-700)] outline-none
                                  placeholder-[var(--text-400)]
                                  border-b border-transparent focus:border-[var(--color-primary)] py-0.5"/>

                    {{-- Variable de proyección --}}
                    <div class="flex items-center gap-1.5 min-w-0">
                        <select :name="`sections[${sIdx}][lines][${lIdx}][projection_driver]`"
                                x-model="line.driver"
                                class="flex-1 min-w-0 bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] px-2 py-1.5
                                       text-[12px] text-[var(--text-700)] outline-none cursor-pointer
                                       focus:ring-1 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]">
                            @foreach($driverShort as $dVal => $dLabel)
                            <option value="{{ $dVal }}">{{ $dLabel }}</option>
                            @endforeach
                        </select>
                        <template x-if="line.driver === 'custom_pct'">
                            <div class="relative flex-shrink-0 w-14">
                                <input type="number" step="0.01" min="0" max="100"
                                       :name="`sections[${sIdx}][lines][${lIdx}][custom_rate]`"
                                       x-model="line.customRate"
                                       placeholder="0"
                                       class="w-full bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] pl-1.5 pr-5 py-1.5
                                              text-[12px] text-right outline-none tabular-nums
                                              focus:ring-1 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]"/>
                                <span class="absolute right-1.5 top-1/2 -translate-y-1/2 text-[10px] text-[var(--text-400)]">%</span>
                            </div>
                        </template>
                    </div>

                    {{-- Valor base (COP) --}}
                    <div>
                        <input type="text"
                               x-model="line.displayValue"
                               @focus="onValueFocus(line, $event)"
                               @blur="onValueBlur(line, $event)"
                               placeholder="$ 0"
                               class="w-full bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-control)] px-3 py-1.5
                                      text-[14px] font-semibold text-right text-[var(--text-900)] outline-none
                                      focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]
                                      placeholder-[var(--text-400)] tabular-nums"/>
                        <input type="hidden"
                               :name="`sections[${sIdx}][lines][${lIdx}][base_value]`"
                               :value="line.baseValue"/>
                    </div>

                    {{-- Eliminar --}}
                    <button type="button" @click="removeLine(sIdx, lIdx)"
                            class="w-6 h-6 flex items-center justify-center rounded-[var(--radius-control)]
                                   text-[var(--text-400)] hover:text-[var(--color-danger)] hover:bg-[var(--color-danger-bg)]
                                   opacity-0 group-hover:opacity-100">
                        <x-lucide-x class="w-3.5 h-3.5" />
                    </button>

                </div>
            </template>

            {{-- Sección vacía --}}
            <template x-if="section.lines.length === 0">
                <div class="px-5 py-4 text-[12px] text-[var(--text-400)]">
                    Presiona <span class="font-medium text-[var(--text-500)]">+ Rubro</span> para agregar el primer concepto.
                </div>
            </template>

        </div>
    </template>

</div>

{{-- ── Acciones ────────────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between gap-3">
    <a href="{{ $preClient ? route('financial.client', $preClient) : route('financial.index') }}"
       class="h-10 flex items-center px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)]">
        Cancelar
    </a>
    <button type="submit"
            class="inline-flex items-center gap-[6px] h-10 px-6 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium rounded-[var(--radius-control)]">
        <x-lucide-check-circle class="w-4 h-4" />
        Guardar presupuesto
    </button>
</div>

</form>

<script>
function budgetForm() {
    const defaults = {!! json_encode($defaultSections) !!};

    return {
        selectedType: '{{ old('type', '') }}',
        sections: [],

        init() {
            if (this.selectedType) this.loadDefaults();
        },

        formatCOP(val) {
            const num = Math.round(parseFloat(val) || 0);
            if (num === 0) return '';
            return '$ ' + num.toLocaleString('es-CO');
        },

        parseCOP(str) {
            if (!str && str !== 0) return 0;
            return parseFloat(String(str).replace(/\s/g, '').replace(/\$/g, '').replace(/\./g, '').replace(',', '.')) || 0;
        },

        onValueFocus(line, event) {
            event.target.value = line.baseValue > 0 ? String(line.baseValue) : '';
            event.target.select();
        },

        onValueBlur(line, event) {
            line.baseValue    = this.parseCOP(event.target.value);
            line.displayValue = this.formatCOP(line.baseValue);
        },

        loadDefaults() {
            if (!this.selectedType || !defaults[this.selectedType]) {
                this.sections = [];
                return;
            }
            this.sections = defaults[this.selectedType].map(s => ({
                name: s.name,
                lines: s.lines.map(l => ({
                    name:         l.name,
                    driver:       l.driver || 'ipc',
                    customRate:   '',
                    baseValue:    0,
                    displayValue: '',
                })),
            }));
        },

        addSection() {
            this.sections.push({ name: 'Nueva sección', lines: [] });
        },

        removeSection(idx) {
            this.sections.splice(idx, 1);
        },

        addLine(sIdx) {
            this.sections[sIdx].lines.push({
                name: '', driver: 'ipc', customRate: '', baseValue: 0, displayValue: '',
            });
        },

        removeLine(sIdx, lIdx) {
            this.sections[sIdx].lines.splice(lIdx, 1);
        },
    };
}
</script>

</x-app-layout>
