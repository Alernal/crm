@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-[var(--color-primary)] text-start text-[15px] font-medium text-[var(--color-primary)] bg-[var(--color-primary-light)] focus:outline-none'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-[15px] font-medium text-[var(--text-700)] hover:text-[var(--text-900)] hover:bg-[var(--surface-muted)] hover:border-[var(--border-strong)] focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
