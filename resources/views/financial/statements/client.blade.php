<x-app-layout>
<x-slot name="title">Estados Financieros</x-slot>

@php
    $periodLabels  = \App\Models\Budget::PERIOD_TYPES;
    $typeLabels    = \App\Models\Budget::TYPES;
    $statusVariant = ['draft' => 'neutral', 'final' => 'success'];
    $statusText    = \App\Models\Budget::STATUS_LABELS;
    $typeColors    = ['esf' => '#4F46E5', 'eri' => '#0D9488'];

    // ESF y ERI siempre se crean como un solo par vinculado — se agrupan
    // aquí en una sola fila. Un ESF/ERI heredado de antes de este cambio
    // que haya quedado sin contraparte se muestra aparte (flujo individual
    // anterior, rutas financial.show/financial.edit).
    $rows = collect();
    $consumed = [];
    foreach ($budgets as $b) {
        if (in_array($b->id, $consumed, true)) continue;

        $counterpart = $b->linked_counterpart_budget_id
            ? $budgets->firstWhere('id', $b->linked_counterpart_budget_id)
            : null;

        if ($counterpart && $counterpart->linked_counterpart_budget_id === $b->id) {
            [$esf, $eri] = $b->type === 'esf' ? [$b, $counterpart] : [$counterpart, $b];
            $rows->push(['esf' => $esf, 'eri' => $eri]);
            $consumed[] = $esf->id;
            $consumed[] = $eri->id;
        } else {
            $rows->push(['solo' => $b]);
            $consumed[] = $b->id;
        }
    }

    $kpiItems = [
        ['val' => $rows->count(), 'label' => 'Estado financiero', 'icon' => 'scale', 'fg' => 'var(--color-primary)', 'bg' => 'var(--color-primary-light)'],
        ['val' => $rows->filter(fn($r) => ($r['esf'] ?? $r['solo'])->status === 'draft')->count(), 'label' => 'Borrador', 'icon' => 'file-edit', 'fg' => 'var(--text-500)', 'bg' => 'var(--surface-muted)'],
        ['val' => $rows->filter(fn($r) => ($r['esf'] ?? $r['solo'])->status === 'final')->count(), 'label' => 'Aprobado', 'icon' => 'check-circle', 'fg' => 'var(--color-success)', 'bg' => 'var(--color-success-bg)'],
        ['val' => $rows->filter(fn($r) => isset($r['esf']) && ($balanceByBudget[$r['esf']->id] ?? null) === false)->count(), 'label' => 'Descuadre', 'icon' => 'alert-triangle', 'fg' => 'var(--color-danger)', 'bg' => 'var(--color-danger-bg)'],
    ];
    $visibleKpis = collect($kpiItems)->filter(fn ($k) => $k['val'] > 0)->values();
@endphp

<a href="{{ route('financial.statements.index') }}"
   class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)] mb-5">
    <x-lucide-arrow-left class="w-4 h-4" />
    Volver
</a>

{{-- Flash — desaparece solo a los pocos segundos, sin necesidad de recargar
     ni cerrarlo a mano (a pedido explícito del usuario). --}}
@if(session('success'))
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="mb-5 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

{{-- Cabecera del cliente: sin tarjeta, el contenido queda directo sobre la página --}}
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
        <div class="flex items-center gap-4 min-w-0">
            <x-avatar-chip :name="$client->name" class="w-12 h-12" />
            <div class="min-w-0">
                <p class="text-[22px] font-bold text-[var(--text-900)] truncate">{{ $client->name }}</p>
                <p class="text-[13px] text-[var(--text-500)] mt-0.5">
                    {{ $client->document_type }} {{ $client->document_number }}
                    @if($client->dv)-{{ $client->dv }}@endif
                    @if($client->city) · {{ $client->city }}@endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0 flex-wrap">
            <a href="{{ route('financial.statements.create', ['client_id' => $client->id]) }}"
               class="inline-flex items-center gap-[6px] h-9 px-4 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[13px] font-medium">
                <x-lucide-plus class="w-3.5 h-3.5" />
                Nuevo estado financiero
            </a>
        </div>
    </div>
</div>

{{-- KPIs --}}
@if($visibleKpis->isNotEmpty())
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach($visibleKpis as $k)
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-5 shadow-[var(--shadow-card)]">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">{{ $k['label'] }}{{ $k['val'] > 1 ? 's' : '' }}</p>
            <div class="w-8 h-8 rounded-[8px] flex items-center justify-center flex-shrink-0" style="background: {{ $k['bg'] }};">
                @svg('lucide-' . $k['icon'], 'w-4 h-4', ['style' => 'color: ' . $k['fg'] . ';'])
            </div>
        </div>
        <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $k['val'] }}</p>
    </div>
    @endforeach
</div>
@endif

{{-- Tabla de estados financieros --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
    @if($rows->isEmpty())
    <div class="text-center py-16">
        <div class="w-14 h-14 rounded-[var(--radius-card)] bg-[var(--color-primary-light)] flex items-center justify-center mx-auto mb-4">
            <x-lucide-scale class="w-7 h-7 text-[var(--color-primary)]" />
        </div>
        <p class="text-[14px] font-semibold text-[var(--text-700)]">No hay estados financieros para este cliente</p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">Crea el primero con los rubros NIIF y digita las cifras por período</p>
        <a href="{{ route('financial.statements.create', ['client_id' => $client->id]) }}"
           class="mt-4 inline-flex items-center gap-[6px] h-10 px-5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium rounded-[var(--radius-control)]">
            <x-lucide-plus class="w-4 h-4" />
            Crear primer estado financiero
        </a>
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
                    <th class="{{ $thClass }} text-left">Estado financiero</th>
                    <th class="{{ $thClass }} text-left hidden md:table-cell">Tipo</th>
                    <th class="{{ $thClass }} text-left hidden lg:table-cell">Período</th>
                    <th class="{{ $thClass }} text-left hidden sm:table-cell">Creado</th>
                    <th class="{{ $thClass }} text-center hidden sm:table-cell">Cuadre</th>
                    <th class="{{ $thClass }} text-center">Estado</th>
                    <th class="{{ $thClass }} text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                @if(isset($row['esf']))
                @php
                    $esf = $row['esf'];
                    $eri = $row['eri'];
                    $st  = $statusText[$esf->status] ?? $statusText['draft'];
                    $sv  = $statusVariant[$esf->status] ?? 'neutral';
                    $isBalanced = $balanceByBudget[$esf->id] ?? null;
                    $firstYear  = $esf->calendarYearForPeriod(0);
                    $lastYear   = $esf->calendarYearForPeriod($esf->periods_count);
                    $yearLabel  = $firstYear === $lastYear ? (string) $firstYear : "{$firstYear}-{$lastYear}";
                @endphp
                <tr class="border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">
                    <td class="px-6 py-[14px]">
                        <a href="{{ route('financial.statements.show', $esf) }}"
                           class="text-[14px] text-[var(--text-900)] hover:text-[var(--color-primary)]">
                            Estados Financieros {{ $yearLabel }}
                        </a>
                    </td>
                    <td class="px-6 py-[14px] hidden md:table-cell text-[14px]">
                        <span class="font-medium" style="color: {{ $typeColors['esf'] }};">ESF</span>
                        <span class="text-[var(--text-400)] mx-0.5">+</span>
                        <span class="font-medium" style="color: {{ $typeColors['eri'] }};">ERI</span>
                    </td>
                    <td class="px-6 py-[14px] hidden lg:table-cell text-[14px] text-[var(--text-500)]">
                        {{ $periodLabels[$esf->period_type] }} ·
                        {{ $yearLabel }}
                        ({{ $esf->periods_count + 1 }} período{{ $esf->periods_count > 0 ? 's' : '' }})
                    </td>
                    <td class="px-6 py-[14px] hidden sm:table-cell text-[14px] text-[var(--text-500)]">
                        {{ $esf->created_at->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-[14px] text-center hidden sm:table-cell">
                        @if($isBalanced !== null)
                        <x-status-badge :variant="$isBalanced ? 'success' : 'danger'">{{ $isBalanced ? 'Cuadra' : 'Descuadre' }}</x-status-badge>
                        @else
                        <span class="text-[var(--border-strong)] text-[14px]">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-[14px] text-center">
                        <x-status-badge :variant="$sv">{{ $st['label'] }}</x-status-badge>
                    </td>
                    <td class="px-6 py-[14px] text-right">
                        <div class="flex items-center justify-end gap-[10px]">
                            <a href="{{ route('financial.statements.show', $esf) }}"
                               class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Ver estados financieros">
                                <x-lucide-eye class="w-4 h-4" />
                            </a>
                            <a href="{{ route('financial.statements.edit', $esf) }}"
                               class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Editar estructura">
                                <x-lucide-edit-2 class="w-4 h-4" />
                            </a>
                            <form method="POST" action="{{ route('financial.statements.destroy', $esf) }}"
                                  x-data=""
                                  x-on:submit.prevent="if(confirm('¿Eliminar el Estado de Situación Financiera y el Estado de Resultados de «{{ addslashes($client->name) }}»?')) $el.submit()">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Eliminar">
                                    <x-lucide-trash-2 class="w-4 h-4" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>

                @else
                {{-- Heredado de antes de este cambio: ESF o ERI sin contraparte vinculada --}}
                @php
                    $budget = $row['solo'];
                    $st = $statusText[$budget->status] ?? $statusText['draft'];
                    $sv = $statusVariant[$budget->status] ?? 'neutral';
                    $firstYear = $budget->calendarYearForPeriod(0);
                    $lastYear  = $budget->calendarYearForPeriod($budget->periods_count);
                    $yearLabel = $firstYear === $lastYear ? (string) $firstYear : "{$firstYear}-{$lastYear}";
                @endphp
                <tr class="border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">
                    <td class="px-6 py-[14px]">
                        <a href="{{ route('financial.show', $budget) }}"
                           class="text-[14px] text-[var(--text-900)] hover:text-[var(--color-primary)]">
                            {{ $budget->name }}
                        </a>
                    </td>
                    <td class="px-6 py-[14px] hidden md:table-cell text-[14px] font-medium" style="color: {{ $typeColors[$budget->type] }};">
                        {{ $typeLabels[$budget->type] }}
                    </td>
                    <td class="px-6 py-[14px] hidden lg:table-cell text-[14px] text-[var(--text-500)]">
                        {{ $periodLabels[$budget->period_type] }} ·
                        {{ $yearLabel }}
                        ({{ $budget->periods_count + 1 }} período{{ $budget->periods_count > 0 ? 's' : '' }})
                    </td>
                    <td class="px-6 py-[14px] hidden sm:table-cell text-[14px] text-[var(--text-500)]">
                        {{ $budget->created_at->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-[14px] text-center hidden sm:table-cell">
                        <span class="text-[var(--border-strong)] text-[14px]">—</span>
                    </td>
                    <td class="px-6 py-[14px] text-center">
                        <div class="flex items-center justify-center gap-1.5 flex-wrap">
                            <x-status-badge :variant="$sv">{{ $st['label'] }}</x-status-badge>
                            <x-status-badge variant="warning">Sin vincular</x-status-badge>
                        </div>
                    </td>
                    <td class="px-6 py-[14px] text-right">
                        <div class="flex items-center justify-end gap-[10px]">
                            <a href="{{ route('financial.show', $budget) }}"
                               class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Ver estado financiero">
                                <x-lucide-eye class="w-4 h-4" />
                            </a>
                            <a href="{{ route('financial.edit', $budget) }}"
                               class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Editar estructura">
                                <x-lucide-edit-2 class="w-4 h-4" />
                            </a>
                            <form method="POST" action="{{ route('financial.destroy', $budget) }}"
                                  x-data=""
                                  x-on:submit.prevent="if(confirm('¿Eliminar «{{ addslashes($budget->name) }}»?')) $el.submit()">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Eliminar">
                                    <x-lucide-trash-2 class="w-4 h-4" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
    @endif
</div>

</x-app-layout>
