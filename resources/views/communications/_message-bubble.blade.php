{{-- Requiere `message` en el scope de Alpine (x-for="message in ...") --}}
<div class="flex items-start" :id="'message-' + message.id" :class="message.is_mine ? 'justify-end' : 'justify-start'">
    <div class="max-w-[min(70%,480px)] group">
        {{-- Cita del mensaje original, estilo WhatsApp: clic para saltar a él en el timeline --}}
        <template x-if="message.reply_preview">
            <button type="button" @click="scrollToMessage(message.reply_preview.id)"
                    class="flex flex-col w-full text-left mb-1 pl-2.5 pr-3 py-1.5 rounded-[var(--radius-control)] border-l-[3px] border-[var(--color-primary)] bg-[var(--surface-subtle)] hover:bg-[var(--surface-muted)]">
                <span class="text-[12px] font-semibold text-[var(--color-primary)] truncate" x-text="message.reply_preview.author_name"></span>
                <span class="text-[12px] text-[var(--text-500)] truncate" x-text="message.reply_preview.content || '📎 Adjunto'"></span>
            </button>
        </template>

        {{-- Nombre del remitente dentro del propio mensaje, estilo WhatsApp — solo se muestra
             aparte cuando el mensaje es únicamente un adjunto (sin texto), ya que ahí no hay
             burbuja de color donde meterlo. Nunca se muestra en "mis" mensajes: el propio
             usuario no necesita verse el nombre a sí mismo, solo el resto del canal lo ve. --}}
        <template x-if="!message.content && !message.is_mine">
            <p class="text-[12.5px] font-semibold mb-1 text-left text-[var(--color-primary)]" x-text="message.author_name"></p>
        </template>

        <div class="px-3 py-2 rounded-[var(--radius-control)] rounded-tl-[4px] shadow-[var(--shadow-card)]"
             :class="message.is_mine ? 'bg-[var(--color-primary)] text-white' : 'bg-[var(--surface-card)] text-[var(--text-900)] border border-[var(--border-default)]'"
             x-show="message.content">
            <p class="text-[12.5px] font-semibold mb-0.5 text-[var(--color-primary)]" x-show="!message.is_mine" x-text="message.author_name"></p>
            <p class="text-[14px] whitespace-pre-wrap break-words" x-text="message.content"></p>
        </div>

        <template x-for="attachment in message.attachments" :key="attachment.id">
            <div class="mt-1">
                {{-- Imagen: miniatura real en el chat, con lightbox al hacer clic --}}
                <template x-if="isImage(attachment)">
                    <button type="button" @click="openLightbox(attachment.url)" class="block">
                        <img :src="attachment.url" :alt="attachment.name"
                             class="max-w-[280px] max-h-[280px] rounded-[var(--radius-control)] border border-[var(--border-default)] shadow-[var(--shadow-card)] object-cover cursor-zoom-in hover:opacity-90 transition-opacity">
                    </button>
                </template>

                {{-- PDF: vista previa en imagen de la página 1 (estilo WhatsApp), renderizada en el
                     navegador con pdf.js — este servidor no tiene Imagick/Ghostscript para generarla --}}
                <template x-if="fileKind(attachment.name) === 'pdf'">
                    <div x-data="{ pdfFailed: false }" class="w-[220px]">
                        <a :href="attachment.url" target="_blank"
                           class="block rounded-[var(--radius-control)] border border-[var(--border-default)] bg-[var(--surface-subtle)] shadow-[var(--shadow-card)] hover:border-[var(--border-strong)] overflow-hidden">
                            <canvas x-show="!pdfFailed" x-init="renderPdfThumbnail(attachment.url, $el).catch(() => pdfFailed = true)"
                                    class="block w-full"></canvas>
                            <div x-show="pdfFailed" class="h-[160px] flex items-center justify-center">
                                <x-lucide-file-text class="w-8 h-8 text-[var(--color-danger)]" />
                            </div>
                            <div class="flex items-center gap-2 px-3 py-2 border-t border-[var(--border-default)] bg-[var(--surface-card)]">
                                <x-lucide-file-text class="w-4 h-4 text-[var(--color-danger)] flex-shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-[12.5px] font-medium text-[var(--text-900)] truncate" x-text="attachment.name"></p>
                                    <p class="text-[11px] text-[var(--text-400)]" x-text="attachment.size"></p>
                                </div>
                            </div>
                        </a>
                    </div>
                </template>

                {{-- Otro documento (Excel/Word/CSV): tarjeta con ícono por tipo, no solo el nombre --}}
                <template x-if="!isImage(attachment) && fileKind(attachment.name) !== 'pdf'">
                    <a :href="attachment.url" target="_blank"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-[var(--radius-control)] border border-[var(--border-default)] bg-[var(--surface-card)] shadow-[var(--shadow-card)] hover:bg-[var(--surface-muted)] hover:border-[var(--border-strong)] max-w-[280px]">
                        <div class="w-9 h-9 rounded-[var(--radius-control)] flex items-center justify-center flex-shrink-0"
                             :class="{
                                 'bg-[var(--color-success-bg)]': fileKind(attachment.name) === 'sheet',
                                 'bg-[var(--color-primary-light)]': fileKind(attachment.name) === 'doc',
                                 'bg-[var(--surface-muted)]': fileKind(attachment.name) === 'file',
                             }">
                            <x-lucide-file-spreadsheet x-show="fileKind(attachment.name) === 'sheet'" class="w-[18px] h-[18px] text-[var(--color-success)]" />
                            <x-lucide-file-text x-show="fileKind(attachment.name) === 'doc'" class="w-[18px] h-[18px] text-[var(--color-primary)]" />
                            <x-lucide-file x-show="fileKind(attachment.name) === 'file'" class="w-[18px] h-[18px] text-[var(--text-400)]" />
                        </div>
                        <div class="min-w-0 flex-1 text-left">
                            <p class="text-[13px] font-medium text-[var(--text-900)] truncate" x-text="attachment.name"></p>
                            <p class="text-[11px] text-[var(--text-400)]" x-text="attachment.size"></p>
                        </div>
                        <x-lucide-download class="w-4 h-4 text-[var(--text-400)] flex-shrink-0" />
                    </a>
                </template>
            </div>
        </template>

        {{-- Reacciones + acciones: pegadas al mensaje (justo debajo de la burbuja/adjunto), no
             separadas de él por la hora — las reacciones existentes se solapan levemente con el
             borde inferior de la burbuja, como en cualquier chat moderno --}}
        <div class="flex items-center gap-1 -mt-1.5 relative z-[1] flex-wrap" :class="message.is_mine ? 'justify-end' : 'justify-start'">
            <template x-for="reaction in message.reactions" :key="reaction.emoji">
                <button type="button" @click="toggleReaction(message.id, reaction.emoji)"
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[12px] border bg-[var(--surface-card)] shadow-[var(--shadow-card)]"
                        :class="reaction.mine ? 'border-[var(--color-primary)] text-[var(--color-primary)]' : 'border-[var(--border-default)] text-[var(--text-500)]'">
                    <span x-text="reaction.emoji"></span>
                    <span x-text="reaction.count"></span>
                </button>
            </template>

            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" @click.outside="open = false"
                        class="opacity-0 group-hover:opacity-100 focus:opacity-100 w-6 h-6 flex items-center justify-center rounded-full text-[var(--text-400)] bg-[var(--surface-card)] shadow-[var(--shadow-card)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-700)] border border-[var(--border-default)]">
                    <x-lucide-smile-plus class="w-3.5 h-3.5" />
                </button>
                <div x-show="open" x-cloak @click.outside="open = false"
                     class="absolute bottom-full mb-1 flex items-center gap-1 bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-control)] shadow-[var(--shadow-card-hover)] p-1.5 z-10"
                     :class="message.is_mine ? 'right-0' : 'left-0'">
                    <template x-for="emoji in quickEmojis" :key="emoji">
                        <button type="button" @click="toggleReaction(message.id, emoji); open = false"
                                class="w-7 h-7 flex items-center justify-center rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] text-[15px]" x-text="emoji"></button>
                    </template>
                </div>
            </div>

            <button type="button" @click="startReply(message)"
                    class="opacity-0 group-hover:opacity-100 focus:opacity-100 w-6 h-6 flex items-center justify-center rounded-full text-[var(--text-400)] bg-[var(--surface-card)] shadow-[var(--shadow-card)] hover:bg-[var(--surface-muted)] hover:text-[var(--color-primary)] border border-[var(--border-default)]" title="Responder">
                <x-lucide-reply class="w-3.5 h-3.5" />
            </button>
        </div>

        {{-- Hora/fecha: al final, después del mensaje y sus reacciones --}}
        <div class="mt-1" :class="message.is_mine ? 'text-right' : 'text-left'">
            <span class="text-[11px] text-[var(--text-400)]" x-text="message.created_at_label"></span>
        </div>
    </div>
</div>
