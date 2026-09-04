<x-app-layout>
<x-slot name="title">Primas</x-slot>

<div x-data="{ isPrinting: false, printUrl: '' }">

<a href="{{ route('prima-settlements.index', [], false) }}"
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

{{-- Cabecera --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] p-6 mb-5">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <p class="text-[22px] font-bold text-[var(--text-900)]">
                Liquidación de prima — {{ \App\Models\PrimaSettlement::SEMESTERS[$primaSettlement->semester] }} {{ $primaSettlement->year }}
            </p>
            <p class="text-[14px] text-[var(--text-500)] mt-0.5">
                {{ $primaSettlement->client->name }}
                &bull; {{ $primaSettlement->start_date->format('d/m/Y') }} – {{ $primaSettlement->end_date->format('d/m/Y') }}
                &bull; Pago: {{ $primaSettlement->payment_date->format('d/m/Y') }}
            </p>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('prima-settlements.pdf', $primaSettlement, false) }}"
               class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                <x-lucide-download class="w-4 h-4" />
                Descargar PDF
            </a>
            <button @click="printUrl = '{{ route('prima-settlements.print', $primaSettlement, false) }}'; isPrinting = true"
               class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                <x-lucide-printer class="w-4 h-4" />
                Imprimir
            </button>
            <form method="POST" action="{{ route('prima-settlements.destroy', $primaSettlement, false) }}"
                  x-data="" x-on:submit.prevent="if(confirm('¿Eliminar esta liquidación de prima? Esta acción no se puede deshacer.')) $el.submit()">
                @csrf @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] border bg-[var(--color-danger-bg)]/50 border-[var(--color-danger)]/30 text-[var(--color-danger)] text-[14px] font-medium hover:bg-[var(--color-danger-bg)]">
                    <x-lucide-trash-2 class="w-4 h-4" />
                    Eliminar
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Tabla --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
    <div class="overflow-x-auto p-3">
        <table class="w-full">
            <thead>
                <tr>
                    @php
                        $thClass = 'bg-[var(--surface-card)] border-b border-[var(--border-default)] text-[13px] font-bold text-[var(--text-900)] uppercase tracking-[0.06em] px-6 py-3.5';
                    @endphp
                    <th class="{{ $thClass }} text-left">Empleado</th>
                    <th class="{{ $thClass }} text-right">Días</th>
                    <th class="{{ $thClass }} text-right">Valor prima</th>
                </tr>
            </thead>
            <tbody>
                @foreach($primaSettlement->items as $item)
                <tr class="border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">
                    <td class="px-6 py-[14px] text-[14px] text-[var(--text-700)]">{{ $item->employee->full_name }}</td>
                    <td class="px-6 py-[14px] text-right text-[14px] text-[var(--text-700)] tabular-nums">{{ number_format($item->worked_days, 0, ',', '.') }}</td>
                    <td class="px-6 py-[14px] text-right text-[14px] text-[var(--text-900)] tabular-nums">$ {{ number_format($item->prima_value, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-[var(--surface-card)] border-t-2 border-t-[var(--border-default)]">
                    <td class="px-6 py-3 text-[13px] font-semibold text-[var(--text-900)] uppercase tracking-[0.06em]">Total</td>
                    <td class="px-6 py-3 text-right text-[14px] font-semibold text-[var(--text-700)] tabular-nums">{{ number_format($primaSettlement->items->sum('worked_days'), 0, ',', '.') }}</td>
                    <td class="px-6 py-3 text-right font-bold text-[var(--text-900)] tabular-nums">$ {{ number_format($primaSettlement->total_prima, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
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
</x-app-layout>
