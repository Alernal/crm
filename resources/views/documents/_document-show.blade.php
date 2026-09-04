{{-- Requiere en scope: $document, $routePrefix ('documents.contracts'|'documents.proposals'),
     $showCommunication (bool) --}}
@php
    $st = \App\Models\GeneratedDocument::STATUSES[$document->status] ?? ['label' => $document->status, 'variant' => 'neutral'];
@endphp

<div x-data="documentShowPage()" @keydown.escape.window="emailModal = false">

<a href="{{ route('documents.contracts.index') }}"
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

{{-- Encabezado --}}
<div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
    <div class="flex items-start gap-4 bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5 flex-1 min-w-0">
        <div class="w-12 h-12 rounded-[10px] bg-[var(--color-primary-light)] flex items-center justify-center flex-shrink-0">
            @svg('lucide-' . $document->documentType->icon, 'w-6 h-6 text-[var(--color-primary)]')
        </div>

        <div class="min-w-0 flex-1">
            <p class="text-[12.5px] font-medium text-[var(--text-500)] mb-1 truncate">
                {{ $document->documentType->label }} <span class="text-[var(--text-400)]">·</span> {{ $document->client->name }}
            </p>
            <div class="flex items-center gap-2 flex-wrap">
                <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $document->documentType->default_prefix }} {{ $document->full_number }}</p>
                <x-status-badge :variant="$st['variant']">{{ $st['label'] }}</x-status-badge>
            </div>
            <div class="flex items-center gap-4 mt-2 flex-wrap">
                <span class="inline-flex items-center gap-1.5 text-[12.5px] text-[var(--text-500)]">
                    <x-lucide-calendar class="w-3.5 h-3.5 text-[var(--text-400)]" />
                    Creado {{ $document->created_at->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
                </span>
                @if($document->responsible_user_id)
                <span class="inline-flex items-center gap-1.5 text-[12.5px] text-[var(--text-500)]">
                    <x-lucide-user class="w-3.5 h-3.5 text-[var(--text-400)]" />
                    {{ $document->user->name }}
                </span>
                @endif
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2 flex-shrink-0 flex-wrap">
        @if($showCommunication)
        <a href="{{ route('communications.context.open', ['type' => 'contrato', 'id' => $document->id]) }}"
           class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-message-square class="w-3.5 h-3.5" />
            Comunicación
        </a>
        @endif
        <button @click="printUrl = '{{ route($routePrefix.'.print', $document) }}'; isPrinting = true"
                class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-printer class="w-3.5 h-3.5" />
            Imprimir
        </button>
        <a href="{{ route($routePrefix.'.pdf', $document) }}"
           class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-download class="w-3.5 h-3.5" />
            Descargar PDF
        </a>
        <button @click="emailModal = true"
                class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[13px] font-medium">
            <x-lucide-mail class="w-3.5 h-3.5" />
            Enviar por correo
        </button>
        <form method="POST" action="{{ route($routePrefix.'.destroy', $document) }}"
              x-data=""
              x-on:submit.prevent="if(confirm('¿Eliminar este documento?')) $el.submit()">
            @csrf @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center justify-center w-10 h-10 flex-shrink-0 rounded-[var(--radius-control)] bg-[var(--color-danger-bg)] text-[var(--color-danger-text)] hover:opacity-80" title="Eliminar">
                <x-lucide-trash-2 class="w-3.5 h-3.5" />
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

{{-- Índice + contenido del documento --}}
<div class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-5">
    <aside class="hidden lg:block" x-show="outline.length > 0" x-cloak>
        <div class="sticky top-6 bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-4">
            <p class="flex items-center gap-1.5 text-[12px] font-semibold text-[var(--text-400)] mb-3">
                <x-lucide-list class="w-3.5 h-3.5" />
                Índice del documento
            </p>
            <nav class="space-y-0.5 max-h-[70vh] overflow-y-auto -mr-1 pr-1">
                <template x-for="item in outline" :key="item.id">
                    <button type="button" @click="scrollToClause(item.id)"
                            class="block w-full text-left px-2.5 py-1.5 rounded-[6px] text-[12.5px] leading-snug"
                            :class="active === item.id ? 'bg-[var(--color-primary-light)] text-[var(--color-primary)] font-medium' : 'text-[var(--text-500)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-700)]'"
                            x-text="item.label"></button>
                </template>
            </nav>
        </div>
    </aside>

    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-8 min-w-0">
        <div class="document-content text-[14px] leading-relaxed text-[var(--text-700)]" x-ref="documentContent">
            {!! $document->currentVersion->content_html !!}
        </div>
    </div>
</div>

<style>
    .document-content { text-align: justify; }

    .document-content .clause { padding: 1.5rem 0; border-top: 1px solid var(--border-default); }
    .document-content .clause:first-child { padding-top: 0; border-top: none; }

    .document-content .clause-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--color-primary);
        padding-left: 0.75rem;
        margin-bottom: 0.85rem;
        border-left: 3px solid var(--color-primary);
        text-align: left;
    }

    .document-content p { margin-bottom: 0.75rem; }
    .document-content p:last-child { margin-bottom: 0; }

    .document-content ol, .document-content ul { padding-left: 1.35rem; margin: 0.5rem 0 1rem; }
    .document-content ol { list-style: decimal; }
    .document-content ul { list-style: disc; }
    .document-content li { margin-bottom: 0.4rem; }
    .document-content li::marker { color: var(--color-primary); font-weight: 600; }
    .document-content strong { color: var(--text-900); }

    {{-- Ficha "Etiqueta: valor" — generada en vivo por enhanceKeyValueBlocks() a partir de
         los párrafos que ya vienen así en el HTML persistido (ver ClauseEngine::wrapClauseHtml) --}}
    .document-content .field-grid {
        display: grid;
        grid-template-columns: minmax(140px, 34%) 1fr;
        gap: 0.55rem 1rem;
        background: var(--surface-subtle);
        border: 1px solid var(--border-default);
        border-radius: var(--radius-control);
        padding: 0.9rem 1.1rem;
        margin-bottom: 0.75rem;
    }
    .document-content .field-label { font-size: 12.5px; font-weight: 600; color: var(--text-500); text-align: left; }
    .document-content .field-value { font-size: 13.5px; color: var(--text-900); text-align: left; }

    {{-- Firmas --}}
    .document-content .clearfix::after { content: ''; display: table; clear: both; }
    .document-content .signatures { width: 100%; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-default); }
    .document-content .signature-box { float: left; width: 46%; text-align: center; }
    .document-content .signature-box + .signature-box { float: right; }
    .document-content .signature-mark { min-height: 32px; }
    .document-content .signature-mark img { max-height: 32px; max-width: 100%; }
    .document-content .signature-line { border-top: 1px solid var(--border-strong); margin-top: 8px; padding-top: 6px; }
    .document-content .signature-name { font-size: 13px; font-weight: 600; color: var(--text-900); }
    .document-content .signature-role { font-size: 11.5px; color: var(--text-500); margin-top: 1px; }
    .document-content .signature-detail { font-size: 12px; color: var(--text-500); margin-top: 2px; }
</style>

<script>
    function documentShowPage() {
        return {
            isPrinting: false,
            printUrl: '',
            emailModal: false,
            outline: [],
            active: null,

            init() {
                this.buildOutline();
                this.enhanceKeyValueBlocks();
            },

            // Detecta los párrafos-título que arma ClauseEngine::wrapClauseHtml
            // (<p><strong>Título.</strong></p>, siempre primer hijo y único contenido de
            // ese párrafo) para armar el índice lateral y resaltar la cláusula visible
            // mientras se hace scroll — sin tocar el HTML persistido del documento.
            buildOutline() {
                const root = this.$refs.documentContent;
                const clauses = root.querySelectorAll(':scope > .clause');
                const items = [];

                clauses.forEach((clause, index) => {
                    const firstP = clause.querySelector(':scope > p:first-child');
                    const strong = firstP?.querySelector(':scope > strong');
                    const isHeading = strong && firstP.children.length === 1 && firstP.textContent.trim() === strong.textContent.trim();
                    if (!isHeading) return;

                    const id = 'clause-' + index;
                    clause.id = id;
                    firstP.classList.add('clause-title');
                    items.push({ id, label: strong.textContent.replace(/\.\s*$/, '') });
                });

                this.outline = items;
                if (!items.length || !('IntersectionObserver' in window)) return;

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) this.active = entry.target.id;
                    });
                }, { rootMargin: '-15% 0px -70% 0px' });

                clauses.forEach(clause => clause.id && observer.observe(clause));
            },

            scrollToClause(id) {
                document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            },

            // Reformatea en una ficha de 2 columnas los párrafos "Etiqueta: valor<br>..."
            // (Datos Generales, Datos del Cliente, Datos del Consultor) — el HTML no trae
            // ninguna marca de qué cláusula los generó, así que se detectan por forma: un
            // párrafo con <br> donde TODAS las líneas calzan el patrón "texto corto: resto".
            // Si una sola línea no calza, el párrafo se deja intacto (nunca se adivina).
            enhanceKeyValueBlocks() {
                const paragraphs = this.$refs.documentContent.querySelectorAll('.clause > p:not(.clause-title)');

                paragraphs.forEach(p => {
                    if (!p.innerHTML.includes('<br>')) return;

                    const lines = p.innerHTML.split(/<br\s*\/?>/i);
                    const rows = lines.map(line => {
                        const idx = line.indexOf(': ');
                        return idx > 0 && idx <= 40 ? { label: line.slice(0, idx), value: line.slice(idx + 2) } : null;
                    });

                    if (rows.some(r => r === null)) return;

                    const grid = document.createElement('div');
                    grid.className = 'field-grid';
                    grid.innerHTML = rows.map(r => `
                        <div class="field-label">${r.label}</div>
                        <div class="field-value">${r.value}</div>
                    `).join('');
                    p.replaceWith(grid);
                });
            },
        };
    }
</script>

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

        <form method="POST" action="{{ route($routePrefix.'.send_email', $document) }}" class="px-6 py-5 space-y-4">
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
