<x-app-layout>
<x-slot name="title">Cuentas de Cobro</x-slot>

@php
    $statusConfig = [
        'draft'     => ['label' => 'Borrador',  'variant' => 'neutral'],
        'sent'      => ['label' => 'Emitida',   'variant' => 'info'],
        'paid'      => ['label' => 'Pagada',    'variant' => 'success'],
        'overdue'   => ['label' => 'Vencida',   'variant' => 'danger'],
        'cancelled' => ['label' => 'Anulada',   'variant' => 'neutral'],
    ];
    $sc          = $statusConfig[$invoice->status] ?? $statusConfig['draft'];
    $paidAmount  = $invoice->paid_amount;
    $balance     = $invoice->balance;
    $paidPct     = $invoice->total > 0 ? min(100, (int) round($paidAmount / (float) $invoice->total * 100)) : 0;
    $canPay      = in_array($invoice->status, ['sent', 'overdue']);
    $methodLabels = [
        'efectivo'      => 'Efectivo',
        'transferencia' => 'Transferencia bancaria',
        'cheque'        => 'Cheque',
        'otro'          => 'Otro',
    ];
    $fieldClass = 'w-full h-10 px-3.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none';
@endphp

<div class="max-w-4xl mx-auto"
     x-data="{ modalPago: false, modalEmail: false, isPrinting: false, printUrl: '' }"
     @keydown.escape.window="modalPago = false; modalEmail = false">

    {{-- Breadcrumb + acciones --}}
    <div class="flex items-start justify-between mb-5 gap-4">
        <div>
            <nav class="flex items-center gap-1.5 text-[14px] text-[var(--text-400)] mb-1">
                <a href="{{ route('invoices.index') }}" class="hover:text-[var(--color-primary)]">Cuentas de cobro</a>
                <x-lucide-chevron-right class="w-3.5 h-3.5" />
                <span class="text-[var(--text-700)] font-medium font-mono">{{ $invoice->number }}</span>
            </nav>
        </div>
        <div class="flex items-center gap-2 flex-wrap">

            {{-- Enviar por correo --}}
            @if($invoice->client->email)
            <button @click="modalEmail = true"
                    class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                <x-lucide-mail class="w-4 h-4" />
                Enviar por correo
            </button>
            @endif

            {{-- Dropdown: Imprimir --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open"
                        class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                    <x-lucide-printer class="w-4 h-4" />
                    Imprimir
                    <x-lucide-chevron-down class="w-3.5 h-3.5 text-[var(--text-400)]" />
                </button>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 top-full mt-1.5 w-56 bg-[var(--surface-card)] rounded-[var(--radius-control)] border border-[var(--border-default)] shadow-[var(--shadow-card-hover)] z-30 overflow-hidden py-1"
                     style="display:none">
                    <button @click="printUrl = '{{ route('invoices.print', $invoice) }}'; isPrinting = true; open = false"
                            class="w-full text-left px-4 py-2.5 text-[14px] text-[var(--text-700)] hover:bg-[var(--surface-muted)] flex items-center gap-2.5">
                        <x-lucide-file-text class="w-4 h-4 text-[var(--text-400)] flex-shrink-0" />
                        Solo cuenta de cobro
                    </button>
                    <div class="h-px bg-[var(--border-default)] mx-3"></div>
                    <button @click="printUrl = '{{ route('invoices.print_statement', $invoice) }}'; isPrinting = true; open = false"
                            class="w-full text-left px-4 py-2.5 text-[14px] text-[var(--text-700)] hover:bg-[var(--surface-muted)] flex items-center gap-2.5">
                        <x-lucide-list-check class="w-4 h-4 text-[var(--text-400)] flex-shrink-0" />
                        Con estado de cuenta
                    </button>
                </div>
            </div>

            {{-- Dropdown: PDF --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open"
                        class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                    <x-lucide-download class="w-4 h-4" />
                    PDF
                    <x-lucide-chevron-down class="w-3.5 h-3.5 text-[var(--text-400)]" />
                </button>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 top-full mt-1.5 w-56 bg-[var(--surface-card)] rounded-[var(--radius-control)] border border-[var(--border-default)] shadow-[var(--shadow-card-hover)] z-30 overflow-hidden py-1"
                     style="display:none">
                    <a href="{{ route('invoices.pdf', $invoice) }}"
                       @click="open = false"
                       class="block px-4 py-2.5 text-[14px] text-[var(--text-700)] hover:bg-[var(--surface-muted)] flex items-center gap-2.5">
                        <x-lucide-file-text class="w-4 h-4 text-[var(--text-400)] flex-shrink-0" />
                        Solo cuenta de cobro
                    </a>
                    <div class="h-px bg-[var(--border-default)] mx-3"></div>
                    <a href="{{ route('invoices.pdf_statement', $invoice) }}"
                       @click="open = false"
                       class="block px-4 py-2.5 text-[14px] text-[var(--text-700)] hover:bg-[var(--surface-muted)] flex items-center gap-2.5">
                        <x-lucide-list-check class="w-4 h-4 text-[var(--text-400)] flex-shrink-0" />
                        Con estado de cuenta
                    </a>
                </div>
            </div>
            @if($canPay)
            <button @click="modalPago = true"
                    class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">
                <x-lucide-credit-card class="w-4 h-4" />
                Registrar pago
            </button>
            @endif
            @if(!in_array($invoice->status, ['paid','cancelled']))
            <a href="{{ route('invoices.edit', $invoice) }}"
               class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                <x-lucide-edit-2 class="w-4 h-4" />
                Editar
            </a>
            @endif
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-4 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
        <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
        {{ session('success') }}
    </div>
    @endif

    {{-- Cabecera de la cuenta --}}
    <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] p-6 mb-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h1 class="text-[22px] font-bold text-[var(--text-900)] font-mono tracking-tight">{{ $invoice->number }}</h1>
                    <x-status-badge :variant="$sc['variant']">{{ $sc['label'] }}</x-status-badge>
                </div>
                <p class="text-[var(--text-500)] text-[14px]">
                    Emisión: <span class="text-[var(--text-700)] font-medium">{{ $invoice->issue_date->format('d \d\e F \d\e Y') }}</span>
                    @if($invoice->due_date)
                    &nbsp;·&nbsp; Vence: <span class="font-medium {{ $invoice->due_date->isPast() && !in_array($invoice->status,['paid','cancelled']) ? 'text-[var(--color-danger)]' : 'text-[var(--text-700)]' }}">{{ $invoice->due_date->format('d \d\e F \d\e Y') }}</span>
                    @endif
                </p>
            </div>
            <div class="text-right">
                <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] mb-0.5">Total</p>
                <p class="text-[26px] font-bold text-[var(--text-900)] tracking-tight">${{ number_format($invoice->total, 0, ',', '.') }}</p>
                <p class="text-[12px] text-[var(--text-400)] mb-3">COP</p>
                @if(!in_array($invoice->status, ['draft','cancelled']))
                <div class="space-y-1 text-right">
                    <div class="flex items-center justify-end gap-4 text-[12px]">
                        <span class="text-[var(--text-400)]">Abonado</span>
                        <span class="font-semibold text-[var(--color-success)]">${{ number_format($paidAmount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-end gap-4 text-[12px]">
                        <span class="text-[var(--text-400)]">Saldo</span>
                        <span class="font-bold {{ $balance <= 0 ? 'text-[var(--color-success)]' : 'text-[var(--text-900)]' }}">${{ number_format(max(0,$balance), 0, ',', '.') }}</span>
                    </div>
                    <div class="h-1.5 bg-[var(--surface-muted)] rounded-full overflow-hidden w-32 ml-auto mt-2">
                        <div class="h-full {{ $paidPct >= 100 ? 'bg-[var(--color-success)]' : 'bg-[var(--color-primary)]' }} rounded-full"
                             style="width: {{ $paidPct }}%"></div>
                    </div>
                    <p class="text-[12px] text-[var(--text-400)]">{{ $paidPct }}% cobrado</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Cliente --}}
    <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] px-6 py-5 mb-5">
        <h2 class="text-[16px] font-semibold text-[var(--text-900)] mb-3">Cliente</h2>
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-full bg-[var(--color-primary-light)] flex items-center justify-center flex-shrink-0">
                <span class="text-[12px] font-semibold text-[var(--color-primary)]">{{ strtoupper(substr($invoice->client->name, 0, 2)) }}</span>
            </div>
            <div>
                <p class="font-semibold text-[14px] text-[var(--text-900)]">{{ $invoice->client->name }}</p>
                <p class="text-[14px] text-[var(--text-500)]">{{ $invoice->client->document_type }} {{ $invoice->client->full_document }}</p>
                @if($invoice->client->address || $invoice->client->city)
                <p class="text-[14px] text-[var(--text-500)]">{{ implode(', ', array_filter([$invoice->client->address, $invoice->client->city, $invoice->client->department])) }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Ítems --}}
    <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] overflow-hidden mb-5">
        <div class="px-6 py-5 border-b border-[var(--border-default)]">
            <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Detalle de servicios</h2>
        </div>
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
                        <p class="text-[12px] text-[var(--color-warning)] mt-0.5 sm:hidden">IVA {{ $item->vat_rate }}%</p>
                        @endif
                    </td>
                    <td class="px-6 py-[14px] text-right text-[14px] text-[var(--text-700)] hidden sm:table-cell">{{ number_format($item->quantity, 0) }}</td>
                    <td class="px-6 py-[14px] text-right text-[14px] text-[var(--text-700)] hidden sm:table-cell">${{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="px-6 py-[14px] text-right hidden sm:table-cell">
                        @if($item->vat_rate > 0)
                        <span class="text-[12px] text-[var(--color-warning)] font-medium">{{ $item->vat_rate }}%</span>
                        @else
                        <span class="text-[var(--border-strong)]">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-[14px] text-right font-semibold text-[14px] text-[var(--text-900)]">${{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-[var(--border-default)] flex justify-end">
            <div class="w-60 space-y-1.5">
                <div class="flex justify-between text-[14px] text-[var(--text-700)]">
                    <span>Subtotal</span>
                    <span>${{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-[14px] text-[var(--text-700)]">
                    <span>IVA</span>
                    <span>${{ number_format($invoice->vat_amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-[18px] font-bold text-[var(--text-900)] border-t border-[var(--border-default)] pt-2">
                    <span>Total</span>
                    <span>${{ number_format($invoice->total, 0, ',', '.') }} COP</span>
                </div>
            </div>
        </div>
    </div>

    @if($invoice->notes)
    <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] px-6 py-5 mb-5">
        <h2 class="text-[16px] font-semibold text-[var(--text-900)] mb-2">Notas</h2>
        <p class="text-[14px] text-[var(--text-700)] whitespace-pre-line">{{ $invoice->notes }}</p>
    </div>
    @endif

    {{-- ── Historial de pagos ──────────────────────────────── --}}
    @if(!in_array($invoice->status, ['draft','cancelled']))
    <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] overflow-hidden mb-5">
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
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-center hidden lg:table-cell">Soporte</th>
                    <th class="px-6 py-3 w-10"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->payments as $payment)
                <tr class="border-b border-[var(--surface-muted)] hover:bg-[var(--surface-subtle)] group">
                    <td class="px-6 py-[14px]">
                        <p class="font-medium text-[14px] text-[var(--text-700)]">{{ $payment->payment_date->format('d/m/Y') }}</p>
                    </td>
                    <td class="px-6 py-[14px] text-right">
                        <p class="font-semibold text-[14px] text-[var(--color-success)]">$ {{ number_format($payment->amount, 0, ',', '.') }}</p>
                    </td>
                    <td class="px-6 py-[14px] hidden sm:table-cell text-[13px] text-[var(--text-500)]">
                        {{ $methodLabels[$payment->payment_method] ?? $payment->payment_method }}
                    </td>
                    <td class="px-6 py-[14px] hidden md:table-cell">
                        <span class="font-mono text-[12px] text-[var(--text-500)]">{{ $payment->reference ?: '—' }}</span>
                    </td>
                    <td class="px-6 py-[14px] text-center hidden lg:table-cell">
                        @if($payment->receipt_path)
                        <a href="{{ route('payments.receipt', $payment) }}"
                           target="_blank"
                           title="Ver soporte"
                           class="inline-flex items-center justify-center w-7 h-7 rounded-[var(--radius-control)] bg-[var(--color-primary-light)] hover:bg-[var(--color-primary-light)] text-[var(--color-primary)]">
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
                              x-on:submit.prevent="if(confirm('¿Eliminar pago de $ {{ $amountStr }}?')) $el.submit()">
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
                    <td colspan="4" class="px-6 py-3"></td>
                </tr>
            </tfoot>
        </table>
        @else
        <div class="px-6 py-10 text-center">
            <p class="text-[14px] text-[var(--text-400)]">Sin pagos registrados aún.</p>
            @if($canPay)
            <button @click="modalPago = true"
                    class="mt-3 inline-flex items-center gap-1.5 h-10 px-5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white rounded-[var(--radius-control)] text-[14px] font-medium">
                Registrar primer pago
            </button>
            @endif
        </div>
        @endif
    </div>
    @endif

    <div class="flex items-center justify-end mt-2">
        <form method="POST" action="{{ route('invoices.destroy', $invoice) }}"
              x-data=""
              x-on:submit.prevent="if(confirm('¿Eliminar cuenta {{ $invoice->number }}? Esta acción no se puede deshacer.')) $el.submit()">
            @csrf @method('DELETE')
            <button type="submit" class="text-[14px] text-[var(--color-danger)] hover:underline">Eliminar</button>
        </form>
    </div>

    {{-- iframe oculto — carga la vista de impresión y dispara print() automáticamente --}}
    <iframe
        x-ref="printFrame"
        :src="isPrinting ? printUrl : ''"
        @load="if (isPrinting) { $refs.printFrame.contentWindow.print(); isPrinting = false }"
        style="position:fixed;top:-9999px;left:-9999px;width:0;height:0;border:0"
        title="Vista de impresión">
    </iframe>

    {{-- ══ MODAL: Enviar por correo ══════════════════════════════ --}}
    @if($invoice->client->email)
    <div x-show="modalEmail"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display:none"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="absolute inset-0 bg-gray-900/50" @click="modalEmail = false"></div>

        <div class="relative bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-lg z-10"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-5 border-b border-[var(--border-default)]">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 bg-[var(--color-primary-light)] rounded-[var(--radius-control)] flex items-center justify-center">
                        <x-lucide-mail class="w-5 h-5 text-[var(--color-primary)]" />
                    </div>
                    <div>
                        <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Enviar cuenta por correo</h2>
                        <p class="text-[12px] text-[var(--text-400)] mt-0.5 font-mono">{{ $invoice->number }} · PDF adjunto</p>
                    </div>
                </div>
                <button @click="modalEmail = false"
                        class="p-1.5 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                    <x-lucide-x class="w-4 h-4" />
                </button>
            </div>

            {{-- Vista previa del destinatario --}}
            <div class="px-6 pt-4">
                <div class="flex items-center gap-3 bg-[var(--color-primary-light)] rounded-[var(--radius-control)] px-4 py-3 border border-[var(--border-default)]">
                    <div class="w-8 h-8 rounded-full bg-[var(--color-primary)] flex items-center justify-center flex-shrink-0">
                        <span class="text-[12px] font-semibold text-white">{{ strtoupper(substr($invoice->client->name, 0, 2)) }}</span>
                    </div>
                    <div>
                        <p class="text-[14px] font-semibold text-[var(--color-primary-dark)]">{{ $invoice->client->name }}</p>
                        <p class="text-[12px] text-[var(--color-primary)]">{{ $invoice->client->email }}</p>
                    </div>
                </div>
            </div>

            {{-- Formulario --}}
            <form method="POST" action="{{ route('invoices.send_email', $invoice) }}" class="px-6 py-5 space-y-4">
                @csrf

                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                        Correo electrónico <span class="text-[var(--color-danger)]">*</span>
                    </label>
                    <input type="email"
                           name="email"
                           value="{{ old('email', $invoice->client->email) }}"
                           required
                           class="{{ $fieldClass }}">
                    <p class="text-[12px] text-[var(--text-400)] mt-1">Puede modificar el destinatario antes de enviar.</p>
                </div>

                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                        Mensaje personalizado <span class="text-[var(--text-400)] font-normal">(opcional)</span>
                    </label>
                    <textarea name="message"
                              rows="3"
                              maxlength="1000"
                              placeholder="Escriba un mensaje adicional para el cliente…"
                              class="w-full px-3.5 py-2.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none resize-none">{{ old('message') }}</textarea>
                </div>

                <div class="bg-[var(--surface-subtle)] rounded-[var(--radius-control)] px-4 py-3 border border-[var(--border-default)]">
                    <p class="text-[12px] font-medium text-[var(--text-500)] mb-1.5">El correo incluirá:</p>
                    <ul class="space-y-1">
                        <li class="flex items-center gap-2 text-[12px] text-[var(--text-500)]">
                            <x-lucide-check-circle class="w-3.5 h-3.5 text-[var(--color-success)] flex-shrink-0" />
                            PDF de la cuenta de cobro No. {{ $invoice->number }} adjunto
                        </li>
                        <li class="flex items-center gap-2 text-[12px] text-[var(--text-500)]">
                            <x-lucide-check-circle class="w-3.5 h-3.5 text-[var(--color-success)] flex-shrink-0" />
                            Total: ${{ number_format($invoice->total, 0, ',', '.') }} COP
                        </li>
                        <li class="flex items-center gap-2 text-[12px] text-[var(--text-500)]">
                            <x-lucide-check-circle class="w-3.5 h-3.5 text-[var(--color-success)] flex-shrink-0" />
                            Información de pago y datos de contacto
                        </li>
                    </ul>
                </div>

                <div class="flex items-center justify-end gap-3 pt-1 border-t border-[var(--border-default)]">
                    <button type="button" @click="modalEmail = false"
                            class="px-4 h-10 text-[14px] text-[var(--text-500)] hover:text-[var(--text-700)]">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-[6px] h-10 px-5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white rounded-[var(--radius-control)] text-[14px] font-medium">
                        <x-lucide-mail class="w-4 h-4" />
                        Enviar correo
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

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

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-gray-900/50"
             @click="modalPago = false"></div>

        {{-- Panel --}}
        <div class="relative bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-lg z-10"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-5 border-b border-[var(--border-default)]">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 bg-[var(--color-primary-light)] rounded-[var(--radius-control)] flex items-center justify-center">
                        <x-lucide-credit-card class="w-4 h-4 text-[var(--color-primary)]" />
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

            {{-- Formulario --}}
            <form method="POST"
                  action="{{ route('payments.store', $invoice) }}"
                  enctype="multipart/form-data"
                  class="px-6 py-5 space-y-4">
                @csrf
                <input type="hidden" name="_from" value="{{ url()->current() }}">

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
                        @error('amount')<p class="text-[12px] text-[var(--color-danger)] mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                            Fecha <span class="text-[var(--color-danger)]">*</span>
                        </label>
                        <input type="date"
                               name="payment_date"
                               value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                               class="{{ $fieldClass }}">
                        @error('payment_date')<p class="text-[12px] text-[var(--color-danger)] mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                            Método <span class="text-[var(--color-danger)]">*</span>
                        </label>
                        <select name="payment_method" class="{{ $fieldClass }}">
                            <option value="">Seleccionar…</option>
                            <option value="efectivo"      {{ old('payment_method') === 'efectivo'      ? 'selected' : '' }}>Efectivo</option>
                            <option value="transferencia" {{ old('payment_method') === 'transferencia' ? 'selected' : '' }}>Transferencia bancaria</option>
                            <option value="cheque"        {{ old('payment_method') === 'cheque'        ? 'selected' : '' }}>Cheque</option>
                            <option value="otro"          {{ old('payment_method') === 'otro'          ? 'selected' : '' }}>Otro</option>
                        </select>
                        @error('payment_method')<p class="text-[12px] text-[var(--color-danger)] mt-1">{{ $message }}</p>@enderror
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
                    @error('receipt')<p class="text-[12px] text-[var(--color-danger)] mt-1">{{ $message }}</p>@enderror
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
