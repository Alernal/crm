{{-- ══════════════ Modal — Editar conceptos ══════════════ --}}
<div x-show="conceptos.open"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" style="display:none">
    <div @click="conceptos.open = false" class="fixed inset-0 bg-gray-900/50"></div>
    <div class="flex min-h-full items-start justify-center p-4 pt-10">
        <div class="relative bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-2xl overflow-hidden"
             @click.stop
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-center justify-between px-6 py-5 border-b border-[var(--border-default)]">
                <div>
                    <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Editar conceptos</h2>
                    <p class="text-[12px] text-[var(--text-400)] mt-0.5" x-text="conceptos.row?.employee_name"></p>
                </div>
                <button @click="conceptos.open = false" class="p-2 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                    <x-lucide-x class="w-4 h-4" />
                </button>
            </div>

            <template x-if="conceptos.row">
            <form method="POST" :action="conceptos.row.update_url" class="px-6 py-5 space-y-5 max-h-[70vh] overflow-y-auto">
                @csrf @method('PATCH')

                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1">Días laborados</label>
                    <input type="number" step="0.5" min="0" max="31" name="worked_days" x-model.number="conceptos.form.worked_days" class="{{ $fieldClass }} max-w-[140px]">
                </div>

                <div>
                    <p class="text-[12px] font-semibold text-[var(--text-400)] uppercase tracking-[0.05em] mb-2">Devengado salarial</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-[13px] text-[var(--text-700)] mb-1">Comisiones</label><input type="number" step="0.01" min="0" name="commissions" x-model.number="conceptos.form.commissions" class="{{ $fieldClass }}"></div>
                        <div><label class="block text-[13px] text-[var(--text-700)] mb-1">Bonificaciones salariales</label><input type="number" step="0.01" min="0" name="bonuses_salarial" x-model.number="conceptos.form.bonuses_salarial" class="{{ $fieldClass }}"></div>
                        <div><label class="block text-[13px] text-[var(--text-700)] mb-1">Viáticos permanentes</label><input type="number" step="0.01" min="0" name="per_diem_salarial" x-model.number="conceptos.form.per_diem_salarial" class="{{ $fieldClass }}"></div>
                        <div><label class="block text-[13px] text-[var(--text-700)] mb-1">Otros pagos salariales</label><input type="number" step="0.01" min="0" name="other_salarial" x-model.number="conceptos.form.other_salarial" class="{{ $fieldClass }}"></div>
                    </div>
                </div>

                <div>
                    <p class="text-[12px] font-semibold text-[var(--text-400)] uppercase tracking-[0.05em] mb-2">Devengado no salarial</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-[13px] text-[var(--text-700)] mb-1">Bonificaciones ocasionales</label><input type="number" step="0.01" min="0" name="occasional_bonuses" x-model.number="conceptos.form.occasional_bonuses" class="{{ $fieldClass }}"></div>
                        <div><label class="block text-[13px] text-[var(--text-700)] mb-1">Primas extralegales</label><input type="number" step="0.01" min="0" name="extralegal_premiums" x-model.number="conceptos.form.extralegal_premiums" class="{{ $fieldClass }}"></div>
                        <div><label class="block text-[13px] text-[var(--text-700)] mb-1">Viáticos (no salariales)</label><input type="number" step="0.01" min="0" name="per_diem_no_salarial" x-model.number="conceptos.form.per_diem_no_salarial" class="{{ $fieldClass }}"></div>
                        <div><label class="block text-[13px] text-[var(--text-700)] mb-1">Otros no salariales</label><input type="number" step="0.01" min="0" name="other_no_salarial" x-model.number="conceptos.form.other_no_salarial" class="{{ $fieldClass }}"></div>
                    </div>
                </div>

                <div>
                    <p class="text-[12px] font-semibold text-[var(--text-400)] uppercase tracking-[0.05em] mb-2">Deducciones</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-[13px] text-[var(--text-700)] mb-1">Préstamos a empleados</label><input type="number" step="0.01" min="0" name="loans_deduction" x-model.number="conceptos.form.loans_deduction" class="{{ $fieldClass }}"></div>
                        <div><label class="block text-[13px] text-[var(--text-700)] mb-1">Retención en la fuente</label><input type="number" step="0.01" min="0" name="withholding_tax" x-model.number="conceptos.form.withholding_tax" class="{{ $fieldClass }}"></div>
                        <div><label class="block text-[13px] text-[var(--text-700)] mb-1">Otras deducciones</label><input type="number" step="0.01" min="0" name="other_deductions" x-model.number="conceptos.form.other_deductions" class="{{ $fieldClass }}"></div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2 border-t border-[var(--border-default)]">
                    <button type="submit" class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">Guardar</button>
                    <button type="button" @click="conceptos.open = false" class="h-10 flex items-center px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">Cancelar</button>
                </div>
            </form>
            </template>
        </div>
    </div>
</div>

{{-- ══════════════ Modal — Horas extra ══════════════ --}}
<div x-show="overtime.open"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" style="display:none">
    <div @click="overtime.open = false" class="fixed inset-0 bg-gray-900/50"></div>
    <div class="flex min-h-full items-start justify-center p-4 pt-10">
        <div class="relative bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-3xl overflow-hidden"
             @click.stop
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-center justify-between px-6 py-5 border-b border-[var(--border-default)]">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 bg-[var(--color-primary-light)] rounded-[var(--radius-control)] flex items-center justify-center">
                        <x-lucide-clock class="w-4 h-4 text-[var(--color-primary)]" />
                    </div>
                    <div>
                        <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Horas extra y recargos</h2>
                        <p class="text-[12px] text-[var(--text-400)] mt-0.5" x-text="overtime.row?.employee_name"></p>
                    </div>
                </div>
                <button @click="overtime.open = false" class="p-2 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                    <x-lucide-x class="w-4 h-4" />
                </button>
            </div>

            <template x-if="overtime.row">
            <form method="POST" :action="overtime.row.overtime_url" class="flex flex-col">
                @csrf @method('PUT')

                <div class="px-6 py-5 max-h-[55vh] overflow-y-auto">
                    <template x-if="overtime.items.length === 0">
                        <p class="text-[13px] text-[var(--text-400)] text-center py-6">Sin horas extra registradas en este período.</p>
                    </template>

                    <div class="space-y-3">
                        <template x-for="(item, index) in overtime.items" :key="index">
                            <div class="flex items-end gap-3">
                                <div class="flex-1">
                                    <label class="block text-[12px] text-[var(--text-500)] mb-1">Tipo</label>
                                    <select :name="'items['+index+'][type]'" x-model="item.type" class="{{ $fieldClass }}">
                                        @foreach(\App\Models\PayrollOvertimeItem::TYPES as $val => $lbl)
                                        <option value="{{ $val }}">{{ $lbl }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-28">
                                    <label class="block text-[12px] text-[var(--text-500)] mb-1">Horas</label>
                                    <input type="number" step="0.25" min="0.25" :name="'items['+index+'][hours]'" x-model.number="item.hours" class="{{ $fieldClass }}">
                                </div>
                                <div class="w-32 text-right pb-2.5">
                                    <p class="text-[12px] text-[var(--text-400)]">Total</p>
                                    <p class="text-[14px] font-semibold text-[var(--text-900)]" x-text="'$ ' + fmt(overtimeLineTotal(item))"></p>
                                </div>
                                <button type="button" @click="removeOvertimeItem(index)" class="pb-2.5 text-[var(--color-danger)]/60 hover:text-[var(--color-danger)]">
                                    <x-lucide-trash-2 class="w-4 h-4" />
                                </button>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="addOvertimeItem()"
                            class="mt-4 inline-flex items-center gap-1.5 px-3 h-8 text-[13px] font-medium text-[var(--color-primary)] border border-[var(--border-default)] bg-[var(--color-primary-light)] rounded-[var(--radius-control)]">
                        <x-lucide-plus class="w-3.5 h-3.5" /> Agregar hora extra
                    </button>
                </div>

                <div class="px-6 py-4 border-t border-[var(--border-default)] flex items-center justify-between">
                    <div>
                        <p class="text-[12px] text-[var(--text-400)]">Total horas extra</p>
                        <p class="text-[18px] font-bold text-[var(--text-900)]" x-text="'$ ' + fmt(overtimeTotal)"></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="overtime.open = false" class="h-10 flex items-center px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">Cancelar</button>
                        <button type="submit" class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">Guardar</button>
                    </div>
                </div>
            </form>
            </template>
        </div>
    </div>
</div>

{{-- ══════════════ Modal — Enviar por correo ══════════════ --}}
<div x-show="email.open"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" style="display:none">
    <div @click="email.open = false" class="fixed inset-0 bg-gray-900/50"></div>
    <div class="flex min-h-full items-start justify-center p-4 pt-10">
        <div class="relative bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-md overflow-hidden"
             @click.stop
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-center justify-between px-6 py-5 border-b border-[var(--border-default)]">
                <div>
                    <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Enviar desprendible</h2>
                    <p class="text-[12px] text-[var(--text-400)] mt-0.5" x-text="email.row?.employee_name"></p>
                </div>
                <button @click="email.open = false" class="p-2 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                    <x-lucide-x class="w-4 h-4" />
                </button>
            </div>

            <template x-if="email.row">
            <form method="POST" :action="email.row.email_url" class="px-6 py-5 space-y-4">
                @csrf

                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1">Correo del empleado</label>
                    <input type="email" name="email" x-model="email.email" class="{{ $fieldClass }}" required>
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1">Mensaje (opcional)</label>
                    <textarea name="message" x-model="email.message" rows="3" class="w-full border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 py-2.5 text-[14px] text-[var(--text-700)] bg-[var(--surface-card)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none"></textarea>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">Enviar</button>
                    <button type="button" @click="email.open = false" class="h-10 flex items-center px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">Cancelar</button>
                </div>
            </form>
            </template>
        </div>
    </div>
</div>
