<x-app-layout title="Calendario Tributario">

{{-- Header --}}
<p class="text-[13px] text-[var(--text-500)] mb-6">Selecciona un cliente para ver su calendario de obligaciones</p>

{{-- Estado vacío --}}
@if($clients->isEmpty())
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] text-center py-20">
    <div class="w-16 h-16 rounded-[var(--radius-card)] bg-[var(--color-primary-light)] flex items-center justify-center mx-auto mb-4">
        <x-lucide-calendar-check class="w-8 h-8 text-[var(--color-primary)]" />
    </div>
    <p class="text-[14px] font-semibold text-[var(--text-700)]">No tienes clientes activos aún</p>
    <p class="text-[12px] text-[var(--text-400)] mt-1">Crea un cliente primero para gestionar su calendario tributario</p>
    <a href="{{ route('clients.create') }}"
       class="mt-4 inline-flex items-center gap-[6px] h-10 px-5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium rounded-[var(--radius-control)]">
        <x-lucide-plus class="w-4 h-4" />
        Crear primer cliente
    </a>
</div>

@else

{{-- Grid de tarjetas de clientes --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    @foreach($clients as $client)
    @php
        $clientEvents  = $eventsByClient->get($client->id, collect());
        $pendingCount  = $clientEvents->where('status', 'pending')->count();
        $overdueCount  = $clientEvents->filter(fn($e) => $e->status === 'overdue' || ($e->status === 'pending' && $e->due_date->isPast()))->count();

        $initials = collect(explode(' ', $client->name))
            ->filter()->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');
    @endphp

    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] hover:shadow-[var(--shadow-card-hover)] flex flex-col overflow-hidden">

        {{-- Cabecera de la tarjeta --}}
        <div class="p-5 flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-[var(--color-primary-light)] flex items-center justify-center text-[var(--color-primary)] font-semibold text-[13px] flex-shrink-0">
                {{ $initials }}
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-[14px] font-semibold text-[var(--text-900)] leading-tight truncate">{{ $client->name }}</h3>
                <p class="text-[12px] text-[var(--text-400)] mt-0.5">
                    {{ $client->document_type }} {{ $client->document_number }}
                    @if($client->dv)-{{ $client->dv }}@endif
                </p>
            </div>
        </div>

        {{-- Resumen de obligaciones --}}
        <div class="px-5 pb-4 flex items-center gap-2 flex-wrap">
            @if($overdueCount > 0)
            <x-status-badge variant="danger">{{ $overdueCount }} vencida{{ $overdueCount > 1 ? 's' : '' }}</x-status-badge>
            @endif
            @if($pendingCount > 0)
            <x-status-badge variant="warning">{{ $pendingCount }} pendiente{{ $pendingCount > 1 ? 's' : '' }}</x-status-badge>
            @endif
            @if($pendingCount === 0 && $overdueCount === 0)
            <x-status-badge variant="neutral">Sin obligaciones registradas</x-status-badge>
            @endif
        </div>

        {{-- Botón Ver --}}
        <div class="mt-auto border-t border-[var(--border-default)] px-5 py-3 flex items-center justify-end">
            <a href="{{ route('tax-events.show', $client) }}"
               title="Ver calendario"
               class="inline-flex items-center gap-[6px] h-9 px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
                Ver
            </a>
        </div>

    </div>
    @endforeach
</div>
@endif

</x-app-layout>
