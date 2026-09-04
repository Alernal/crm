<x-app-layout>
<x-slot name="title">Cartera</x-slot>

@php
    $today = \Carbon\Carbon::today();
    $fieldClass = 'w-full h-10 px-3.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none';
@endphp

<div x-data="{
        stmtModal: false,
        stmtAction: '',
        stmtEmail: '',
        stmtName: '',
        stmtBalance: '',
        abrirStatement(action, email, name, balance) {
            this.stmtAction  = action;
            this.stmtEmail   = email;
            this.stmtName    = name;
            this.stmtBalance = balance;
            this.stmtModal   = true;
        }
     }"
     @keydown.escape.window="stmtModal = false">

{{-- ── Header ─────────────────────────────────────────────── --}}
<div class="flex items-start justify-end mb-6 gap-3 flex-wrap">
    <div class="flex items-center gap-2">
        <button x-data @click="$dispatch('abrir-pago-modal')"
                class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[15px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-wallet class="w-4 h-4" />
            Agregar pago o abono
        </button>
        <a href="{{ route('invoices.create') }}"
           class="inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
            <x-lucide-plus class="w-4 h-4" />
            Nueva cuenta
        </a>
    </div>
</div>

{{-- ── Flash ───────────────────────────────────────────────── --}}
@if(session('success'))
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="mb-5 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[15px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

{{-- ── Antigüedad de cartera ───────────────────────────────── --}}
@php
$agingBuckets = [
    ['key' => 'al_dia',     'label' => 'Al día',       'plazo' => 'Sin vencer',           'icon' => 'check-circle',   'icon_c' => 'var(--color-success)'],
    ['key' => 'dias_1_30',  'label' => '1 – 30 días',  'plazo' => 'Vencida reciente',     'icon' => 'clock',          'icon_c' => 'var(--color-warning)'],
    ['key' => 'dias_31_60', 'label' => '31 – 60 días', 'plazo' => 'Seguimiento urgente',  'icon' => 'alert-triangle', 'icon_c' => '#EA580C'],
    ['key' => 'dias_mas60', 'label' => '+60 días',     'plazo' => 'Cartera crítica',      'icon' => 'x-circle',       'icon_c' => 'var(--color-danger)'],
];
@endphp
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @foreach($agingBuckets as $b)
    @php $saldo = $aging[$b['key']]; @endphp
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">{{ $b['label'] }}</p>
            @svg('lucide-' . $b['icon'], 'w-5 h-5', ['style' => 'color: ' . $b['icon_c'] . ';'])
        </div>
        <p class="text-[22px] font-bold {{ $saldo > 0 ? 'text-[var(--text-900)]' : 'text-[var(--border-strong)]' }}">
            $ {{ number_format($saldo, 0, ',', '.') }}
        </p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">{{ $b['plazo'] }}</p>
    </div>
    @endforeach
</div>

{{-- ── Búsqueda ─────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('cartera.index') }}" class="flex gap-3 items-center mb-5">
    <div class="flex-1 relative">
        <x-lucide-search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-400)]" />
        <input type="text"
               name="search"
               value="{{ $search }}"
               placeholder="Buscar cliente por nombre o NIT…"
               class="w-full h-10 pl-10 pr-3.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[15px] bg-[var(--surface-subtle)] text-[var(--text-700)] outline-none transition-all focus:bg-[var(--surface-card)] focus:border-[var(--border-strong)] focus:ring-2 focus:ring-[var(--color-primary-light)]">
    </div>
    <button type="submit" class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
        Buscar
    </button>
    @if($search)
    <a href="{{ route('cartera.index') }}" class="h-10 flex items-center px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[15px] font-medium hover:bg-[var(--surface-muted)]">
        Limpiar
    </a>
    @endif
</form>

{{-- ── Tabla por cliente ───────────────────────────────────── --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
    <div class="overflow-x-auto p-3">
    <div class="overflow-y-auto max-h-[65vh]">
        <table class="w-full">
            <thead>
                <tr>
                    @php
                        $thClass = 'sticky top-0 z-[1] bg-[var(--surface-card)] border-b border-[var(--border-default)] text-[13px] font-bold text-[var(--text-900)] px-6 py-3.5';
                    @endphp
                    <th class="{{ $thClass }} text-left">Cliente</th>
                    <th class="{{ $thClass }} text-left">Identificación</th>
                    <th class="{{ $thClass }} text-center hidden md:table-cell">Cuentas</th>
                    <th class="{{ $thClass }} text-right hidden lg:table-cell">Facturado</th>
                    <th class="{{ $thClass }} text-right hidden lg:table-cell">Cobrado</th>
                    <th class="{{ $thClass }} text-right">Saldo</th>
                    <th class="{{ $thClass }} text-right hidden lg:table-cell">Días</th>
                    <th class="{{ $thClass }} text-left hidden md:table-cell">Estado</th>
                    <th class="{{ $thClass }} w-14"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($clientSummaries as $summary)
                @php
                    $c           = $summary['client'];
                    $balance     = $summary['balance'];
                    $totalInv    = $summary['total_invoiced'];
                    $paidPct     = $totalInv > 0 ? min(100, (int) round($summary['total_paid'] / $totalInv * 100)) : 0;
                    $hasOverdue  = $summary['count_overdue'] > 0;
                    $hasPending  = $summary['count_pending'] > 0;
                    $daysOv      = $summary['days_overdue'];
                @endphp
                <tr class="border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)] group">

                    {{-- Cliente --}}
                    <td class="px-6 py-5">
                        <div class="min-w-0">
                            <p class="text-[14px] text-[var(--text-500)] leading-snug truncate">{{ $c->name }}</p>
                        </div>
                    </td>

                    {{-- Identificación --}}
                    <td class="px-6 py-5 text-[14px] text-[var(--text-500)] tabular-nums">
                        {{ $c->full_document }}
                    </td>

                    {{-- Cuentas --}}
                    <td class="px-6 py-5 text-center hidden md:table-cell">
                        <p class="text-[14px] text-[var(--text-500)] tabular-nums">{{ $summary['count_total'] }}</p>
                    </td>

                    {{-- Facturado --}}
                    <td class="px-6 py-5 text-right hidden lg:table-cell">
                        <p class="text-[14px] text-[var(--text-500)] tabular-nums">$ {{ number_format($totalInv, 0, ',', '.') }}</p>
                    </td>

                    {{-- Cobrado --}}
                    <td class="px-6 py-5 text-right hidden lg:table-cell">
                        <p class="text-[14px] text-[var(--color-success)] tabular-nums">$ {{ number_format($summary['total_paid'], 0, ',', '.') }}</p>
                        @if($totalInv > 0)
                        <div class="mt-2 h-1.5 bg-[var(--surface-muted)] rounded-full overflow-hidden w-20 ml-auto">
                            <div class="h-full {{ $paidPct >= 100 ? 'bg-[var(--color-success)]' : 'bg-[var(--color-primary)]' }} rounded-full"
                                 style="width: {{ $paidPct }}%"></div>
                        </div>
                        @endif
                    </td>

                    {{-- Saldo --}}
                    <td class="px-6 py-5 text-right">
                        <p class="text-[14px] tabular-nums {{ $balance <= 0 ? 'text-[var(--color-success)]' : 'text-[var(--text-500)]' }}">
                            $ {{ number_format($balance, 0, ',', '.') }}
                        </p>
                    </td>

                    {{-- Días --}}
                    <td class="px-6 py-5 text-right hidden lg:table-cell">
                        @if($hasOverdue && $daysOv > 0)
                        <p class="text-[14px] text-[var(--color-danger)] tabular-nums">{{ $daysOv }}</p>
                        @elseif($balance <= 0)
                        <p class="text-[13px] text-[var(--color-success)]">Al día</p>
                        @else
                        <p class="text-[var(--border-strong)] text-[14px]">—</p>
                        @endif
                    </td>

                    {{-- Estado --}}
                    <td class="px-6 py-5 hidden md:table-cell">
                        @if($hasOverdue)
                            <x-status-badge variant="danger">Vencida</x-status-badge>
                        @elseif($hasPending)
                            <x-status-badge variant="warning">Pendiente</x-status-badge>
                        @else
                            <x-status-badge variant="success">Al día</x-status-badge>
                        @endif
                    </td>

                    {{-- Acciones --}}
                    <td class="px-4 py-5 text-right">
                        <div class="flex items-center justify-end gap-[10px]">
                            @if($c->email)
                            <button @click="abrirStatement(
                                        '{{ route('cartera.send_statement', $c) }}',
                                        '{{ addslashes($c->email ?? '') }}',
                                        '{{ addslashes($c->name) }}',
                                        '${{ number_format($summary['balance'], 0, ',', '.') }}'
                                    )"
                                    title="Enviar estado de cuenta por correo"
                                    class="text-[var(--text-400)] hover:text-[var(--text-900)]">
                                <x-lucide-mail class="w-4 h-4" />
                            </button>
                            @endif
                            <a href="{{ route('cartera.client', $c) }}"
                               title="Ver cuentas de {{ $c->name }}"
                               class="text-[var(--text-400)] hover:text-[var(--text-900)]">
                                <x-lucide-eye class="w-4 h-4" />
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-20 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-16 h-16 bg-[var(--surface-muted)] rounded-[var(--radius-card)] flex items-center justify-center">
                                <x-lucide-wallet class="w-8 h-8 text-[var(--text-400)]" />
                            </div>
            <div>
                                <p class="text-[15px] font-semibold text-[var(--text-700)]">
                                    @if($search) Sin clientes con ese criterio @else No hay cuentas en cartera @endif
                                </p>
                                <p class="text-[13px] text-[var(--text-400)] mt-1">
                                    @if($search)
                                        <a href="{{ route('cartera.index') }}" class="text-[var(--color-primary)] hover:underline">Limpiar búsqueda</a>
                                    @else
                                        Emite cuentas de cobro para comenzar
                                    @endif
                                </p>
                            </div>
                            @unless($search)
                            <a href="{{ route('invoices.create') }}"
                               class="h-10 px-5 flex items-center bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white rounded-[var(--radius-control)] text-[15px] font-medium">
                                Nueva cuenta de cobro
                            </a>
                            @endunless
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
</div>

{{-- ══ MODAL: Enviar estado de cuenta ═════════════════════════ --}}
<div x-show="stmtModal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="display:none"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="absolute inset-0 bg-gray-900/50" @click="stmtModal = false"></div>

    <div class="relative bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-lg z-10"
         @click.stop
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <div class="flex items-center justify-between px-6 py-5 border-b border-[var(--border-default)]">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-[var(--color-primary-light)] rounded-[var(--radius-control)] flex items-center justify-center">
                    <x-lucide-mail class="w-5 h-5 text-[var(--color-primary)]" />
                </div>
                <div>
                    <h2 class="text-[16px] font-bold text-[var(--text-900)]">Enviar estado de cuenta</h2>
                    <p class="text-[12px] text-[var(--text-400)] mt-0.5" x-text="stmtName + ' · PDF adjunto'"></p>
                </div>
            </div>
            <button @click="stmtModal = false"
                    class="p-1.5 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                <x-lucide-x class="w-4 h-4" />
            </button>
        </div>

        <div class="px-6 pt-4">
            <div class="flex items-center justify-between bg-[var(--color-primary-light)] rounded-[var(--radius-control)] px-4 py-3 border border-[var(--border-default)]">
                <div>
                    <p class="text-[14px] font-semibold text-[var(--color-primary-dark)]" x-text="stmtName"></p>
                    <p class="text-[12px] text-[var(--color-primary)]" x-text="stmtEmail"></p>
                </div>
                <div class="text-right">
                    <p class="text-[12px] text-[var(--color-primary)]">Saldo</p>
                    <p class="text-[14px] font-bold text-[var(--color-primary-dark)]" x-text="stmtBalance + ' COP'"></p>
                </div>
            </div>
        </div>

        <form :action="stmtAction" method="POST" class="px-6 py-5 space-y-4">
            @csrf

            <div>
                <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                    Correo electrónico <span class="text-[var(--color-danger)]">*</span>
                </label>
                <input type="email"
                       name="email"
                       :value="stmtEmail"
                       required
                       class="{{ $fieldClass }}">
                <p class="text-[12px] text-[var(--text-400)] mt-1">Puede modificar el destinatario antes de enviar.</p>
            </div>

            <div>
                <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                    Mensaje personalizado <span class="text-[var(--text-400)] font-normal">(opcional)</span>
                </label>
                <textarea name="message" rows="3" maxlength="1000"
                          placeholder="Ej: Le informamos que a la fecha presenta el siguiente estado de cuenta…"
                          class="w-full px-3.5 py-2.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none resize-none"></textarea>
            </div>

            <div class="bg-[var(--color-warning-bg)] rounded-[var(--radius-control)] px-4 py-3 border border-[#FCD34D] space-y-1">
                <p class="text-[12px] font-medium text-[var(--color-warning-text)] mb-1.5">El correo incluirá:</p>
                @foreach([
                    'PDF del estado de cuenta completo con todas las cuentas activas',
                    'Resumen de saldos: facturado, abonado y pendiente',
                    'Medios de pago disponibles y datos bancarios',
                    'Canales de contacto para regularizar la situación',
                ] as $item)
                <div class="flex items-center gap-2 text-[12px] text-[var(--color-warning-text)]">
                    <x-lucide-check-circle class="w-3.5 h-3.5 text-[var(--color-warning)] flex-shrink-0" />
                    {{ $item }}
                </div>
                @endforeach
            </div>

            <div class="flex items-center justify-end gap-3 pt-1 border-t border-[var(--border-default)]">
                <button type="button" @click="stmtModal = false"
                        class="px-4 h-10 text-[14px] text-[var(--text-500)] hover:text-[var(--text-700)]">
                    Cancelar
                </button>
                <button type="submit"
                        class="inline-flex items-center gap-[6px] h-10 px-5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white rounded-[var(--radius-control)] text-[14px] font-medium">
                    <x-lucide-mail class="w-4 h-4" />
                    Enviar estado de cuenta
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     Modal — Agregar pago o abono
═══════════════════════════════════════════════════════════════ --}}
<div x-data="pagoModal()"
     @abrir-pago-modal.window="abrirModal()"
     @keydown.escape.window="open && cerrar()">

    {{-- Overlay --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display:none">

        {{-- Backdrop --}}
        <div @click="cerrar()" class="fixed inset-0 bg-gray-900/50"></div>

        {{-- Panel --}}
        <div class="flex min-h-full items-start justify-center p-4 pt-10">
            <div class="relative bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-2xl overflow-hidden"
                 @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 style="max-height: 88vh; display: flex; flex-direction: column;">

                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-5 border-b border-[var(--border-default)] flex-shrink-0">
                    <div class="flex items-center gap-2.5">
                        <button x-show="step !== 'clientes'"
                                @click="irAtras()"
                                class="p-1.5 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                            <x-lucide-arrow-left class="w-4 h-4" />
                        </button>
                        <div class="w-9 h-9 bg-[var(--color-primary-light)] rounded-[var(--radius-control)] flex items-center justify-center">
                            <x-lucide-wallet class="w-4 h-4 text-[var(--color-primary)]" />
                        </div>
                        <div>
                            <h2 class="text-[16px] font-bold text-[var(--text-900)]">Agregar pago o abono</h2>
                            <p class="text-[12px] text-[var(--text-400)] mt-0.5">
                                <span x-show="step === 'clientes'">Selecciona el cliente</span>
                                <span x-show="step === 'cuentas'" x-text="clienteActivo?.name"></span>
                            </p>
                        </div>
                    </div>
                    <button @click="cerrar()"
                            class="p-2 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                        <x-lucide-x class="w-4 h-4" />
                    </button>
                </div>

                {{-- ── PASO 1: Lista de clientes ─────────────────────── --}}
                <div x-show="step === 'clientes'" class="flex flex-col overflow-hidden flex-1">

                    {{-- Buscador --}}
                    <div class="px-6 py-4 border-b border-[var(--border-default)] flex-shrink-0">
                        <div class="relative">
                            <x-lucide-search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-400)] pointer-events-none" />
                            <input type="text"
                                   x-model="busqueda"
                                   x-ref="inputBusqueda"
                                   placeholder="Buscar cliente por nombre o documento…"
                                   class="{{ $fieldClass }} pl-10">
                        </div>
                    </div>

                    {{-- Lista de clientes --}}
                    <div class="overflow-y-auto flex-1">

                        <template x-if="clientesFiltrados.length === 0">
                            <div class="px-6 py-16 text-center">
                                <div class="w-12 h-12 bg-[var(--surface-muted)] rounded-[var(--radius-card)] flex items-center justify-center mx-auto mb-3">
                                    <x-lucide-wallet class="w-6 h-6 text-[var(--text-400)]" />
                                </div>
                                <p class="text-[14px] font-semibold text-[var(--text-500)]">
                                    <span x-show="busqueda">Sin coincidencias para "<span x-text="busqueda"></span>"</span>
                                    <span x-show="!busqueda">No hay cuentas pendientes de cobro</span>
                                </p>
                            </div>
                        </template>

                        <template x-for="cliente in clientesFiltrados" :key="cliente.id">
                            <button @click="seleccionarCliente(cliente)"
                                    class="w-full flex items-center justify-between px-6 py-4 border-b border-[var(--border-default)] last:border-b-0
                                           hover:bg-[var(--surface-subtle)] text-left group">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-full bg-[var(--color-primary-light)] flex items-center justify-center flex-shrink-0">
                                        <span class="text-[12px] font-semibold text-[var(--color-primary)]"
                                              x-text="cliente.iniciales"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-[14px] text-[var(--text-900)] truncate"
                                           x-text="cliente.name"></p>
                                        <p class="text-[12px] text-[var(--text-400)] font-mono mt-0.5"
                                           x-text="cliente.doc"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 flex-shrink-0 ml-4">
                                    <div class="text-right">
                                        <p class="text-[14px] font-bold text-[var(--text-900)]"
                                           x-text="formatCOP(cliente.saldo)"></p>
                                        <p class="text-[12px] text-[var(--text-400)]"
                                           x-text="cliente.cuentas + (cliente.cuentas === 1 ? ' cuenta' : ' cuentas') + ' pendiente' + (cliente.cuentas === 1 ? '' : 's')"></p>
                                    </div>
                                    <x-lucide-chevron-right class="w-4 h-4 text-[var(--border-strong)] group-hover:text-[var(--color-primary)]" />
                                </div>
                            </button>
                        </template>

                    </div>{{-- /overflow-y-auto --}}
                </div>{{-- /paso 1 --}}

                {{-- ── PASO 2: Cuentas del cliente + formularios inline ─ --}}
                <div x-show="step === 'cuentas'" class="flex flex-col overflow-hidden flex-1">

                    {{-- Cabecera del cliente --}}
                    <div class="px-6 py-3 bg-[var(--color-primary-light)] border-b border-[var(--border-default)] flex-shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-[var(--color-primary)] flex items-center justify-center flex-shrink-0">
                                <span class="text-[11px] font-semibold text-white"
                                      x-text="clienteActivo?.iniciales"></span>
                            </div>
                            <div>
                                <p class="text-[14px] font-semibold text-[var(--color-primary-dark)]"
                                   x-text="clienteActivo?.name"></p>
                                <p class="text-[12px] text-[var(--color-primary)] font-mono"
                                   x-text="clienteActivo?.doc"></p>
                            </div>
                            <div class="ml-auto text-right">
                                <p class="text-[12px] text-[var(--color-primary)]">Saldo total</p>
                                <p class="text-[14px] font-bold text-[var(--color-primary-dark)]"
                                   x-text="formatCOP(clienteActivo?.saldo || 0)"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Lista de cuentas --}}
                    <div class="overflow-y-auto flex-1 p-5 space-y-3">

                        <template x-if="cuentasActivas.length === 0">
                            <div class="py-12 text-center">
                                <p class="text-[14px] text-[var(--text-400)]">Este cliente no tiene cuentas pendientes</p>
                            </div>
                        </template>

                        <template x-for="cuenta in cuentasActivas" :key="cuenta.id">
                            <div class="border rounded-[var(--radius-card)] overflow-hidden"
                                 :class="cuentaAbierta === cuenta.id
                                     ? 'border-[var(--color-success)]/40 shadow-[var(--shadow-card)]'
                                     : 'border-[var(--border-default)]'">

                                {{-- Fila de la cuenta --}}
                                <div class="px-4 py-3 flex items-center gap-3 justify-between">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-mono font-bold text-[14px] text-[var(--text-900)]"
                                                  x-text="cuenta.number"></span>
                                            <span class="inline-flex items-center px-[10px] py-[3px] rounded-[var(--radius-badge)] text-[12px] font-medium"
                                                  :class="cuenta.status === 'overdue'
                                                      ? 'bg-[var(--color-danger-bg)] text-[var(--color-danger-text)]'
                                                      : 'bg-[var(--surface-muted)] text-[var(--text-700)]'"
                                                  x-text="cuenta.status === 'overdue' ? 'Vencida' : 'Emitida'">
                                            </span>
                                        </div>
                                        <p class="text-[12px] text-[var(--text-400)] mt-0.5">
                                            Saldo:&nbsp;<span class="font-semibold text-[var(--text-700)]"
                                                              x-text="formatCOP(cuenta.balance)"></span>
                                            <template x-if="cuenta.due_date">
                                                <span>&nbsp;· Vence: <span x-text="cuenta.due_date"></span></span>
                                            </template>
                                        </p>
                                    </div>
                                    <button @click="cuentaAbierta = (cuentaAbierta === cuenta.id ? null : cuenta.id)"
                                            :class="cuentaAbierta === cuenta.id
                                                ? 'bg-[var(--surface-muted)] text-[var(--text-700)] hover:bg-[var(--border-default)]'
                                                : 'bg-[var(--color-success)] hover:opacity-90 text-white'"
                                            class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 h-8
                                                   rounded-[var(--radius-control)] text-[13px] font-medium">
                                        <x-lucide-plus class="w-3.5 h-3.5" />
                                        <span x-text="cuentaAbierta === cuenta.id ? 'Cancelar' : 'Registrar pago'"></span>
                                    </button>
                                </div>

                                {{-- Formulario de pago inline --}}
                                <div x-show="cuentaAbierta === cuenta.id"
                                     x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="opacity-0"
                                     x-transition:enter-end="opacity-100"
                                     class="border-t border-[var(--color-success)]/30 bg-[var(--color-success-bg)]/30 px-4 py-4">

                                    <p class="text-[11px] font-medium text-[var(--color-success-text)] uppercase tracking-[0.06em] mb-3">
                                        Registrar pago — <span x-text="cuenta.number"></span>
                                    </p>

                                    <form method="POST" :action="cuenta.pay_url" enctype="multipart/form-data">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input type="hidden" name="_from"
                                               :value="'/cartera/cliente/' + (clienteActivo?.id || '')">

                                        <div class="grid grid-cols-2 gap-3 mb-3">
                                            <div x-data="{ monto: cuenta.balance }">
                                                <label class="block text-[12px] font-medium text-[var(--text-700)] mb-1">
                                                    Monto <span class="text-[var(--color-danger)]">*</span>
                                                </label>
                                                <div class="relative">
                                                    <span class="absolute inset-y-0 left-2.5 flex items-center text-[var(--text-400)] text-[12px]">$</span>
                                                    <input type="text"
                                                           name="amount"
                                                           x-money="monto"
                                                           class="w-full pl-6 pr-2 h-9 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-[12px] font-medium text-[var(--text-700)] mb-1">
                                                    Fecha <span class="text-[var(--color-danger)]">*</span>
                                                </label>
                                                <input type="date" name="payment_date"
                                                       value="{{ now()->format('Y-m-d') }}"
                                                       class="w-full px-2.5 h-9 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none">
                                            </div>
                                            <div>
                                                <label class="block text-[12px] font-medium text-[var(--text-700)] mb-1">
                                                    Método <span class="text-[var(--color-danger)]">*</span>
                                                </label>
                                                <select name="payment_method"
                                                        class="w-full px-2.5 h-9 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none">
                                                    <option value="">Seleccionar…</option>
                                                    <option value="efectivo">Efectivo</option>
                                                    <option value="transferencia">Transferencia bancaria</option>
                                                    <option value="cheque">Cheque</option>
                                                    <option value="otro">Otro</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[12px] font-medium text-[var(--text-700)] mb-1">Referencia</label>
                                                <input type="text" name="reference"
                                                       placeholder="# comprobante"
                                                       class="w-full px-2.5 h-9 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none">
                                            </div>
                                        </div>

                                        <div x-data="{ nombre: '' }">
                                            <label class="block text-[12px] font-medium text-[var(--text-700)] mb-1">Soporte</label>
                                            <label class="flex items-center gap-2 px-2.5 h-9 border border-dashed border-[var(--border-strong)] rounded-[var(--radius-control)] cursor-pointer hover:border-[var(--color-success)] hover:bg-[var(--color-success-bg)]/40">
                                                <x-lucide-upload class="w-4 h-4 text-[var(--text-400)] flex-shrink-0" />
                                                <span class="text-[12px] text-[var(--text-500)] truncate" x-text="nombre || 'Seleccionar…'"></span>
                                                <input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png" class="hidden"
                                                       @change="nombre = $event.target.files[0]?.name || ''">
                                            </label>
                                            <p class="text-[12px] text-[var(--text-400)] mt-0.5">PDF, JPG o PNG · 5 MB máx.</p>
                                        </div>

                                        <div class="flex justify-end mt-3">
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 h-10 px-4
                                                           bg-[var(--color-success)] hover:opacity-90 text-white
                                                           rounded-[var(--radius-control)] text-[14px] font-medium">
                                                <x-lucide-check-circle class="w-4 h-4" />
                                                Guardar pago
                                            </button>
                                        </div>
                                    </form>
                                </div>{{-- /formulario --}}
                            </div>{{-- /tarjeta cuenta --}}
                        </template>

                    </div>{{-- /overflow-y-auto --}}
                </div>{{-- /paso 2 --}}

            </div>{{-- /panel --}}
        </div>
    </div>{{-- /overlay --}}
</div>{{-- /x-data --}}

<script>
function pagoModal() {
    const cuentas = @json($pendingForModal);

    const mapaClientes = {};
    cuentas.forEach(function(c) {
        if (!mapaClientes[c.client_id]) {
            const name = c.client_name || '';
            mapaClientes[c.client_id] = {
                id: c.client_id,
                name: name,
                doc: c.client_doc || '',
                iniciales: name.substring(0, 2).toUpperCase(),
                saldo: 0,
                cuentas: 0,
            };
        }
        mapaClientes[c.client_id].saldo  += c.balance;
        mapaClientes[c.client_id].cuentas++;
    });

    const clientes = Object.values(mapaClientes).sort(function(a, b) {
        return b.saldo - a.saldo;
    });

    return {
        open: false,
        step: 'clientes',
        busqueda: '',
        clienteActivo: null,
        cuentaAbierta: null,
        clientes: clientes,
        cuentas: cuentas,

        get clientesFiltrados() {
            if (!this.busqueda.trim()) return this.clientes;
            const t = this.busqueda.toLowerCase();
            return this.clientes.filter(function(c) {
                return c.name.toLowerCase().includes(t) || c.doc.toLowerCase().includes(t);
            });
        },

        get cuentasActivas() {
            if (!this.clienteActivo) return [];
            const id = this.clienteActivo.id;
            return this.cuentas.filter(function(c) { return c.client_id === id; });
        },

        abrirModal() {
            this.open = true;
            const self = this;
            this.$nextTick(function() {
                if (self.$refs.inputBusqueda) self.$refs.inputBusqueda.focus();
            });
        },

        cerrar() {
            this.open = false;
            this.step = 'clientes';
            this.busqueda = '';
            this.clienteActivo = null;
            this.cuentaAbierta = null;
        },

        seleccionarCliente(cliente) {
            this.clienteActivo = cliente;
            this.cuentaAbierta = null;
            this.step = 'cuentas';
        },

        irAtras() {
            this.step = 'clientes';
            this.clienteActivo = null;
            this.cuentaAbierta = null;
        },

        formatCOP(amount) {
            return '$ ' + Math.round(amount || 0).toLocaleString('es-CO');
        }
    };
}
</script>

</div>{{-- /x-data stmtModal --}}

</x-app-layout>
