<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] text-[14px] font-medium text-[var(--text-700)] bg-[var(--surface-subtle)] border border-[var(--border-default)] hover:bg-[var(--surface-muted)] disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] focus:ring-offset-0']) }}>
    {{ $slot }}
</button>
