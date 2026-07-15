@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-[14px] text-[var(--color-success)]']) }}>
        {{ $status }}
    </div>
@endif
