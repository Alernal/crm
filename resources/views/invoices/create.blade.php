<x-app-layout>
<x-slot name="title">Nueva Cuenta de Cobro</x-slot>

<div class="max-w-5xl mx-auto">

    {{-- Breadcrumb + título --}}
    <div class="mb-6">
        <nav class="flex items-center gap-1.5 text-[14px] text-[var(--text-400)] mb-2">
            <a href="{{ route('invoices.index') }}" class="hover:text-[var(--color-primary)]">Cuentas de cobro</a>
            <x-lucide-chevron-right class="w-3.5 h-3.5" />
            <span class="text-[var(--text-700)] font-medium">Nueva cuenta</span>
        </nav>
        <h1 class="text-[22px] font-semibold text-[var(--text-900)]">Nueva cuenta de cobro</h1>
        <p class="text-[14px] text-[var(--text-500)] mt-0.5">Selecciona el cliente, añade los servicios y genera el documento</p>
    </div>

    <form method="POST" action="{{ route('invoices.store') }}">
        @csrf

        @include('invoices._form')

        <div class="flex items-center justify-between mt-5">
            <a href="{{ route('invoices.index') }}"
               class="h-10 flex items-center px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
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
