<x-app-layout>
<x-slot name="title">Clientes</x-slot>

<div class="max-w-5xl mx-auto space-y-5">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-[14px] text-[var(--text-400)] mb-1">
        <a href="{{ route('clients.index') }}" class="hover:text-[var(--color-primary)]">Clientes</a>
        <x-lucide-chevron-right class="w-3.5 h-3.5" />
        <span class="text-[var(--text-700)] font-medium truncate">{{ $client->name }}</span>
    </nav>

    {{-- Flash --}}
    @if(session('success'))
    <div class="flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
        <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
        {{ session('success') }}
    </div>
    @endif

    {{-- ===== CABECERA ===== --}}
    <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-[var(--color-primary-light)] flex items-center justify-center text-[var(--color-primary)] font-semibold text-[18px] flex-shrink-0">
                    {{ strtoupper(substr($client->name, 0, 2)) }}
                </div>
                <div>
                    <h1 class="text-[22px] font-semibold text-[var(--text-900)]">{{ $client->name }}</h1>
                    <p class="text-[14px] text-[var(--text-500)] mt-0.5">
                        {{ $client->document_type }} {{ $client->full_document }}
                        &bull; {{ $client->person_type === 'natural' ? 'Persona Natural' : 'Persona Jurídica' }}
                        &bull; {{ \App\Models\Client::TAX_RESPONSIBILITIES[$client->tax_regime] ?? $client->tax_regime }}
                    </p>
                    <div class="flex items-center gap-2 mt-2">
                        <x-status-badge :variant="$client->status === 'active' ? 'success' : 'neutral'">
                            {{ $client->status === 'active' ? 'Activo' : 'Inactivo' }}
                        </x-status-badge>
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('clients.edit', $client) }}"
                   class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                    <x-lucide-edit-2 class="w-4 h-4" />
                    Editar
                </a>
                <form method="POST" action="{{ route('clients.destroy', $client) }}"
                      x-data=""
                      x-on:submit.prevent="if(confirm('¿Eliminar a {{ addslashes($client->name) }}? Se eliminarán también sus cuentas de cobro y eventos tributarios.')) $el.submit()">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] border border-[var(--color-danger)]/30 text-[var(--color-danger)] text-[14px] font-medium hover:bg-[var(--color-danger-bg)]">
                        <x-lucide-trash-2 class="w-4 h-4" />
                        Eliminar
                    </button>
                </form>
            </div>
        </div>

        {{-- Información de contacto --}}
        <div class="mt-5 pt-5 border-t border-[var(--border-default)] grid grid-cols-2 sm:grid-cols-4 gap-4">
            @if($client->email)
            <div>
                <p class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em]">Correo</p>
                <a href="mailto:{{ $client->email }}" class="text-[14px] text-[var(--color-primary)] hover:underline mt-0.5 block truncate">{{ $client->email }}</a>
            </div>
            @endif
            @if($client->phone)
            <div>
                <p class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em]">Teléfono</p>
                <p class="text-[14px] text-[var(--text-700)] mt-0.5">{{ $client->phone }}</p>
            </div>
            @endif
            @if($client->city)
            <div>
                <p class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em]">Ciudad</p>
                <p class="text-[14px] text-[var(--text-700)] mt-0.5">{{ $client->city }}{{ $client->department ? ', '.$client->department : '' }}</p>
            </div>
            @endif
            @if($client->contact_person)
            <div>
                <p class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em]">Contacto</p>
                <p class="text-[14px] text-[var(--text-700)] mt-0.5">{{ $client->contact_person }}</p>
            </div>
            @endif
        </div>

        {{-- Obligaciones tributarias --}}
        @if($client->tax_responsibilities && count($client->tax_responsibilities))
        @php
            $obligationNames = \App\Models\TaxObligationType::whereIn('code', $client->tax_responsibilities)->pluck('name', 'code');
        @endphp
        <div class="mt-4 pt-4 border-t border-[var(--border-default)]">
            <p class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em] mb-2">Obligaciones tributarias</p>
            <div class="flex flex-wrap gap-2">
                @foreach($client->tax_responsibilities as $resp)
                <x-status-badge variant="info">{{ $obligationNames[$resp] ?? $resp }}</x-status-badge>
                @endforeach
            </div>
        </div>
        @endif

        @if($client->notes)
        <div class="mt-4 pt-4 border-t border-[var(--border-default)]">
            <p class="text-[11px] text-[var(--text-400)] font-medium uppercase tracking-[0.06em] mb-1">Notas</p>
            <p class="text-[14px] text-[var(--text-700)]">{{ $client->notes }}</p>
        </div>
        @endif
    </div>

    {{-- ===== KPI CARDS ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-5 shadow-[var(--shadow-card)]">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Cuentas de cobro</p>
                <x-lucide-file-text class="w-5 h-5 text-[var(--color-primary)]" />
            </div>
            <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $client->invoices->count() }}</p>
            <p class="text-[12px] text-[var(--text-400)] mt-1">Total cuentas emitidas</p>
        </div>

        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-5 shadow-[var(--shadow-card)]">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Saldo pendiente</p>
                <x-lucide-wallet class="w-5 h-5 {{ $pendingBalance > 0 ? 'text-[var(--color-warning)]' : 'text-[var(--text-400)]' }}" />
            </div>
            <p class="text-[22px] font-bold text-[var(--text-900)]">${{ number_format($pendingBalance, 0, ',', '.') }}</p>
            <p class="text-[12px] text-[var(--text-400)] mt-1">Por cobrar</p>
        </div>

        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-5 shadow-[var(--shadow-card)]">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Eventos tributarios</p>
                <x-lucide-calendar-check class="w-5 h-5 text-[var(--color-primary)]" />
            </div>
            <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $client->taxEvents->count() }}</p>
            <p class="text-[12px] text-[var(--text-400)] mt-1">Obligaciones registradas</p>
        </div>
    </div>

    {{-- ===== ACCESOS RÁPIDOS DE MÓDULOS ===== --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        {{-- Archivo Virtual --}}
        <a href="{{ route('archive.files.index', $client) }}"
           class="flex items-center gap-3 bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-4 hover:shadow-[var(--shadow-card-hover)]">
            <div class="w-10 h-10 rounded-[var(--radius-control)] bg-[var(--color-primary-light)] flex items-center justify-center flex-shrink-0">
                <x-lucide-folder class="w-5 h-5 text-[var(--color-primary)]" />
            </div>
            <div>
                <p class="text-[14px] font-semibold text-[var(--text-900)]">Archivo Virtual</p>
                <p class="text-[12px] text-[var(--text-400)]">Documentos</p>
            </div>
        </a>

        {{-- Cartera --}}
        <a href="{{ route('cartera.client', $client) }}"
           class="flex items-center gap-3 bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-4 hover:shadow-[var(--shadow-card-hover)]">
            <div class="w-10 h-10 rounded-[var(--radius-control)] bg-[var(--color-primary-light)] flex items-center justify-center flex-shrink-0">
                <x-lucide-wallet class="w-5 h-5 text-[var(--color-primary)]" />
            </div>
            <div>
                <p class="text-[14px] font-semibold text-[var(--text-900)]">Cartera</p>
                <p class="text-[12px] text-[var(--text-400)]">Pagos y saldos</p>
            </div>
        </a>

        {{-- Calendario --}}
        <a href="{{ route('tax-events.client-calendar', $client) }}"
           class="flex items-center gap-3 bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-4 hover:shadow-[var(--shadow-card-hover)]">
            <div class="w-10 h-10 rounded-[var(--radius-control)] bg-[var(--color-primary-light)] flex items-center justify-center flex-shrink-0">
                <x-lucide-calendar-check class="w-5 h-5 text-[var(--color-primary)]" />
            </div>
            <div>
                <p class="text-[14px] font-semibold text-[var(--text-900)]">Calendario</p>
                <p class="text-[12px] text-[var(--text-400)]">Obligaciones</p>
            </div>
        </a>
    </div>

    {{-- ===== DOS COLUMNAS ===== --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">

        {{-- Cuentas de cobro recientes --}}
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)]">
            <div class="px-6 py-5 border-b border-[var(--border-default)] flex items-center justify-between">
                <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Cuentas de cobro</h2>
                <a href="{{ Route::has('invoices.create') ? route('invoices.create').'?client='.$client->id : '#' }}"
                   class="text-[13px] text-[var(--color-primary)] hover:underline font-medium">+ Nueva cuenta</a>
            </div>
            @if($recentInvoices->isEmpty())
            <div class="flex flex-col items-center justify-center py-10 text-center">
                <p class="text-[14px] font-semibold text-[var(--text-700)]">Sin cuentas de cobro</p>
                <p class="text-[12px] text-[var(--text-400)] mt-1">Aún no hay cuentas emitidas</p>
            </div>
            @else
            <div>
                @foreach($recentInvoices as $invoice)
                @php
                    $badge = match($invoice->status) {
                        'draft'     => ['text' => 'Borrador',  'variant' => 'neutral'],
                        'sent'      => ['text' => 'Enviada',   'variant' => 'info'],
                        'paid'      => ['text' => 'Pagada',    'variant' => 'success'],
                        'overdue'   => ['text' => 'Vencida',   'variant' => 'danger'],
                        'cancelled' => ['text' => 'Anulada',   'variant' => 'neutral'],
                        default     => ['text' => $invoice->status, 'variant' => 'neutral'],
                    };
                @endphp
                <div class="px-6 py-[14px] flex items-center justify-between gap-3 border-b border-[var(--border-default)] last:border-b-0 hover:bg-[var(--surface-subtle)]">
                    <div>
                        <p class="text-[14px] font-medium text-[var(--color-primary)]">{{ $invoice->number }}</p>
                        <p class="text-[12px] text-[var(--text-400)]">{{ $invoice->issue_date->format('d/m/Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[14px] font-semibold text-[var(--text-900)]">${{ number_format($invoice->total, 0, ',', '.') }}</p>
                        <x-status-badge :variant="$badge['variant']">{{ $badge['text'] }}</x-status-badge>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Próximos vencimientos tributarios --}}
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)]">
            <div class="px-6 py-5 border-b border-[var(--border-default)] flex items-center justify-between">
                <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Vencimientos tributarios</h2>
                <div class="flex items-center gap-3">
                    <a href="{{ route('tax-events.client-calendar', $client) }}"
                       class="text-[13px] text-[var(--color-primary)] hover:underline font-medium">Ver calendario completo</a>
                    <a href="{{ Route::has('tax-events.create') ? route('tax-events.create').'?client='.$client->id : '#' }}"
                       class="text-[13px] text-[var(--text-500)] hover:text-[var(--text-900)] font-medium">+ Agregar evento</a>
                </div>
            </div>
            @if($upcomingEvents->isEmpty())
            <div class="flex flex-col items-center justify-center py-10 text-center">
                <p class="text-[14px] font-semibold text-[var(--text-700)]">Sin eventos tributarios</p>
                <p class="text-[12px] text-[var(--text-400)] mt-1">No hay obligaciones pendientes</p>
            </div>
            @else
            <div>
                @foreach($upcomingEvents as $event)
                @php
                    $days    = now()->startOfDay()->diffInDays($event->due_date->startOfDay(), false);
                    $urgent  = $days <= 5;
                    $warning = !$urgent && $days <= 10;
                    $dotColor = $urgent ? 'var(--color-danger)' : ($warning ? 'var(--color-warning)' : 'var(--color-primary)');
                    $textColor = $urgent ? 'var(--color-danger)' : ($warning ? 'var(--color-warning)' : 'var(--text-700)');
                @endphp
                <div class="px-6 py-[14px] flex items-center gap-3 border-b border-[var(--border-default)] last:border-b-0 hover:bg-[var(--surface-subtle)]">
                    <div class="w-2 h-2 rounded-full flex-shrink-0" style="background: {{ $dotColor }};"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-medium text-[var(--text-900)] truncate">{{ $event->title }}</p>
                        <p class="text-[12px] text-[var(--text-400)]">{{ $event->obligation_type }} &bull; {{ $event->period }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-[13px] font-semibold" style="color: {{ $textColor }};">
                            @if($days === 0) Hoy
                            @elseif($days === 1) Mañana
                            @else {{ $days }}d
                            @endif
                        </p>
                        <p class="text-[12px] text-[var(--text-400)]">{{ $event->due_date->format('d/m/Y') }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>

</div>
</x-app-layout>
