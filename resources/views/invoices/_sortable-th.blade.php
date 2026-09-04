<th class="{{ $thClass }} {{ $extra }}">
    <a href="{{ $sortUrl($field) }}" class="inline-flex items-center gap-1 hover:text-[var(--color-primary)] {{ $extra === 'text-right' || str_contains($extra, 'text-right') ? 'justify-end' : '' }}">
        {{ $label }}
        @if($currentSort !== $field)
            <x-lucide-chevrons-up-down class="w-3.5 h-3.5 text-[var(--text-400)]" />
        @elseif($currentDir === 'asc')
            <x-lucide-arrow-up class="w-3.5 h-3.5 text-[var(--color-primary)]" />
        @else
            <x-lucide-arrow-down class="w-3.5 h-3.5 text-[var(--color-primary)]" />
        @endif
    </a>
</th>
