@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-[var(--color-primary)] text-[14px] font-medium leading-5 text-[var(--text-900)] focus:outline-none'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-[14px] font-medium leading-5 text-[var(--text-500)] hover:text-[var(--text-900)] hover:border-[var(--border-strong)] focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
