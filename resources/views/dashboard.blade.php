<x-app-layout>
<x-slot name="title">Dashboard</x-slot>

{{-- ===== KPI CARDS ===== --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">

    {{-- Clientes activos --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Clientes activos</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($activeClients) }}</p>
        </div>
    </div>

    {{-- Cartera por cobrar --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
        </div>
        <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Cartera por cobrar</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5">
                ${{ number_format($receivable, 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Cobrado este mes --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
        <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Cobrado este mes</p>
            <p class="text-2xl font-bold text-gray-900 mt-0.5">
                ${{ number_format($collectedThisMonth, 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Vencimientos próximos --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4
                {{ $upcomingTaxEvents > 0 ? 'border-red-200 bg-red-50' : '' }}">
        <div class="w-12 h-12 {{ $upcomingTaxEvents > 0 ? 'bg-red-100' : 'bg-gray-100' }} rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 {{ $upcomingTaxEvents > 0 ? 'text-red-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <div>
            <p class="text-xs {{ $upcomingTaxEvents > 0 ? 'text-red-500' : 'text-gray-500' }} font-medium uppercase tracking-wide">Vencen en 15 días</p>
            <p class="text-2xl font-bold {{ $upcomingTaxEvents > 0 ? 'text-red-700' : 'text-gray-900' }} mt-0.5">
                {{ $upcomingTaxEvents }}
                <span class="text-sm font-normal {{ $upcomingTaxEvents > 0 ? 'text-red-500' : 'text-gray-400' }}">obligaciones</span>
            </p>
        </div>
    </div>

</div>

{{-- Alerta facturas vencidas --}}
@if($overdueInvoices > 0)
<div class="mb-5 bg-amber-50 border border-amber-200 rounded-xl px-5 py-3 flex items-center gap-3">
    <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <p class="text-sm text-amber-800">
        Tienes <strong>{{ $overdueInvoices }} {{ $overdueInvoices === 1 ? 'cuenta vencida' : 'cuentas vencidas' }}</strong> sin pagar.
        <a href="{{ Route::has('invoices.index') ? route('invoices.index') : '#' }}" class="underline font-medium ml-1">Ver cuentas →</a>
    </p>
</div>
@endif

{{-- ===== CUERPO: dos columnas ===== --}}
<div class="grid grid-cols-1 xl:grid-cols-5 gap-5">

    {{-- Cuentas de cobro recientes (3/5) --}}
    <div class="xl:col-span-3 bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800 text-sm">Cuentas de cobro recientes</h2>
            <a href="{{ Route::has('invoices.index') ? route('invoices.index') : '#' }}"
               class="text-xs text-blue-600 hover:text-blue-700 font-medium">Ver todas →</a>
        </div>

        @if($recentInvoices->isEmpty())
        <div class="px-5 py-12 text-center">
            <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-400 text-sm">Aún no hay cuentas de cobro</p>
            <a href="{{ Route::has('invoices.create') ? route('invoices.create') : '#' }}"
               class="mt-3 inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700 font-medium">
                + Crear primera cuenta
            </a>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-400 uppercase tracking-wide border-b border-gray-50">
                        <th class="px-5 py-3 text-left font-medium">N°</th>
                        <th class="px-5 py-3 text-left font-medium">Cliente</th>
                        <th class="px-5 py-3 text-right font-medium">Total</th>
                        <th class="px-5 py-3 text-center font-medium">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($recentInvoices as $invoice)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $invoice->number }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $invoice->client->name }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-900">
                            ${{ number_format($invoice->total, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-3 text-center">
                            @php
                                $badge = match($invoice->status) {
                                    'draft'     => ['text' => 'Borrador',  'class' => 'bg-gray-100 text-gray-600'],
                                    'sent'      => ['text' => 'Enviada',   'class' => 'bg-blue-100 text-blue-700'],
                                    'paid'      => ['text' => 'Pagada',    'class' => 'bg-emerald-100 text-emerald-700'],
                                    'overdue'   => ['text' => 'Vencida',   'class' => 'bg-red-100 text-red-700'],
                                    'cancelled' => ['text' => 'Anulada',   'class' => 'bg-gray-100 text-gray-400'],
                                    default     => ['text' => $invoice->status, 'class' => 'bg-gray-100 text-gray-600'],
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge['class'] }}">
                                {{ $badge['text'] }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Próximos vencimientos tributarios (2/5) --}}
    <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800 text-sm">Próximos vencimientos</h2>
            <a href="{{ Route::has('tax-events.index') ? route('tax-events.index') : '#' }}"
               class="text-xs text-blue-600 hover:text-blue-700 font-medium">Ver calendario →</a>
        </div>

        @if($nextTaxEvents->isEmpty())
        <div class="px-5 py-12 text-center">
            <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-gray-400 text-sm">Sin vencimientos próximos</p>
        </div>
        @else
        <div class="divide-y divide-gray-50">
            @foreach($nextTaxEvents as $event)
            @php
                $daysLeft = now()->startOfDay()->diffInDays($event->due_date->startOfDay(), false);
                $urgent   = $daysLeft <= 5;
                $warning  = !$urgent && $daysLeft <= 10;
            @endphp
            <div class="px-5 py-3.5 flex items-start gap-3">
                <div class="flex-shrink-0 mt-0.5">
                    <div class="w-2 h-2 rounded-full mt-1.5
                        {{ $urgent ? 'bg-red-500' : ($warning ? 'bg-amber-400' : 'bg-blue-400') }}">
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $event->title }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ $event->client->name }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-xs font-semibold {{ $urgent ? 'text-red-600' : ($warning ? 'text-amber-600' : 'text-gray-600') }}">
                        @if($daysLeft === 0) Hoy
                        @elseif($daysLeft === 1) Mañana
                        @elseif($daysLeft < 0) Vencido
                        @else {{ $daysLeft }}d
                        @endif
                    </p>
                    <p class="text-xs text-gray-400">{{ $event->due_date->format('d/m/Y') }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>

</x-app-layout>
