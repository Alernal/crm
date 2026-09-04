{{-- Menú de adjuntar: paperclip + popover vertical con tipos de archivo, mismo patrón
     que WhatsApp/Messenger. Requiere `openAttachPicker(kind)` y `$refs.fileInput` en el
     x-data raíz del chat (chatChannel(), ver _chat-scripts.blade.php). --}}
<input type="file" x-ref="fileInput" class="hidden" @change="onFileChange($event)">
<div class="relative" x-data="{ attachOpen: false }">
    <button type="button" @click="attachOpen = !attachOpen" @click.outside="attachOpen = false"
            class="h-8 w-8 flex-shrink-0 flex items-center justify-center rounded-full transition-colors"
            :class="attachOpen ? 'bg-[var(--color-primary-light)] text-[var(--color-primary)]' : 'text-[var(--text-500)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-700)]'"
            title="Adjuntar archivo">
        <x-lucide-paperclip class="w-4 h-4" />
    </button>

    <div x-show="attachOpen" x-cloak
         x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="absolute bottom-full left-0 mb-2 w-48 bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-control)] shadow-[var(--shadow-card-hover)] p-1.5 z-10">
        <button type="button" @click="openAttachPicker('image'); attachOpen = false"
                class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-left">
            <span class="w-7 h-7 rounded-full bg-[var(--color-primary-light)] text-[var(--color-primary)] flex items-center justify-center flex-shrink-0">
                <x-lucide-image class="w-4 h-4" />
            </span>
            <span class="text-[13px] text-[var(--text-700)] font-medium">Imagen</span>
        </button>
        <button type="button" @click="openAttachPicker('document'); attachOpen = false"
                class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-left">
            <span class="w-7 h-7 rounded-full bg-[var(--color-success-bg)] text-[var(--color-success)] flex items-center justify-center flex-shrink-0">
                <x-lucide-file-text class="w-4 h-4" />
            </span>
            <span class="text-[13px] text-[var(--text-700)] font-medium">Documento</span>
        </button>
        <button type="button" @click="openAttachPicker('other'); attachOpen = false"
                class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-left">
            <span class="w-7 h-7 rounded-full bg-[var(--surface-muted)] text-[var(--text-500)] flex items-center justify-center flex-shrink-0">
                <x-lucide-paperclip class="w-4 h-4" />
            </span>
            <span class="text-[13px] text-[var(--text-700)] font-medium">Otro archivo</span>
        </button>
    </div>
</div>
