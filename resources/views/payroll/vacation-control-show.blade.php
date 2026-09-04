<x-app-layout>
<x-slot name="title">Control de Vacaciones</x-slot>

@php
    $fieldClass = 'w-full h-10 px-3.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none';
    $rowClass = 'flex items-center justify-between py-2.5 border-b border-[var(--surface-muted)] last:border-b-0';
    $labelClass = 'text-[14px] text-[var(--text-700)]';
    $valueClass = 'text-[14px] text-[var(--text-900)] tabular-nums';
@endphp

<div x-data="vacationControlPage()" @keydown.escape.window="periodModal.open = false; balanceModal.open = false">

<a href="{{ route('vacation-control.index', [], false) }}"
   class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)] mb-4">
    <x-lucide-arrow-left class="w-4 h-4" />
    Volver
</a>

@if(session('success'))
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="mb-5 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="mb-5 flex items-start gap-2 bg-[var(--color-danger-bg)] border border-[var(--color-danger)]/20 text-[var(--color-danger-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-alert-triangle class="w-4 h-4 flex-shrink-0 mt-0.5" />
    <div>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
</div>
@endif

{{-- Cabecera --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] p-6 mb-5">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-[var(--color-primary-light)] flex items-center justify-center text-[var(--color-primary)] font-semibold text-[18px] flex-shrink-0">
                {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
            </div>
            <div>
                <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $employee->full_name }}</p>
                <p class="text-[14px] text-[var(--text-500)] mt-0.5">
                    {{ $employee->position ?? 'Sin cargo asignado' }}
                    &bull; {{ $employee->client->name }}
                    &bull; Ingreso: {{ $employee->hire_date->format('d/m/Y') }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('vacation-control.pdf', $employee, false) }}"
               class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                <x-lucide-download class="w-4 h-4" />
                Descargar PDF
            </a>
            <button @click="printUrl = '{{ route('vacation-control.print', $employee, false) }}'; isPrinting = true"
               class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                <x-lucide-printer class="w-4 h-4" />
                Imprimir
            </button>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">

        {{-- Períodos tomados --}}
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-6">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <h3 class="text-[13px] font-semibold text-[var(--text-900)] uppercase tracking-[0.06em]">Períodos disfrutados</h3>
                    <x-help-icon title="Períodos disfrutados">
                        Cada registro descuenta del saldo pendiente los días hábiles indicados. Solo se restan los períodos con fecha de inicio posterior a la fecha de referencia del saldo inicial — los anteriores ya están reflejados en ese saldo.
                    </x-help-icon>
                </div>
                <button @click="openNewPeriod()" class="inline-flex items-center gap-[6px] h-9 px-4 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[13px] font-medium">
                    <x-lucide-plus class="w-3.5 h-3.5" />
                    Registrar período
                </button>
            </div>

            @if($periods->isEmpty())
            <p class="text-[14px] text-[var(--text-400)] py-6 text-center">Aún no se han registrado períodos de vacaciones disfrutados.</p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr>
                            @php $thClass = 'text-left text-[13px] font-bold text-[var(--text-900)] uppercase tracking-[0.05em] border-b border-[var(--border-default)] px-3 py-2.5'; @endphp
                            <th class="{{ $thClass }}">Desde</th>
                            <th class="{{ $thClass }}">Hasta</th>
                            <th class="{{ $thClass }} text-right">Días hábiles</th>
                            <th class="{{ $thClass }}">Notas</th>
                            <th class="{{ $thClass }} text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($periods as $period)
                        <tr class="border-b border-[var(--surface-muted)]">
                            <td class="px-3 py-2.5 text-[14px] text-[var(--text-700)]">{{ $period->start_date->format('d/m/Y') }}</td>
                            <td class="px-3 py-2.5 text-[14px] text-[var(--text-700)]">{{ $period->end_date->format('d/m/Y') }}</td>
                            <td class="px-3 py-2.5 text-right text-[14px] text-[var(--text-900)] tabular-nums">{{ number_format($period->business_days, 1) }}</td>
                            <td class="px-3 py-2.5 text-[13px] text-[var(--text-400)]">{{ $period->notes ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openEditPeriod({ id: {{ $period->id }}, start_date: '{{ $period->start_date->format('Y-m-d') }}', end_date: '{{ $period->end_date->format('Y-m-d') }}', business_days: {{ $period->business_days }}, notes: {{ \Illuminate\Support\Js::from($period->notes) }} })" class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Editar">
                                        <x-lucide-edit-2 class="w-4 h-4" />
                                    </button>
                                    <form method="POST" action="{{ route('vacation-control.periods.destroy', $period, false) }}"
                                          x-data="" x-on:submit.prevent="if(confirm('¿Eliminar este período de vacaciones?')) $el.submit()">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-[var(--text-400)] hover:text-[var(--color-danger)]" title="Eliminar">
                                            <x-lucide-trash-2 class="w-4 h-4" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- Saldo inicial --}}
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-6">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-[13px] font-semibold text-[var(--text-900)] uppercase tracking-[0.06em]">Saldo inicial</h3>
                <button @click="balanceModal.open = true" class="text-[13px] font-medium text-[var(--color-primary)] hover:underline">Editar</button>
            </div>
            <p class="text-[12px] text-[var(--text-400)] mb-3">Días pendientes conocidos a una fecha de referencia — úsalo para empleados que ya tenían historial antes de usar este módulo. Desde esa fecha, el sistema acumula 15 días hábiles por cada año cumplido automáticamente.</p>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Días</span><span class="{{ $valueClass }}">{{ number_format($balance['opening_balance'], 1) }}</span></div>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Fecha de referencia</span><span class="{{ $valueClass }}">{{ $balance['opening_date']?->format('d/m/Y') ?? '—' }}</span></div>
        </div>
    </div>

    {{-- Resumen de saldo --}}
    <div class="space-y-5">
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-6">
            <h3 class="flex items-center gap-2 text-[13px] font-semibold text-[var(--text-900)] uppercase tracking-[0.06em] mb-4">
                Saldo de vacaciones
                <x-help-icon title="Saldo de vacaciones">
                    Saldo pendiente = saldo inicial + 15 días por cada año cumplido desde la fecha de referencia − días tomados desde esa fecha. Se recalcula solo, sin campos que haya que actualizar cada año.
                </x-help-icon>
            </h3>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Años cumplidos</span><span class="{{ $valueClass }}">{{ $balance['accrued_years'] }}</span></div>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Días acumulados</span><span class="{{ $valueClass }}">{{ number_format($balance['accrued_days'], 1) }}</span></div>
            <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Días tomados</span><span class="{{ $valueClass }}">{{ number_format($balance['taken_days_since_opening'], 1) }}</span></div>
            <div class="mt-4 bg-[#1E3A8A] rounded-[var(--radius-control)] px-4 py-4 flex items-center justify-between">
                <span class="text-[13px] font-semibold uppercase tracking-[0.06em] text-blue-100">Saldo pendiente</span>
                <span class="text-[20px] font-bold text-white tabular-nums">{{ number_format($balance['pending_balance'], 1) }} días</span>
            </div>
            <div class="mt-4 pt-4 border-t border-[var(--border-default)]">
                <div class="{{ $rowClass }}"><span class="{{ $labelClass }}">Próxima acumulación</span><span class="{{ $valueClass }}">{{ $balance['next_accrual_date']?->format('d/m/Y') ?? '—' }}</span></div>
                <div class="flex items-center justify-between py-2.5">
                    <span class="{{ $labelClass }}">Mínimo legal este año</span>
                    @if($balance['complies_minimum_current_year'])
                    <x-status-badge variant="success">Cumple (art. 190 CST)</x-status-badge>
                    @else
                    <x-status-badge variant="warning">Pendiente ({{ number_format($balance['taken_days_current_year'], 1) }}/6 días)</x-status-badge>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════ Modal — Registrar/editar período ══════════════ --}}
<div x-show="periodModal.open"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" style="display:none">
    <div @click="periodModal.open = false" class="fixed inset-0 bg-gray-900/50"></div>
    <div class="flex min-h-full items-start justify-center p-4 pt-10">
        <div class="relative bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-lg overflow-hidden"
             @click.stop
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-center justify-between px-6 py-5 border-b border-[var(--border-default)]">
                <h2 class="text-[16px] font-bold text-[var(--text-900)]" x-text="periodModal.editing ? 'Editar período' : 'Registrar período de vacaciones'"></h2>
                <button @click="periodModal.open = false" class="p-2 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                    <x-lucide-x class="w-4 h-4" />
                </button>
            </div>

            <form method="POST" :action="periodModal.editing ? periodModal.updateUrlTemplate.replace('__ID__', periodModal.form.id) : periodModal.storeUrl" class="px-6 py-5 space-y-4">
                @csrf
                <template x-if="periodModal.editing"><input type="hidden" name="_method" value="PATCH"></template>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1">Fecha inicio <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="date" name="start_date" x-model="periodModal.form.start_date" @change="suggestDays()" class="{{ $fieldClass }}" required>
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1">Fecha fin <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="date" name="end_date" x-model="periodModal.form.end_date" @change="suggestDays()" class="{{ $fieldClass }}" required>
                    </div>
                </div>

                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1">Días hábiles <span class="text-[var(--color-danger)]">*</span></label>
                    <input type="number" step="0.5" min="0.5" name="business_days" x-model.number="periodModal.form.business_days" class="{{ $fieldClass }} max-w-[160px]" required>
                    <p class="mt-1.5 text-[12px] text-[var(--text-400)]">Sugerido automáticamente excluyendo fines de semana y festivos colombianos — ajústalo si es necesario.</p>
                </div>

                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1">Notas</label>
                    <input type="text" name="notes" x-model="periodModal.form.notes" maxlength="255" class="{{ $fieldClass }}">
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">Guardar</button>
                    <button type="button" @click="periodModal.open = false" class="h-10 flex items-center px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══════════════ Modal — Editar saldo inicial ══════════════ --}}
<div x-show="balanceModal.open"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" style="display:none">
    <div @click="balanceModal.open = false" class="fixed inset-0 bg-gray-900/50"></div>
    <div class="flex min-h-full items-start justify-center p-4 pt-10">
        <div class="relative bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-md overflow-hidden"
             @click.stop
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-center justify-between px-6 py-5 border-b border-[var(--border-default)]">
                <h2 class="text-[16px] font-bold text-[var(--text-900)]">Editar saldo inicial</h2>
                <button @click="balanceModal.open = false" class="p-2 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                    <x-lucide-x class="w-4 h-4" />
                </button>
            </div>

            <form method="POST" action="{{ route('vacation-control.opening-balance.update', $employee, false) }}" class="px-6 py-5 space-y-4">
                @csrf @method('PATCH')

                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1">Días pendientes <span class="text-[var(--color-danger)]">*</span></label>
                    <input type="number" step="0.5" min="0" name="vacation_opening_balance_days" value="{{ old('vacation_opening_balance_days', $balance['opening_balance']) }}" class="{{ $fieldClass }}" required>
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1">Fecha de referencia <span class="text-[var(--color-danger)]">*</span></label>
                    <input type="date" name="vacation_opening_balance_date" value="{{ old('vacation_opening_balance_date', $balance['opening_date']?->format('Y-m-d')) }}" class="{{ $fieldClass }}" required>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">Guardar</button>
                    <button type="button" @click="balanceModal.open = false" class="h-10 flex items-center px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
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

<script>
function vacationControlPage() {
    return {
        isPrinting: false,
        printUrl: '',
        periodModal: {
            open: false,
            editing: false,
            storeUrl: '{{ route('vacation-control.periods.store', $employee, false) }}',
            updateUrlTemplate: '{{ route('vacation-control.periods.update', ['period' => '__ID__'], false) }}',
            form: { id: null, start_date: '', end_date: '', business_days: '', notes: '' },
        },
        balanceModal: { open: false },

        openNewPeriod() {
            this.periodModal.editing = false;
            this.periodModal.form = { id: null, start_date: '', end_date: '', business_days: '', notes: '' };
            this.periodModal.open = true;
        },
        openEditPeriod(period) {
            this.periodModal.editing = true;
            this.periodModal.form = {
                id: period.id,
                start_date: period.start_date,
                end_date: period.end_date,
                business_days: period.business_days,
                notes: period.notes || '',
            };
            this.periodModal.open = true;
        },
        async suggestDays() {
            if (!this.periodModal.form.start_date || !this.periodModal.form.end_date) return;
            try {
                const url = new URL('{{ route('vacation-control.suggest-business-days', [], false) }}', window.location.origin);
                url.searchParams.set('start_date', this.periodModal.form.start_date);
                url.searchParams.set('end_date', this.periodModal.form.end_date);
                const r = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!r.ok) return;
                const data = await r.json();
                this.periodModal.form.business_days = data.business_days;
            } catch (e) { /* deja el valor actual si falla la sugerencia */ }
        },
    };
}
</script>
</x-app-layout>
