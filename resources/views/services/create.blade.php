<x-app-layout>
<x-slot name="title">Nuevo Servicio</x-slot>

<div class="max-w-3xl mx-auto">

    {{-- Breadcrumb + título --}}
    <div class="mb-6">
        <nav class="flex items-center gap-1.5 text-[14px] text-[var(--text-400)] mb-2">
            <a href="{{ route('services.index') }}" class="hover:text-[var(--color-primary)]">Servicios</a>
            <x-lucide-chevron-right class="w-3.5 h-3.5" />
            <span class="text-[var(--text-700)] font-medium">Nuevo servicio</span>
        </nav>
        <h1 class="text-[22px] font-semibold text-[var(--text-900)]">Nuevo servicio</h1>
        <p class="text-[14px] text-[var(--text-500)] mt-0.5">Agrega un servicio o producto a tu catálogo</p>
    </div>

    <form method="POST" action="{{ route('services.store') }}">
        @csrf

        @include('services._form')

        <div class="flex items-center gap-3 mt-6">
            <button type="submit"
                    class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">
                Guardar servicio
            </button>
            <a href="{{ route('services.index') }}"
               class="h-10 flex items-center px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                Cancelar
            </a>
        </div>
    </form>

</div>
</x-app-layout>
