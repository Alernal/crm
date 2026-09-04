@php
    $esfBalanced = $isEsf ? collect($statementReport['diferencia'] ?? [])->every(fn ($d) => abs($d) < 1) : null;
@endphp

@if($isEsf)
<div class="flex items-center gap-2 {{ $esfBalanced ? 'bg-[var(--color-success-bg)] border-[var(--color-success)]/20 text-[var(--color-success-text)]' : 'bg-[var(--color-danger-bg)] border-[var(--color-danger)]/20 text-[var(--color-danger-text)]' }} border text-[14px] px-4 py-3 rounded-[var(--radius-control)] mb-4">
    @if($esfBalanced)
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    El balance cuadra en todos los períodos: Total Activo = Total Pasivo + Patrimonio.
    @else
    <x-lucide-alert-triangle class="w-4 h-4 flex-shrink-0" />
    Hay descuadre en al menos un período — revisa la fila "Diferencia" al final de la tabla.
    @endif
</div>
@endif

<div class="bg-[var(--surface-card)] border border-t-0 border-[var(--border-default)] rounded-b-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden mb-5">

    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-[var(--surface-subtle)] border-b border-[var(--border-default)]">
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.06em] text-[var(--text-400)] whitespace-nowrap">Concepto</th>
                    @foreach($periodLabels as $idx => $label)
                    <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-[0.06em] whitespace-nowrap {{ $idx === 0 ? 'text-[var(--text-900)]' : 'text-[var(--text-400)]' }}"
                        style="width:160px">
                        {{ $label }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($statementReport['rows'] as $row)
                    @include('financial._statement-row', ['row' => $row, 'periodLabels' => $periodLabels])
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="px-4 py-2.5 border-t border-[var(--border-default)] flex items-center gap-2 text-[12px] text-[var(--text-400)]">
        <x-lucide-edit-2 class="w-3.5 h-3.5 flex-shrink-0" />
        Doble clic en cualquier celda para editar. Los subtotales{{ $isEsf ? ' y los vínculos con el Estado de Resultados' : ' y los vínculos con el Estado de Situación Financiera' }} se actualizan automáticamente al guardar, sin recargar la página.
    </div>
</div>
