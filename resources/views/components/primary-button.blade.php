<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] text-[14px] font-medium text-white bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] focus:ring-offset-0']) }}>
    {{ $slot }}
</button>
