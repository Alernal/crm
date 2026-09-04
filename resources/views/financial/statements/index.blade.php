<x-app-layout>
<x-slot name="title">Estados Financieros</x-slot>

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

{{-- Estado vacío --}}
@if($clients->isEmpty())
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] text-center py-20">
    <div class="w-16 h-16 rounded-[var(--radius-card)] bg-[var(--color-primary-light)] flex items-center justify-center mx-auto mb-4">
        <x-lucide-scale class="w-8 h-8 text-[var(--color-primary)]" />
    </div>
    <p class="text-[14px] font-semibold text-[var(--text-700)]">No tienes clientes activos aún</p>
    <p class="text-[12px] text-[var(--text-400)] mt-1">Crea un cliente primero para comenzar a registrar estados financieros</p>
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
        $clientBudgets  = $budgetsByClient->get($client->id, collect());
        $hasBudgets     = $clientBudgets->isNotEmpty();
        $budgetCount    = $clientBudgets->count();
        $isBalanced     = $balanceByClient[$client->id] ?? null;

        $initials = collect(explode(' ', $client->name))
            ->filter()->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');
    @endphp

    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] hover:shadow-[var(--shadow-card-hover)] flex flex-col overflow-hidden">

        {{-- Cabecera de la tarjeta --}}
        <div class="p-5 flex items-start gap-4">
            <div class="w-11 h-11 rounded-full bg-[var(--color-primary-light)] flex items-center justify-center text-[var(--color-primary)] font-semibold text-[13px] flex-shrink-0">
                {{ $initials }}
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-[14px] font-bold text-[var(--text-900)] leading-tight truncate">{{ $client->name }}</h3>
                <p class="text-[12px] text-[var(--text-400)] mt-0.5">
                    {{ $client->document_type }} {{ $client->document_number }}
                    @if($client->dv) -{{ $client->dv }}@endif
                </p>
                <p class="text-[12px] text-[var(--text-400)] mt-0.5">{{ $client->city }}</p>
            </div>
        </div>

        {{-- Resumen --}}
        <div class="px-5 pb-4">
            @if($hasBudgets)
            <p class="text-[12px] text-[var(--text-400)] flex items-center gap-1.5 flex-wrap">
                {{ $budgetCount }} estado{{ $budgetCount !== 1 ? 's' : '' }} financiero{{ $budgetCount !== 1 ? 's' : '' }}
                @if($isBalanced !== null)
                <x-status-badge :variant="$isBalanced ? 'success' : 'danger'">{{ $isBalanced ? 'Cuadra' : 'Descuadre' }}</x-status-badge>
                @endif
            </p>
            @else
            <p class="text-[12px] text-[var(--text-400)]">Sin estados financieros aún</p>
            @endif
        </div>

        {{-- Botones de acción --}}
        <div class="mt-auto px-5 py-3 flex items-center justify-end gap-2">

            @if($hasBudgets)
            <a href="{{ route('financial.statements.client', $client) }}"
               title="Ver estados financieros"
               class="w-9 h-9 rounded-[var(--radius-control)] bg-[var(--color-primary-light)] hover:bg-[var(--color-primary-light)] text-[var(--color-primary)] flex items-center justify-center">
                <x-lucide-eye class="w-4 h-4" />
            </a>
            @endif

            <a href="{{ route('financial.statements.create', ['client_id' => $client->id]) }}"
               title="Nuevo estado financiero"
               class="w-9 h-9 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white flex items-center justify-center">
                <x-lucide-plus class="w-4 h-4" />
            </a>

        </div>

    </div>
    @endforeach
</div>
@endif

</x-app-layout>
