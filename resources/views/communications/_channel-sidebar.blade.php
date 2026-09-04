<div class="w-full sm:w-[260px] flex-shrink-0 flex flex-col bg-[var(--color-primary)]">
    <div class="p-4 flex items-center justify-between">
        <h2 class="text-[14px] font-bold text-white">Canales</h2>
        <button type="button" @click="$dispatch('open-modal', 'nuevo-canal')"
                class="w-7 h-7 flex items-center justify-center rounded-full bg-white/15 hover:bg-white text-white hover:text-[var(--color-primary)] transition-colors" title="Nuevo canal">
            <x-lucide-plus class="w-4 h-4" />
        </button>
    </div>

    <div class="flex-1 overflow-y-auto py-2 px-2">
        @php $grouped = $channels->groupBy('channel_type'); @endphp

        @if($channels->isEmpty())
            <div class="text-center px-4 py-8">
                <div class="w-9 h-9 bg-white shadow-[var(--shadow-card)] rounded-full flex items-center justify-center mx-auto mb-2">
                    <x-lucide-hash class="w-4 h-4 text-[var(--color-primary)]" />
                </div>
                <p class="text-[13px] text-white/70">Aún no hay canales.</p>
            </div>
        @endif

        @foreach(['general' => ['Generales', 'hash'], 'contextual' => ['Por contexto', 'link']] as $type => [$label, $typeIcon])
            @if($grouped->has($type))
                <p class="flex items-center gap-[6px] px-2 pt-3 pb-1.5 text-[12px] font-normal text-white/60">
                    @svg('lucide-' . $typeIcon, 'w-3.5 h-3.5 flex-shrink-0 text-white/50')
                    {{ $label }}
                </p>
                <div class="space-y-0.5 mb-1">
                    @foreach($grouped[$type] as $ch)
                        @php $chActive = isset($activeChannel) && $activeChannel && $activeChannel->id === $ch->id; @endphp
                        <a href="{{ route('communications.show', $ch) }}"
                           class="flex items-center justify-between gap-2 px-2.5 py-[7px] rounded-[var(--radius-control)] text-[13.5px] border-l-[3px]
                                  {{ $chActive ? 'border-white bg-white/15 text-white font-semibold' : 'border-transparent font-normal text-white/80 hover:bg-white/10 hover:text-white' }}">
                            <span class="flex items-center gap-[6px] min-w-0">
                                <x-lucide-hash class="w-3.5 h-3.5 flex-shrink-0 {{ $chActive ? 'text-white' : 'text-white/50' }}" />
                                <span class="truncate">{{ $ch->name }}</span>
                            </span>
                            @if($ch->unread_count > 0)
                                <span class="inline-flex items-center px-[8px] py-[2px] rounded-[var(--radius-badge)] text-[11px] font-semibold bg-white text-[var(--color-primary)] flex-shrink-0">{{ $ch->unread_count }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>

    @auth('web')
        <div class="p-2">
            <a href="{{ route('communications.team.index') }}"
               class="flex items-center gap-[8px] px-2.5 py-[7px] rounded-[var(--radius-control)] text-[13.5px] font-normal text-white/80 hover:bg-white/10 hover:text-white">
                <x-lucide-users class="w-4 h-4 flex-shrink-0 text-white/50" />
                Gestionar equipo
            </a>
        </div>
    @endauth
</div>
