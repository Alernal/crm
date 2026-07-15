@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'h-10 w-full rounded-[var(--radius-control)] border border-[var(--border-default)] bg-[var(--surface-card)] text-[14px] text-[var(--text-700)] shadow-sm focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:ring-offset-0']) }}>
