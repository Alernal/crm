@props(['variant' => 'neutral'])

@php
$variants = [
    'danger'  => 'bg-[var(--color-danger-bg)] text-[var(--color-danger-text)]',
    'warning' => 'bg-[var(--color-warning-bg)] text-[var(--color-warning-text)]',
    'success' => 'bg-[var(--color-success-bg)] text-[var(--color-success-text)]',
    'info'    => 'bg-[var(--surface-muted)] text-[var(--text-700)]',
    'neutral' => 'bg-[var(--surface-muted)] text-[var(--text-500)]',
];
$classes = $variants[$variant] ?? $variants['neutral'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-[10px] py-[3px] rounded-[var(--radius-badge)] text-[12px] font-medium whitespace-nowrap {$classes}"]) }}>
    {{ $slot }}
</span>
