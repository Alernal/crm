<x-app-layout>
<x-slot name="title">Cartera</x-slot>

@php
    $today = \Carbon\Carbon::today();

    $statusConfig = [
        'draft'     => ['label' => 'Borrador', 'variant' => 'neutral'],
        'sent'      => ['label' => 'Emitida',  'variant' => 'info'],
        'paid'      => ['label' => 'Pagada',   'variant' => 'success'],
        'overdue'   => ['label' => 'Vencida',  'variant' => 'danger'],
        'cancelled' => ['label' => 'Anulada',  'variant' => 'neutral'],
    ];
    $sc = $statusConfig[$invoice->status] ?? $statusConfig['draft'];

    $paidAmount  = $invoice->paid_amount;
    $balance     = $invoice->balance;
    $paidPct     = $invoice->total > 0 ? min(100, (int) round($paidAmount / (float) $invoice->total * 100)) : 0;
    $canPay      = !in_array($invoice->status, ['paid', 'cancelled']);

    $daysOverdue = 0;
    $daysLeft    = null;
    if ($invoice->due_date) {
        $diff = $today->diffInDays($invoice->due_date, false);
        if ($diff < 0) {
            $daysOverdue = abs($diff);
        } else {
            $daysLeft = $diff;
        }
    }

    $methodLabels = [
        'efectivo'      => 'Efectivo',
        'transferencia' => 'Transferencia bancaria',
        'cheque'        => 'Cheque',
        'otro'          => 'Otro',
    ];

    $fieldClass = 'w-full h-10 px-3.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none';
@endphp

<div class="max-w-4xl mx-auto"
     x-data="{ modalPago: false }"
     @keydown.escape.window="modalPago && (modalPago = false)">

{{-- ── Breadcrumb + Header ─────────────────────────────────── --}}
<div class="flex items-start justify-between mb-6 gap-4">
    <div>
        <nav class="flex items-center gap-1.5 text-[14px] text-[var(--text-400)] mb-2">
            <a href="{{ route('cartera.index') }}" class="hover:text-[var(--color-primary)]">Cartera</a>
            <x-lucide-chevron-right class="w-3.5 h-3.5" />
            <a href="{{ route('cartera.client', $invoice->client) }}" class="hover:text-[var(--color-primary)] truncate">{{ $invoice->client->name }}</a>
            <x-lucide-chevron-right class="w-3.5 h-3.5" />
            <span class="text-[var(--text-700)] font-medium font-mono">{{ $invoice->number }}</span>
        </nav>
        <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-[22px] font-bold text-[var(--text-900)] font-mono tracking-tight">{{ $invoice->number }}</h1>
            <x-status-badge :variant="$sc['variant']">{{ $sc['label'] }}</x-status-badge>
        </div>
        <p class="text-[14px] text-[var(--text-500)] mt-1">{{ $invoice->client->name }}</p>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
        @if($canPay)
        <button @click="modalPago = true"
                class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">
            <x-lucide-wallet class="w-4 h-4" />
            Registrar pago
        </button>
        @endif
        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank"
           class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-download class="w-4 h-4" />
            PDF
        </a>
        <a href="{{ route('invoices.show', $invoice) }}"
           class="h-10 flex items-center px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
            Ver cuenta
        </a>
    </div>
</div>

{{-- ── Flash ───────────────────────────────────────────────── --}}
@if(session('success'))
<div class="mb-5 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="mb-5 bg-[var(--color-danger-bg)] border border-[var(--color-danger)]/20 text-[var(--color-danger-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <p class="font-semibold mb-1">Por favor corrige los errores:</p>
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- ── Tarjetas de resumen ─────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5">
        <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Total facturado</p>
        <p class="text-[22px] font-bold text-[var(--text-900)] mt-1">$ {{ number_format($invoice->total, 0, ',', '.') }}</p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">COP</p>
    </div>

    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5">
        <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Abonado</p>
        <p class="text-[22px] font-bold text-[var(--text-900)] mt-1">$ {{ number_format($paidAmount, 0, ',', '.') }}</p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">{{ $paidPct }}% del total</p>
    </div>

    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5">
        <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Saldo pendiente</p>
        <p class="text-[22px] font-bold {{ $balance <= 0 ? 'text-[var(--color-success)]' : ($invoice->status === 'overdue' ? 'text-[var(--color-danger)]' : 'text-[var(--text-900)]') }} mt-1">
            $ {{ number_format(max(0, $balance), 0, ',', '.') }}
        </p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">{{ $balance <= 0 ? 'Saldo en cero' : 'Por cobrar' }}</p>
    </div>

    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5">
        <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Vencimiento</p>
        @if($invoice->due_date)
            <p class="text-[18px] font-bold {{ $daysOverdue > 0 && $invoice->status !== 'paid' ? 'text-[var(--color-danger)]' : 'text-[var(--text-900)]' }} mt-1">
                {{ $invoice->due_date->format('d/m/Y') }}
            </p>
            @if($daysOverdue > 0 && $invoice->status !== 'paid')
                <p class="text-[12px] text-[var(--color-danger)] font-semibold mt-1">{{ $daysOverdue }} días vencida</p>
            @elseif($daysLeft !== null && $invoice->status !== 'paid')
                <p class="text-[12px] text-[var(--text-400)] mt-1">
                    {{ $daysLeft === 0 ? 'Vence hoy' : "Vence en {$daysLeft} días" }}
                </p>
            @else
                <p class="text-[12px] text-[var(--color-success)] mt-1">Pagada</p>
            @endif
        @else
            <p class="text-[18px] font-bold text-[var(--border-strong)] mt-1">—</p>
            <p class="text-[12px] text-[var(--text-400)] mt-1">Sin fecha límite</p>
        @endif
    </div>

</div>

{{-- ── Barra de progreso de cobro ──────────────────────────── --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] px-6 py-5 mb-5">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Progreso de cobro</h2>
        <span class="text-[14px] font-bold {{ $paidPct >= 100 ? 'text-[var(--color-success)]' : 'text-[var(--color-primary)]' }}">
            {{ $paidPct }}%
        </span>
    </div>
    <div class="h-3 bg-[var(--surface-muted)] rounded-full overflow-hidden">
        <div class="h-full rounded-full {{ $paidPct >= 100 ? 'bg-[var(--color-success)]' : 'bg-[var(--color-primary)]' }}"
             style="width: {{ $paidPct }}%"></div>
    </div>
    <div class="flex justify-between mt-2">
        <span class="text-[12px] text-[var(--text-400)]">$ 0</span>
        @if($paidAmount > 0 && $paidPct < 100)
            <span class="text-[12px] text-[var(--color-success)] font-medium">
                Abonado: $ {{ number_format($paidAmount, 0, ',', '.') }}
            </span>
        @endif
        <span class="text-[12px] text-[var(--text-400)]">$ {{ number_format($invoice->total, 0, ',', '.') }}</span>
    </div>
</div>

{{-- ── Historial de pagos ──────────────────────────────────── --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden mb-5">
    <div class="flex items-center justify-between px-6 py-5 border-b border-[var(--border-default)]">
        <div class="flex items-center gap-2">
            <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Historial de pagos</h2>
            @if($invoice->payments->count() > 0)
            <span class="text-[12px] text-[var(--text-400)]">· {{ $invoice->payments->count() }} {{ $invoice->payments->count() === 1 ? 'pago' : 'pagos' }}</span>
            @endif
        </div>
        @if($canPay)
        <button @click="modalPago = true"
                class="inline-flex items-center gap-1.5 px-3 h-8 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white rounded-[var(--radius-control)] text-[13px] font-medium">
            <x-lucide-plus class="w-3.5 h-3.5" />
            Agregar pago
        </button>
        @endif
    </div>

    @if($invoice->payments->count() > 0)
    <table class="w-full">
        <thead>
            <tr class="border-b border-[var(--border-default)]">
                <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-left">Fecha</th>
                <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-right">Monto</th>
                <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-left hidden sm:table-cell">Método</th>
                <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-left hidden md:table-cell">Referencia</th>
                <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-left hidden lg:table-cell">Notas</th>
                <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-center hidden lg:table-cell">Soporte</th>
                <th class="px-6 py-3 w-12"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->payments as $payment)
            <tr class="border-b border-[var(--surface-muted)] hover:bg-[var(--surface-subtle)] group">
                <td class="px-6 py-[14px]">
                    <p class="font-medium text-[14px] text-[var(--text-700)]">{{ $payment->payment_date->format('d/m/Y') }}</p>
                    <p class="text-[12px] text-[var(--text-400)] mt-0.5 sm:hidden">{{ $methodLabels[$payment->payment_method] ?? $payment->payment_method }}</p>
                </td>
                <td class="px-6 py-[14px] text-right">
                    <p class="text-[14px] font-semibold text-[var(--color-success)]">$ {{ number_format($payment->amount, 0, ',', '.') }}</p>
                </td>
                <td class="px-6 py-[14px] hidden sm:table-cell text-[13px] text-[var(--text-500)]">
                    {{ $methodLabels[$payment->payment_method] ?? $payment->payment_method }}
                </td>
                <td class="px-6 py-[14px] hidden md:table-cell">
                    <span class="font-mono text-[12px] text-[var(--text-500)]">{{ $payment->reference ?: '—' }}</span>
                </td>
                <td class="px-6 py-[14px] hidden lg:table-cell">
                    <span class="text-[12px] text-[var(--text-500)]">{{ $payment->notes ?: '—' }}</span>
                </td>
                <td class="px-6 py-[14px] text-center hidden lg:table-cell">
                    @if($payment->receipt_path)
                    <a href="{{ route('payments.receipt', $payment) }}"
                       target="_blank"
                       title="Ver soporte"
                       class="inline-flex items-center justify-center w-7 h-7 rounded-[var(--radius-control)] bg-[var(--color-primary-light)] text-[var(--color-primary)]">
                        <x-lucide-download class="w-3.5 h-3.5" />
                    </a>
                    @else
                    <span class="text-[var(--border-strong)] text-[12px]">—</span>
                    @endif
                </td>
                <td class="px-6 py-[14px] text-right">
                    @php $amountStr = number_format($payment->amount, 0, ',', '.'); @endphp
                    <form method="POST" action="{{ route('payments.destroy', $payment) }}"
                          x-data=""
                          x-on:submit.prevent="if(confirm('¿Eliminar este pago de $ {{ $amountStr }}? Esta acción no se puede deshacer.')) $el.submit()">
                        @csrf @method('DELETE')
                        <button type="submit"
                                title="Eliminar pago"
                                class="p-1.5 rounded-[var(--radius-control)] hover:bg-[var(--color-danger-bg)] text-[var(--color-danger)]/60 hover:text-[var(--color-danger)] opacity-0 group-hover:opacity-100">
                            <x-lucide-trash-2 class="w-4 h-4" />
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-[var(--surface-subtle)] border-t border-[var(--border-default)]">
                <td class="px-6 py-3 text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Total recibido</td>
                <td class="px-6 py-3 text-right font-bold text-[var(--color-success)]">
                    $ {{ number_format($paidAmount, 0, ',', '.') }}
                </td>
                <td colspan="5" class="px-6 py-3"></td>
            </tr>
        </tfoot>
    </table>
    @else
    <div class="px-6 py-10 text-center">
        <div class="w-12 h-12 bg-[var(--surface-muted)] rounded-[var(--radius-card)] flex items-center justify-center mx-auto mb-3">
            <x-lucide-wallet class="w-6 h-6 text-[var(--text-400)]" />
        </div>
        <p class="text-[14px] font-semibold text-[var(--text-500)]">Sin pagos registrados</p>
        @if($canPay)
        <button @click="modalPago = true"
                class="mt-3 inline-flex items-center gap-1.5 h-10 px-5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white rounded-[var(--radius-control)] text-[14px] font-medium">
            Registrar primer pago
        </button>
        @endif
    </div>
    @endif
</div>

{{-- ── Detalle de servicios (colapsable) ──────────────────── --}}
<div x-data="{ open: false }"
     class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden mb-5">

    <button @click="open = !open"
            class="w-full flex items-center justify-between px-6 py-5 text-left hover:bg-[var(--surface-subtle)]">
        <h2 class="text-[16px] font-semibold text-[var(--text-900)]">
            Detalle de servicios ({{ $invoice->items->count() }} ítem{{ $invoice->items->count() !== 1 ? 's' : '' }})
        </h2>
        <span :class="open ? 'rotate-180' : ''" class="transition-transform duration-200">
            <x-lucide-chevron-down class="w-4 h-4 text-[var(--text-400)]" />
        </span>
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="border-t border-[var(--border-default)]">
        <table class="w-full">
            <thead>
                <tr class="border-b border-[var(--border-default)]">
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-left">Descripción</th>
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-right hidden sm:table-cell">Cantidad</th>
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-right hidden sm:table-cell">Precio unit.</th>
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-right hidden sm:table-cell">IVA</th>
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr class="border-b border-[var(--surface-muted)] hover:bg-[var(--surface-subtle)]">
                    <td class="px-6 py-[14px]">
                        <p class="font-medium text-[14px] text-[var(--text-900)]">{{ $item->description }}</p>
                        @if($item->vat_rate > 0)
                        <p class="text-[12px] text-[var(--color-warning)] mt-0.5 sm:hidden">IVA {{ number_format($item->vat_rate, 0) }}%</p>
                        @endif
                    </td>
                    <td class="px-6 py-[14px] text-right text-[14px] text-[var(--text-700)] hidden sm:table-cell">
                        {{ number_format($item->quantity, 0) }}
                    </td>
                    <td class="px-6 py-[14px] text-right text-[14px] text-[var(--text-700)] hidden sm:table-cell">
                        $ {{ number_format($item->unit_price, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-[14px] text-right hidden sm:table-cell">
                        @if($item->vat_rate > 0)
                        <span class="text-[12px] text-[var(--color-warning)] font-medium">{{ number_format($item->vat_rate, 0) }}%</span>
                        @else
                        <span class="text-[var(--border-strong)]">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-[14px] text-right text-[14px] text-[var(--text-900)]">
                        $ {{ number_format($item->subtotal, 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-[var(--border-default)] flex justify-end bg-[var(--surface-subtle)]">
            <div class="w-56 space-y-1.5">
                <div class="flex justify-between text-[14px] text-[var(--text-500)]">
                    <span>Subtotal</span>
                    <span>$ {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-[14px] text-[var(--text-500)]">
                    <span>IVA</span>
                    <span>$ {{ number_format($invoice->vat_amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-[18px] font-bold text-[var(--text-900)] border-t border-[var(--border-default)] pt-2">
                    <span>Total</span>
                    <span>$ {{ number_format($invoice->total, 0, ',', '.') }} COP</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Cliente ─────────────────────────────────────────────── --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] px-6 py-5 mb-6">
    <h2 class="text-[16px] font-semibold text-[var(--text-900)] mb-4">Datos del cliente</h2>
    <div class="flex items-start gap-4">
        <div class="w-10 h-10 rounded-full bg-[var(--color-primary-light)] flex items-center justify-center flex-shrink-0">
            <span class="text-[13px] font-semibold text-[var(--color-primary)]">{{ strtoupper(substr($invoice->client->name, 0, 2)) }}</span>
        </div>
        <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2">
            <div>
                <p class="text-[12px] text-[var(--text-400)]">Nombre / Razón social</p>
                <p class="text-[14px] font-semibold text-[var(--text-900)]">{{ $invoice->client->name }}</p>
            </div>
            <div>
                <p class="text-[12px] text-[var(--text-400)]">{{ $invoice->client->document_type }}</p>
                <p class="text-[14px] font-mono font-semibold text-[var(--text-900)]">{{ $invoice->client->full_document }}</p>
            </div>
            @if($invoice->client->email)
            <div>
                <p class="text-[12px] text-[var(--text-400)]">Correo electrónico</p>
                <p class="text-[14px] text-[var(--text-700)]">{{ $invoice->client->email }}</p>
            </div>
            @endif
            @if($invoice->client->phone)
            <div>
                <p class="text-[12px] text-[var(--text-400)]">Teléfono</p>
                <p class="text-[14px] text-[var(--text-700)]">{{ $invoice->client->phone }}</p>
            </div>
            @endif
            @if($invoice->client->city || $invoice->client->address)
            <div class="sm:col-span-2">
                <p class="text-[12px] text-[var(--text-400)]">Dirección</p>
                <p class="text-[14px] text-[var(--text-700)]">
                    {{ implode(' · ', array_filter([$invoice->client->address, $invoice->client->city, $invoice->client->department])) }}
                </p>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ── Footer ──────────────────────────────────────────────── --}}
<div class="flex items-center justify-end gap-3">
    @if(!in_array($invoice->status, ['paid', 'cancelled']))
    <a href="{{ route('invoices.edit', $invoice) }}"
       class="h-10 flex items-center px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)]">
        Editar cuenta
    </a>
    @endif
</div>

{{-- ══ MODAL: Registrar pago ════════════════════════════════ --}}
@if($canPay)
<div x-show="modalPago"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="display:none"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="absolute inset-0 bg-gray-900/50"
         @click="modalPago = false"></div>

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
                    <x-lucide-wallet class="w-4 h-4 text-[var(--color-primary)]" />
                </div>
                <div>
                    <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Registrar pago</h2>
                    <p class="text-[12px] text-[var(--text-400)] mt-0.5 font-mono">{{ $invoice->number }} · Saldo: $ {{ number_format(max(0,$balance), 0, ',', '.') }}</p>
                </div>
            </div>
            <button @click="modalPago = false"
                    class="p-1.5 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                <x-lucide-x class="w-4 h-4" />
            </button>
        </div>

        <form method="POST"
              action="{{ route('payments.store', $invoice) }}"
              enctype="multipart/form-data"
              class="px-6 py-5 space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                        Monto <span class="text-[var(--color-danger)]">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-[var(--text-400)] text-[14px]">$</span>
                        <input type="number"
                               name="amount"
                               value="{{ old('amount', number_format(max(0,$balance), 2, '.', '')) }}"
                               min="0.01" step="0.01"
                               class="{{ $fieldClass }} pl-7">
                    </div>
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                        Fecha <span class="text-[var(--color-danger)]">*</span>
                    </label>
                    <input type="date"
                           name="payment_date"
                           value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                           class="{{ $fieldClass }}">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                        Método <span class="text-[var(--color-danger)]">*</span>
                    </label>
                    <select name="payment_method" class="{{ $fieldClass }}">
                        <option value="">Seleccionar…</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia bancaria</option>
                        <option value="cheque">Cheque</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">Referencia</label>
                    <input type="text"
                           name="reference"
                           value="{{ old('reference') }}"
                           maxlength="100"
                           placeholder="# comprobante"
                           class="{{ $fieldClass }}">
                </div>
            </div>

            <div>
                <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">Notas</label>
                <input type="text"
                       name="notes"
                       value="{{ old('notes') }}"
                       maxlength="500"
                       placeholder="Observación opcional…"
                       class="{{ $fieldClass }}">
            </div>

            <div>
                <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">Soporte / comprobante</label>
                <label class="flex items-center gap-3 px-3.5 h-10 border border-dashed border-[var(--border-strong)] rounded-[var(--radius-control)] cursor-pointer hover:border-[var(--color-primary)] hover:bg-[var(--color-primary-light)]"
                       x-data="{ nombre: '' }">
                    <x-lucide-upload class="w-4 h-4 text-[var(--text-400)] flex-shrink-0" />
                    <span class="text-[14px] text-[var(--text-500)]" x-text="nombre || 'Seleccionar archivo…'"></span>
                    <input type="file"
                           name="receipt"
                           accept=".pdf,.jpg,.jpeg,.png"
                           class="hidden"
                           @change="nombre = $event.target.files[0]?.name || ''">
                </label>
                <p class="text-[12px] text-[var(--text-400)] mt-1">PDF, JPG o PNG — máx. 5 MB</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-[var(--border-default)]">
                <button type="button" @click="modalPago = false"
                        class="px-4 h-10 text-[14px] text-[var(--text-500)] hover:text-[var(--text-700)]">
                    Cancelar
                </button>
                <button type="submit"
                        class="inline-flex items-center gap-[6px] h-10 px-5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white rounded-[var(--radius-control)] text-[14px] font-medium">
                    <x-lucide-check-circle class="w-4 h-4" />
                    Guardar pago
                </button>
            </div>
        </form>
    </div>
</div>
@endif

</div>
</x-app-layout>
