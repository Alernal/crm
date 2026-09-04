{{-- ══ MODAL: Movimientos de "Real" (trazabilidad) ═══════════════════════
     Requiere entriesModal/openEntriesModal/addEntryDraft/entriesModalTotal/
     saveEntriesModal/formatGridNumber/parseGridNumber/reformatGridInput/
     formatCOP en el x-data raíz (budgetTable(), ver <script> de esta vista). --}}
<div x-show="entriesModal.open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="display:none"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="absolute inset-0 bg-gray-900/50" @click="entriesModal.open = false"></div>

    <div class="relative bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-2xl z-10 max-h-[90vh] overflow-y-auto"
         @click.stop>

        <div class="flex items-center justify-between px-6 py-5 border-b border-[var(--border-default)] sticky top-0 bg-[var(--surface-card)]">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-[var(--color-success-bg)] rounded-[var(--radius-control)] flex items-center justify-center">
                    <x-lucide-list-checks class="w-5 h-5 text-[var(--color-success)]" />
                </div>
                <div>
                    <h2 class="text-[16px] font-bold text-[var(--text-900)]">Movimientos de "Real"</h2>
                    <p class="text-[12px] text-[var(--text-400)] mt-0.5">Registra cada movimiento con su fecha y tercero — la suma reemplaza el valor de la celda al guardar</p>
                </div>
            </div>
            <button @click="entriesModal.open = false"
                    class="p-1.5 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                <x-lucide-x class="w-4 h-4" />
            </button>
        </div>

        <div class="px-6 py-5 space-y-5">

            {{-- Tabla de movimientos --}}
            <div class="border border-[var(--border-default)] rounded-[var(--radius-control)] overflow-hidden">
                <table class="w-full text-[13px]">
                    <thead>
                        <tr class="bg-[var(--surface-subtle)] border-b border-[var(--border-default)]">
                            <th class="text-left px-3 py-2.5 text-[11px] font-semibold uppercase tracking-[0.04em] text-[var(--text-400)]">Fecha</th>
                            <th class="text-left px-3 py-2.5 text-[11px] font-semibold uppercase tracking-[0.04em] text-[var(--text-400)]">Tercero</th>
                            <th class="text-left px-3 py-2.5 text-[11px] font-semibold uppercase tracking-[0.04em] text-[var(--text-400)]">Descripción</th>
                            <th class="text-right px-3 py-2.5 text-[11px] font-semibold uppercase tracking-[0.04em] text-[var(--text-400)]">Valor</th>
                            <th class="w-9"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="entriesModal.loading">
                            <tr><td colspan="5" class="px-3 py-6 text-center text-[13px] text-[var(--text-400)]">Cargando…</td></tr>
                        </template>
                        <template x-if="!entriesModal.loading && entriesModal.entries.length === 0">
                            <tr><td colspan="5" class="px-3 py-6 text-center text-[13px] text-[var(--text-400)]">Sin movimientos todavía.</td></tr>
                        </template>
                        <template x-for="(entry, idx) in entriesModal.entries" :key="idx">
                            <tr class="border-b border-[var(--surface-muted)] last:border-b-0 hover:bg-[var(--surface-subtle)]">
                                <td class="px-3 py-2 text-[var(--text-500)]" x-text="entry.entry_date"></td>
                                <td class="px-3 py-2 text-[var(--text-700)]" x-text="entry.tercero || '—'"></td>
                                <td class="px-3 py-2 text-[var(--text-500)]" x-text="entry.description || '—'"></td>
                                <td class="px-3 py-2 text-right tabular-nums text-[var(--text-900)]" x-text="formatCOP(entry.value)"></td>
                                <td class="px-2 py-2 text-right">
                                    <button type="button" @click="entriesModal.entries.splice(idx, 1)"
                                            class="text-[var(--text-400)] hover:text-[var(--color-danger)]" title="Quitar">
                                        <x-lucide-x class="w-3.5 h-3.5" />
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="bg-[var(--surface-subtle)] border-t border-[var(--border-default)]">
                            <td colspan="3" class="px-3 py-2.5 text-[12px] font-semibold text-[var(--text-700)] uppercase tracking-[0.04em]">Total</td>
                            <td class="px-3 py-2.5 text-right text-[14px] font-bold text-[var(--text-900)] tabular-nums" x-text="formatCOP(entriesModalTotal())"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Agregar movimiento --}}
            <div>
                <p class="text-[13px] font-semibold text-[var(--text-700)] mb-3">Agregar movimiento</p>
                <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
                    <div>
                        <label class="block text-[12px] font-medium text-[var(--text-700)] mb-1.5">Fecha</label>
                        <input type="date" x-model="entriesModal.draftDate"
                               class="w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-2.5 text-[13px] outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]"/>
                    </div>
                    <div>
                        <label class="block text-[12px] font-medium text-[var(--text-700)] mb-1.5">Tercero</label>
                        <input type="text" x-model="entriesModal.draftTercero" placeholder="Ej: Proveedor XYZ"
                               class="w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-2.5 text-[13px] outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]"/>
                    </div>
                    <div>
                        <label class="block text-[12px] font-medium text-[var(--text-700)] mb-1.5">Descripción</label>
                        <input type="text" x-model="entriesModal.draftDescription" placeholder="Opcional"
                               class="w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-2.5 text-[13px] outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]"/>
                    </div>
                    <div>
                        <label class="block text-[12px] font-medium text-[var(--text-700)] mb-1.5">Valor</label>
                        <input type="text" inputmode="decimal" x-model="entriesModal.draftValueDisplay"
                               @input="entriesModal.draftValueDisplay = reformatGridInput($event)"
                               @keydown.enter.prevent="addEntryDraft()"
                               placeholder="0"
                               class="w-full h-10 text-right border border-[var(--border-default)] rounded-[var(--radius-control)] px-2.5 text-[13px] tabular-nums outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]"/>
                    </div>
                    <button type="button" @click="addEntryDraft()"
                            class="h-10 px-3.5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[13px] font-medium flex-shrink-0">
                        <x-lucide-plus class="w-4 h-4" />
                    </button>
                </div>
            </div>

        </div>

        <div class="flex justify-end gap-2 px-6 py-4 border-t border-[var(--border-default)] sticky bottom-0 bg-[var(--surface-card)]">
            <button type="button" @click="entriesModal.open = false"
                    class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                Cancelar
            </button>
            <button type="button" @click="saveEntriesModal()" :disabled="entriesModal.saving"
                    class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] disabled:opacity-50 text-white text-[14px] font-medium">
                <span x-text="entriesModal.saving ? 'Guardando…' : 'Guardar'"></span>
            </button>
        </div>
    </div>
</div>
