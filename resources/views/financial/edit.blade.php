<x-app-layout>
<x-slot name="title">{{ in_array($budget->type, \App\Models\Budget::ESTADO_FINANCIERO_TYPES, true) ? 'Editar estado financiero' : 'Editar presupuesto' }}</x-slot>

@php
    $periodTypes = \App\Models\Budget::PERIOD_TYPES;
    $statusLabels= \App\Models\Budget::STATUS_LABELS;
    $driverShort = [
        'manual'     => 'Manual',
        'fixed'      => 'Fijo',
        'custom_pct' => '% Personalizado',
        'inflation'  => 'Inflación (Datos)',
        'smmlv'      => 'SMMLV (Datos)',
    ];

    $isStatement = in_array($budget->type, \App\Models\Budget::ESTADO_FINANCIERO_TYPES, true);

    $currentPeriodYears = $isStatement
        ? collect(range(0, $budget->periods_count))->map(fn ($i) => $budget->calendarYearForPeriod($i))->all()
        : [];

    $currentSections = $budget->sections->map(fn($s) => [
        'name'           => $s->name,
        'is_outflow'     => $s->is_outflow,
        'statement_role' => $s->statement_role,
        'lines' => $s->lines->map(fn($l) => [
            'name'         => $l->name,
            'driver'       => $l->projection_driver,
            'customRate'   => $l->custom_rate,
            'baseValue'    => $l->getValueForPeriod(0),
            'signNegative' => $l->sign_negative,
            'values'       => $isStatement
                ? collect(range(0, $budget->periods_count))->mapWithKeys(fn($i) => [$i => $l->getValueForPeriod($i)])->all()
                : null,
        ])->values()->all(),
    ])->values()->all();

    $inputClass = 'w-full bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 h-10 text-[14px] text-[var(--text-700)] outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]';
    $labelClass = 'block text-[13px] font-medium text-[var(--text-700)] mb-1.5';

@endphp

{{-- Eliminar vive en su propio <form> para no anidar formularios; su botón
     vive en la barra de acciones de arriba y dispara este form vía el
     atributo HTML `form=""`. --}}
<form method="POST" action="{{ route('financial.destroy', $budget) }}"
      id="deleteBudgetForm"
      x-data=""
      x-on:submit.prevent="if(confirm('¿Eliminar «{{ addslashes($budget->name) }}»? Esta acción no se puede deshacer.')) $el.submit()">
    @csrf @method('DELETE')
</form>

{{-- Barra de acciones --}}
<div class="flex items-center justify-between gap-3 mb-5">
    <a href="{{ route('financial.show', $budget) }}"
       class="h-10 inline-flex items-center gap-1.5 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)]">
        <x-lucide-arrow-left class="w-4 h-4" />
        Cancelar
    </a>
    <div class="flex items-center gap-2">
        <button type="submit" form="deleteBudgetForm"
                class="h-10 inline-flex items-center gap-1.5 px-3.5 rounded-[var(--radius-control)] border bg-[var(--color-danger-bg)] border-[var(--color-danger)] text-[13px] font-medium text-[var(--color-danger)] hover:bg-[var(--color-danger)] hover:text-white">
            <x-lucide-trash-2 class="w-3.5 h-3.5" />
            Eliminar {{ $isStatement ? 'estado financiero' : 'presupuesto' }}
        </button>
    </div>
</div>

{{-- Botón flotante de guardar — siempre visible sin importar el scroll,
     mismo criterio que `financial/statements/edit.blade.php` (a pedido
     explícito del usuario, extendido aquí también a Presupuestos). Sigue
     asociado al formulario por `form="budgetEditForm"` — no necesita vivir
     dentro del `<form>` ni cambiar de lugar en el DOM. --}}
<button type="submit" form="budgetEditForm"
        class="fixed bottom-6 left-6 lg:left-[244px] z-40 w-14 h-14 rounded-full bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white shadow-[var(--shadow-card-hover)] flex items-center justify-center transition-transform hover:scale-105"
        title="Guardar cambios">
    <x-lucide-save class="w-6 h-6" />
</button>

<form method="POST" action="{{ route('financial.update', $budget) }}"
      id="budgetEditForm"
      x-data="editForm()"
      x-init="init()">
@csrf @method('PUT')

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
        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Parametrización</h2>
    </div>

    <div class="px-6 pt-5 pb-4">

        {{-- Fila 1: Período base · Estado · Período / N° --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">

            @unless($isStatement)
            <div>
                <label class="{{ $labelClass }}">
                    Período base <span class="text-[var(--color-danger)]">*</span>
                </label>
                <input type="number" name="base_year" value="{{ old('base_year', $budget->base_year) }}"
                       required min="2000" max="2100"
                       class="{{ $inputClass }} tabular-nums"/>
            </div>
            @else
            <input type="hidden" name="base_year" :value="periodYears[0] || {{ $budget->base_year }}"/>
            @endunless

            <div>
                <label class="{{ $labelClass }}">Estado</label>
                <select name="status" class="{{ $inputClass }}">
                    @foreach($statusLabels as $val => $s)
                    <option value="{{ $val }}" {{ old('status', $budget->status) === $val ? 'selected' : '' }}>{{ $s['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-3 {{ $isStatement ? 'sm:col-span-2 lg:col-span-2' : '' }}">
                <div class="flex-1">
                    <label class="{{ $labelClass }}">
                        Período <span class="text-[var(--color-danger)]">*</span>
                    </label>
                    <select name="period_type" x-model="periodType" @change="clampPeriodsCount()" class="{{ $inputClass }}">
                        @foreach($periodTypes as $val => $label)
                        <option value="{{ $val }}" {{ old('period_type', $budget->period_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @unless($isStatement)
                <template x-if="periodType === 'monthly'">
                    <div class="w-32">
                        <label class="{{ $labelClass }}">Mes de inicio</label>
                        <select name="base_month" x-model.number="baseMonth" @change="clampPeriodsCount()" class="{{ $inputClass }}">
                            @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $mes)
                            <option value="{{ $i + 1 }}" {{ (int) old('base_month', $budget->base_month) === $i + 1 ? 'selected' : '' }}>{{ $mes }}</option>
                            @endforeach
                        </select>
                    </div>
                </template>
                @endunless
                <div class="w-20">
                    <label class="{{ $labelClass }}">
                        N° <span class="text-[var(--color-danger)]">*</span>
                    </label>
                    <input type="number" name="periods_count" x-model.number="periodsCount"
                           @input="clampPeriodsCount()"
                           required min="0" :max="maxPeriodsCount()"
                           class="{{ $inputClass }} text-center tabular-nums"/>
                </div>
            </div>
            @unless($isStatement)
            <template x-if="periodType !== 'annual'">
                <p class="text-[11px] text-[var(--text-400)] mt-1.5">
                    Con esta periodicidad el presupuesto no puede cruzar de un año a otro — máximo <span x-text="maxPeriodsCount() + 1"></span> período(s) desde el período base.
                </p>
            </template>
            @endunless
        </div>

        @if($isStatement)
        {{-- Estados Financieros: períodos a registrar (año editable por período) --}}
        <div class="mb-4 pb-4 border-b border-[var(--border-default)]">
            <label class="{{ $labelClass }} mb-1">Períodos a registrar</label>
            <p class="text-[11px] text-[var(--text-400)] mb-2.5">
                El primer período es el año base. Al aumentar "N°" se agregan los siguientes años en orden — cada año es editable si necesitas una comparación no consecutiva.
            </p>
            <div class="flex items-center gap-3 flex-wrap">
                <template x-for="(year, p) in periodYears" :key="p">
                    <div class="flex-shrink-0 w-[116px] rounded-[var(--radius-card)] border bg-[var(--surface-card)] overflow-hidden transition-shadow"
                         :class="p === 0 ? 'border-[var(--color-primary)] shadow-[var(--shadow-card)]' : 'border-[var(--border-default)] hover:shadow-[var(--shadow-card)]'">
                        <div class="flex items-center justify-center gap-1.5 pt-2.5">
                            <span class="w-[5px] h-[5px] rounded-full flex-shrink-0" :class="p === 0 ? 'bg-[var(--color-primary)]' : 'bg-[var(--border-strong)]'"></span>
                            <span class="text-[10px] font-semibold uppercase tracking-[0.06em]"
                                  :class="p === 0 ? 'text-[var(--color-primary)]' : 'text-[var(--text-400)]'"
                                  x-text="p === 0 ? 'Base' : 'Período ' + p"></span>
                        </div>
                        <template x-if="periodType === 'semiannual'">
                            <p class="text-center text-[9.5px] text-[var(--text-400)] mt-0.5" x-text="'Semestre ' + ((p % 2) + 1)"></p>
                        </template>
                        <template x-if="periodType === 'four_monthly'">
                            <p class="text-center text-[9.5px] text-[var(--text-400)] mt-0.5" x-text="'Cuatrimestre ' + ((p % 3) + 1)"></p>
                        </template>
                        <template x-if="periodType === 'quarterly'">
                            <p class="text-center text-[9.5px] text-[var(--text-400)] mt-0.5" x-text="'Trimestre ' + ((p % 4) + 1)"></p>
                        </template>
                        <input type="number" x-model.number="periodYears[p]"
                               :name="`period_years[${p}]`"
                               min="1900" max="2200" required
                               class="w-full bg-transparent border-0 px-2 pt-1 pb-2.5 text-[18px] font-bold text-center text-[var(--text-900)] outline-none tabular-nums focus:ring-2 focus:ring-inset focus:ring-[var(--color-primary-light)]"/>
                    </div>
                </template>
            </div>
        </div>
        @endif

        {{-- Fila 2: Nombre (Presupuestos) — Estados Financieros se nombran solos --}}
        @unless($isStatement)
        <div class="pb-4 border-t border-[var(--border-default)] pt-4">
            <div class="max-w-md">
                <label class="{{ $labelClass }}">
                    Nombre del presupuesto <span class="text-[var(--color-danger)]">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $budget->name) }}" required maxlength="200"
                       class="{{ $inputClass }}"/>
            </div>
        </div>
        @else
        <input type="hidden" name="name" :value="statementName()"/>
        @endif

        @if($budget->type === 'esf')
        <div class="pt-4 border-t border-[var(--border-default)]">
            <label class="{{ $labelClass }}">Vincular con Estado de Resultados (ERI)</label>
            <select name="linked_counterpart_budget_id" class="{{ $inputClass }} max-w-md">
                <option value="">Sin vincular</option>
                @foreach($siblingBudgets->where('type', 'eri') as $eb)
                <option value="{{ $eb->id }}" {{ (string) old('linked_counterpart_budget_id', $budget->linked_counterpart_budget_id) === (string) $eb->id ? 'selected' : '' }}>{{ $eb->name }}</option>
                @endforeach
            </select>
            <p class="text-[11px] text-[var(--text-400)] mt-1">
                Al vincular, "{{ \App\Models\Budget::ESF_UTILIDAD_LINE }}" se auto-completa con la utilidad neta del ERI al Proyectar.
            </p>
        </div>
        @endif
        @if($budget->type === 'eri')
        <div class="pt-4 border-t border-[var(--border-default)]">
            <label class="{{ $labelClass }}">Vincular con Estado de Situación Financiera (ESF)</label>
            <select name="linked_counterpart_budget_id" class="{{ $inputClass }} max-w-md">
                <option value="">Sin vincular</option>
                @foreach($siblingBudgets->where('type', 'esf') as $sb)
                <option value="{{ $sb->id }}" {{ (string) old('linked_counterpart_budget_id', $budget->linked_counterpart_budget_id) === (string) $sb->id ? 'selected' : '' }}>{{ $sb->name }}</option>
                @endforeach
            </select>
            <p class="text-[11px] text-[var(--text-400)] mt-1">
                Al vincular, "{{ \App\Models\Budget::ERI_DEPRECIACION_GASTO_LINE }}" se auto-completa con la porción del período de la depreciación acumulada del ESF.
            </p>
        </div>
        @endif

    </div>
</div>

{{-- ── BLOQUE 2: Secciones y rubros ─────────────────────────────────────── --}}
<div class="mb-5">

    <div class="flex items-center justify-between mb-3">
        <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">
            {{ $isStatement ? 'Estructura del estado financiero (rubros NIIF)' : 'Estructura del presupuesto' }}
        </p>
        @unless($isStatement)
        <button type="button" @click="addSection()"
                class="inline-flex items-center gap-1.5 px-3 h-8 bg-[var(--surface-subtle)] border border-[var(--border-default)] hover:bg-[var(--surface-muted)] text-[var(--text-700)] text-[12px] font-medium rounded-[var(--radius-control)]">
            <x-lucide-plus class="w-3 h-3" />
            Nueva sección
        </button>
        @endunless
    </div>

    @if($isStatement)
    <p class="text-[11px] text-[var(--text-400)] mb-3">
        Las secciones siguen la estructura NIIF y no se pueden renombrar ni reclasificar — agrega o quita rubros libremente dentro de cada una.
    </p>
    @else
    {{-- Aviso de recálculo --}}
    <div class="flex items-center gap-2 bg-[var(--color-warning-bg)] border border-[#FCD34D] rounded-[var(--radius-control)] px-4 py-2.5 mb-3 text-[12px] text-[var(--color-warning-text)]">
        <x-lucide-alert-triangle class="w-3.5 h-3.5 flex-shrink-0" />
        Al guardar, las proyecciones se recalculan automáticamente con los nuevos valores base.
    </div>
    @endif

    {{-- Una tarjeta por sección (Ventas / Gastos Admón y Ventas / Flujo de Caja) --}}
    @unless($isStatement)
    <template x-for="(section, sIdx) in sections" :key="sIdx">
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] overflow-hidden mb-3 shadow-[var(--shadow-card)]">

            {{-- Cabecera de sección --}}
            <div class="bg-[var(--surface-subtle)] border-b border-[var(--border-default)] px-5 py-3 flex items-center justify-between gap-4">
                <input type="text"
                       :name="`sections[${sIdx}][name]`"
                       x-model="section.name"
                       placeholder="Nombre de la sección"
                       class="flex-1 bg-transparent text-[14px] font-semibold text-[var(--text-700)] uppercase tracking-wide border-none outline-none placeholder-[var(--text-400)] min-w-0"/>
                <input type="hidden" :name="`sections[${sIdx}][is_outflow]`" :value="section.is_outflow ? 1 : 0"/>
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($budget->type === 'flujo_caja')
                    <label class="flex items-center gap-1.5 text-[11px] text-[var(--text-500)] flex-shrink-0" title="Los renglones de esta sección restan del saldo en vez de sumar">
                        <input type="checkbox" x-model="section.is_outflow"/>
                        Es salida
                    </label>
                    @endif
                    <button type="button" @click="addLine(sIdx)"
                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-[var(--surface-subtle)] border border-[var(--border-default)] hover:bg-[var(--surface-muted)] text-[var(--text-700)] text-[11px] font-medium rounded-[var(--radius-control)]">
                        <x-lucide-plus class="w-2.5 h-2.5" />
                        Rubro
                    </button>
                    <button type="button" @click="removeSection(sIdx)"
                            class="w-6 h-6 flex items-center justify-center text-[var(--text-400)] hover:text-[var(--color-danger)] hover:bg-[var(--color-danger-bg)] rounded-[var(--radius-control)]">
                        <x-lucide-x class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>

            {{-- Filas de rubros --}}
            <template x-for="(line, lIdx) in section.lines" :key="lIdx">
                <div class="flex flex-wrap items-center gap-2 px-5 py-2.5 border-b border-[var(--surface-muted)] hover:bg-[var(--surface-subtle)] group transition-opacity"
                     draggable="true"
                     @dragstart="lineDragStart(sIdx, lIdx, $event)"
                     @dragover.prevent="lineDragOver(sIdx, lIdx)"
                     @dragend="lineDragEnd()"
                     :class="isDraggingLine(sIdx, lIdx) ? 'opacity-40' : ''">

                    <span class="cursor-grab active:cursor-grabbing text-[var(--text-400)] hover:text-[var(--text-700)] flex-shrink-0" title="Arrastra para reordenar">
                        <x-lucide-grip-vertical class="w-3.5 h-3.5" />
                    </span>

                    <input type="text"
                           :name="`sections[${sIdx}][lines][${lIdx}][name]`"
                           x-model="line.name"
                           placeholder="Nombre del concepto"
                           class="flex-1 min-w-[160px] bg-transparent text-[14px] text-[var(--text-700)] outline-none
                                  placeholder-[var(--text-400)]
                                  border-b border-transparent focus:border-[var(--color-primary)] py-0.5"/>

                    @if($isStatement)
                    <input type="hidden" :name="`sections[${sIdx}][lines][${lIdx}][projection_driver]`" value="manual"/>
                    @else
                    <select :name="`sections[${sIdx}][lines][${lIdx}][projection_driver]`"
                            x-model="line.driver"
                            class="w-40 bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] px-2 py-1.5
                                   text-[12px] text-[var(--text-700)] outline-none cursor-pointer
                                   focus:ring-1 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]">
                        @foreach($driverShort as $dVal => $dLabel)
                        <option value="{{ $dVal }}">{{ $dLabel }}</option>
                        @endforeach
                    </select>
                    @endif

                    <template x-if="line.driver === 'custom_pct'">
                        <div class="relative flex-shrink-0 w-16">
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

                    @unless($isStatement)
                    <div>
                        <input type="text"
                               x-money="line.baseValue"
                               placeholder="$ 0"
                               class="w-32 bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-control)] px-3 py-1.5
                                      text-[14px] font-semibold text-right text-[var(--text-900)] outline-none
                                      focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]
                                      placeholder-[var(--text-400)] tabular-nums"/>
                    </div>
                    @endunless

                    <input type="hidden" :name="`sections[${sIdx}][lines][${lIdx}][base_value]`" :value="line.baseValue"/>
                    <input type="hidden" :name="`sections[${sIdx}][lines][${lIdx}][sign_negative]`" :value="line.signNegative ? 1 : 0"/>

                    <button type="button" @click="removeLine(sIdx, lIdx)"
                            class="w-6 h-6 flex items-center justify-center rounded-[var(--radius-control)]
                                   text-[var(--text-400)] hover:text-[var(--color-danger)] hover:bg-[var(--color-danger-bg)]
                                   opacity-0 group-hover:opacity-100 flex-shrink-0">
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
    @endunless

    @if($isStatement)
    {{-- Estados Financieros: estructura fija (NIIF), tabla compacta tipo hoja de cálculo --}}
    <template x-for="(section, sIdx) in sections" :key="sIdx">
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] overflow-hidden mb-3 shadow-[var(--shadow-card)]">

            {{-- Cabecera de sección: nombre y rol fijos (no editables) --}}
            <div class="bg-[var(--surface-subtle)] border-b border-[var(--border-default)] px-4 py-2.5 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide truncate" x-text="section.name"></span>
                    <span class="text-[10.5px] px-2 py-1 rounded-[var(--radius-control)] bg-[var(--color-primary-light)] text-[var(--color-primary)] font-medium flex-shrink-0"
                          x-text="(selectedType === 'esf' ? esfRoles : eriRoles)[section.statementRole]"></span>
                </div>
                <input type="hidden" :name="`sections[${sIdx}][name]`" :value="section.name"/>
                <input type="hidden" :name="`sections[${sIdx}][statement_role]`" :value="section.statementRole"/>
                <input type="hidden" :name="`sections[${sIdx}][is_outflow]`" value="0"/>
                <button type="button" @click="addLine(sIdx)"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-[var(--surface-subtle)] border border-[var(--border-default)] hover:bg-[var(--surface-muted)] text-[var(--text-700)] text-[11px] font-medium rounded-[var(--radius-control)] flex-shrink-0">
                    <x-lucide-plus class="w-3 h-3" />
                    Rubro
                </button>
            </div>

            <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="text-[10px] text-[var(--text-400)] uppercase tracking-[0.05em]">
                        <th class="text-left px-4 py-2 font-medium whitespace-nowrap">Concepto</th>
                        <template x-for="p in periodsArray()" :key="p">
                            <th class="text-right px-2 py-2 font-medium whitespace-nowrap" style="width:220px" x-text="p === 0 ? 'Base' : 'Período ' + p"></th>
                        </template>
                        <th class="w-7"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(line, lIdx) in section.lines" :key="lIdx">
                        <tr class="border-t border-[var(--surface-muted)] hover:bg-[var(--surface-subtle)] group">
                            <td class="px-4 py-1.5">
                                <input type="text"
                                       :name="`sections[${sIdx}][lines][${lIdx}][name]`"
                                       x-model="line.name"
                                       placeholder="Nombre del concepto"
                                       class="w-full min-w-[180px] bg-transparent text-[13px] text-[var(--text-700)] outline-none border-b border-transparent focus:border-[var(--color-primary)] py-1"/>
                            </td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="px-1.5 py-1.5">
                                    <input type="text" inputmode="decimal"
                                           x-model="line.valuesDisplay[p]"
                                           @focus="onCellFocus(line, p, $event)"
                                           @input="onCellInput(line, p, $event)"
                                           @blur="onCellBlur(line, p, $event)"
                                           :disabled="isAutoCell(line.name, p)"
                                           :placeholder="isAutoCell(line.name, p) ? 'Auto' : '0'"
                                           :class="isAutoCell(line.name, p)
                                                ? 'bg-[var(--surface-muted)] text-[var(--text-400)] italic cursor-not-allowed'
                                                : 'bg-[var(--surface-subtle)] text-[var(--text-900)]'"
                                           class="w-full border-0 rounded-[var(--radius-control)] px-2 py-1.5 text-[13px] text-right outline-none tabular-nums focus:bg-[var(--color-primary-light)] focus:ring-2 focus:ring-[var(--color-primary-light)]"/>
                                </td>
                            </template>
                            <td class="px-1 py-1.5 text-center">
                                <input type="hidden" :name="`sections[${sIdx}][lines][${lIdx}][projection_driver]`" value="manual"/>
                                <input type="hidden" :name="`sections[${sIdx}][lines][${lIdx}][sign_negative]`" :value="line.signNegative ? 1 : 0"/>
                                <template x-for="p in periodsArray()" :key="p">
                                    <input type="hidden" :name="`sections[${sIdx}][lines][${lIdx}][values][${p}]`" :value="line.values[p]" :disabled="isAutoCell(line.name, p)"/>
                                </template>
                                <button type="button" @click="removeLine(sIdx, lIdx)"
                                        class="w-6 h-6 inline-flex items-center justify-center rounded-[var(--radius-control)]
                                               text-[var(--text-400)] hover:text-[var(--color-danger)] hover:bg-[var(--color-danger-bg)]
                                               opacity-0 group-hover:opacity-100">
                                    <x-lucide-x class="w-3.5 h-3.5" />
                                </button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="section.lines.length === 0">
                        <tr>
                            <td class="px-4 py-3.5 text-[13px] text-[var(--text-400)]" :colspan="periodsArray().length + 2">
                                Presiona <span class="font-medium text-[var(--text-500)]">+ Rubro</span> para agregar el primer concepto.
                            </td>
                        </tr>
                    </template>
                </tbody>
                @if(in_array($budget->type, ['esf', 'eri'], true))
                {{-- ESF/ERI: total de la sección justo debajo de su último rubro --}}
                <tfoot>
                    <template x-if="section.statementRole === 'activo_corriente'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">TOTAL ACTIVO CORRIENTE</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod(section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'activo_no_corriente'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">TOTAL ACTIVO NO CORRIENTE</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod(section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'activo_no_corriente'">
                        <tr class="border-t-2 border-[var(--color-primary)]/30 bg-[var(--color-primary-light)]">
                            <td class="px-4 py-2.5 text-[14px] font-bold text-[var(--text-900)] whitespace-nowrap">TOTAL ACTIVO</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2.5 text-[14px] font-bold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(totalActivoForPeriod(p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'pasivo_corriente'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">TOTAL PASIVO CORRIENTE</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod(section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'pasivo_no_corriente'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">TOTAL PASIVO NO CORRIENTE</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod(section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'pasivo_no_corriente'">
                        <tr class="border-t-2 border-[var(--color-primary)]/30 bg-[var(--color-primary-light)]">
                            <td class="px-4 py-2.5 text-[14px] font-bold text-[var(--text-900)] whitespace-nowrap">TOTAL PASIVO</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2.5 text-[14px] font-bold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(totalPasivoForPeriod(p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'patrimonio'">
                        <tr class="border-t-2 border-[var(--color-primary)]/30 bg-[var(--color-primary-light)]">
                            <td class="px-4 py-2.5 text-[14px] font-bold text-[var(--text-900)] whitespace-nowrap">TOTAL PATRIMONIO</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2.5 text-[14px] font-bold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod(section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'patrimonio'">
                        <tr class="border-t-2 border-[var(--color-primary)]/30 bg-[var(--color-primary-light)]">
                            <td class="px-4 py-2.5 text-[14px] font-bold text-[var(--text-900)] whitespace-nowrap">TOTAL PASIVO + PATRIMONIO</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2.5 text-[14px] font-bold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(totalPasivoPatrimonioForPeriod(p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'patrimonio'">
                        <tr class="border-t border-[var(--border-default)]">
                            <td class="px-4 py-2.5 text-[13px] font-semibold text-[var(--text-700)] whitespace-nowrap">Diferencia (Activo − Pasivo − Patrimonio)</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2.5 text-[13px] font-semibold tabular-nums whitespace-nowrap"
                                    :class="Math.abs(diferenciaForPeriod(p)) < 1 ? 'text-[var(--color-success-text)]' : 'text-[var(--color-danger-text)]'"
                                    x-text="Math.abs(diferenciaForPeriod(p)) < 1 ? 'Cuadra' : ('$ ' + formatGridNumber(diferenciaForPeriod(p)))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'ingresos_operacionales'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">VENTAS NETAS</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod(section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'costo_ventas'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">COSTO DE VENTAS</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod(section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'costo_ventas'">
                        <tr class="border-t-2 border-[var(--color-primary)]/30 bg-[var(--color-primary-light)]">
                            <td class="px-4 py-2.5 text-[14px] font-bold text-[var(--text-900)] whitespace-nowrap">UTILIDAD BRUTA</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2.5 text-[14px] font-bold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(utilidadBrutaForPeriod(p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'gastos_administracion'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">TOTAL GASTOS ADMINISTRACIÓN</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod(section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'gastos_ventas'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">TOTAL GASTOS DE VENTAS</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod(section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'gastos_ventas'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">TOTAL GASTOS OPERACIONALES</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(totalGastosOpForPeriod(p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'gastos_ventas'">
                        <tr class="border-t-2 border-[var(--color-primary)]/30 bg-[var(--color-primary-light)]">
                            <td class="px-4 py-2.5 text-[14px] font-bold text-[var(--text-900)] whitespace-nowrap">UTILIDAD OPERACIONAL (EBIT)</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2.5 text-[14px] font-bold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(ebitForPeriod(p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'gastos_ventas'">
                        <tr class="border-t-2 border-[var(--color-primary)]/30 bg-[var(--color-primary-light)]">
                            <td class="px-4 py-2.5 text-[14px] font-bold text-[var(--text-900)] whitespace-nowrap">EBITDA</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2.5 text-[14px] font-bold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(ebitdaForPeriod(p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'ingresos_no_operacionales'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">TOTAL INGRESOS NO OPERACIONALES</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod(section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'gastos_no_operacionales'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">TOTAL GASTOS NO OPERACIONALES</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod(section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'gastos_no_operacionales'">
                        <tr class="border-t-2 border-[var(--color-primary)]/30 bg-[var(--color-primary-light)]">
                            <td class="px-4 py-2.5 text-[14px] font-bold text-[var(--text-900)] whitespace-nowrap">UTILIDAD ANTES DE IMPUESTOS (UAI)</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2.5 text-[14px] font-bold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(uaiForPeriod(p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'impuestos'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">TOTAL IMPUESTO DE RENTA</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod(section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'impuestos'">
                        <tr class="border-t-2 border-[var(--color-primary)]/30 bg-[var(--color-primary-light)]">
                            <td class="px-4 py-2.5 text-[14px] font-bold text-[var(--text-900)] whitespace-nowrap">UTILIDAD NETA DEL PERÍODO</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2.5 text-[14px] font-bold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(utilidadNetaForPeriod(p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'ori'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">TOTAL ORI</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod(section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'ori'">
                        <tr class="border-t-2 border-[var(--color-primary)]/30 bg-[var(--color-primary-light)]">
                            <td class="px-4 py-2.5 text-[14px] font-bold text-[var(--text-900)] whitespace-nowrap">RESULTADO INTEGRAL TOTAL DEL PERÍODO</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2.5 text-[14px] font-bold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(resultadoIntegralForPeriod(p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                </tfoot>
                @endif
            </table>
            </div>
        </div>
    </template>
    @endif

</div>

</form>

<script>
function editForm() {
    const raw = {!! json_encode($currentSections) !!};

    return {
        selectedType: '{{ $budget->type }}',
        isStatementType: {{ $isStatement ? 'true' : 'false' }},
        esfRoles: @json(\App\Models\Budget::ESF_SECTION_ROLES),
        eriRoles: @json(\App\Models\Budget::ERI_SECTION_ROLES),
        periodsCount: {{ (int) $budget->periods_count }},
        periodType: '{{ $budget->period_type }}',
        baseMonth: {{ (int) old('base_month', $budget->base_month) }},
        periodYears: @json($currentPeriodYears),
        autoLines: [@json(\App\Models\Budget::ESF_UTILIDAD_LINE), @json(\App\Models\Budget::ERI_DEPRECIACION_GASTO_LINE)],
        sections: [],
        draggingLine: null,

        // Presupuestos con periodicidad distinta a anual nunca cruzan de un
        // año al siguiente: mensual respeta los meses que quedan desde el
        // mes de inicio elegido; trimestral/semestral/cuatrimestral siempre
        // inician en enero, así que su tope es fijo (un año completo).
        maxPeriodsCount() {
            if (this.isStatementType) return 10;
            if (this.periodType === 'monthly') return Math.max(0, 12 - (this.baseMonth || 1));
            const perYear = { four_monthly: 3, quarterly: 4, semiannual: 2 }[this.periodType];
            return perYear ? perYear - 1 : 10; // anual: sin límite de un año
        },

        clampPeriodsCount() {
            const max = this.maxPeriodsCount();
            if (this.periodsCount > max) this.periodsCount = max;
            if (this.isStatementType) this.resizePeriodYears();
        },

        // Estados Financieros: mantiene `periodYears` alineado con `periodsCount`.
        // Agrega años consecutivos al final o recorta desde el final — nunca
        // toca años ya editados en medio del arreglo.
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

        // Estados Financieros no se nombran a mano: el nombre se deriva del
        // tipo y del rango de años de "Períodos a registrar".
        statementName() {
            const base = this.selectedType === 'eri' ? 'Estado de Resultados' : 'Estado de Situación Financiera';
            if (!this.periodYears.length) return base;
            const first = this.periodYears[0];
            const last = this.periodYears[this.periodYears.length - 1];
            return first === last ? `${base} ${first}` : `${base} ${first}-${last}`;
        },

        // ESF: totales en vivo (misma lógica que Budget::roleTotals() en el backend
        // — suma los rubros de cada sección restando los marcados signo negativo).
        sectionTotalForPeriod(section, p) {
            let sum = 0;
            section.lines.forEach(line => {
                const raw = line.values[p];
                const v = (raw === '' || raw === null || raw === undefined) ? 0 : (parseFloat(raw) || 0);
                sum += line.signNegative ? -v : v;
            });
            return sum;
        },

        roleTotalForPeriod(role, p) {
            let sum = 0;
            this.sections.filter(s => s.statementRole === role).forEach(s => sum += this.sectionTotalForPeriod(s, p));
            return sum;
        },

        totalActivoForPeriod(p) {
            return this.roleTotalForPeriod('activo_corriente', p) + this.roleTotalForPeriod('activo_no_corriente', p);
        },

        totalPasivoForPeriod(p) {
            return this.roleTotalForPeriod('pasivo_corriente', p) + this.roleTotalForPeriod('pasivo_no_corriente', p);
        },

        totalPasivoPatrimonioForPeriod(p) {
            return this.totalPasivoForPeriod(p) + this.roleTotalForPeriod('patrimonio', p);
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
            this.sections.forEach(s => s.lines.forEach(l => {
                if (l.name === 'Depreciaciones y amortizaciones') {
                    const raw = l.values[p];
                    sum += (raw === '' || raw === null || raw === undefined) ? 0 : (parseFloat(raw) || 0);
                }
            }));
            return sum;
        },

        utilidadBrutaForPeriod(p) {
            return this.roleTotalForPeriod('ingresos_operacionales', p) + this.roleTotalForPeriod('costo_ventas', p);
        },

        totalGastosOpForPeriod(p) {
            return this.roleTotalForPeriod('gastos_administracion', p) + this.roleTotalForPeriod('gastos_ventas', p);
        },

        ebitForPeriod(p) {
            return this.utilidadBrutaForPeriod(p) + this.totalGastosOpForPeriod(p);
        },

        ebitdaForPeriod(p) {
            return this.ebitForPeriod(p) + this.depreciacionForPeriod(p);
        },

        uaiForPeriod(p) {
            return this.ebitForPeriod(p) + this.roleTotalForPeriod('ingresos_no_operacionales', p) + this.roleTotalForPeriod('gastos_no_operacionales', p);
        },

        utilidadNetaForPeriod(p) {
            return this.uaiForPeriod(p) + this.roleTotalForPeriod('impuestos', p);
        },

        resultadoIntegralForPeriod(p) {
            return this.utilidadNetaForPeriod(p) + this.roleTotalForPeriod('ori', p);
        },

        init() {
            this.sections = raw.map(s => ({
                name: s.name,
                is_outflow: s.is_outflow || false,
                statementRole: s.statement_role || null,
                lines: s.lines.map(l => ({
                    name:          l.name,
                    driver:        l.driver,
                    customRate:    l.customRate ?? '',
                    signNegative:  !!l.signNegative,
                    baseValue:     l.baseValue ?? 0,
                    displayValue:  this.formatCOP(l.baseValue ?? 0),
                    values:        l.values || {},
                    valuesDisplay: this.buildValuesDisplay(l.values || {}),
                })),
            }));
        },

        buildValuesDisplay(values) {
            const display = {};
            Object.keys(values).forEach(p => { display[p] = this.formatGridNumber(values[p]); });
            return display;
        },

        periodsArray() {
            return Array.from({ length: (this.periodsCount || 0) + 1 }, (_, i) => i);
        },

        isAutoCell(lineName, period) {
            if (this.autoLines.includes(lineName)) return true;
            return false;
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

        // Estados Financieros: formato compacto sin "$" para las celdas de la tabla.
        formatGridNumber(val) {
            if (val === '' || val === null || val === undefined) return '';
            const num = Math.round(parseFloat(val) * 100) / 100;
            if (isNaN(num)) return '';
            return num.toLocaleString('es-CO');
        },

        onCellFocus(line, p, event) {
            const v = line.values[p];
            event.target.value = (v !== '' && v !== null && v !== undefined) ? String(v) : '';
            event.target.select();
        },

        // Se sincroniza en cada tecla (no solo al perder el foco) para que el
        // valor quede correcto incluso si el usuario envía el formulario con
        // Enter sin que el campo llegue a perder el foco.
        onCellInput(line, p, event) {
            const raw = event.target.value.trim();
            line.values[p] = raw === '' ? '' : this.parseCOP(raw);
        },

        onCellBlur(line, p, event) {
            line.valuesDisplay[p] = this.formatGridNumber(line.values[p]);
        },

        addSection() {
            const roles = this.selectedType === 'esf' ? this.esfRoles : (this.selectedType === 'eri' ? this.eriRoles : null);
            this.sections.push({
                name: 'Nueva sección', is_outflow: false,
                statementRole: roles ? Object.keys(roles)[0] : null,
                lines: [],
            });
        },

        removeSection(idx) {
            this.sections.splice(idx, 1);
        },

        addLine(sIdx) {
            this.sections[sIdx].lines.push({
                name: '', driver: 'manual', customRate: '', signNegative: false,
                baseValue: 0, displayValue: '',
                values: {}, valuesDisplay: {},
            });
        },

        removeLine(sIdx, lIdx) {
            this.sections[sIdx].lines.splice(lIdx, 1);
        },

        // Reordenar rubros dentro de una sección arrastrando la fila —
        // reordena en vivo mientras se arrastra sobre otra fila (mismo
        // criterio que un sortable list clásico); el orden final del array
        // es lo que persiste como `sort_order` al guardar.
        lineDragStart(sIdx, lIdx, event) {
            this.draggingLine = { sIdx, lIdx };
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(lIdx));
        },

        lineDragOver(sIdx, lIdx) {
            if (!this.draggingLine || this.draggingLine.sIdx !== sIdx || this.draggingLine.lIdx === lIdx) return;
            const lines = this.sections[sIdx].lines;
            const [moved] = lines.splice(this.draggingLine.lIdx, 1);
            lines.splice(lIdx, 0, moved);
            this.draggingLine.lIdx = lIdx;
        },

        lineDragEnd() {
            this.draggingLine = null;
        },

        isDraggingLine(sIdx, lIdx) {
            return !!this.draggingLine && this.draggingLine.sIdx === sIdx && this.draggingLine.lIdx === lIdx;
        },
    };
}
</script>

</x-app-layout>
