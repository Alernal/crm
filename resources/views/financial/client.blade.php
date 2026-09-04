<x-app-layout>
<x-slot name="title">Presupuestos</x-slot>

@php
    $typeLabels  = \App\Models\Budget::TYPES;
    $periodLabels= \App\Models\Budget::PERIOD_TYPES;
    $typeVariant = [
        'flujo_caja' => ['icon' => 'bar-chart-2', 'bg' => 'var(--color-primary-light)', 'fg' => 'var(--color-primary)'],
    ];
    $statusVariant = ['draft' => 'neutral', 'final' => 'success'];
    $statusText    = \App\Models\Budget::STATUS_LABELS;

    $kpiItems = [
        ['val' => $kpis['total'],     'label' => 'Total',      'icon' => 'bar-chart-2',  'fg' => 'var(--color-primary)',      'bg' => 'var(--color-primary-light)'],
        ['val' => $kpis['draft'],     'label' => 'Borrador',   'icon' => 'file-edit',    'fg' => 'var(--text-500)',           'bg' => 'var(--surface-muted)'],
        ['val' => $kpis['final'],     'label' => 'Aprobado',   'icon' => 'check-circle', 'fg' => 'var(--color-success)',      'bg' => 'var(--color-success-bg)'],
    ];
    $visibleKpis = collect($kpiItems)->filter(fn ($k) => $k['val'] > 0)->values();
@endphp

<a href="{{ route('financial.index') }}"
   class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)] mb-5">
    <x-lucide-arrow-left class="w-4 h-4" />
    Volver
</a>

{{-- Flash --}}
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
            <a href="{{ route('financial.data', $client) }}"
               class="inline-flex items-center gap-[6px] h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[13px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)]">
                <x-lucide-database class="w-3.5 h-3.5" />
                {{ $data ? 'Editar datos' : 'Configurar datos' }}
            </a>
            <a href="{{ route('financial.create', ['client_id' => $client->id]) }}"
               class="inline-flex items-center gap-[6px] h-9 px-4 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[13px] font-medium">
                <x-lucide-plus class="w-3.5 h-3.5" />
                Nuevo presupuesto
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

{{-- Tabla de presupuestos --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
    @if($budgets->isEmpty())
    <div class="text-center py-16">
        <div class="w-14 h-14 rounded-[var(--radius-card)] bg-[var(--color-primary-light)] flex items-center justify-center mx-auto mb-4">
            <x-lucide-bar-chart-2 class="w-7 h-7 text-[var(--color-primary)]" />
        </div>
        <p class="text-[14px] font-semibold text-[var(--text-700)]">No hay presupuestos para este cliente</p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">Crea el primero usando la plantilla automática</p>
        <a href="{{ route('financial.create', ['client_id' => $client->id]) }}"
           class="mt-4 inline-flex items-center gap-[6px] h-10 px-5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium rounded-[var(--radius-control)]">
            <x-lucide-plus class="w-4 h-4" />
            Crear primer presupuesto
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
                    <th class="{{ $thClass }} text-left">Presupuesto</th>
                    <th class="{{ $thClass }} text-left hidden md:table-cell">Tipo</th>
                    <th class="{{ $thClass }} text-left hidden lg:table-cell">Período</th>
                    <th class="{{ $thClass }} text-left hidden sm:table-cell">Creado</th>
                    <th class="{{ $thClass }} text-center">Estado</th>
                    <th class="{{ $thClass }} text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($budgets as $budget)
                @php
                    $tv = $typeVariant[$budget->type] ?? $typeVariant['flujo_caja'];
                    $st = $statusText[$budget->status] ?? $statusText['draft'];
                    $sv = $statusVariant[$budget->status] ?? 'neutral';
                @endphp
                <tr class="border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">
                    <td class="px-6 py-[14px]">
                        <a href="{{ route('financial.show', $budget) }}"
                           class="text-[14px] text-[var(--text-900)] hover:text-[var(--color-primary)]">
                            {{ $budget->name }}
                        </a>
                    </td>
                    <td class="px-6 py-[14px] hidden md:table-cell text-[14px] font-medium" style="color: {{ $tv['fg'] }};">
                        {{ $typeLabels[$budget->type] }}
                    </td>
                    <td class="px-6 py-[14px] hidden lg:table-cell text-[14px] text-[var(--text-500)]">
                        {{ $periodLabels[$budget->period_type] }} ·
                        {{ $budget->base_year }}–{{ $budget->base_year + $budget->periods_count }}
                        ({{ $budget->periods_count }} período{{ $budget->periods_count > 1 ? 's' : '' }})
                    </td>
                    <td class="px-6 py-[14px] hidden sm:table-cell text-[14px] text-[var(--text-500)]">
                        {{ $budget->created_at->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-[14px] text-center">
                        <x-status-badge :variant="$sv">{{ $st['label'] }}</x-status-badge>
                    </td>
                    <td class="px-6 py-[14px] text-right">
                        <div class="flex items-center justify-end gap-[10px]">
                            <a href="{{ route('financial.show', $budget) }}"
                               class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Ver presupuesto">
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
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
    @endif
</div>

</x-app-layout>
