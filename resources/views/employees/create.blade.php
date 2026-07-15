<x-app-layout>
<x-slot name="title">Nuevo Empleado</x-slot>

<div class="max-w-4xl mx-auto">

    <div class="mb-6">
        <nav class="flex items-center gap-1.5 text-[14px] text-[var(--text-400)] mb-2">
            <a href="{{ route('employees.index', [], false) }}" class="hover:text-[var(--color-primary)]">Empleados</a>
            <x-lucide-chevron-right class="w-3.5 h-3.5" />
            <span class="text-[var(--text-700)] font-medium">Nuevo empleado</span>
        </nav>
        <h1 class="text-[22px] font-semibold text-[var(--text-900)]">Nuevo empleado</h1>
        <p class="text-[14px] text-[var(--text-500)] mt-0.5">Registra un empleado de uno de tus clientes</p>
    </div>

    <form method="POST" action="{{ route('employees.store', [], false) }}">
        @csrf

        @include('employees._form', ['clients' => $clients, 'selectedClientId' => $selectedClientId])

        <div class="flex items-center gap-3 mt-6">
            <button type="submit"
                    class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">
                Guardar empleado
            </button>
            <a href="{{ route('employees.index', [], false) }}"
               class="h-10 flex items-center px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                Cancelar
            </a>
        </div>
    </form>

</div>
</x-app-layout>
