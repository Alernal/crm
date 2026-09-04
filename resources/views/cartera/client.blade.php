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

    $methodLabels = [
        'efectivo'      => 'Efectivo',
        'transferencia' => 'Transferencia',
        'cheque'        => 'Cheque',
        'otro'          => 'Otro',
    ];

    $fieldClass = 'w-full h-10 px-3.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none';
@endphp

<div class="pt-6"
     x-data="{
         ...clientPayModal(),
         modalStatement: false,
         modalInvoiceEmail: false,
         invoiceEmailAction: '',
         invoiceEmailAddress: '{{ addslashes($client->email ?? '') }}',
         invoiceEmailNumber: '',
         invoiceEmailTotal: '',
         abrirInvoiceEmail(action, number, total) {
             this.invoiceEmailAction  = action;
             this.invoiceEmailNumber  = number;
             this.invoiceEmailTotal   = total;
             this.modalInvoiceEmail   = true;
         }
     }"
     @keydown.escape.window="open && cerrar(); modalStatement = false; modalInvoiceEmail = false">

{{-- iframe oculto para impresión de estado de cuenta --}}
<iframe
    x-ref="stmtFrame"
    :src="isPrinting ? '{{ route('cartera.client_statement', $client) }}' : ''"
    @load="if (isPrinting) { $refs.stmtFrame.contentWindow.print(); isPrinting = false }"
    style="position:fixed;top:-9999px;left:-9999px;width:0;height:0;border:0"
    title="Estado de cuenta"></iframe>

{{-- ── Breadcrumb + Header ─────────────────────────────────── --}}
<div class="flex items-start justify-between mb-10 gap-4">
    <div>
        <a href="{{ route('cartera.index') }}"
           class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)] mb-6">
            <x-lucide-arrow-left class="w-4 h-4" />
            Volver
        </a>
        <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $client->name }}</p>
        <p class="text-[14px] text-[var(--text-500)] mt-1 font-mono">{{ $client->document_type }} {{ $client->full_document }}</p>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
        <a href="{{ route('clients.show', $client) }}"
           class="h-10 flex items-center px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
            Ficha del cliente
        </a>
        @if($client->email)
        <button @click="modalStatement = true"
                class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-mail class="w-4 h-4" />
            Enviar estado de cuenta
        </button>
        @endif
        <button @click="isPrinting = true"
                class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-printer class="w-4 h-4" />
            Estado de cuenta
        </button>
        <a href="{{ route('invoices.create') }}?client_id={{ $client->id }}"
           class="inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">
            <x-lucide-plus class="w-4 h-4" />
            Nueva cuenta
        </a>
    </div>
</div>

{{-- ── Flash ───────────────────────────────────────────────── --}}
@if(session('success'))
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="mb-5 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

{{-- ── KPIs del cliente ────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Total facturado</p>
            <x-lucide-file-text class="w-5 h-5 text-[var(--color-primary)]" />
        </div>
        <p class="text-[22px] font-bold text-[var(--text-900)]">$ {{ number_format($totalInvoiced, 0, ',', '.') }}</p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">{{ $invoices->count() }} cuenta{{ $invoices->count() !== 1 ? 's' : '' }}</p>
    </div>
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Total cobrado</p>
            <x-lucide-check-circle class="w-5 h-5 text-[var(--color-success)]" />
        </div>
        @php $globalPct = $totalInvoiced > 0 ? min(100, (int) round($totalPaid / $totalInvoiced * 100)) : 0; @endphp
        <p class="text-[22px] font-bold text-[var(--text-900)]">$ {{ number_format($totalPaid, 0, ',', '.') }}</p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">{{ $globalPct }}% del total</p>
    </div>
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Saldo por cobrar</p>
            <x-lucide-wallet class="w-5 h-5 {{ $balance <= 0 ? 'text-[var(--color-success)]' : ($countOverdue > 0 ? 'text-[var(--color-danger)]' : 'text-[var(--color-warning)]') }}" />
        </div>
        <p class="text-[22px] font-bold {{ $balance <= 0 ? 'text-[var(--color-success)]' : ($countOverdue > 0 ? 'text-[var(--color-danger)]' : 'text-[var(--text-900)]') }}">
            $ {{ number_format($balance, 0, ',', '.') }}
        </p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">
            {{ $balance <= 0 ? 'Sin saldo pendiente' : 'Pendiente de cobro' }}
        </p>
    </div>
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Cuentas vencidas</p>
            <x-lucide-alert-triangle class="w-5 h-5 {{ $countOverdue > 0 ? 'text-[var(--color-danger)]' : 'text-[var(--text-400)]' }}" />
        </div>
        <p class="text-[22px] font-bold {{ $countOverdue > 0 ? 'text-[var(--color-danger)]' : 'text-[var(--text-400)]' }}">{{ $countOverdue }}</p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">
            {{ $countOverdue > 0 ? 'Requieren atención' : 'Sin vencidas' }}
        </p>
    </div>
</div>

{{-- ── Tabla de cuentas ─────────────────────────────────────── --}}
@if($invoices->isEmpty())
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] px-5 py-20 text-center">
    <div class="flex flex-col items-center gap-3">
        <div class="w-16 h-16 bg-[var(--surface-muted)] rounded-[var(--radius-card)] flex items-center justify-center">
            <x-lucide-file-text class="w-8 h-8 text-[var(--text-400)]" />
        </div>
        <div>
            <p class="text-[14px] font-semibold text-[var(--text-700)]">Sin cuentas de cobro</p>
            <p class="text-[12px] text-[var(--text-400)] mt-1">Este cliente no tiene cuentas emitidas aún</p>
        </div>
        <a href="{{ route('invoices.create') }}"
           class="mt-1 h-10 px-5 flex items-center bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white rounded-[var(--radius-control)] text-[14px] font-medium">
            Crear primera cuenta
        </a>
    </div>
</div>
@else

<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
    <div class="overflow-x-auto p-3">
    <div class="overflow-y-auto max-h-[65vh]">
    <table class="w-full">
        <thead>
            <tr>
                @php
                    $thClass = 'sticky top-0 z-[1] bg-[var(--surface-card)] border-b border-[var(--border-default)] text-[13px] font-bold text-[var(--text-900)] px-6 py-3.5';
                @endphp
                <th class="{{ $thClass }} text-left">Cuenta</th>
                <th class="{{ $thClass }} text-left hidden sm:table-cell">Emisión</th>
                <th class="{{ $thClass }} text-right">Total</th>
                <th class="{{ $thClass }} text-right hidden sm:table-cell">Abonado</th>
                <th class="{{ $thClass }} text-right">Saldo</th>
                <th class="{{ $thClass }} text-left hidden lg:table-cell">Progreso</th>
                <th class="{{ $thClass }} text-left hidden md:table-cell">Vencimiento</th>
                <th class="{{ $thClass }} text-left hidden md:table-cell">Días</th>
                <th class="{{ $thClass }} text-left">Estado</th>
                <th class="{{ $thClass }} text-right w-24">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $invoice)
            @php
                $paidAmount  = $invoice->paid_amount;
                $invBalance  = $invoice->balance;
                $paidPct     = $invoice->total > 0 ? min(100, (int) round($paidAmount / (float) $invoice->total * 100)) : 0;
                $sc          = $statusConfig[$invoice->status] ?? $statusConfig['draft'];
                $canPay      = !in_array($invoice->status, ['paid', 'cancelled']);

                $daysOverdue = 0;
                $daysLeft    = null;
                if ($invoice->due_date) {
                    $diff = $today->diffInDays($invoice->due_date, false);
                    $daysOverdue = $diff < 0 ? abs($diff) : 0;
                    $daysLeft    = $diff >= 0 ? $diff : null;
                }

                $barColor = $paidPct >= 100 ? 'bg-[var(--color-success)]' : ($paidPct > 0 ? 'bg-[var(--color-primary)]' : 'bg-[var(--surface-muted)]');
            @endphp

            <tr class="border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">

                <td class="px-6 py-4">
                    <a href="{{ route('invoices.show', $invoice) }}"
                       class="text-[14px] text-[var(--color-primary)] hover:underline">
                        {{ $invoice->number }}
                    </a>
                </td>

                <td class="px-6 py-4 hidden sm:table-cell">
                    <p class="text-[14px] text-[var(--text-500)]">{{ $invoice->issue_date->format('d/m/Y') }}</p>
                </td>

                <td class="px-6 py-4 text-right">
                    <p class="text-[14px] text-[var(--text-700)] tabular-nums">$ {{ number_format($invoice->total, 0, ',', '.') }}</p>
                </td>

                <td class="px-6 py-4 text-right hidden sm:table-cell">
                    <p class="text-[14px] tabular-nums {{ $paidAmount > 0 ? 'text-[var(--color-success)]' : 'text-[var(--border-strong)]' }}">
                        $ {{ number_format($paidAmount, 0, ',', '.') }}
                    </p>
                </td>

                <td class="px-6 py-4 text-right">
                    <p class="text-[14px] tabular-nums {{ $invBalance <= 0 ? 'text-[var(--color-success)]' : 'text-[var(--text-900)]' }}">
                        $ {{ number_format(max(0, $invBalance), 0, ',', '.') }}
                    </p>
                </td>

                <td class="px-6 py-4 hidden lg:table-cell">
                    <div class="flex items-center gap-2 min-w-24">
                        <div class="flex-1 h-1.5 bg-[var(--surface-muted)] rounded-full overflow-hidden">
                            <div class="h-full {{ $barColor }} rounded-full" style="width: {{ $paidPct }}%"></div>
                        </div>
                        <span class="text-[13px] text-[var(--text-400)] tabular-nums w-8 text-right">{{ $paidPct }}%</span>
                    </div>
                </td>

                <td class="px-6 py-4 hidden md:table-cell">
                    @if($invoice->due_date)
                        <p class="text-[14px] text-[var(--text-700)]">
                            {{ $invoice->due_date->format('d/m/Y') }}
                        </p>
                    @else
                        <span class="text-[var(--border-strong)]">—</span>
                    @endif
                </td>

                <td class="px-6 py-4 hidden md:table-cell">
                    @if($daysOverdue > 0)
                        <p class="text-[14px] text-[var(--color-danger)] tabular-nums">{{ $daysOverdue }}</p>
                    @elseif($daysLeft !== null && $invoice->status !== 'paid')
                        <p class="text-[13px] text-[var(--text-400)]">{{ $daysLeft === 0 ? 'Vence hoy' : "Vence en {$daysLeft}" }}</p>
                    @else
                        <span class="text-[var(--border-strong)] text-[14px]">—</span>
                    @endif
                </td>

                <td class="px-6 py-4">
                    <x-status-badge :variant="$sc['variant']">{{ $sc['label'] }}</x-status-badge>
                </td>

                <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-[10px]">
                        <a href="{{ route('invoices.show', $invoice) }}"
                           title="Ver detalle"
                           class="text-[var(--text-400)] hover:text-[var(--text-900)]">
                            <x-lucide-eye class="w-4 h-4" />
                        </a>
                        @if($client->email)
                        <button @click="abrirInvoiceEmail(
                                    '{{ route('invoices.send_email', $invoice) }}',
                                    '{{ addslashes($invoice->number) }}',
                                    '${{ number_format($invoice->total, 0, ',', '.') }}'
                                )"
                                title="Enviar por correo"
                                class="text-[var(--text-400)] hover:text-[var(--text-900)]">
                            <x-lucide-mail class="w-4 h-4" />
                        </button>
                        @endif
                        @if($canPay)
                        <button @click="abrir({
                                    action: '{{ route('payments.store', $invoice) }}',
                                    number: '{{ addslashes($invoice->number) }}',
                                    balance: {{ max(0, $invBalance) }},
                                    from: '{{ route('cartera.client', $client) }}'
                                })"
                                title="Registrar pago"
                                class="text-[var(--text-400)] hover:text-[var(--text-900)]">
                            <x-lucide-wallet class="w-4 h-4" />
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    </div>
</div>
@endif


{{-- ══ MODAL: Enviar estado de cuenta ═════════════════════════ --}}
@if($client->email)
<div x-show="modalStatement"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="display:none"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="absolute inset-0 bg-gray-900/50" @click="modalStatement = false"></div>

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
                    <p class="text-[12px] text-[var(--text-400)] mt-0.5">{{ $client->name }} · PDF adjunto</p>
                </div>
            </div>
            <button @click="modalStatement = false"
                    class="p-1.5 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                <x-lucide-x class="w-4 h-4" />
            </button>
        </div>

        {{-- Resumen del cliente --}}
        <div class="px-6 pt-4">
            <div class="flex items-center gap-3 bg-[var(--color-primary-light)] rounded-[var(--radius-control)] px-4 py-3 border border-[var(--border-default)]">
                <div class="w-8 h-8 rounded-full bg-[var(--color-primary)] flex items-center justify-center flex-shrink-0">
                    <span class="text-[12px] font-semibold text-white">{{ strtoupper(substr($client->name, 0, 2)) }}</span>
                </div>
                <div>
                    <p class="text-[14px] font-semibold text-[var(--color-primary-dark)]">{{ $client->name }}</p>
                    <p class="text-[12px] text-[var(--color-primary)]">{{ $client->email }}</p>
                </div>
                <div class="ml-auto text-right">
                    <p class="text-[12px] text-[var(--color-primary)]">Saldo pendiente</p>
                    <p class="text-[14px] font-bold text-[var(--color-primary-dark)]">${{ number_format($balance, 0, ',', '.') }} COP</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('cartera.send_statement', $client) }}" class="px-6 py-5 space-y-4">
            @csrf

            <div>
                <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                    Correo electrónico <span class="text-[var(--color-danger)]">*</span>
                </label>
                <input type="email"
                       name="email"
                       value="{{ old('email', $client->email) }}"
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
                          placeholder="Ej: Estimado cliente, adjunto encontrará su estado de cuenta actualizado…"
                          class="w-full px-3.5 py-2.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none resize-none">{{ old('message') }}</textarea>
            </div>

            <div class="bg-[var(--color-warning-bg)] rounded-[var(--radius-control)] px-4 py-3 border border-[#FCD34D] space-y-1">
                <p class="text-[12px] font-medium text-[var(--color-warning-text)] mb-1.5">El correo incluirá:</p>
                @foreach([
                    'PDF del estado de cuenta con todas las cuentas activas',
                    'Detalle de saldos pendientes y cuentas vencidas',
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
                <button type="button" @click="modalStatement = false"
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
@endif

{{-- ══ MODAL: Enviar cuenta individual por correo ══════════════ --}}
@if($client->email)
<div x-show="modalInvoiceEmail"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="display:none"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="absolute inset-0 bg-gray-900/50" @click="modalInvoiceEmail = false"></div>

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
                    <h2 class="text-[16px] font-bold text-[var(--text-900)]">Enviar cuenta por correo</h2>
                    <p class="text-[12px] text-[var(--text-400)] mt-0.5">
                        No. <span class="font-mono font-semibold" x-text="invoiceEmailNumber"></span> · PDF adjunto
                    </p>
                </div>
            </div>
            <button @click="modalInvoiceEmail = false"
                    class="p-1.5 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                <x-lucide-x class="w-4 h-4" />
            </button>
        </div>

        <form :action="invoiceEmailAction" method="POST" class="px-6 py-5 space-y-4">
            @csrf
            <div>
                <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                    Correo electrónico <span class="text-[var(--color-danger)]">*</span>
                </label>
                <input type="email"
                       name="email"
                       :value="invoiceEmailAddress"
                       required
                       class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                    Mensaje personalizado <span class="text-[var(--text-400)] font-normal">(opcional)</span>
                </label>
                <textarea name="message" rows="3" maxlength="1000"
                          placeholder="Escriba un mensaje adicional para el cliente…"
                          class="w-full px-3.5 py-2.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none resize-none"></textarea>
            </div>
            <div class="bg-[var(--surface-subtle)] rounded-[var(--radius-control)] px-4 py-3 border border-[var(--border-default)] text-[12px] text-[var(--text-500)] space-y-1">
                <div class="flex items-center gap-2">
                    <x-lucide-check-circle class="w-3.5 h-3.5 text-[var(--color-success)] flex-shrink-0" />
                    PDF adjunto · Total <span class="font-semibold" x-text="invoiceEmailTotal"></span> COP
                </div>
                <div class="flex items-center gap-2">
                    <x-lucide-check-circle class="w-3.5 h-3.5 text-[var(--color-success)] flex-shrink-0" />
                    Sin estado de cuenta incluido
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 pt-1 border-t border-[var(--border-default)]">
                <button type="button" @click="modalInvoiceEmail = false"
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
<div x-show="open"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="display:none"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="absolute inset-0 bg-gray-900/50"
         @click="cerrar()"></div>

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
                    <h2 class="text-[16px] font-bold text-[var(--text-900)]">Registrar pago</h2>
                    <p class="text-[12px] text-[var(--text-400)] mt-0.5">
                        <span class="font-mono" x-text="invoiceNumber"></span>
                        · Saldo: <span x-text="formatCOP(balance)"></span>
                    </p>
                </div>
            </div>
            <button @click="cerrar()"
                    class="p-1.5 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                <x-lucide-x class="w-4 h-4" />
            </button>
        </div>

        <form :action="formAction"
              method="POST"
              enctype="multipart/form-data"
              class="px-6 py-5 space-y-4">
            @csrf
            <input type="hidden" name="_from" :value="fromUrl">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                        Monto <span class="text-[var(--color-danger)]">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-[var(--text-400)] text-[14px]">$</span>
                        <input type="text"
                               name="amount"
                               x-money="amount"
                               class="{{ $fieldClass }} pl-7">
                    </div>
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                        Fecha <span class="text-[var(--color-danger)]">*</span>
                    </label>
                    <input type="date"
                           name="payment_date"
                           value="{{ now()->format('Y-m-d') }}"
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
                           maxlength="100"
                           placeholder="# comprobante"
                           class="{{ $fieldClass }}">
                </div>
            </div>

            <div>
                <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">Notas</label>
                <input type="text"
                       name="notes"
                       maxlength="500"
                       placeholder="Observación opcional…"
                       class="{{ $fieldClass }}">
            </div>

            <div>
                <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">Soporte / comprobante</label>
                <label class="flex items-center gap-3 px-3.5 h-10 border border-dashed border-[var(--border-strong)] rounded-[var(--radius-control)] cursor-pointer hover:border-[var(--color-success)] hover:bg-[var(--color-success-bg)]/40"
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
                <button type="button" @click="cerrar()"
                        class="px-4 h-10 text-[14px] text-[var(--text-500)] hover:text-[var(--text-700)]">
                    Cancelar
                </button>
                <button type="submit"
                        class="inline-flex items-center gap-[6px] h-10 px-5 bg-[var(--color-success)] hover:opacity-90 text-white rounded-[var(--radius-control)] text-[14px] font-medium">
                    <x-lucide-check-circle class="w-4 h-4" />
                    Guardar pago
                </button>
            </div>
        </form>
    </div>
</div>

</div>

<script>
function clientPayModal() {
    return {
        open: false,
        isPrinting: false,
        formAction: '',
        invoiceNumber: '',
        balance: 0,
        amount: 0,
        fromUrl: '',

        abrir(data) {
            this.formAction    = data.action;
            this.invoiceNumber = data.number;
            this.balance       = data.balance;
            this.amount        = data.balance;
            this.fromUrl       = data.from;
            this.open          = true;
        },

        cerrar() {
            this.open = false;
        },

        formatCOP(amount) {
            return '$ ' + Math.round(amount || 0).toLocaleString('es-CO');
        }
    };
}
</script>
</x-app-layout>
