<script>
function chatChannel(config) {
    return {
        content: '',
        messages: config.initialMessages,
        lastId: config.lastId,
        members: config.members,
        quickEmojis: config.quickEmojis,
        typing: [],

        // Solo presente en canales contextuales (config.themes viene poblado desde show-contextual.blade.php)
        allThemes: config.themes ? [...config.themes, { id: null, name: 'General del Canal', description: null }] : null,
        expanded: {},
        targetTheme: 'general',

        replyTo: null,
        pendingFile: null,
        mentionQuery: null,
        sendError: null,
        sending: false,

        pollTimer: null,
        typingThrottleUntil: 0,
        lightbox: null,

        init() {
            if (this.allThemes) {
                this.allThemes.forEach(theme => {
                    this.expanded[this.key(theme.id)] = theme.id === null ? true : !theme.is_collapsed;
                });
            }
            // El x-for de mensajes aún no está en el DOM cuando init() corre (Alpine
            // procesa x-data antes de sus hijos), así que medir scrollHeight aquí mismo
            // da el alto del contenedor vacío y deja la vista clavada arriba, mostrando
            // los mensajes más antiguos en vez del final de la conversación.
            this.$nextTick(() => this.scrollToBottom());
            this.pollTimer = setInterval(() => this.poll(), 4000);
            this.$watch('messages', () => this.$nextTick(() => this.scrollToBottom()));
        },

        key(themeId) {
            return themeId === null || themeId === undefined ? 'general' : String(themeId);
        },

        // Separadores de día ("Hoy" / "Ayer" / fecha) entre grupos de mensajes.
        showDayDivider(list, index) {
            if (index === 0) return true;
            return new Date(list[index].created_at).toDateString() !== new Date(list[index - 1].created_at).toDateString();
        },

        dayLabel(message) {
            const d = new Date(message.created_at);
            const today = new Date();
            const yesterday = new Date();
            yesterday.setDate(today.getDate() - 1);

            if (d.toDateString() === today.toDateString()) return 'Hoy';
            if (d.toDateString() === yesterday.toDateString()) return 'Ayer';

            return d.toLocaleDateString('es-CO', {
                day: 'numeric',
                month: 'long',
                year: d.getFullYear() !== today.getFullYear() ? 'numeric' : undefined,
            });
        },

        isImage(attachment) {
            return (attachment.mime_type || '').startsWith('image/');
        },

        fileKind(name) {
            const ext = (name || '').split('.').pop().toLowerCase();
            if (ext === 'pdf') return 'pdf';
            if (['xls', 'xlsx', 'csv'].includes(ext)) return 'sheet';
            if (['doc', 'docx'].includes(ext)) return 'doc';
            return 'file';
        },

        openLightbox(url) {
            this.lightbox = url;
        },

        closeLightbox() {
            this.lightbox = null;
        },

        // Vista previa tipo WhatsApp: renderiza la página 1 del PDF sobre un <canvas> en
        // el propio cliente (no hay Imagick/Ghostscript en el servidor para generar la
        // miniatura). Si el render falla (PDF corrupto, etc.) el x-init que lo invoca
        // cae al ícono genérico vía .catch().
        async renderPdfThumbnail(url, canvas) {
            // pdfjs-dist ya no acepta una URL como string simple: requiere el objeto { url }
            // (pasar el string directo produce "expected either data, range, or url parameter").
            const pdf = await window.pdfjsLib.getDocument({ url }).promise;
            const page = await pdf.getPage(1);

            const targetWidth = 220;
            const unscaledViewport = page.getViewport({ scale: 1 });
            const viewport = page.getViewport({ scale: targetWidth / unscaledViewport.width });

            const ratio = window.devicePixelRatio || 1;
            canvas.width = viewport.width * ratio;
            canvas.height = viewport.height * ratio;
            canvas.style.width = viewport.width + 'px';
            canvas.style.height = viewport.height + 'px';

            const context = canvas.getContext('2d');
            context.scale(ratio, ratio);

            await page.render({ canvasContext: context, viewport }).promise;
        },

        // Salta al mensaje original citado por una respuesta y lo resalta brevemente
        // (timeline plano estilo WhatsApp: toda respuesta vive en su propio lugar
        // cronológico, no anidada bajo el mensaje que cita).
        scrollToMessage(id) {
            const el = document.getElementById('message-' + id);
            if (!el) return;

            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.classList.add('chat-highlight-flash');
            setTimeout(() => el.classList.remove('chat-highlight-flash'), 1500);
        },

        // --- Acordeón de temas (solo canales contextuales) ---
        messagesFor(themeKey) {
            return this.messages.filter(m => this.key(m.theme_id) === themeKey);
        },

        countFor(themeKey) {
            return this.messagesFor(themeKey).length;
        },

        toggleTheme(themeKey) {
            this.expanded[themeKey] = !this.expanded[themeKey];
        },
        // --- fin acordeón de temas ---

        replyToAuthor() {
            const target = this.messages.find(m => m.id === this.replyTo);
            return target ? target.author_name : '';
        },

        replyToPreview() {
            const target = this.messages.find(m => m.id === this.replyTo);
            if (!target) return '';
            return target.content || (target.attachments.length ? '📎 Adjunto' : '');
        },

        startReply(message) {
            this.replyTo = message.id;
            this.$nextTick(() => this.$refs.textarea?.focus());
        },

        cancelReply() {
            this.replyTo = null;
        },

        onFileChange(event) {
            this.pendingFile = event.target.files[0] || null;
        },

        clearFile() {
            this.pendingFile = null;
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },

        // Filtra el selector nativo de archivos según el tipo elegido en el menú de
        // adjuntar (imagen/documento/otro) antes de abrirlo — mismo patrón de menú
        // contextual que WhatsApp/Messenger.
        openAttachPicker(kind) {
            const accepts = {
                image: 'image/*',
                document: '.pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx',
                other: '',
            };

            if (!this.$refs.fileInput) return;
            this.$refs.fileInput.accept = accepts[kind] ?? '';
            this.$refs.fileInput.click();
        },

        async poll() {
            const response = await fetch(`${config.fetchUrl}?after=${this.lastId}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) return;

            const data = await response.json();

            if (data.messages.length) {
                this.messages.push(...data.messages);
                this.lastId = data.messages[data.messages.length - 1].id;
            }

            this.typing = data.typing || [];
        },

        async send() {
            const value = this.content.trim();
            if ((!value && !this.pendingFile) || this.sending) return;

            this.sending = true;
            this.sendError = null;

            const threadId = this.replyTo;
            const themeId = !threadId && this.allThemes && this.targetTheme !== 'general' ? Number(this.targetTheme) : null;

            try {
                let response;

                if (this.pendingFile) {
                    const form = new FormData();
                    form.append('content', value);
                    if (threadId) form.append('thread_id', threadId);
                    if (themeId) form.append('theme_id', themeId);
                    form.append('file', this.pendingFile);

                    response = await fetch(config.sendUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: form,
                    });
                } else {
                    const body = { content: value };
                    if (threadId) body.thread_id = threadId;
                    if (themeId) body.theme_id = themeId;

                    response = await fetch(config.sendUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(body),
                    });
                }

                if (!response.ok) {
                    const payload = await response.json().catch(() => null);
                    this.sendError = payload?.message || `No se pudo enviar el mensaje (error ${response.status}).`;
                    return;
                }

                const data = await response.json();
                this.messages.push(data.message);
                this.lastId = data.message.id;

                if (this.allThemes) {
                    this.expanded[this.key(data.message.theme_id)] = true;
                }

                this.content = '';
                this.mentionQuery = null;
                this.replyTo = null;
                this.clearFile();
            } catch (error) {
                this.sendError = 'No se pudo conectar con el servidor. Intenta de nuevo.';
            } finally {
                this.sending = false;
            }
        },

        handleEnter(event) {
            if (event.shiftKey) return;
            event.preventDefault();
            this.send();
        },

        async toggleReaction(messageId, emoji) {
            const url = config.reactionUrlTemplate.replace('__MESSAGE__', messageId);

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ emoji }),
            });

            if (!response.ok) return;

            const data = await response.json();
            const message = this.messages.find(m => m.id === messageId);
            if (message) message.reactions = data.reactions;
        },

        sendTyping() {
            const now = Date.now();
            if (now < this.typingThrottleUntil) return;
            this.typingThrottleUntil = now + 3000;

            fetch(config.typingUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
        },

        typingLabel() {
            if (this.typing.length === 0) return '';
            if (this.typing.length === 1) return `${this.typing[0]} está escribiendo…`;
            return `${this.typing[0]} y ${this.typing.length - 1} más están escribiendo…`;
        },

        scrollToBottom() {
            const el = this.$refs.scrollArea;
            if (el) el.scrollTop = el.scrollHeight;
        },

        onInput(event) {
            this.sendTyping();

            const cursor = event.target.selectionStart;
            const upToCursor = this.content.slice(0, cursor);
            const match = upToCursor.match(/@([a-z0-9.\-]*)$/i);
            this.mentionQuery = match ? match[1] : null;
        },

        filteredMembers() {
            if (this.mentionQuery === null) return [];
            const query = this.mentionQuery.toLowerCase();
            return this.members.filter(m => m.handle.startsWith(query)).slice(0, 5);
        },

        pickMention(handle) {
            const cursor = this.$refs.textarea.selectionStart;
            const before = this.content.slice(0, cursor).replace(/@([a-z0-9.\-]*)$/i, '@' + handle + ' ');
            this.content = before + this.content.slice(cursor);
            this.mentionQuery = null;
            this.$nextTick(() => this.$refs.textarea.focus());
        },
    };
}
</script>
