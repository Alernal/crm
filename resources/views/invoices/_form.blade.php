@php
    $isEdit = isset($invoice);

    $servicesJson = $services->map(fn($s) => [
        'id'         => $s->id,
        'name'       => $s->name,
        'unit_price' => (float) $s->base_price,
        'vat_rate'   => $s->applies_vat ? 19 : 0,
    ])->values()->toJson();

    $existingItems = $isEdit
        ? $invoice->items->map(fn($i) => [
            'service_id'    => $i->service_id,
            'description'   => $i->description,
            'quantity'      => (float) $i->quantity,
            'unit_price'    => (float) $i->unit_price,
            'discount_rate' => (float) $i->discount_rate,
            'vat_rate'      => (int) $i->vat_rate,
          ])->values()->toJson()
        : '[]';

    $clientsJson = $clients->map(fn($c) => [
        'id'          => $c->id,
        'name'        => $c->name,
        'nit'         => $c->document_type . ' ' . $c->full_document,
        'prefix'      => trim($c->invoice_prefix ?? ''),
        'consecutive' => (int) ($c->invoice_consecutive ?? 0),
    ])->values()->toJson();

    $displayNumber       = $isEdit ? $invoice->number : null;
    $initClientId        = $isEdit ? $invoice->client_id : null;
    $initClientName      = $isEdit ? $invoice->client->name : '';
    $initClientNit       = $isEdit ? ($invoice->client->document_type . ' ' . $invoice->client->full_document) : '';
    $initIssueDate       = old('issue_date', $isEdit ? $invoice->issue_date->format('Y-m-d') : now()->format('Y-m-d'));
    $initDueDate         = old('due_date',   $isEdit && $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '');
    $initWithholdingRate = old('withholding_rate', $isEdit ? (float) $invoice->withholding_rate : 0);

    $statusBadges = [
        'draft'     => ['label' => 'Borrador',  'variant' => 'neutral'],
        'sent'      => ['label' => 'Emitida',   'variant' => 'info'],
        'paid'      => ['label' => 'Pagada',    'variant' => 'success'],
        'overdue'   => ['label' => 'Vencida',   'variant' => 'danger'],
        'cancelled' => ['label' => 'Anulada',   'variant' => 'neutral'],
    ];
    $sb = $isEdit ? ($statusBadges[$invoice->status] ?? $statusBadges['draft']) : null;

    $inputClass = 'w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] text-[var(--text-700)] bg-[var(--surface-card)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none';
    $labelClass = 'block text-[13px] font-medium text-[var(--text-700)] mb-1.5';
    $labelClassInline = 'w-[145px] shrink-0 text-right text-[13px] font-medium text-[var(--text-700)]';
    $cellInputClass = 'w-full border border-[var(--border-default)] rounded-[var(--radius-control)] px-2.5 py-2 text-[14px] text-[var(--text-700)] bg-[var(--surface-card)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none';
@endphp

<script>
var __invForm = {
    services:        {!! $servicesJson !!},
    items:           {!! $existingItems !!},
    allClients:      {!! $clientsJson !!},
    clientId:        {{ $initClientId ?? 'null' }},
    clientName:      {!! json_encode($initClientName) !!},
    clientNit:       {!! json_encode($initClientNit) !!},
    issueDate:       {!! json_encode($initIssueDate) !!},
    dueDate:         {!! json_encode($initDueDate) !!},
    withholdingRate: {!! json_encode($initWithholdingRate) !!},
    servicesStoreUrl: {!! json_encode(route('services.store')) !!}
};
</script>

@if($errors->any())
<div class="mb-5 bg-[var(--color-danger-bg)] border border-[var(--color-danger)]/20 text-[var(--color-danger-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <p class="font-semibold mb-1">Corrige los siguientes errores:</p>
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div
    x-data="invoiceForm(__invForm.services, __invForm.items, __invForm.allClients, __invForm.clientId, __invForm.clientName, __invForm.clientNit, __invForm.issueDate, __invForm.dueDate, __invForm.withholdingRate, __invForm.servicesStoreUrl)"
    class="space-y-5"
>

{{-- ── Membrete: logo · contador · No. de cuenta (mismo criterio que el encabezado del PDF) ── --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)]">
    <div class="px-6 pt-5">
    <div class="relative flex items-center justify-end gap-4 min-h-[136px] pb-5 border-b border-[var(--border-default)]">
        <div class="absolute left-1/2 -translate-x-1/2">
            @if(auth()->user()->logo_path)
            <img src="{{ Storage::url(auth()->user()->logo_path) }}" alt="Logo" class="h-[136px] max-w-[209px] object-contain" />
            @endif
        </div>
        <div class="flex-shrink-0 flex items-center gap-2">
            <span class="text-[26px] font-bold text-[var(--text-900)] whitespace-nowrap">N°</span>
            <div class="h-9 min-w-[64px] px-3 flex items-center justify-center border border-[var(--border-default)] rounded-[var(--radius-control)] bg-[var(--surface-subtle)]">
                @if($isEdit)
                <span class="text-[15px] font-bold text-[var(--text-900)]">{{ $invoice->number }}</span>
                @else
                <span class="text-[15px] font-bold text-[var(--text-900)]" x-text="clientNextNumber"></span>
                @endif
            </div>
        </div>
    </div>
    </div>
    <div class="px-6 py-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">

        {{-- Cliente --}}
        <div class="flex items-center gap-4">
            <label class="{{ $labelClassInline }}">
                Cliente <span class="text-[var(--color-danger)]">*</span>
            </label>
            <div class="flex-1 max-w-[420px] min-w-0">
                <div class="relative" @click.outside="clientOpen = false">
                    <input
                        type="text"
                        x-model="clientName"
                        @input="searchClients()"
                        @focus="if (clientName.length >= 2 && clientResults.length) clientOpen = true"
                        @keydown.escape="clientOpen = false"
                        @keydown.enter.prevent="if (clientResults.length === 1) selectClient(clientResults[0])"
                        placeholder="Buscar por nombre o NIT…"
                        autocomplete="off"
                        class="{{ $inputClass }}"
                    />
                    <input type="hidden" name="client_id" :value="clientId" />

                    <div
                        x-show="clientOpen"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="absolute z-20 mt-1 w-full bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-control)] shadow-[var(--shadow-card-hover)] overflow-hidden"
                        style="display:none"
                    >
                        <template x-for="c in clientResults" :key="c.id">
                            <div
                                @click="selectClient(c)"
                                class="flex items-center justify-between px-4 py-2.5 hover:bg-[var(--surface-muted)] cursor-pointer border-b border-[var(--border-default)] last:border-0"
                            >
                                <span class="text-[14px] font-medium text-[var(--text-700)]" x-text="c.name"></span>
                                <span class="text-[12px] text-[var(--text-400)] ml-3 shrink-0" x-text="c.nit"></span>
                            </div>
                        </template>
                        <div
                            x-show="clientResults.length === 0"
                            class="px-4 py-3 text-[14px] text-[var(--text-400)] text-center"
                        >Sin resultados para esta búsqueda</div>
                    </div>
                </div>
                @error('client_id')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- NIT auto --}}
        <div class="flex items-center gap-4">
            <label class="{{ $labelClassInline }}">No. de documento</label>
            <div class="flex-1 min-w-0">
                <input
                    type="text"
                    :value="clientNit"
                    readonly
                    placeholder="Se completa al seleccionar cliente"
                    class="{{ $inputClass }} bg-[var(--surface-muted)] text-[var(--text-500)] cursor-default"
                />
            </div>
        </div>

        {{-- Estado (solo edición — se actualiza automáticamente) --}}
        @if($isEdit)
        <div class="flex items-center gap-4">
            <label class="{{ $labelClassInline }}">Estado</label>
            <div class="flex-1 min-w-0 h-10 flex items-center gap-2 px-3.5 bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)]">
                <x-status-badge :variant="$sb['variant']">{{ $sb['label'] }}</x-status-badge>
                <span class="text-[12px] text-[var(--text-400)] whitespace-nowrap">(automático)</span>
            </div>
        </div>
        @endif

        {{-- Fecha emisión --}}
        <div class="flex items-center gap-4">
            <label for="issue_date" class="{{ $labelClassInline }}">
                Fecha de emisión <span class="text-[var(--color-danger)]">*</span>
            </label>
            <div class="flex-1 min-w-0">
                <input type="date" id="issue_date" name="issue_date" required
                       x-model="issueDate"
                       @change="if (plazo !== '') applyPlazo()"
                       class="{{ $inputClass }}" />
                @error('issue_date')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Plazo --}}
        <div class="flex items-center gap-4">
            <label for="plazo" class="{{ $labelClassInline }}">Plazo</label>
            <div class="flex-1 min-w-0">
                <select id="plazo" x-model="plazo" @change="applyPlazo()" class="{{ $inputClass }}">
                    <option value="">— Personalizado —</option>
                    <option value="0">Contado</option>
                    <option value="15">15 días</option>
                    <option value="30">30 días</option>
                    <option value="60">60 días</option>
                    <option value="90">90 días</option>
                </select>
            </div>
        </div>

        {{-- Fecha vencimiento --}}
        <div class="flex items-center gap-4">
            <label for="due_date" class="{{ $labelClassInline }}">Fecha de vencimiento</label>
            <div class="flex-1 min-w-0">
                <input type="date" id="due_date" name="due_date"
                       x-model="dueDate"
                       @change="plazo = ''"
                       class="{{ $inputClass }}" />
            </div>
        </div>

        {{-- Forma de pago --}}
        <div class="flex items-center gap-4">
            <label for="payment_method" class="{{ $labelClassInline }}">Forma de pago</label>
            <div class="flex-1 min-w-0">
                <select id="payment_method" name="payment_method" class="{{ $inputClass }}">
                    <option value="">— Sin especificar —</option>
                    @php $pm = old('payment_method', $isEdit ? $invoice->payment_method : ''); @endphp
                    <option value="cash"          {{ $pm === 'cash'          ? 'selected' : '' }}>Efectivo</option>
                    <option value="bank_transfer" {{ $pm === 'bank_transfer' ? 'selected' : '' }}>Transferencia bancaria</option>
                    <option value="bre_b"         {{ $pm === 'bre_b'         ? 'selected' : '' }}>BRE-B</option>
                    <option value="check"         {{ $pm === 'check'         ? 'selected' : '' }}>Cheque</option>
                </select>
            </div>
        </div>

    </div>
    </div>
</div>

{{-- ── Tabla de ítems ── --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)]">
    <div class="px-6 pt-4">
    <div class="pb-4 border-b border-[var(--border-default)]">
        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Ítems / Servicios</h2>
    </div>
    </div>

    <div class="px-6 py-5">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-[var(--surface-subtle)] border-b border-[var(--border-default)]">
                        <th class="py-2.5 pl-3 pr-3 text-left text-[11px] font-semibold text-[var(--text-400)]">Descripción <span class="text-[var(--color-danger)]">*</span></th>
                        <th class="py-2.5 pr-3 text-right text-[11px] font-semibold text-[var(--text-400)]" style="width:64px">Cant.</th>
                        <th class="py-2.5 pr-3 text-right text-[11px] font-semibold text-[var(--text-400)]" style="width:130px">Precio unit.</th>
                        <th class="py-2.5 pr-3 text-right text-[11px] font-semibold text-[var(--text-400)]" style="width:72px">Desc. %</th>
                        <th class="py-2.5 pr-3 text-right text-[11px] font-semibold text-[var(--text-400)]" style="width:72px">IVA %</th>
                        <th class="py-2.5 pr-3 text-right text-[11px] font-semibold text-[var(--text-400)]" style="width:115px">Subtotal</th>
                        <th class="py-2.5 pr-3" style="width:36px"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, index) in items" :key="index">
                        <tr class="border-b border-[var(--surface-muted)]">

                            <td class="py-2 pr-3 align-top">
                                <input type="hidden" :name="'items['+index+'][service_id]'" x-model="item.service_id" />
                                <div class="relative">
                                    <input type="text"
                                           :name="'items['+index+'][description]'"
                                           x-model="item.description"
                                           @input="searchServiceFor(index, $event)"
                                           @focus="if (item.description.length >= 2 && !item.service_id) refreshServiceDropdown(index, $event)"
                                           @blur="closeServiceDropdownSoon()"
                                           @keydown.escape="serviceDropdownIndex = null"
                                           required maxlength="255"
                                           placeholder="Escribe una descripción o busca un servicio del catálogo…"
                                           class="{{ $cellInputClass }}"
                                           :class="item.service_id ? 'pr-8' : ''" />

                                    <button type="button"
                                            x-show="item.service_id"
                                            @click="unlinkService(index)"
                                            title="Servicio del catálogo — clic para desvincular"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 text-[var(--color-primary)] hover:text-[var(--color-danger)]">
                                        <x-lucide-link class="w-3.5 h-3.5" />
                                    </button>
                                </div>

                                <template x-teleport="body">
                                    <div x-show="serviceDropdownIndex === index"
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="opacity-0 -translate-y-1"
                                         x-transition:enter-end="opacity-100 translate-y-0"
                                         class="fixed z-[60] bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-control)] shadow-[var(--shadow-card-hover)] overflow-hidden"
                                         :style="`top:${serviceDropdownPos.top}px; left:${serviceDropdownPos.left}px; width:${serviceDropdownPos.width}px`"
                                         style="display:none">
                                        <template x-for="s in serviceMatches" :key="s.id">
                                            <div @mousedown.prevent="selectServiceFor(index, s)"
                                                 class="flex items-center justify-between px-3.5 py-2 hover:bg-[var(--surface-muted)] cursor-pointer border-b border-[var(--border-default)] last:border-0">
                                                <span class="text-[13px] font-medium text-[var(--text-700)] truncate" x-text="s.name"></span>
                                                <span class="text-[12px] text-[var(--text-400)] ml-3 shrink-0" x-text="'$ ' + fmt(s.unit_price)"></span>
                                            </div>
                                        </template>
                                        <div x-show="serviceMatches.length === 0"
                                             @mousedown.prevent="openCreateServiceModal(index)"
                                             class="flex items-center gap-2 px-3.5 py-2.5 hover:bg-[var(--surface-muted)] cursor-pointer text-[var(--color-primary)]">
                                            <x-lucide-plus-circle class="w-4 h-4 shrink-0" />
                                            <span class="text-[13px] font-medium truncate">Crear servicio "<span x-text="item.description"></span>"</span>
                                        </div>
                                    </div>
                                </template>
                            </td>

                            <td class="py-2 pr-3 align-top">
                                <input type="number" :name="'items['+index+'][quantity]'"
                                       x-model="item.quantity" min="0.01" step="0.01" required
                                       @input="recalc()"
                                       class="{{ $cellInputClass }} text-right" />
                            </td>

                            <td class="py-2 pr-3 align-top">
                                <input type="hidden" :name="'items['+index+'][unit_price]'" x-model="item.unit_price" />
                                <input type="text" x-money="item.unit_price" required
                                       @input="recalc()"
                                       class="{{ $cellInputClass }} text-right" />
                            </td>

                            <td class="py-2 pr-3 align-top">
                                <input type="number" :name="'items['+index+'][discount_rate]'"
                                       x-model="item.discount_rate" min="0" max="100" step="0.01"
                                       placeholder="0"
                                       @input="recalc()"
                                       class="{{ $cellInputClass }} text-right" />
                            </td>

                            <td class="py-2 pr-3 align-top">
                                <select :name="'items['+index+'][vat_rate]'"
                                        x-model.number="item.vat_rate"
                                        @change="recalc()"
                                        class="{{ $cellInputClass }}">
                                    <option value="0">0%</option>
                                    <option value="19">19%</option>
                                </select>
                            </td>

                            <td class="py-2 pr-3 align-middle">
                                <div class="text-right">
                                    <div class="font-semibold text-[var(--text-900)] text-[14px]"
                                         x-text="'$ ' + fmt(lineTotal(item))"></div>
                                    <div class="text-[11px] text-[var(--color-danger)] leading-tight"
                                         x-show="(parseFloat(item.discount_rate) || 0) > 0"
                                         x-text="'−$ ' + fmt(lineDiscount(item))"></div>
                                </div>
                            </td>

                            <td class="py-2 align-middle">
                                <button type="button" @click="removeItem(index)"
                                        x-show="items.length > 1"
                                        class="p-1.5 text-[var(--text-400)] hover:text-[var(--color-danger)]"
                                        title="Eliminar fila">
                                    <x-lucide-trash-2 class="w-4 h-4" />
                                </button>
                            </td>

                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <button type="button" @click="addItem()"
                    class="inline-flex items-center gap-1.5 px-3 h-8 text-[13px] font-medium text-[var(--color-primary)] border border-[var(--border-default)] bg-[var(--color-primary-light)] hover:bg-[var(--color-primary-light)] rounded-[var(--radius-control)]">
                <x-lucide-plus class="w-3.5 h-3.5" />
                Agregar fila
            </button>
        </div>
    </div>

    {{-- Totales --}}
    <div class="px-6 py-5 border-t border-[var(--border-default)]">
        <div class="flex justify-end">
            <div class="w-full max-w-sm space-y-1.5 text-[14px]">

                {{-- Subtotal bruto — solo si hay descuentos --}}
                <div class="flex justify-between text-[var(--text-500)]" x-show="discountAmount > 0">
                    <span>Subtotal bruto</span>
                    <span x-text="'$ ' + fmt(grossTotal)"></span>
                </div>
                <div class="flex justify-between font-medium text-[var(--color-danger)]" x-show="discountAmount > 0">
                    <span>− Descuentos</span>
                    <span x-text="'$ ' + fmt(discountAmount)"></span>
                </div>

                {{-- Base / subtotal --}}
                <div class="flex justify-between text-[var(--text-700)]"
                     :class="discountAmount > 0 ? 'border-t border-[var(--border-default)] pt-1.5 mt-0.5' : ''">
                    <span x-text="discountAmount > 0 ? 'Base gravable' : 'Subtotal'"></span>
                    <span x-text="'$ ' + fmt(subtotal)"></span>
                </div>

                {{-- IVA --}}
                <div class="flex justify-between text-[var(--text-700)]">
                    <span>IVA</span>
                    <span x-text="'$ ' + fmt(vatAmount)"></span>
                </div>

                {{-- Retefuente --}}
                <div class="flex justify-between items-center text-[var(--text-700)] border-t border-dashed border-[var(--border-default)] pt-2 mt-1">
                    <div class="flex items-center gap-2">
                        <span>Retefuente</span>
                        <div class="relative flex items-center">
                            <input type="number" name="withholding_rate"
                                   x-model="withholdingRate"
                                   @input="recalc()"
                                   min="0" max="100" step="0.01"
                                   placeholder="0"
                                   class="w-16 text-right border border-[var(--border-default)] rounded-[var(--radius-control)] pr-5 pl-2 py-1 text-[12px] bg-[var(--surface-card)] focus:ring-1 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none" />
                            <span class="absolute right-2 text-[12px] text-[var(--text-400)] pointer-events-none">%</span>
                        </div>
                    </div>
                    <span class="font-medium text-[var(--color-danger)]"
                          x-show="withholdingAmount > 0"
                          x-text="'−$ ' + fmt(withholdingAmount)"></span>
                    <span class="text-[var(--border-strong)] text-[12px]"
                          x-show="!(withholdingAmount > 0)">—</span>
                </div>

                {{-- Total final --}}
                <div class="flex justify-between font-bold text-[var(--text-900)] text-[18px] border-t-2 border-[var(--text-900)] pt-2 mt-1">
                    <span>Total a cobrar</span>
                    <span x-text="'$ ' + fmt(total)"></span>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- Notas --}}
<div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] px-6 py-5">
    <label for="notes" class="{{ $labelClass }}">
        Notas / Observaciones
        <span class="text-[12px] text-[var(--text-400)] font-normal">(aparece en el PDF)</span>
    </label>
    <textarea id="notes" name="notes" rows="3" maxlength="1000"
              placeholder="Instrucciones, condiciones, observaciones…"
              class="w-full border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 py-2.5 text-[14px] text-[var(--text-700)] bg-[var(--surface-card)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none">{{ old('notes', $isEdit ? $invoice->notes : '') }}</textarea>
</div>

{{-- ── Modal: Crear servicio (sin perder el avance de la cuenta de cobro) ── --}}
<div x-show="serviceModalOpen"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="display:none"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="absolute inset-0 bg-gray-900/50" @click="serviceModalOpen = false"></div>

    <div class="relative bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-md z-10"
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
                    <x-lucide-briefcase class="w-5 h-5 text-[var(--color-primary)]" />
                </div>
                <h2 class="text-[16px] font-bold text-[var(--text-900)]">Crear servicio</h2>
            </div>
            <button type="button" @click="serviceModalOpen = false"
                    class="p-1.5 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                <x-lucide-x class="w-4 h-4" />
            </button>
        </div>

        <div class="px-6 py-5 space-y-4">

            <template x-if="serviceModalErrors.general">
                <div class="bg-[var(--color-danger-bg)] border border-[var(--color-danger)]/20 text-[var(--color-danger-text)] text-[13px] px-3.5 py-2.5 rounded-[var(--radius-control)]" x-text="serviceModalErrors.general[0]"></div>
            </template>

            <div>
                <label class="{{ $labelClass }}">Nombre del servicio <span class="text-[var(--color-danger)]">*</span></label>
                <input type="text" x-model="serviceModalName" maxlength="255"
                       placeholder="Ej: Declaración de Renta Persona Natural"
                       @keydown.enter.prevent="saveNewService()"
                       class="{{ $inputClass }}" />
                <template x-if="serviceModalErrors.name">
                    <p class="mt-1 text-[12px] text-[var(--color-danger)]" x-text="serviceModalErrors.name[0]"></p>
                </template>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="{{ $labelClass }}">Precio base (COP) <span class="text-[var(--color-danger)]">*</span></label>
                    <input type="text" x-money="serviceModalPrice" @keydown.enter.prevent="saveNewService()" class="{{ $inputClass }}" />
                    <template x-if="serviceModalErrors.base_price">
                        <p class="mt-1 text-[12px] text-[var(--color-danger)]" x-text="serviceModalErrors.base_price[0]"></p>
                    </template>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Unidad de cobro <span class="text-[var(--color-danger)]">*</span></label>
                    <select x-model="serviceModalUnit" class="{{ $inputClass }}">
                        <option value="mes">Mes (mensual)</option>
                        <option value="hora">Hora</option>
                        <option value="declaracion">Declaración</option>
                        <option value="informe">Informe</option>
                        <option value="visita">Visita</option>
                        <option value="proyecto">Proyecto</option>
                        <option value="año">Año (anual)</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
            </div>

            <label class="flex items-center gap-2.5 bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 py-2.5 cursor-pointer">
                <input type="checkbox" x-model="serviceModalVat" class="rounded border-[var(--border-default)]" />
                <span class="text-[13px] font-medium text-[var(--text-700)]">Aplica IVA (19%)</span>
            </label>

            <div class="flex items-center justify-end gap-3 pt-1">
                <button type="button" @click="serviceModalOpen = false"
                        class="h-10 flex items-center px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                    Cancelar
                </button>
                <button type="button" @click="saveNewService()" :disabled="serviceModalSaving"
                        class="inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium disabled:opacity-60">
                    <x-lucide-check-circle class="w-4 h-4" />
                    <span x-text="serviceModalSaving ? 'Creando…' : 'Crear y usar'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

</div>

<script>
function invoiceForm(services, existingItems, allClients, initClientId, initClientName, initClientNit, initIssueDate, initDueDate, initWithholdingRate, servicesStoreUrl) {
    function buildNextNumber(allClients, clientId) {
        if (!clientId) return '0000';
        const c = (allClients || []).find(function(c) { return c.id === clientId; });
        if (!c) return '—';
        const num = String((c.consecutive || 0) + 1).padStart(4, '0');
        return c.prefix ? c.prefix + '-' + num : num;
    }

    return {
        // ── Typeahead cliente ──
        clientId:         initClientId,
        clientName:       initClientName,
        clientNit:        initClientNit,
        clientNextNumber: buildNextNumber(allClients, initClientId),
        clientResults:    [],
        clientOpen:       false,
        allClients:       allClients || [],

        // ── Fechas y plazo ──
        issueDate: initIssueDate || '',
        dueDate:   initDueDate   || '',
        plazo:     '',

        applyPlazo() {
            if (this.plazo === '' || !this.issueDate) return;
            const days = parseInt(this.plazo);
            const d = new Date(this.issueDate + 'T00:00:00');
            d.setDate(d.getDate() + days);
            this.dueDate = d.toISOString().split('T')[0];
        },

        searchClients() {
            this.clientId          = null;
            this.clientNit         = '';
            this.clientNextNumber  = '0000';
            if (this.clientName.length < 2) {
                this.clientOpen    = false;
                this.clientResults = [];
                return;
            }
            const q = this.clientName.toLowerCase();
            this.clientResults = this.allClients.filter(c =>
                c.name.toLowerCase().includes(q) ||
                c.nit.toLowerCase().replace(/\s/g, '').includes(q.replace(/\s/g, ''))
            ).slice(0, 10);
            this.clientOpen = this.clientResults.length > 0;
        },

        selectClient(c) {
            this.clientId          = c.id;
            this.clientName        = c.name;
            this.clientNit         = c.nit;
            this.clientNextNumber  = buildNextNumber(this.allClients, c.id);
            this.clientOpen        = false;
            this.clientResults     = [];
        },

        // ── Ítems ──
        services,
        items: existingItems.length > 0
            ? existingItems.map(i => ({ ...i, discount_rate: i.discount_rate ?? 0 }))
            : [{ service_id: null, description: '', quantity: 1, unit_price: 0, discount_rate: 0, vat_rate: 0 }],

        // ── Typeahead servicio (dentro de la fila de ítem) ──
        serviceDropdownIndex: null,
        serviceMatches:       [],
        serviceDropdownPos:   { top: 0, left: 0, width: 0 },

        // ── Modal "Crear servicio" (sin perder el avance de la cuenta de cobro) ──
        serviceModalOpen:    false,
        serviceModalIndex:   null,
        serviceModalName:    '',
        serviceModalUnit:    'mes',
        serviceModalPrice:   0,
        serviceModalVat:     false,
        serviceModalSaving:  false,
        serviceModalErrors:  {},

        // ── Retefuente ──
        withholdingRate: parseFloat(initWithholdingRate) || 0,

        // ── Totales reactivos ──
        grossTotal:        0,
        discountAmount:    0,
        subtotal:          0,
        vatAmount:         0,
        withholdingAmount: 0,
        total:             0,

        init() { this.recalc(); },

        addItem() {
            this.items.push({ service_id: null, description: '', quantity: 1, unit_price: 0, discount_rate: 0, vat_rate: 0 });
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
                this.recalc();
            }
        },

        positionServiceDropdown(event) {
            const r = event.target.getBoundingClientRect();
            this.serviceDropdownPos = { top: r.bottom + 4, left: r.left, width: Math.max(r.width, 280) };
        },

        searchServiceFor(index, event) {
            const item = this.items[index];
            item.service_id = null;
            const q = (item.description || '').toLowerCase();
            if (q.length < 2) {
                this.serviceDropdownIndex = null;
                this.serviceMatches = [];
                return;
            }
            this.serviceMatches = this.services.filter(s => s.name.toLowerCase().includes(q)).slice(0, 8);
            this.serviceDropdownIndex = index;
            this.positionServiceDropdown(event);
        },

        refreshServiceDropdown(index, event) {
            const item = this.items[index];
            const q = (item.description || '').toLowerCase();
            if (q.length < 2) return;
            this.serviceMatches = this.services.filter(s => s.name.toLowerCase().includes(q)).slice(0, 8);
            this.serviceDropdownIndex = index;
            this.positionServiceDropdown(event);
        },

        openCreateServiceModal(index) {
            this.serviceModalIndex   = index;
            this.serviceModalName    = this.items[index].description;
            this.serviceModalUnit    = 'mes';
            this.serviceModalPrice   = 0;
            this.serviceModalVat     = false;
            this.serviceModalErrors  = {};
            this.serviceDropdownIndex = null;
            this.serviceModalOpen    = true;
        },

        async saveNewService() {
            if (!this.serviceModalName.trim()) {
                this.serviceModalErrors = { name: ['El nombre del servicio es obligatorio.'] };
                return;
            }
            this.serviceModalSaving = true;
            this.serviceModalErrors = {};
            try {
                const res = await fetch(servicesStoreUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        name: this.serviceModalName,
                        unit: this.serviceModalUnit,
                        base_price: this.serviceModalPrice,
                        applies_vat: this.serviceModalVat,
                        status: 'active',
                    }),
                });

                if (res.status === 422) {
                    const data = await res.json();
                    this.serviceModalErrors = data.errors || {};
                    return;
                }
                if (!res.ok) throw new Error('save failed');

                const created = await res.json();
                const s = {
                    id: created.id,
                    name: created.name,
                    unit_price: parseFloat(created.base_price),
                    vat_rate: created.applies_vat ? 19 : 0,
                };
                this.services.push(s);
                if (this.serviceModalIndex !== null) {
                    this.selectServiceFor(this.serviceModalIndex, s);
                }
                this.serviceModalOpen = false;
            } catch (e) {
                this.serviceModalErrors = { general: ['No se pudo crear el servicio. Intenta de nuevo.'] };
            } finally {
                this.serviceModalSaving = false;
            }
        },

        selectServiceFor(index, s) {
            const item = this.items[index];
            item.service_id  = s.id;
            item.description = s.name;
            item.unit_price  = s.unit_price;
            item.vat_rate    = s.vat_rate;
            this.serviceDropdownIndex = null;
            this.serviceMatches = [];
            this.recalc();
        },

        closeServiceDropdownSoon() {
            setTimeout(() => { this.serviceDropdownIndex = null; }, 150);
        },

        unlinkService(index) {
            this.items[index].service_id = null;
        },

        lineDiscount(item) {
            const base = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
            return base * (parseFloat(item.discount_rate) || 0) / 100;
        },

        lineTotal(item) {
            const base      = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
            const disc      = base * (parseFloat(item.discount_rate) || 0) / 100;
            const afterDisc = base - disc;
            return afterDisc + afterDisc * (parseFloat(item.vat_rate) || 0) / 100;
        },

        recalc() {
            let gross = 0, disc = 0, vat = 0;
            this.items.forEach(item => {
                const base     = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
                const d        = base * (parseFloat(item.discount_rate) || 0) / 100;
                const afterD   = base - d;
                vat   += afterD * (parseFloat(item.vat_rate) || 0) / 100;
                gross += base;
                disc  += d;
            });
            this.grossTotal       = gross;
            this.discountAmount   = disc;
            this.subtotal         = gross - disc;
            this.vatAmount        = vat;
            this.withholdingAmount = this.subtotal * (parseFloat(this.withholdingRate) || 0) / 100;
            this.total            = this.subtotal + vat - this.withholdingAmount;
        },

        fmt(val) {
            return Number(val || 0).toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        },
    };
}
</script>
