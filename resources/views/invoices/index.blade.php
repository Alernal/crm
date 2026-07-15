<x-app-layout>
<x-slot name="title">Cuentas de Cobro</x-slot>

<div x-data="{
        emailModal: false,
        emailAction: '',
        emailAddress: '',
        emailNumber: '',
        emailTotal: '',
        abrirEmail(action, address, number, total) {
            this.emailAction  = action;
            this.emailAddress = address;
            this.emailNumber  = number;
            this.emailTotal   = total;
            this.emailModal   = true;
        }
     }"
     @keydown.escape.window="emailModal = false">

@php
    $statusLabels = [
        'draft'     => ['label' => 'Borrador',  'variant' => 'neutral'],
        'sent'      => ['label' => 'Emitida',   'variant' => 'info'],
        'paid'      => ['label' => 'Pagada',    'variant' => 'success'],
        'overdue'   => ['label' => 'Vencida',   'variant' => 'danger'],
        'cancelled' => ['label' => 'Anulada',   'variant' => 'neutral'],
    ];
@endphp

{{-- Header --}}
<div class="flex items-start justify-end mb-6 gap-4">
    <a href="{{ route('invoices.create') }}"
       class="inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium flex-shrink-0">
        <x-lucide-plus class="w-4 h-4" />
        Nueva cuenta
    </a>
</div>

{{-- Flash --}}
@if(session('success'))
<div class="mb-4 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

{{-- Filtros --}}
<form method="GET" action="{{ route('invoices.index') }}" class="mb-5">
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1 relative">
            <x-lucide-search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-400)]" />
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Buscar por número o nombre del cliente..."
                   class="pl-10 w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] bg-[var(--surface-subtle)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none" />
        </div>
        <select name="status" class="h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] bg-[var(--surface-subtle)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none min-w-[160px]">
            <option value="">Todos los estados</option>
            @foreach($statusLabels as $val => $s)
            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $s['label'] }}</option>
            @endforeach
        </select>
        <button type="submit" class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">
            Buscar
        </button>
        @if(request()->hasAny(['search','status']))
        <a href="{{ route('invoices.index') }}" class="h-10 flex items-center px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
            Limpiar
        </a>
        @endif
    </div>
</form>

{{-- Tabla --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
    @if($invoices->isEmpty())
    <div class="text-center py-16">
        <div class="w-16 h-16 rounded-[var(--radius-card)] bg-[var(--surface-muted)] flex items-center justify-center mx-auto mb-4">
            <x-lucide-file-text class="w-8 h-8 text-[var(--text-400)]" />
        </div>
        @if(request()->hasAny(['search','status']))
        <p class="text-[14px] font-semibold text-[var(--text-700)]">No se encontraron cuentas con esos filtros</p>
        <a href="{{ route('invoices.index') }}" class="mt-2 inline-block text-[13px] text-[var(--color-primary)] hover:underline font-medium">Limpiar filtros</a>
        @else
        <p class="text-[14px] font-semibold text-[var(--text-700)]">Aún no tienes cuentas de cobro</p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">Crea la primera para comenzar a facturar</p>
        <a href="{{ route('invoices.create') }}" class="mt-4 inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">
            <x-lucide-plus class="w-4 h-4" />
            Crear primera cuenta de cobro
        </a>
        @endif
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-[var(--border-default)]">
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-left">Número</th>
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-left">Cliente</th>
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-left hidden md:table-cell">Emisión</th>
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-left hidden lg:table-cell">Vencimiento</th>
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-right">Total</th>
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-center">Estado</th>
                    <th class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                @php $s = $statusLabels[$invoice->status] ?? $statusLabels['draft']; @endphp
                <tr class="border-b border-[var(--surface-muted)] hover:bg-[var(--surface-subtle)]">
                    <td class="px-6 py-[14px]">
                        <a href="{{ route('invoices.show', $invoice) }}"
                           class="font-medium text-[13px] text-[var(--color-primary)] hover:underline">
                            {{ $invoice->number }}
                        </a>
                    </td>
                    <td class="px-6 py-[14px] text-[14px] text-[var(--text-700)] font-medium">{{ $invoice->client->name }}</td>
                    <td class="px-6 py-[14px] hidden md:table-cell text-[12px] text-[var(--text-500)]">{{ $invoice->issue_date->format('d/m/Y') }}</td>
                    <td class="px-6 py-[14px] hidden lg:table-cell text-[12px]">
                        @if($invoice->due_date)
                        <span class="{{ $invoice->due_date->isPast() && !in_array($invoice->status, ['paid','cancelled']) ? 'text-[var(--color-danger)] font-semibold' : 'text-[var(--text-500)]' }}">
                            {{ $invoice->due_date->format('d/m/Y') }}
                        </span>
                        @else <span class="text-[var(--border-strong)]">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-[14px] text-right text-[14px] font-semibold text-[var(--text-900)]">
                        ${{ number_format($invoice->total, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-[14px] text-center">
                        <x-status-badge :variant="$s['variant']">{{ $s['label'] }}</x-status-badge>
                    </td>
                    <td class="px-6 py-[14px] text-right">
                        <div class="flex items-center justify-end gap-[10px]">
                            <a href="{{ route('invoices.show', $invoice) }}"
                               class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Ver">
                                <x-lucide-eye class="w-4 h-4" />
                            </a>
                            <a href="{{ route('invoices.pdf', $invoice) }}"
                               class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="PDF">
                                <x-lucide-file-text class="w-4 h-4" />
                            </a>
                            @if($invoice->client->email)
                            <button @click="abrirEmail(
                                        '{{ route('invoices.send_email', $invoice) }}',
                                        '{{ addslashes($invoice->client->email) }}',
                                        '{{ addslashes($invoice->number) }}',
                                        '${{ number_format($invoice->total, 0, ',', '.') }}'
                                    )"
                                    class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Enviar por correo">
                                <x-lucide-mail class="w-4 h-4" />
                            </button>
                            @endif
                            @if(!in_array($invoice->status, ['paid','cancelled']))
                            <a href="{{ route('invoices.edit', $invoice) }}"
                               class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Editar">
                                <x-lucide-edit-2 class="w-4 h-4" />
                            </a>
                            @endif
                            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}"
                                  x-data=""
                                  x-on:submit.prevent="if(confirm('¿Eliminar cuenta {{ $invoice->number }}?')) $el.submit()">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="text-[var(--color-danger)]/60 hover:text-[var(--color-danger)]" title="Eliminar">
                                    <x-lucide-trash-2 class="w-4 h-4" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($invoices->hasPages())
    <div class="px-6 py-4 border-t border-[var(--border-default)]">
        {{ $invoices->links() }}
    </div>
    @endif
    @endif
</div>

{{-- ══ MODAL: Enviar por correo ══════════════════════════════ --}}
<div x-show="emailModal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="display:none"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="absolute inset-0 bg-gray-900/50" @click="emailModal = false"></div>

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
                    <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Enviar cuenta por correo</h2>
                    <p class="text-[12px] text-[var(--text-400)] mt-0.5">
                        No. <span class="font-mono font-semibold" x-text="emailNumber"></span>
                        · PDF adjunto
                    </p>
                </div>
            </div>
            <button @click="emailModal = false"
                    class="p-1.5 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                <x-lucide-x class="w-4 h-4" />
            </button>
        </div>

        <form :action="emailAction" method="POST" class="px-6 py-5 space-y-4">
            @csrf

            <div>
                <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                    Correo electrónico <span class="text-[var(--color-danger)]">*</span>
                </label>
                <input type="email"
                       name="email"
                       :value="emailAddress"
                       required
                       class="w-full h-10 px-3.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none">
                <p class="text-[12px] text-[var(--text-400)] mt-1">Correo registrado del cliente. Puede modificarlo.</p>
            </div>

            <div>
                <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                    Mensaje personalizado <span class="text-[var(--text-400)] font-normal">(opcional)</span>
                </label>
                <textarea name="message"
                          rows="3"
                          maxlength="1000"
                          placeholder="Escriba un mensaje adicional para el cliente…"
                          class="w-full px-3.5 py-2.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none resize-none"></textarea>
            </div>

            <div class="bg-[var(--surface-subtle)] rounded-[var(--radius-control)] px-4 py-3 border border-[var(--border-default)] text-[12px] text-[var(--text-500)] space-y-1">
                <div class="flex items-center gap-2">
                    <x-lucide-check-circle class="w-3.5 h-3.5 text-[var(--color-success)] flex-shrink-0" />
                    PDF adjunto de la cuenta No. <span class="font-mono font-semibold" x-text="emailNumber"></span>
                </div>
                <div class="flex items-center gap-2">
                    <x-lucide-check-circle class="w-3.5 h-3.5 text-[var(--color-success)] flex-shrink-0" />
                    Total <span class="font-semibold" x-text="emailTotal"></span> COP · Datos de pago y contacto
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-1 border-t border-[var(--border-default)]">
                <button type="button" @click="emailModal = false"
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

</div>{{-- /x-data emailModal --}}

</x-app-layout>
