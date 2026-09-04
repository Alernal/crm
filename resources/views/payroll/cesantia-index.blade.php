<x-app-layout>
<x-slot name="title">Cesantías</x-slot>

@php
    $fieldClass = 'w-full h-10 px-3.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none';
@endphp

<div x-data="generarCesantiaModal()" @keydown.escape.window="open && cerrar()">

{{-- Header --}}
<div class="flex items-start justify-end mb-6">
    <button @click="abrir()"
            class="inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
        <x-lucide-plus class="w-4 h-4" />
        Generar liquidación
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
<form method="GET" action="{{ route('cesantia-settlements.index', [], false) }}" class="flex flex-col sm:flex-row gap-3 mb-5">
    <select name="client_id" class="h-10 px-3.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[15px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-1 focus:ring-[var(--color-primary-light)] focus:border-[var(--border-default)] outline-none sm:max-w-[220px]">
        <option value="">Todos los clientes</option>
        @foreach($clients as $c)
        <option value="{{ $c->id }}" {{ (string) request('client_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
        @endforeach
    </select>
    <button type="submit" class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
        Buscar
    </button>
    @if(request()->hasAny(['client_id']))
    <a href="{{ route('cesantia-settlements.index', [], false) }}" class="h-10 flex items-center px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[15px] font-medium hover:bg-[var(--surface-muted)]">
        Limpiar
    </a>
    @endif
</form>

{{-- Tabla --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
    @if($settlements->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-16 h-16 rounded-[var(--radius-card)] bg-[var(--surface-muted)] flex items-center justify-center mb-4">
            <x-lucide-piggy-bank class="w-8 h-8 text-[var(--text-400)]" />
        </div>
        <p class="text-[15px] font-semibold text-[var(--text-700)]">Aún no has generado liquidaciones de cesantías</p>
        <p class="text-[13px] text-[var(--text-400)] mt-1">Genera la primera para uno de tus clientes con nóminas del año ya cargadas</p>
        <button @click="abrir()" class="mt-4 inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
            <x-lucide-plus class="w-4 h-4" />
            Generar liquidación
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
                    <th class="{{ $thClass }} text-left">Año</th>
                    <th class="{{ $thClass }} text-left">Cliente</th>
                    <th class="{{ $thClass }} text-left hidden sm:table-cell">Fecha de pago</th>
                    <th class="{{ $thClass }} text-right">Cesantías + intereses</th>
                    <th class="{{ $thClass }} text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($settlements as $settlement)
                <tr class="border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">
                    <td class="px-6 py-[14px]">
                        <a href="{{ route('cesantia-settlements.show', $settlement, false) }}" class="text-[14px] text-[var(--text-500)] hover:text-[var(--color-primary)]">
                            {{ $settlement->year }}
                        </a>
                    </td>
                    <td class="px-6 py-[14px] text-[14px] text-[var(--text-500)]">{{ $settlement->client->name }}</td>
                    <td class="px-6 py-[14px] hidden sm:table-cell text-[14px] text-[var(--text-500)]">{{ $settlement->payment_date->format('d/m/Y') }}</td>
                    <td class="px-6 py-[14px] text-right text-[14px] text-[var(--text-500)] tabular-nums">
                        $ {{ number_format($settlement->total_cesantias + $settlement->total_interest, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-[14px] text-right">
                        <a href="{{ route('cesantia-settlements.show', $settlement, false) }}" class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Ver">
                            <x-lucide-eye class="w-4 h-4" />
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
    @if($settlements->hasPages())
    <div class="px-6 py-4 border-t border-[var(--border-default)]">
        {{ $settlements->links() }}
    </div>
    @endif
    @endif
</div>

{{-- ══════════════ Modal — Generar liquidación de cesantías ══════════════ --}}
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
                        <x-lucide-piggy-bank class="w-4 h-4 text-[var(--color-primary)]" />
                    </div>
                    <h2 class="text-[16px] font-bold text-[var(--text-900)]">Generar liquidación de cesantías</h2>
                    <x-help-icon title="Liquidación de cesantías">
                        Suma automáticamente la provisión de cesantías e intereses ya calculada mes a mes en las nóminas generadas del cliente dentro del año elegido — no vuelve a calcular nada, solo consolida. Genera cada año solo una vez.
                    </x-help-icon>
                </div>
                <button @click="cerrar()" class="p-2 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                    <x-lucide-x class="w-4 h-4" />
                </button>
            </div>

            <form method="POST" action="{{ route('cesantia-settlements.store', [], false) }}" class="px-6 py-5 space-y-4">
                @csrf

                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1">Cliente <span class="text-[var(--color-danger)]">*</span></label>
                    <select name="client_id" class="{{ $fieldClass }}" required>
                        <option value="">Selecciona un cliente...</option>
                        @foreach($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1">Año <span class="text-[var(--color-danger)]">*</span></label>
                    <input type="number" name="year" x-model="year" @change="suggestPaymentDate()" min="2000" max="2100" class="{{ $fieldClass }}" required>
                </div>

                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1">Fecha de pago <span class="text-[var(--color-danger)]">*</span></label>
                    <input type="date" name="payment_date" x-model="paymentDate" class="{{ $fieldClass }}" required>
                    <p class="mt-1.5 text-[12px] text-[var(--text-400)]">Sugerido: 14 de febrero del año siguiente (plazo legal de consignación al fondo).</p>
                </div>

                <p class="text-[12px] text-[var(--text-400)]">
                    Se suman las provisiones de cesantías e intereses ya calculadas en las nóminas generadas para este cliente dentro del año seleccionado.
                </p>

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
function generarCesantiaModal() {
    return {
        open: {{ $errors->any() ? 'true' : 'false' }},
        year: new Date().getFullYear(),
        paymentDate: '',
        suggestPaymentDate() {
            this.paymentDate = `${parseInt(this.year, 10) + 1}-02-14`;
        },
        abrir() { this.suggestPaymentDate(); this.open = true; },
        cerrar() { this.open = false; },
    };
}
</script>
</x-app-layout>
