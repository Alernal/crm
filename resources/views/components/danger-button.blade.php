<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] text-[14px] font-medium text-white bg-[var(--color-danger)] hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-[var(--color-danger-bg)] focus:ring-offset-0']) }}>
    {{ $slot }}
</button>
