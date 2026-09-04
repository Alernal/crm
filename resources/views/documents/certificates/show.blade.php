<x-app-layout>
<x-slot name="title">{{ $document->documentType->label }} {{ $document->full_number }}</x-slot>

@php
    $st = \App\Models\GeneratedDocument::STATUSES[$document->status] ?? ['label' => $document->status, 'variant' => 'neutral'];
@endphp

<div x-data="{ isPrinting: false, printUrl: '', emailModal: false }" @keydown.escape.window="emailModal = false">

<a href="{{ route('documents.certificates.index') }}"
   class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)] mb-5">
    <x-lucide-arrow-left class="w-4 h-4" />
    Volver
</a>

@if(session('success'))
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="mb-5 flex items-center gap-2 bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">
    <x-lucide-check-circle class="w-4 h-4 flex-shrink-0" />
    {{ session('success') }}
</div>
@endif

<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-5">
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5 flex-1">
        <p class="text-[11px] font-semibold uppercase tracking-[0.06em] text-[var(--text-400)] mb-1">
            {{ $document->documentType->label }} · {{ $document->client->name }}
        </p>
        <div class="flex items-center gap-2 flex-wrap">
            <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $document->documentType->default_prefix }} {{ $document->full_number }}</p>
            <x-status-badge :variant="$st['variant']">{{ $st['label'] }}</x-status-badge>
        </div>
        <p class="text-[13px] text-[var(--text-500)] mt-1">
            Creado {{ $document->created_at->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
            @if($document->responsible_user_id)
            · Responsable: {{ $document->user->name }}
            @endif
        </p>
    </div>

    <div class="flex items-center gap-2 flex-shrink-0 flex-wrap">
        <button @click="printUrl = '{{ route('documents.certificates.print', $document) }}'; isPrinting = true"
                class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-printer class="w-3.5 h-3.5" />
            Imprimir
        </button>
        <a href="{{ route('documents.certificates.pdf', $document) }}"
           class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-download class="w-3.5 h-3.5" />
            Descargar PDF
        </a>
        <button @click="emailModal = true"
                class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-mail class="w-3.5 h-3.5" />
            Enviar por correo
        </button>
        <form method="POST" action="{{ route('documents.certificates.destroy', $document) }}"
              x-data=""
              x-on:submit.prevent="if(confirm('¿Eliminar este documento?')) $el.submit()">
            @csrf @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--color-danger-bg)] text-[var(--color-danger-text)] text-[13px] font-medium hover:opacity-80">
                <x-lucide-trash-2 class="w-3.5 h-3.5" />
                Eliminar
            </button>
        </form>
    </div>

    {{-- iframe oculto — carga la vista de impresión y dispara print() automáticamente (mismo patrón que financial/show.blade.php) --}}
    <iframe
        x-ref="printFrame"
        :src="isPrinting ? printUrl : ''"
        @load="if (isPrinting) { $refs.printFrame.contentWindow.print(); isPrinting = false }"
        style="position:fixed;top:-9999px;left:-9999px;width:0;height:0;border:0"
        title="Vista de impresión">
    </iframe>
</div>

@php
    $footerContact = collect([
        $document->user->email,
        $document->user->phone,
        collect([$document->user->address, $document->user->city])->filter()->implode(', ') ?: null,
    ])->filter()->implode('  ·  ');
@endphp

{{-- Contenido del documento --}}
<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-8">
    <div class="document-content max-w-3xl mx-auto text-[15px] leading-[1.7] text-[var(--text-700)]">
        {!! $document->currentVersion->content_html !!}
    </div>

    {{-- Pie de página corporativo — datos de contacto de la firma, separados de la
         firma legal (que solo lleva identificación/T.P. de quien certifica) --}}
    <div class="max-w-3xl mx-auto mt-16 pt-5 border-t border-[var(--border-default)] text-center">
        @if($footerContact !== '')
        <p class="text-[12px] text-[var(--text-400)]">{{ $footerContact }}</p>
        @endif
    </div>
</div>

<style>
    .document-content .clause { margin-bottom: 1.25rem; }
    .document-content ol, .document-content ul { list-style: decimal; padding-left: 1.5rem; margin: 0.5rem 0; }
    .document-content ul { list-style: disc; }
    .document-content strong { color: var(--text-900); }

    /* Firma (una sola caja — carta unilateral, sin firma del cliente) */
    .document-content .signature-single { width: 260px; margin-top: 2.5rem; }
    .document-content .signature-mark { min-height: 32px; }
    .document-content .signature-mark img { max-height: 32px; max-width: 100%; }
    .document-content .signature-line { border-top: 1px solid var(--border-strong); margin-top: 8px; padding-top: 6px; }
    .document-content .signature-name { font-size: 13px; font-weight: 600; color: var(--text-900); }
    .document-content .signature-detail { font-size: 12px; color: var(--text-500); margin-top: 2px; }
</style>

{{-- Modal: enviar por correo --}}
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
                    <h2 class="text-[16px] font-bold text-[var(--text-900)]">Enviar por correo</h2>
                    <p class="text-[12px] text-[var(--text-400)] mt-0.5">
                        {{ $document->full_number }} · PDF adjunto
                    </p>
                </div>
            </div>
            <button @click="emailModal = false"
                    class="p-1.5 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[var(--text-400)] hover:text-[var(--text-700)]">
                <x-lucide-x class="w-4 h-4" />
            </button>
        </div>

        <form method="POST" action="{{ route('documents.certificates.send_email', $document) }}" class="px-6 py-5 space-y-4">
            @csrf

            <div>
                <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                    Correo electrónico <span class="text-[var(--color-danger)]">*</span>
                </label>
                <input type="email"
                       name="email"
                       value="{{ old('email', $document->client->email) }}"
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
                          placeholder="Escriba un mensaje adicional…"
                          class="w-full px-3.5 py-2.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none resize-none"></textarea>
            </div>

            <div class="bg-[var(--surface-subtle)] rounded-[var(--radius-control)] px-4 py-3 border border-[var(--border-default)] text-[12px] text-[var(--text-500)]">
                <div class="flex items-center gap-2">
                    <x-lucide-check-circle class="w-3.5 h-3.5 text-[var(--color-success)] flex-shrink-0" />
                    PDF adjunto de {{ $document->documentType->label }} {{ $document->full_number }}
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

</div>{{-- /x-data --}}

</x-app-layout>
