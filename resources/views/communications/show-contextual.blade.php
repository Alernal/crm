@php
    $themesConfig = $themes->map(fn ($theme) => [
        'id' => $theme->id,
        'name' => $theme->name,
        'description' => $theme->description,
        'is_collapsed' => $theme->is_collapsed,
    ])->values();

    $chatConfig = [
        'fetchUrl' => route('communications.messages.index', $channel),
        'sendUrl' => route('communications.messages.store', $channel),
        'reactionUrlTemplate' => route('communications.messages.reactions.store', [$channel, '__MESSAGE__']),
        'typingUrl' => route('communications.typing.heartbeat', $channel),
        'lastId' => $initialMessages->last()['id'] ?? 0,
        'initialMessages' => $initialMessages,
        'themes' => $themesConfig,
        'members' => $mentionable,
        'quickEmojis' => \App\Models\MessageReaction::QUICK_EMOJIS,
    ];
@endphp

<x-communications-layout :title="'#'.$channel->name">

    <div class="h-[calc(100vh-92px)] lg:h-[calc(100vh-108px)] min-h-[420px] flex border border-[var(--border-default)] rounded-[var(--radius-card)] overflow-hidden shadow-[var(--shadow-card)] bg-[var(--surface-card)]">
        @include('communications._channel-sidebar')

        <div class="flex-1 flex flex-col min-w-0" x-data="chatChannel(@js($chatConfig))">

            {{-- Header --}}
            <div class="border-b border-[var(--border-default)] px-4 py-3 flex-shrink-0 bg-[var(--surface-card)] relative z-10">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex items-start gap-[10px]">
                        <div class="w-8 h-8 mt-0.5 rounded-[8px] bg-[var(--color-primary-light)] flex items-center justify-center flex-shrink-0">
                            <x-lucide-hash class="w-4 h-4 text-[var(--color-primary)] flex-shrink-0" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-[15px] font-bold text-[var(--text-900)] truncate">{{ $channel->name }}</h2>
                            @if($contextUrl)
                                <a href="{{ $contextUrl }}" class="inline-flex items-center gap-1 text-[12px] text-[var(--color-primary)] hover:underline mt-0.5">
                                    <x-lucide-external-link class="w-3 h-3" />
                                    {{ $contextLabel }}
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        @if($canManage)
                            <button type="button" @click="$dispatch('open-modal', 'nuevo-tema')"
                                    class="inline-flex items-center gap-[6px] px-3 py-1.5 rounded-[var(--radius-control)] text-[13px] text-[var(--text-500)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-700)]">
                                <x-lucide-plus class="w-4 h-4" />
                                Nuevo tema
                            </button>
                            <button type="button" @click="$dispatch('open-modal', 'miembros-canal')"
                                    class="inline-flex items-center gap-[6px] px-3 py-1.5 rounded-[var(--radius-control)] text-[13px] text-[var(--text-500)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-700)]">
                                <x-lucide-users class="w-4 h-4" />
                                Miembros ({{ $members->count() }})
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Acordeón de temas: fondo blanco plano, a pedido explícito del usuario --}}
            <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3 bg-[var(--surface-card)]" x-ref="scrollArea">
                <template x-for="theme in allThemes" :key="key(theme.id)">
                    <div class="border border-[var(--border-default)] rounded-[var(--radius-control)] overflow-hidden shadow-[var(--shadow-card)] bg-[var(--surface-card)]">
                        <button type="button" @click="toggleTheme(key(theme.id))"
                                class="w-full flex items-center justify-between gap-3 px-4 py-3 bg-[var(--surface-subtle)] hover:bg-[var(--surface-muted)] text-left">
                            <div class="flex items-center gap-2 min-w-0">
                                <span :class="expanded[key(theme.id)] ? 'rotate-90' : ''" class="flex-shrink-0 transition-transform duration-150">
                                    <x-lucide-chevron-right class="w-4 h-4 text-[var(--text-400)]" />
                                </span>
                                <span class="text-[14px] font-medium text-[var(--text-900)] truncate" x-text="theme.name"></span>
                                <span x-show="theme.description" class="text-[12px] text-[var(--text-400)] truncate" x-text="theme.description"></span>
                            </div>
                            <span class="text-[12px] text-[var(--text-400)] flex-shrink-0" x-text="countFor(key(theme.id)) + ' mensaje' + (countFor(key(theme.id)) === 1 ? '' : 's')"></span>
                        </button>

                        <div x-show="expanded[key(theme.id)]"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="px-4 py-3 space-y-1">
                            <template x-if="countFor(key(theme.id)) === 0">
                                <p class="text-[13px] text-[var(--text-400)] py-2">Sin mensajes todavía.</p>
                            </template>

                            <template x-for="(message, index) in messagesFor(key(theme.id))" :key="message.id">
                                <div>
                                    <template x-if="showDayDivider(messagesFor(key(theme.id)), index)">
                                        <div class="flex items-center justify-center my-3">
                                            <span class="text-[11px] font-medium text-[var(--text-500)] bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-full px-3 py-1" x-text="dayLabel(message)"></span>
                                        </div>
                                    </template>

                                    <div class="py-1.5">
                                        @include('communications._message-bubble')
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- "Escribiendo..." --}}
            <div class="px-4 pb-1 h-5 flex-shrink-0 bg-[var(--surface-subtle)]" x-show="typing.length > 0" x-cloak>
                <p class="text-[12px] text-[var(--text-400)] italic" x-text="typingLabel()"></p>
            </div>

            {{-- Composer --}}
            <div class="border-t border-[var(--border-default)] p-3 flex-shrink-0 relative bg-[var(--surface-card)]">
                <template x-if="mentionQuery !== null && filteredMembers().length">
                    <div class="absolute bottom-full left-3 mb-1 w-56 bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-control)] shadow-[var(--shadow-card-hover)] overflow-hidden z-10">
                        <template x-for="m in filteredMembers()" :key="m.handle">
                            <button type="button" @click="pickMention(m.handle)"
                                    class="w-full text-left px-3 py-2 text-[13px] hover:bg-[var(--surface-muted)]">
                                <span class="font-medium text-[var(--text-900)]" x-text="m.name"></span>
                                <span class="text-[var(--text-400)]" x-text="' @' + m.handle"></span>
                            </button>
                        </template>
                    </div>
                </template>

                <div x-show="replyTo" x-cloak class="flex items-center gap-2 mb-2 pl-2.5 pr-3 py-1.5 bg-[var(--surface-subtle)] rounded-[var(--radius-control)] border-l-[3px] border-[var(--color-primary)]">
                    <div class="min-w-0 flex-1">
                        <p class="text-[12px] font-semibold text-[var(--color-primary)]">Respondiendo a <span x-text="replyToAuthor()"></span> <span class="font-normal text-[var(--text-400)]">(hereda el tema del mensaje original)</span></p>
                        <p class="text-[12px] text-[var(--text-500)] truncate" x-text="replyToPreview()"></p>
                    </div>
                    <button type="button" @click="cancelReply()" class="text-[var(--text-400)] hover:text-[var(--text-700)] flex-shrink-0">
                        <x-lucide-x class="w-3.5 h-3.5" />
                    </button>
                </div>

                <div x-show="pendingFile" x-cloak class="flex items-center justify-between mb-2 px-3 py-1.5 bg-[var(--surface-subtle)] rounded-[var(--radius-control)] text-[12px] text-[var(--text-500)]">
                    <span class="inline-flex items-center gap-1.5 truncate">
                        <x-lucide-paperclip class="w-3.5 h-3.5 flex-shrink-0" />
                        <span x-text="pendingFile?.name"></span>
                    </span>
                    <button type="button" @click="clearFile()" class="text-[var(--text-400)] hover:text-[var(--text-700)] flex-shrink-0">
                        <x-lucide-x class="w-3.5 h-3.5" />
                    </button>
                </div>

                <div x-show="sendError" x-cloak class="flex items-center justify-between mb-2 px-3 py-1.5 bg-[var(--color-danger-bg)] rounded-[var(--radius-control)] text-[12px] text-[var(--color-danger-text)]">
                    <span x-text="sendError"></span>
                    <button type="button" @click="sendError = null" class="text-[var(--color-danger-text)]/70 hover:text-[var(--color-danger-text)] flex-shrink-0">
                        <x-lucide-x class="w-3.5 h-3.5" />
                    </button>
                </div>

                <div class="flex items-center gap-2 mb-2" x-show="!replyTo">
                    <span class="text-[12px] text-[var(--text-500)] flex-shrink-0">Enviando a:</span>
                    <select x-model="targetTheme" class="h-8 rounded-[var(--radius-control)] border border-[var(--border-strong)] text-[13px] focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]">
                        <template x-for="theme in allThemes" :key="key(theme.id)">
                            <option :value="key(theme.id)" x-text="theme.name"></option>
                        </template>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <div class="flex-1 flex items-end gap-1 rounded-full border border-[var(--border-default)] bg-[var(--surface-subtle)] focus-within:border-[var(--color-primary)] focus-within:bg-[var(--surface-card)] focus-within:ring-2 focus-within:ring-[var(--color-primary-light)] pl-1.5 pr-2 py-1.5 transition-colors">
                        @include('communications._attach-menu')
                        <textarea x-ref="textarea" x-model="content" @input="onInput($event)"
                                  @keydown.enter="handleEnter($event)"
                                  rows="1" placeholder="Escribe un mensaje…"
                                  class="flex-1 resize-none bg-transparent border-0 px-1 py-1 text-[14px] focus:outline-none focus:ring-0"></textarea>
                    </div>
                    <button type="button" @click="send()" :disabled="(!content.trim() && !pendingFile) || sending"
                            class="h-10 w-10 flex-shrink-0 flex items-center justify-center bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] disabled:opacity-40 text-white rounded-full shadow-[var(--shadow-card)] transition-colors" title="Enviar (Enter)">
                        <x-lucide-send class="w-4 h-4" />
                    </button>
                </div>
            </div>

            {{-- Lightbox de imágenes --}}
            <div x-show="lightbox" x-cloak @click="closeLightbox()" @keydown.escape.window="closeLightbox()"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-6 cursor-zoom-out">
                <button type="button" @click="closeLightbox()" class="absolute top-4 right-4 text-white/70 hover:text-white">
                    <x-lucide-x class="w-6 h-6" />
                </button>
                <img :src="lightbox" @click.stop class="max-w-full max-h-full rounded-[var(--radius-control)] shadow-2xl cursor-default">
            </div>
        </div>
    </div>

    @include('communications._new-channel-modal')
    @include('communications._new-theme-modal')
    @if($canManage)
        @include('communications._channel-members-modal')
    @endif

</x-communications-layout>

@include('communications._chat-scripts')
