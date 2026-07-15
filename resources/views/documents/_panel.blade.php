{{--
    Variables esperadas:
    $document  → Model\Document|null
    $type      → 'cedula' | 'tarjeta_profesional'
    $label     → string (nombre legible)
    $panelId   → string (id único para canvas/inputs)
--}}

<div
    x-data="documentPanel('{{ $panelId }}', '{{ $document ? route('documents.show', $document) : '' }}', '{{ $document ? $document->file_type : '' }}')"
    x-init="init()"
    class="grid grid-cols-1 lg:grid-cols-2 gap-6"
>

    {{-- ===== COLUMNA IZQUIERDA: controles ===== --}}
    <div class="space-y-5">

        {{-- Card: Documento actual --}}
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)]">
            <div class="px-5 py-4 border-b border-[var(--border-default)]">
                <h2 class="text-[16px] font-semibold text-[var(--text-900)]">{{ $label }}</h2>
            </div>

            <div class="p-5">
                @if($document)
                {{-- Documento existente --}}
                <div class="flex items-center gap-3 p-3 bg-[var(--color-success-bg)] border border-[#BBF7D0] rounded-[var(--radius-control)] mb-4">
                    <x-lucide-image class="w-[18px] h-[18px] text-[var(--color-success)] flex-shrink-0" />
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-medium text-[var(--color-success-text)] truncate">{{ $document->original_filename }}</p>
                        <p class="text-[12px] text-[var(--color-success)]">Subido {{ $document->created_at->diffForHumans() }}</p>
                    </div>
                    <form method="POST" action="{{ route('documents.destroy', $document) }}"
                          onsubmit="return confirm('¿Eliminar este documento?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-[var(--color-danger)] hover:opacity-80" title="Eliminar">
                            <x-lucide-trash-2 class="w-4 h-4" />
                        </button>
                    </form>
                </div>

                {{-- Reemplazar --}}
                <p class="text-[13px] text-[var(--text-500)] mb-3">¿Quieres reemplazarlo? Sube una nueva imagen:</p>
                @else
                {{-- Sin documento --}}
                <p class="text-[14px] text-[var(--text-500)] mb-4">Aún no has subido tu {{ strtolower($label) }}.</p>
                @endif

                {{-- Upload form --}}
                <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">

                    <label
                        class="flex flex-col items-center gap-2 w-full border-2 border-dashed border-[var(--border-default)] rounded-[var(--radius-control)] p-5 cursor-pointer hover:border-[var(--color-primary)] hover:bg-[var(--color-primary-light)]"
                        x-bind:class="dragOver ? 'border-[var(--color-primary)] bg-[var(--color-primary-light)]' : ''"
                        @dragover.prevent="dragOver = true"
                        @dragleave.prevent="dragOver = false"
                        @drop.prevent="handleDrop($event, '{{ $type }}')"
                    >
                        <x-lucide-image class="w-8 h-8 text-[var(--text-400)]" />
                        <span class="text-[13px] text-[var(--text-500)] text-center">
                            Arrastra una imagen aquí o <span class="text-[var(--color-primary)] font-medium">haz clic para seleccionar</span>
                        </span>
                        <span class="text-[12px] text-[var(--text-400)]">JPG, PNG, WEBP, PDF — máx. 10 MB</span>
                        <input
                            type="file"
                            name="file"
                            accept="image/jpeg,image/png,image/webp,application/pdf"
                            class="hidden"
                            x-ref="fileInput_{{ $panelId }}"
                            @change="previewUpload($event)"
                        >
                    </label>

                    @error('file') <p class="text-[12px] text-[var(--color-danger)]">{{ $message }}</p> @enderror

                    <button type="submit"
                            x-show="fileSelected"
                            class="w-full h-10 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium rounded-[var(--radius-control)]">
                        Subir documento
                    </button>
                </form>
            </div>
        </div>

        @if($document)
        {{-- Card: Marca de agua --}}
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)]">
            <div class="px-5 py-4 border-b border-[var(--border-default)]">
                <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Marca de agua</h2>
            </div>

            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">Texto de la marca de agua</label>
                    <input
                        type="text"
                        x-model="watermarkText"
                        @input="drawWatermark()"
                        placeholder="Ej: Solo para uso de Empresa XYZ"
                        class="w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none"
                        maxlength="80"
                    >
                    <p class="text-[12px] text-[var(--text-400)] mt-1">Se mostrará en diagonal sobre el documento</p>
                </div>

                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                        Opacidad: <span x-text="Math.round(opacity * 100) + '%'"></span>
                    </label>
                    <input
                        type="range"
                        min="0.1" max="0.7" step="0.05"
                        x-model="opacity"
                        @input="drawWatermark()"
                        class="w-full accent-[var(--color-primary)]"
                    >
                </div>

                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">Color</label>
                    <div class="flex gap-2">
                        <template x-for="color in colors" :key="color.value">
                            <button
                                type="button"
                                @click="selectedColor = color.value; drawWatermark()"
                                :title="color.label"
                                class="w-8 h-8 rounded-[var(--radius-control)] border-2"
                                :class="selectedColor === color.value ? 'border-[var(--text-700)]' : 'border-[var(--border-default)] hover:border-[var(--border-strong)]'"
                                :style="'background-color:' + color.value"
                            ></button>
                        </template>
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="flex gap-2 pt-1">
                    <button
                        type="button"
                        @click="downloadPdf()"
                        :disabled="!watermarkText || !imageLoaded"
                        class="flex-1 inline-flex items-center justify-center gap-2 h-10 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] disabled:bg-[var(--surface-muted)] disabled:text-[var(--text-400)] disabled:cursor-not-allowed text-white text-[14px] font-medium rounded-[var(--radius-control)]"
                    >
                        <x-lucide-download class="w-4 h-4" />
                        Descargar PDF
                    </button>
                    <button
                        type="button"
                        @click="printPdf()"
                        :disabled="!watermarkText || !imageLoaded"
                        class="flex-1 inline-flex items-center justify-center gap-2 h-10 border border-[var(--border-default)] hover:bg-[var(--surface-muted)] disabled:bg-[var(--surface-muted)] disabled:text-[var(--text-400)] disabled:cursor-not-allowed text-[var(--text-700)] text-[14px] font-medium rounded-[var(--radius-control)]"
                    >
                        <x-lucide-printer class="w-4 h-4" />
                        Imprimir
                    </button>
                </div>
                <p x-show="!watermarkText" class="text-[12px] text-[var(--color-warning)] text-center">
                    Escribe el texto de la marca de agua para habilitar la descarga e impresión
                </p>
            </div>
        </div>
        @endif

    </div>

    {{-- ===== COLUMNA DERECHA: vista previa ===== --}}
    <div class="flex flex-col">
        <div class="bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] flex-1 flex flex-col">
            <div class="px-5 py-4 border-b border-[var(--border-default)]">
                <div class="flex items-center justify-between">
                    <h2 class="text-[16px] font-semibold text-[var(--text-900)] flex items-center gap-2">
                        <x-lucide-eye class="w-4 h-4 text-[var(--color-primary)]" />
                        Vista previa en vivo
                    </h2>
                    <span x-show="watermarkText && imageLoaded"
                          class="text-[12px] font-medium px-[10px] py-[3px] bg-[var(--color-primary-light)] text-[var(--color-primary)] rounded-[var(--radius-badge)]">
                        Con marca de agua
                    </span>
                </div>
            </div>

            <div class="p-5 flex-1 flex items-center justify-center">
                {{-- Canvas preview --}}
                <div class="w-full flex items-center justify-center bg-[var(--surface-muted)] border border-[var(--border-default)] rounded-[var(--radius-control)] overflow-hidden min-h-64 relative">

                    {{-- Estado: sin documento --}}
                    @if(!$document)
                    <div class="flex flex-col items-center gap-3 text-center p-8">
                        <div class="w-16 h-16 rounded-[var(--radius-card)] bg-[var(--surface-card)] flex items-center justify-center">
                            <x-lucide-image class="w-8 h-8 text-[var(--text-400)]" />
                        </div>
                        <p class="text-[14px] font-semibold text-[var(--text-700)]">Sin documento</p>
                        <p class="text-[12px] text-[var(--text-400)]">Sube tu {{ strtolower($label) }} para ver la vista previa aquí</p>
                    </div>
                    @else

                    {{-- Canvas donde se renderiza el documento + marca de agua --}}
                    <canvas
                        id="canvas_{{ $panelId }}"
                        x-ref="canvas_{{ $panelId }}"
                        class="max-w-full max-h-[500px] object-contain rounded-[var(--radius-control)] shadow"
                        style="display:none"
                    ></canvas>

                    {{-- Loader mientras carga la imagen --}}
                    <div x-show="!imageLoaded" class="flex flex-col items-center gap-2">
                        <svg class="animate-spin w-8 h-8 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-[12px] text-[var(--text-400)]">Cargando documento...</p>
                    </div>

                    {{-- Hint cuando no hay texto --}}
                    <div x-show="imageLoaded && !watermarkText"
                         class="absolute bottom-3 left-0 right-0 flex justify-center pointer-events-none">
                        <span class="bg-gray-900/70 text-white text-[12px] px-3 py-1.5 rounded-[var(--radius-control)]">
                            Escribe el texto de la marca de agua para verla aquí
                        </span>
                    </div>

                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- iframe oculto para impresión directa del documento con marca de agua --}}
    <iframe
        x-ref="printFrame_{{ $panelId }}"
        style="position:fixed;top:-9999px;left:-9999px;width:0;height:0;border:0"
        title="Vista de impresión">
    </iframe>

</div>

@once
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function documentPanel(panelId, imageUrl, fileType) {
    return {
        panelId,
        imageUrl,
        fileType,
        watermarkText: '',
        opacity: 0.35,
        selectedColor: '#cc0000',
        imageLoaded: false,
        fileSelected: false,
        dragOver: false,
        img: null,
        colors: [
            { label: 'Rojo',  value: '#cc0000' },
            { label: 'Azul',  value: '#1e3a8a' },
            { label: 'Gris',  value: '#4b5563' },
            { label: 'Negro', value: '#111827' },
        ],

        init() {
            if (!this.imageUrl) return;
            if (this.fileType === 'application/pdf') {
                this.loadPdf();
            } else {
                this.loadImage();
            }
        },

        loadImage() {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                this.img = img;
                this.imageLoaded = true;
                this.$nextTick(() => this.drawWatermark());
            };
            img.onerror = () => { this.imageLoaded = true; };
            img.src = this.imageUrl;
        },

        async loadPdf() {
            try {
                const pdfjsLib = window['pdfjs-dist/build/pdf'];
                pdfjsLib.GlobalWorkerOptions.workerSrc =
                    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

                const response = await fetch(this.imageUrl, { credentials: 'same-origin' });
                const arrayBuffer = await response.arrayBuffer();
                const pdf  = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                const page = await pdf.getPage(1);

                const viewport = page.getViewport({ scale: 2.0 });
                const offscreen = document.createElement('canvas');
                offscreen.width  = viewport.width;
                offscreen.height = viewport.height;

                await page.render({
                    canvasContext: offscreen.getContext('2d'),
                    viewport,
                }).promise;

                const img = new Image();
                img.onload = () => {
                    this.img = img;
                    this.imageLoaded = true;
                    this.$nextTick(() => this.drawWatermark());
                };
                img.src = offscreen.toDataURL('image/png');
            } catch (e) {
                console.error('Error cargando PDF:', e);
                this.imageLoaded = true;
            }
        },

        getCanvas() {
            return document.getElementById('canvas_' + this.panelId);
        },

        drawWatermark() {
            const canvas = this.getCanvas();
            if (!canvas || !this.img) return;

            const ctx = canvas.getContext('2d');
            canvas.width  = this.img.naturalWidth;
            canvas.height = this.img.naturalHeight;

            // Mostrar canvas
            canvas.style.display = 'block';

            // Dibujar imagen original
            ctx.drawImage(this.img, 0, 0);

            if (!this.watermarkText.trim()) return;

            // Config marca de agua
            const text   = this.watermarkText.trim();
            const w      = canvas.width;
            const h      = canvas.height;
            const angle  = -35 * Math.PI / 180;
            const diag   = Math.sqrt(w * w + h * h);

            // Tamaño de fuente proporcional al documento
            const fontSize = Math.max(14, Math.min(w, h) * 0.04);

            ctx.save();
            ctx.translate(w / 2, h / 2);
            ctx.rotate(angle);

            ctx.font        = `bold ${fontSize}px Arial, sans-serif`;
            ctx.fillStyle   = this.hexToRgba(this.selectedColor, parseFloat(this.opacity));
            ctx.textAlign   = 'center';
            ctx.textBaseline = 'middle';

            // Repetir el texto en diagonal para cubrir el documento
            const spacing = fontSize * 6;
            const lines   = Math.ceil(diag / spacing) + 1;

            for (let i = -lines; i <= lines; i++) {
                ctx.fillText(text, 0, i * spacing);
            }

            ctx.restore();
        },

        hexToRgba(hex, alpha) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r},${g},${b},${alpha})`;
        },

        buildPdf() {
            const canvas = this.getCanvas();
            if (!canvas) return null;

            const { jsPDF } = window.jspdf;
            const w = canvas.width;
            const h = canvas.height;

            const pdf = new jsPDF({
                orientation: w >= h ? 'landscape' : 'portrait',
                unit: 'px',
                format: [w, h],
                hotfixes: ['px_scaling'],
            });

            const imgData = canvas.toDataURL('image/jpeg', 0.92);
            pdf.addImage(imgData, 'JPEG', 0, 0, w, h);
            return pdf;
        },

        downloadPdf() {
            if (!this.watermarkText.trim()) return;
            const pdf = this.buildPdf();
            if (!pdf) return;

            const name = (this.panelId === 'cedula' ? 'cedula' : 'tarjeta_profesional')
                       + '_marca_agua.pdf';
            pdf.save(name);
        },

        printPdf() {
            if (!this.watermarkText.trim()) return;
            const pdf = this.buildPdf();
            if (!pdf) return;

            const blob = pdf.output('blob');
            const url  = URL.createObjectURL(blob);
            const iframe = this.$refs['printFrame_' + this.panelId];

            iframe.onload = () => {
                iframe.contentWindow.print();
                URL.revokeObjectURL(url);
                iframe.onload = null;
            };
            iframe.src = url;
        },

        previewUpload(event) {
            this.fileSelected = event.target.files.length > 0;
        },

        handleDrop(event, type) {
            this.dragOver = false;
            const file = event.dataTransfer.files[0];
            if (!file) return;

            const ref = this.$refs['fileInput_' + this.panelId];
            if (!ref) return;

            const dt = new DataTransfer();
            dt.items.add(file);
            ref.files = dt.files;
            this.fileSelected = true;
        },
    };
}
</script>
@endonce
