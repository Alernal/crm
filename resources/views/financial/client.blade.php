<x-app-layout>
<x-slot name="title">Financiero</x-slot>

@php
    $typeLabels  = \App\Models\Budget::TYPES;
    $periodLabels= \App\Models\Budget::PERIOD_TYPES;
    $typeVariant = [
        'ventas'     => ['icon' => 'trending-up',    'bg' => 'var(--color-success-bg)', 'fg' => 'var(--color-success)'],
        'gastos'     => ['icon' => 'trending-down',  'bg' => 'var(--color-danger-bg)',  'fg' => 'var(--color-danger)'],
        'compras'    => ['icon' => 'shopping-cart',  'bg' => 'var(--color-warning-bg)', 'fg' => 'var(--color-warning)'],
        'flujo_caja' => ['icon' => 'bar-chart-2',    'bg' => 'var(--color-primary-light)', 'fg' => 'var(--color-primary)'],
        'nomina'     => ['icon' => 'users',          'bg' => 'var(--surface-muted)',    'fg' => 'var(--text-700)'],
    ];
    $statusVariant = ['draft' => 'neutral', 'projected' => 'info', 'final' => 'success'];
    $statusText    = \App\Models\Budget::STATUS_LABELS;

    $initials = collect(explode(' ', $client->name))
        ->filter()->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');
@endphp

<nav class="flex items-center gap-1.5 text-[14px] text-[var(--text-400)] mb-5">
    <a href="{{ route('financial.index') }}" class="hover:text-[var(--color-primary)]">Financiero</a>
    <x-lucide-chevron-right class="w-3.5 h-3.5" />
    <span class="text-[var(--text-700)] font-medium truncate">{{ $client->name }}</span>
</nav>

{{-- Flash --}}
@if(session('success'))
<div class="mb-5 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

{{-- Cabecera del cliente --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-6 mb-5">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-full bg-[var(--color-primary-light)] flex items-center justify-center text-[var(--color-primary)] font-semibold text-[16px] flex-shrink-0">
                {{ $initials }}
            </div>
            <div>
                <h1 class="text-[20px] font-semibold text-[var(--text-900)]">{{ $client->name }}</h1>
                <p class="text-[13px] text-[var(--text-500)] mt-0.5">
                    {{ $client->document_type }} {{ $client->document_number }}
                    @if($client->dv)-{{ $client->dv }}@endif
                    @if($client->city) · {{ $client->city }}@endif
                </p>
                <div class="flex items-center gap-2 mt-2 flex-wrap">
                    @php
                        $kpiItems = [
                            ['val' => $kpis['total'],     'label' => 'Total'],
                            ['val' => $kpis['draft'],     'label' => 'Borrador'],
                            ['val' => $kpis['projected'], 'label' => 'Proyectado'],
                            ['val' => $kpis['final'],     'label' => 'Aprobado'],
                        ];
                    @endphp
                    @foreach($kpiItems as $k)
                    @if($k['val'] > 0)
                    <x-status-badge variant="neutral">{{ $k['val'] }} {{ $k['label'] }}{{ $k['val'] > 1 ? 's' : '' }}</x-status-badge>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0 flex-wrap justify-end">
            <a href="{{ route('financial.variables', $client) }}"
               class="inline-flex items-center gap-[6px] h-9 px-3.5 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[13px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)]">
                <x-lucide-settings class="w-3.5 h-3.5" />
                {{ $variables ? 'Editar variables' : 'Configurar variables' }}
            </a>
            <a href="{{ route('financial.create', ['client_id' => $client->id]) }}"
               class="inline-flex items-center gap-[6px] h-9 px-4 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[13px] font-medium">
                <x-lucide-plus class="w-3.5 h-3.5" />
                Nuevo presupuesto
            </a>
        </div>
    </div>
</div>

{{-- Lista de presupuestos --}}
@if($budgets->isEmpty())
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] text-center py-16">
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

<div class="space-y-3">
    @foreach($budgets as $budget)
    @php
        $tv = $typeVariant[$budget->type] ?? $typeVariant['ventas'];
        $st = $statusText[$budget->status] ?? $statusText['draft'];
        $sv = $statusVariant[$budget->status] ?? 'neutral';
    @endphp

    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] hover:shadow-[var(--shadow-card-hover)]">
        <div class="flex items-center gap-4 px-5 py-4">

            {{-- Ícono tipo --}}
            <div class="w-[38px] h-[38px] rounded-[8px] flex items-center justify-center flex-shrink-0" style="background: {{ $tv['bg'] }};">
                @svg('lucide-' . $tv['icon'], 'w-5 h-5', ['style' => 'color: ' . $tv['fg'] . ';'])
            </div>

            {{-- Info principal --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('financial.show', $budget) }}"
                       class="text-[15px] font-semibold text-[var(--text-900)] hover:text-[var(--color-primary)] truncate">
                        {{ $budget->name }}
                    </a>
                    <x-status-badge :variant="$sv">{{ $st['label'] }}</x-status-badge>
                </div>
                <p class="text-[13px] text-[var(--text-500)] mt-0.5">
                    <span class="font-medium" style="color: {{ $tv['fg'] }};">{{ $typeLabels[$budget->type] }}</span>
                    <span class="mx-1">·</span>
                    {{ $periodLabels[$budget->period_type] }}
                    <span class="mx-1">·</span>
                    {{ $budget->base_year }} – {{ $budget->base_year + $budget->periods_count }}
                    ({{ $budget->periods_count }} período{{ $budget->periods_count > 1 ? 's' : '' }})
                </p>
            </div>

            {{-- Acciones --}}
            <div class="flex items-center gap-[10px] flex-shrink-0">
                {{-- Proyectar --}}
                <form method="POST" action="{{ route('financial.project', $budget) }}">
                    @csrf
                    <button type="submit"
                            title="Proyectar"
                            class="text-[var(--text-400)] hover:text-[var(--text-900)]">
                        <x-lucide-trending-up class="w-4 h-4" />
                    </button>
                </form>

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
                            class="text-[var(--color-danger)]/60 hover:text-[var(--color-danger)]" title="Eliminar">
                        <x-lucide-trash-2 class="w-4 h-4" />
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

</x-app-layout>
