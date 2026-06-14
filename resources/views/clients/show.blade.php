<x-app-layout>
<x-slot name="title">{{ $client->name }}</x-slot>

<div class="max-w-5xl space-y-5">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('clients.index') }}" class="hover:text-blue-600 transition-colors">Clientes</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-gray-800 font-medium">{{ $client->name }}</span>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3 rounded-lg">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- ===== CABECERA ===== --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-blue-700 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                    {{ strtoupper(substr($client->name, 0, 2)) }}
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $client->name }}</h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ $client->document_type }} {{ $client->full_document }}
                        &bull; {{ $client->person_type === 'natural' ? 'Persona Natural' : 'Persona Jurídica' }}
                    </p>
                    <div class="flex items-center gap-2 mt-2">
                        @php
                            $regimeColors = [
                                'gran_contribuyente'   => 'bg-purple-100 text-purple-700',
                                'autorretenedor'       => 'bg-blue-100 text-blue-700',
                                'agente_retencion_iva' => 'bg-indigo-100 text-indigo-700',
                                'regimen_simple'       => 'bg-amber-100 text-amber-700',
                                'no_aplica'            => 'bg-gray-100 text-gray-600',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium {{ $regimeColors[$client->tax_regime] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ \App\Models\Client::TAX_RESPONSIBILITIES[$client->tax_regime] ?? $client->tax_regime }}
                        </span>
                        @if($client->status === 'active')
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Activo
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactivo
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('clients.edit', $client) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Editar
                </a>
                <form method="POST" action="{{ route('clients.destroy', $client) }}"
                      x-data=""
                      x-on:submit.prevent="if(confirm('¿Eliminar a {{ addslashes($client->name) }}? Se eliminarán también sus cuentas de cobro y eventos tributarios.')) $el.submit()">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 border border-red-200 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Eliminar
                    </button>
                </form>
            </div>
        </div>

        {{-- Información de contacto --}}
        <div class="mt-5 pt-5 border-t border-gray-100 grid grid-cols-2 sm:grid-cols-4 gap-4">
            @if($client->email)
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Correo</p>
                <a href="mailto:{{ $client->email }}" class="text-sm text-blue-600 hover:underline mt-0.5 block truncate">{{ $client->email }}</a>
            </div>
            @endif
            @if($client->phone)
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Teléfono</p>
                <p class="text-sm text-gray-800 mt-0.5">{{ $client->phone }}</p>
            </div>
            @endif
            @if($client->city)
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Ciudad</p>
                <p class="text-sm text-gray-800 mt-0.5">{{ $client->city }}{{ $client->department ? ', '.$client->department : '' }}</p>
            </div>
            @endif
            @if($client->contact_person)
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Contacto</p>
                <p class="text-sm text-gray-800 mt-0.5">{{ $client->contact_person }}</p>
            </div>
            @endif
        </div>

        {{-- Obligaciones tributarias --}}
        @if($client->tax_responsibilities && count($client->tax_responsibilities))
        <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-2">Obligaciones tributarias</p>
            <div class="flex flex-wrap gap-2">
                @foreach($client->tax_responsibilities as $resp)
                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                    {{ \App\Models\Client::TAX_OBLIGATIONS[$resp] ?? $resp }}
                </span>
                @endforeach
            </div>
        </div>
        @endif

        @if($client->notes)
        <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Notas</p>
            <p class="text-sm text-gray-600">{{ $client->notes }}</p>
        </div>
        @endif
    </div>

    {{-- ===== KPI CARDS ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Cuentas de cobro</p>
                <p class="text-2xl font-bold text-gray-900">{{ $client->invoices->count() }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4 {{ $pendingBalance > 0 ? 'border-amber-200' : '' }}">
            <div class="w-10 h-10 {{ $pendingBalance > 0 ? 'bg-amber-50' : 'bg-gray-50' }} rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 {{ $pendingBalance > 0 ? 'text-amber-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Saldo pendiente</p>
                <p class="text-2xl font-bold {{ $pendingBalance > 0 ? 'text-amber-700' : 'text-gray-900' }}">
                    ${{ number_format($pendingBalance, 0, ',', '.') }}
                </p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Eventos tributarios</p>
                <p class="text-2xl font-bold text-gray-900">{{ $client->taxEvents->count() }}</p>
            </div>
        </div>
    </div>

    {{-- ===== DOS COLUMNAS ===== --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">

        {{-- Cuentas de cobro recientes --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 text-sm">Cuentas de cobro</h3>
                <a href="{{ Route::has('invoices.create') ? route('invoices.create').'?client='.$client->id : '#' }}"
                   class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700 font-medium">
                    + Nueva cuenta
                </a>
            </div>
            @if($recentInvoices->isEmpty())
            <div class="px-5 py-10 text-center">
                <p class="text-sm text-gray-400">Sin cuentas de cobro</p>
            </div>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($recentInvoices as $invoice)
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
                <div class="px-5 py-3 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $invoice->number }}</p>
                        <p class="text-xs text-gray-400">{{ $invoice->issue_date->format('d/m/Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-900">${{ number_format($invoice->total, 0, ',', '.') }}</p>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge['class'] }}">
                            {{ $badge['text'] }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Próximos vencimientos tributarios --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 text-sm">Vencimientos tributarios</h3>
                <a href="{{ Route::has('tax-events.create') ? route('tax-events.create').'?client='.$client->id : '#' }}"
                   class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700 font-medium">
                    + Agregar evento
                </a>
            </div>
            @if($upcomingEvents->isEmpty())
            <div class="px-5 py-10 text-center">
                <p class="text-sm text-gray-400">Sin eventos tributarios pendientes</p>
            </div>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($upcomingEvents as $event)
                @php
                    $days    = now()->startOfDay()->diffInDays($event->due_date->startOfDay(), false);
                    $urgent  = $days <= 5;
                    $warning = !$urgent && $days <= 10;
                @endphp
                <div class="px-5 py-3 flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full flex-shrink-0 mt-0.5
                        {{ $urgent ? 'bg-red-500' : ($warning ? 'bg-amber-400' : 'bg-blue-400') }}">
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $event->title }}</p>
                        <p class="text-xs text-gray-400">{{ $event->obligation_type }} &bull; {{ $event->period }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-xs font-semibold {{ $urgent ? 'text-red-600' : ($warning ? 'text-amber-600' : 'text-gray-600') }}">
                            @if($days === 0) Hoy
                            @elseif($days === 1) Mañana
                            @else {{ $days }}d
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

</div>
</x-app-layout>
