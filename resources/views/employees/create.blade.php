<x-app-layout>
<x-slot name="title">Nuevo Empleado</x-slot>

<div class="max-w-4xl mx-auto">

    <div class="mb-6">
        <a href="{{ route('employees.index', [], false) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)] mb-2">
            <x-lucide-arrow-left class="w-4 h-4" />
            Cancelar
        </a>
        <p class="text-[22px] font-bold text-[var(--text-900)]">Nuevo empleado</p>
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
               class="h-10 flex items-center px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                Cancelar
            </a>
        </div>
    </form>

</div>
</x-app-layout>
