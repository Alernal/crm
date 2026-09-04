<x-app-layout>
<x-slot name="title">Nueva Cuenta de Cobro</x-slot>

<div>

    {{-- Breadcrumb + título --}}
    <div class="mb-6 max-w-[950px] mx-auto">
        <a href="{{ route('invoices.index') }}"
           class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)] mb-2">
            <x-lucide-arrow-left class="w-4 h-4" />
            Cancelar
        </a>
        <p class="text-[22px] font-bold text-[var(--text-900)]">Nueva cuenta de cobro</p>
    </div>

    <form method="POST" action="{{ route('invoices.store') }}" class="max-w-[950px] mx-auto">
        @csrf

        @include('invoices._form')

        <div class="flex items-center justify-between mt-5">
            <a href="{{ route('invoices.index') }}"
               class="h-10 flex items-center px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                Cancelar
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">
                <x-lucide-check-circle class="w-4 h-4" />
                Crear cuenta de cobro
            </button>
        </div>
    </form>

</div>
</x-app-layout>
