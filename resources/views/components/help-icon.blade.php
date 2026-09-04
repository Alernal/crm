@props(['title' => null, 'align' => 'left'])

@php
$alignClasses = $align === 'right' ? 'right-0 origin-top-right' : 'left-0 origin-top-left';
$arrowClasses = $align === 'right' ? 'right-3' : 'left-3';
@endphp

<span x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative inline-flex align-middle">
    <button type="button" @click="open = !open"
            {{ $attributes->merge(['class' => 'inline-flex items-center justify-center w-4 h-4 rounded-full text-[var(--text-400)] hover:text-[var(--color-primary)] hover:bg-[var(--color-primary-light)] transition-colors']) }}
            aria-label="Ayuda">
        <x-lucide-help-circle class="w-3.5 h-3.5" />
    </button>

    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.stop
         class="normal-case tracking-normal font-sans absolute z-40 top-full {{ $alignClasses }} mt-2 w-72 max-w-[calc(100vw_-_2rem)] rounded-[10px] bg-[var(--text-900)] shadow-[var(--shadow-card-hover)]">
        <span class="absolute -top-[5px] {{ $arrowClasses }} w-2.5 h-2.5 rotate-45 bg-[var(--text-900)] rounded-[2px]"></span>
        <div class="relative p-3.5">
            @if($title)
            <p class="text-[12.5px] font-semibold text-white mb-1 normal-case">{{ $title }}</p>
            @endif
            <p class="text-[12.5px] font-normal text-gray-300 leading-relaxed normal-case">{{ $slot }}</p>
        </div>
    </div>
</span>
