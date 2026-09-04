<x-app-layout>
<x-slot name="title">Períodos de Nómina</x-slot>

@php
    $fieldClass = 'w-full h-10 px-3.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none';
@endphp

<div x-data="generarNominaModal()" @keydown.escape.window="open && cerrar()">

{{-- Header --}}
<div class="flex items-start justify-end mb-6">
    <button @click="abrir()"
            class="inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
        <x-lucide-plus class="w-4 h-4" />
        Generar nómina
    </button>
</div>

@if(session('success'))
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="mb-5 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[15px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="mb-5 flex items-center gap-2 bg-[var(--color-danger-bg)] border border-[var(--color-danger)]/20 text-[var(--color-danger-text)] text-[15px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-alert-triangle class="w-4 h-4 flex-shrink-0" />
    {{ $errors->first() }}
</div>
@endif

{{-- Filtros --}}
<form method="GET" action="{{ route('payroll-periods.index', [], false) }}" class="flex flex-col sm:flex-row gap-3 mb-5">
    <select name="client_id" class="h-10 px-3.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[15px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-1 focus:ring-[var(--color-primary-light)] focus:border-[var(--border-default)] outline-none sm:max-w-[220px]">
        <option value="">Todos los clientes</option>
        @foreach($clients as $c)
        <option value="{{ $c->id }}" {{ (string) request('client_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
        @endforeach
    </select>
    <select name="status" class="h-10 px-3.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[15px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-1 focus:ring-[var(--color-primary-light)] focus:border-[var(--border-default)] outline-none sm:max-w-[180px]">
        <option value="">Todos los estados</option>
        @foreach(\App\Models\PayrollPeriod::STATUSES as $val => $lbl)
        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
        @endforeach
    </select>
    <button type="submit" class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
        Buscar
    </button>
    @if(request()->hasAny(['client_id','status']))
    <a href="{{ route('payroll-periods.index', [], false) }}" class="h-10 flex items-center px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[15px] font-medium hover:bg-[var(--surface-muted)]">
        Limpiar
    </a>
    @endif
</form>

{{-- Tabla --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
    @if($periods->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-16 h-16 rounded-[var(--radius-card)] bg-[var(--surface-muted)] flex items-center justify-center mb-4">
            <x-lucide-banknote class="w-8 h-8 text-[var(--text-400)]" />
        </div>
        <p class="text-[15px] font-semibold text-[var(--text-700)]">Aún no has generado períodos de nómina</p>
        <p class="text-[13px] text-[var(--text-400)] mt-1">Genera el primer período para uno de tus clientes</p>
        <button @click="abrir()" class="mt-4 inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
            <x-lucide-plus class="w-4 h-4" />
            Generar nómina
        </button>
    </div>
    @else
    <div class="overflow-x-auto p-3">
    <div class="overflow-y-auto max-h-[65vh]">
        <table class="w-full">
            <thead>
                <tr>
                    @php
                        $thClass = 'sticky top-0 z-[1] bg-[var(--surface-card)] border-b border-[var(--border-default)] text-[13px] font-bold text-[var(--text-900)] px-6 py-3.5';
                    @endphp
                    <th class="{{ $thClass }} text-left">Período</th>
                    <th class="{{ $thClass }} text-left hidden lg:table-cell">Periodicidad</th>
                    <th class="{{ $thClass }} text-left">Cliente</th>
                    <th class="{{ $thClass }} text-left hidden md:table-cell">Rango</th>
                    <th class="{{ $thClass }} text-right hidden sm:table-cell">Neto total</th>
                    <th class="{{ $thClass }} text-center">Estado</th>
                    <th class="{{ $thClass }} text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($periods as $period)
                <tr class="border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">
                    <td class="px-6 py-[14px]">
                        <a href="{{ route('payroll-periods.show', $period, false) }}" class="text-[14px] text-[var(--text-500)] hover:text-[var(--color-primary)]">
                            {{ $period->number }}
                        </a>
                        <p class="text-[13px] text-[var(--text-400)] mt-0.5 lg:hidden">{{ \App\Models\PayrollPeriod::PERIOD_TYPES[$period->period_type] }}</p>
                    </td>
                    <td class="px-6 py-[14px] hidden lg:table-cell text-[14px] text-[var(--text-500)]">{{ \App\Models\PayrollPeriod::PERIOD_TYPES[$period->period_type] }}</td>
                    <td class="px-6 py-[14px] text-[14px] text-[var(--text-500)]">{{ $period->client->name }}</td>
                    <td class="px-6 py-[14px] hidden md:table-cell text-[14px] text-[var(--text-500)]">
                        {{ $period->start_date->format('d/m/Y') }} – {{ $period->end_date->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-[14px] hidden sm:table-cell text-right text-[14px] text-[var(--text-500)] tabular-nums">
                        $ {{ number_format($period->total_net_pay, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-[14px] text-center">
                        <x-status-badge :variant="match($period->status) { 'pagada' => 'success', 'procesada' => 'info', 'anulada' => 'neutral', default => 'warning' }">
                            {{ \App\Models\PayrollPeriod::STATUSES[$period->status] }}
                        </x-status-badge>
                    </td>
                    <td class="px-6 py-[14px] text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('payroll-periods.show', $period, false) }}" class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Ver">
                                <x-lucide-eye class="w-4 h-4" />
                            </a>
                            <button @click="duplicar({{ $period->id }}, {{ $period->client_id }})" title="Duplicar (copiar conceptos a un nuevo período)" class="text-[var(--text-400)] hover:text-[var(--text-900)]">
                                <x-lucide-copy class="w-4 h-4" />
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
    @if($periods->hasPages())
    <div class="px-6 py-4 border-t border-[var(--border-default)]">
        {{ $periods->links() }}
    </div>
    @endif
    @endif
</div>

{{-- ══════════════ Modal — Generar nómina ══════════════ --}}
<div x-show="open"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display:none">

    <div @click="cerrar()" class="fixed inset-0 bg-gray-900/50"></div>

    <div class="flex min-h-full items-start justify-center p-4 pt-10">
        <div class="relative bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-lg overflow-hidden"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-center justify-between px-6 py-5 border-b border-[var(--border-default)]">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 bg-[var(--color-primary-light)] rounded-[var(--radius-control)] flex items-center justify-center">
                        <x-lucide-banknote class="w-4 h-4 text-[var(--color-primary)]" />
                    </div>
                    <h2 class="text-[16px] font-bold text-[var(--text-900)]">Generar nómina</h2>
                    <x-help-icon title="Generar nómina">
                        Crea un desprendible por cada empleado activo del cliente para el rango de fechas indicado. Si ya generaste un período anterior, usa "Copiar conceptos desde" para partir de las mismas comisiones/bonificaciones/horas extra en vez de digitarlas de nuevo.
                    </x-help-icon>
                </div>
                <button @click="cerrar()" class="p-2 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                    <x-lucide-x class="w-4 h-4" />
                </button>
            </div>

            <form method="POST" action="{{ route('payroll-periods.store', [], false) }}" class="px-6 py-5 space-y-4">
                @csrf

                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1">Cliente <span class="text-[var(--color-danger)]">*</span></label>
                    <select name="client_id" x-model="clientId" @change="copyFromPeriodId = ''" class="{{ $fieldClass }}" required>
                        <option value="">Selecciona un cliente...</option>
                        @foreach($clients as $c)
                        <option value="{{ $c->id }}" data-periodicity="{{ $c->payroll_periodicity }}">
                            {{ $c->name }} @if(!$c->payroll_periodicity) (sin periodicidad configurada) @endif
                        </option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-[12px] text-[var(--text-400)]">
                        La periodicidad (mensual/quincenal) se toma de la configuración de nómina del cliente.
                        <a href="{{ route('clients.index', [], false) }}" class="text-[var(--color-primary)] hover:underline">Configúrala en la ficha del cliente</a> si aún no la tiene.
                    </p>
                </div>

                <div x-show="periodsForClient.length > 0" x-transition>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1">Copiar conceptos desde (opcional)</label>
                    <select name="copy_from_period_id" x-model="copyFromPeriodId" class="{{ $fieldClass }}">
                        <option value="">No copiar — empezar en cero</option>
                        <template x-for="p in periodsForClient" :key="p.id">
                            <option :value="p.id" x-text="p.number + ' (' + p.start_date.slice(0,10) + ' – ' + p.end_date.slice(0,10) + ')'"></option>
                        </template>
                    </select>
                    <p class="mt-1.5 text-[12px] text-[var(--text-400)]">
                        Copia comisiones, bonificaciones, descuentos y horas extra de ese período como punto de partida. Los días trabajados y la seguridad social siempre se calculan de cero para el período nuevo.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1">Fecha inicial <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="date" name="start_date" class="{{ $fieldClass }}" required>
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1">Fecha final <span class="text-[var(--color-danger)]">*</span></label>
                        <input type="date" name="end_date" class="{{ $fieldClass }}" required>
                    </div>
                </div>

                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1">Fecha de pago <span class="text-[var(--color-danger)]">*</span></label>
                    <input type="date" name="payment_date" class="{{ $fieldClass }}" required>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">
                        Generar
                    </button>
                    <button type="button" @click="cerrar()" class="h-10 flex items-center px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>

<script>
function generarNominaModal() {
    return {
        open: {{ $errors->any() || request('duplicate_from') ? 'true' : 'false' }},
        clientId: '{{ request('client_id', '') }}',
        copyFromPeriodId: '{{ request('duplicate_from', '') }}',
        periods: @js($periodsForCopy),
        get periodsForClient() {
            return this.periods.filter(p => String(p.client_id) === String(this.clientId));
        },
        duplicar(periodId, clientId) {
            this.clientId = String(clientId);
            this.copyFromPeriodId = String(periodId);
            this.open = true;
        },
        abrir() { this.open = true; },
        cerrar() { this.open = false; },
    };
}
</script>
</x-app-layout>
