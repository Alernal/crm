{{-- Editor de estructura NIIF para un solo estado (ESF o ERI) dentro del
     formulario combinado del par — parametrizado por $prefix ('esf'|'eri')
     para que Alpine lea/escriba en `{{ $prefix }}Sections` y los campos se
     envíen como `{{ $prefix }}_sections[...]`. Reutilizado tal cual por
     crear y editar. --}}
<div>
    <template x-for="(section, sIdx) in {{ $prefix }}Sections" :key="sIdx">
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] overflow-hidden mb-3 shadow-[var(--shadow-card)]">

            {{-- Cabecera de sección: nombre y rol fijos (no editables) --}}
            <div class="bg-[var(--surface-subtle)] border-b border-[var(--border-default)] px-4 py-2.5 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="text-[13px] font-semibold text-[var(--text-700)] uppercase tracking-wide truncate" x-text="section.name"></span>
                    <span class="text-[10.5px] px-2 py-1 rounded-[var(--radius-control)] bg-[var(--color-primary-light)] text-[var(--color-primary)] font-medium flex-shrink-0"
                          x-text="{{ $prefix }}Roles[section.statementRole]"></span>
                </div>
                <input type="hidden" :name="`{{ $prefix }}_sections[${sIdx}][name]`" :value="section.name"/>
                <input type="hidden" :name="`{{ $prefix }}_sections[${sIdx}][statement_role]`" :value="section.statementRole"/>
                <button type="button" @click="addLine('{{ $prefix }}', sIdx)"
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
                            <th class="text-right px-2 py-2 font-medium whitespace-nowrap" style="width:220px">
                                <template x-if="periodType === 'annual'">
                                    <span x-text="'Período ' + (p + 1)"></span>
                                </template>
                                <template x-if="periodType !== 'annual'">
                                    <input type="text" x-model="periodLabels[p]"
                                           :name="`period_labels[${p}]`"
                                           maxlength="100"
                                           class="w-full bg-transparent border-0 border-b border-dashed border-[var(--border-strong)] text-right text-[10px] font-medium uppercase tracking-[0.05em] text-[var(--text-700)] outline-none px-0.5 py-0.5 focus:border-solid focus:border-[var(--color-primary)]"/>
                                </template>
                            </th>
                        </template>
                        <th class="w-7"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(line, lIdx) in section.lines" :key="lIdx">
                        <tr class="border-t border-[var(--surface-muted)] hover:bg-[var(--surface-subtle)] group">
                            <td class="px-4 py-1.5">
                                <input type="text"
                                       :name="`{{ $prefix }}_sections[${sIdx}][lines][${lIdx}][name]`"
                                       x-model="line.name"
                                       placeholder="Nombre del concepto"
                                       class="w-full min-w-[180px] bg-transparent text-[13px] text-[var(--text-700)] outline-none border-b border-transparent focus:border-[var(--color-primary)] py-1"/>
                            </td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="px-1.5 py-1.5">
                                    <template x-if="!isAutoCell(line.name, p)">
                                        <input type="text" inputmode="decimal"
                                               x-model="line.valuesDisplay[p]"
                                               @focus="onCellFocus(line, p, $event)"
                                               @input="onCellInput(line, p, $event)"
                                               @blur="onCellBlur(line, p, $event)"
                                               placeholder="0"
                                               class="w-full border-0 rounded-[var(--radius-control)] px-2 py-1.5 text-[13px] text-right outline-none tabular-nums bg-[var(--surface-subtle)] text-[var(--text-500)] focus:bg-[var(--color-primary-light)] focus:ring-2 focus:ring-[var(--color-primary-light)]"/>
                                    </template>
                                    <template x-if="isAutoCell(line.name, p)">
                                        <input type="text" disabled
                                               :value="formatGridNumber(autoCellDisplayValue(line.name, p))"
                                               placeholder="Auto"
                                               title="Se calcula automáticamente en tiempo real a partir del otro estado vinculado"
                                               class="w-full border-0 rounded-[var(--radius-control)] px-2 py-1.5 text-[13px] text-right outline-none tabular-nums bg-[var(--surface-muted)] text-[var(--text-400)] italic cursor-not-allowed"/>
                                    </template>
                                </td>
                            </template>
                            <td class="px-1 py-1.5 text-center">
                                <input type="hidden" :name="`{{ $prefix }}_sections[${sIdx}][lines][${lIdx}][sign_negative]`" :value="line.signNegative ? 1 : 0"/>
                                <template x-for="p in periodsArray()" :key="p">
                                    <input type="hidden" :name="`{{ $prefix }}_sections[${sIdx}][lines][${lIdx}][values][${p}]`" :value="line.values[p]" :disabled="isAutoCell(line.name, p)"/>
                                </template>
                                <button type="button" @click="removeLine('{{ $prefix }}', sIdx, lIdx)"
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
                {{-- Total de la sección justo debajo de su último rubro, encadenando
                     hasta los subtotales del estado — el rol de cada sección decide
                     qué fila de totales aplica, así que este bloque sirve igual para
                     ESF y ERI sin necesitar ramas por $prefix. --}}
                <tfoot>
                    <template x-if="section.statementRole === 'activo_corriente'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">TOTAL ACTIVO CORRIENTE</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod('esf', section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'activo_no_corriente'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">TOTAL ACTIVO NO CORRIENTE</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod('esf', section, p))"></td>
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
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod('esf', section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'pasivo_no_corriente'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">TOTAL PASIVO NO CORRIENTE</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod('esf', section, p))"></td>
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
                                <td class="text-right px-2 py-2.5 text-[14px] font-bold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod('esf', section, p))"></td>
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
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod('eri', section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'costo_ventas'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">COSTO DE VENTAS</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod('eri', section, p))"></td>
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
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod('eri', section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'gastos_ventas'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">TOTAL GASTOS DE VENTAS</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod('eri', section, p))"></td>
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
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod('eri', section, p))"></td>
                            </template>
                            <td></td>
                        </tr>
                    </template>
                    <template x-if="section.statementRole === 'gastos_no_operacionales'">
                        <tr class="border-t border-[var(--border-default)] bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2 text-[13px] font-semibold text-[var(--text-900)] whitespace-nowrap">TOTAL GASTOS NO OPERACIONALES</td>
                            <template x-for="p in periodsArray()" :key="p">
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod('eri', section, p))"></td>
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
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod('eri', section, p))"></td>
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
                                <td class="text-right px-2 py-2 text-[13px] font-semibold text-[var(--text-900)] tabular-nums whitespace-nowrap" x-text="'$ ' + formatGridNumber(sectionTotalForPeriod('eri', section, p))"></td>
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
            </table>
            </div>
        </div>
    </template>
</div>
