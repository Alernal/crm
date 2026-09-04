@props(['name'])

<div {{ $attributes->merge(['class' => 'w-9 h-9 rounded-[10px] bg-[var(--color-primary-light)] flex items-center justify-center flex-shrink-0']) }}>
    <span class="text-[13px] font-semibold text-[var(--color-primary)]">{{ strtoupper(substr($name, 0, 2)) }}</span>
</div>
