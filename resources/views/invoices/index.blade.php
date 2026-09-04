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
       class="inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium flex-shrink-0">
        <x-lucide-plus class="w-4 h-4" />
        Nueva cuenta
    </a>
</div>

{{-- Flash --}}
@if(session('success'))
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="mb-4 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[15px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

{{-- Filtros --}}
<form method="GET" action="{{ route('invoices.index') }}" class="mb-5">
    @php
        $exceptQuery = fn(array $keys) => array_filter(
            request()->except(array_merge($keys, ['page'])),
            fn($v) => $v !== null && $v !== ''
        );
    @endphp
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1 relative">
            <x-lucide-search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-400)]" />
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Buscar por número o nombre del cliente..."
                   class="pl-10 w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[15px] bg-[var(--surface-subtle)] text-[var(--text-700)] outline-none transition-all focus:bg-[var(--surface-card)] focus:border-[var(--border-strong)] focus:ring-2 focus:ring-[var(--color-primary-light)]" />
        </div>

        {{-- Estado: píldoras --}}
        <div class="inline-flex items-center gap-1 p-1 rounded-full bg-[var(--surface-subtle)] border border-[var(--border-default)] w-fit max-w-full overflow-x-auto">
            @php
                $statusPillClass = function (string $value) use ($statusLabels) {
                    $isActive = $value === '' ? !request()->filled('status') : request('status') === $value;
                    $activeColor = $value === ''
                        ? 'bg-[var(--color-primary)] text-white'
                        : match ($statusLabels[$value]['variant'] ?? 'neutral') {
                            'success' => 'bg-[var(--color-success)] text-white',
                            'danger'  => 'bg-[var(--color-danger)] text-white',
                            'info'    => 'bg-[var(--color-primary)] text-white',
                            default   => 'bg-[var(--text-500)] text-white',
                        };
                    return 'h-8 px-3.5 rounded-full text-[13.5px] font-medium whitespace-nowrap flex-shrink-0 transition-colors '
                        . ($isActive ? $activeColor : 'text-[var(--text-500)] hover:text-[var(--text-900)]');
                };
            @endphp
            <button type="submit" name="status" value="" class="{{ $statusPillClass('') }}">Todos</button>
            @foreach($statusLabels as $val => $s)
            <button type="submit" name="status" value="{{ $val }}" class="{{ $statusPillClass($val) }}">{{ $s['label'] }}</button>
            @endforeach
        </div>

        <button type="submit" class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
            Buscar
        </button>
    </div>

    {{-- Filtros activos --}}
    @if(request()->filled('search') || request()->filled('status'))
    <div class="flex flex-wrap items-center gap-2 mt-3">
        <span class="text-[12.5px] text-[var(--text-400)]">Filtros activos:</span>

        @if(request()->filled('search'))
        <a href="{{ route('invoices.index', $exceptQuery(['search'])) }}"
           class="inline-flex items-center gap-1.5 h-7 pl-3 pr-2 rounded-full bg-[var(--color-primary-light)] text-[var(--color-primary)] text-[12.5px] font-medium hover:opacity-80">
            &ldquo;{{ request('search') }}&rdquo;
            <x-lucide-x class="w-3 h-3" />
        </a>
        @endif

        @if(request()->filled('status'))
        <a href="{{ route('invoices.index', $exceptQuery(['status'])) }}"
           class="inline-flex items-center gap-1.5 h-7 pl-3 pr-2 rounded-full bg-[var(--color-primary-light)] text-[var(--color-primary)] text-[12.5px] font-medium hover:opacity-80">
            {{ $statusLabels[request('status')]['label'] ?? request('status') }}
            <x-lucide-x class="w-3 h-3" />
        </a>
        @endif

        <a href="{{ route('invoices.index') }}" class="text-[12.5px] text-[var(--text-400)] hover:text-[var(--text-900)] underline">
            Limpiar todo
        </a>
    </div>
    @endif
</form>

{{-- Tabla --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
    @if($invoices->isEmpty())
    <div class="text-center py-16">
        <div class="w-16 h-16 rounded-[var(--radius-card)] bg-[var(--surface-muted)] flex items-center justify-center mx-auto mb-4">
            <x-lucide-file-text class="w-8 h-8 text-[var(--text-400)]" />
        </div>
        @if(request()->filled('search') || request()->filled('status'))
        <p class="text-[15px] font-semibold text-[var(--text-700)]">No se encontraron cuentas con esos filtros</p>
        <a href="{{ route('invoices.index') }}" class="mt-2 inline-block text-[14px] text-[var(--color-primary)] hover:underline font-medium">Limpiar filtros</a>
        @else
        <p class="text-[15px] font-semibold text-[var(--text-700)]">Aún no tienes cuentas de cobro</p>
        <p class="text-[13px] text-[var(--text-400)] mt-1">Crea la primera para comenzar a facturar</p>
        <a href="{{ route('invoices.create') }}" class="mt-4 inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[15px] font-medium">
            <x-lucide-plus class="w-4 h-4" />
            Crear primera cuenta de cobro
        </a>
        @endif
    </div>
    @else
    <div class="overflow-x-auto p-3">
    <div class="overflow-y-auto max-h-[65vh]">
        <table class="w-full">
            <thead>
                <tr>
                    @php
                        $thClass = 'sticky top-0 z-[1] bg-[var(--surface-card)] border-b border-[var(--border-default)] text-[13px] font-bold text-[var(--text-900)] px-6 py-3.5';

                        $currentSort = in_array(request('sort'), ['number', 'issue_date', 'due_date', 'total'], true) ? request('sort') : null;
                        $currentDir  = request('direction') === 'desc' ? 'desc' : 'asc';

                        $sortUrl = function (string $field) use ($currentSort, $currentDir) {
                            $nextDir = ($currentSort === $field && $currentDir === 'asc') ? 'desc' : 'asc';
                            return route('invoices.index', array_filter(
                                request()->except(['sort', 'direction', 'page']) + ['sort' => $field, 'direction' => $nextDir],
                                fn($v) => $v !== null && $v !== ''
                            ));
                        };
                    @endphp
                    @include('invoices._sortable-th', ['field' => 'number', 'label' => 'Número', 'thClass' => $thClass, 'extra' => 'text-left', 'currentSort' => $currentSort, 'currentDir' => $currentDir, 'sortUrl' => $sortUrl])
                    <th class="{{ $thClass }} text-left">Cliente</th>
                    @include('invoices._sortable-th', ['field' => 'issue_date', 'label' => 'Emisión', 'thClass' => $thClass, 'extra' => 'text-left hidden md:table-cell', 'currentSort' => $currentSort, 'currentDir' => $currentDir, 'sortUrl' => $sortUrl])
                    @include('invoices._sortable-th', ['field' => 'due_date', 'label' => 'Vencimiento', 'thClass' => $thClass, 'extra' => 'text-left hidden lg:table-cell', 'currentSort' => $currentSort, 'currentDir' => $currentDir, 'sortUrl' => $sortUrl])
                    @include('invoices._sortable-th', ['field' => 'total', 'label' => 'Total', 'thClass' => $thClass, 'extra' => 'text-right', 'currentSort' => $currentSort, 'currentDir' => $currentDir, 'sortUrl' => $sortUrl])
                    <th class="{{ $thClass }} text-center">Estado</th>
                    <th class="{{ $thClass }} text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                @php $s = $statusLabels[$invoice->status] ?? $statusLabels['draft']; @endphp
                <tr class="border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">
                    <td class="px-6 py-[14px]">
                        <a href="{{ route('invoices.show', $invoice) }}"
                           class="text-[14px] text-[var(--text-500)] hover:text-[var(--color-primary)]">
                            {{ $invoice->number }}
                        </a>
                    </td>
                    <td class="px-6 py-[14px] text-[14px] text-[var(--text-500)]">{{ $invoice->client->name }}</td>
                    <td class="px-6 py-[14px] hidden md:table-cell text-[14px] text-[var(--text-500)]">{{ $invoice->issue_date->format('d/m/Y') }}</td>
                    <td class="px-6 py-[14px] hidden lg:table-cell text-[14px] text-[var(--text-500)]">
                        @if($invoice->due_date)
                            {{ $invoice->due_date->format('d/m/Y') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-6 py-[14px] text-right text-[14px] text-[var(--text-500)] tabular-nums">
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
                                        class="text-[var(--text-400)] hover:text-[var(--text-900)]" title="Eliminar">
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
                    <h2 class="text-[17px] font-bold text-[var(--text-900)]">Enviar cuenta por correo</h2>
                    <p class="text-[13px] text-[var(--text-400)] mt-0.5">
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
                <label class="block text-[14px] font-medium text-[var(--text-700)] mb-1.5">
                    Correo electrónico <span class="text-[var(--color-danger)]">*</span>
                </label>
                <input type="email"
                       name="email"
                       :value="emailAddress"
                       required
                       class="w-full h-10 px-3.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[15px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none">
                <p class="text-[13px] text-[var(--text-400)] mt-1">Correo registrado del cliente. Puede modificarlo.</p>
            </div>

            <div>
                <label class="block text-[14px] font-medium text-[var(--text-700)] mb-1.5">
                    Mensaje personalizado <span class="text-[var(--text-400)] font-normal">(opcional)</span>
                </label>
                <textarea name="message"
                          rows="3"
                          maxlength="1000"
                          placeholder="Escriba un mensaje adicional para el cliente…"
                          class="w-full px-3.5 py-2.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[15px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none resize-none"></textarea>
            </div>

            <div class="bg-[var(--surface-subtle)] rounded-[var(--radius-control)] px-4 py-3 border border-[var(--border-default)] text-[13px] text-[var(--text-500)] space-y-1">
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
                        class="px-4 h-10 text-[15px] text-[var(--text-500)] hover:text-[var(--text-700)]">
                    Cancelar
                </button>
                <button type="submit"
                        class="inline-flex items-center gap-[6px] h-10 px-5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white rounded-[var(--radius-control)] text-[15px] font-medium">
                    <x-lucide-mail class="w-4 h-4" />
                    Enviar correo
                </button>
            </div>
        </form>
    </div>
</div>

</div>{{-- /x-data emailModal --}}

</x-app-layout>
