<x-app-layout>
<x-slot name="title">Nuevo Cliente</x-slot>

<div class="max-w-4xl">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-5">
        <a href="{{ route('clients.index') }}" class="hover:text-blue-600 transition-colors">Clientes</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-gray-800 font-medium">Nuevo cliente</span>
    </div>

    <form method="POST" action="{{ route('clients.store') }}">
        @csrf

        @include('clients._form')

        <div class="flex items-center gap-3 mt-6">
            <button type="submit"
                    class="px-6 py-2.5 bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Guardar cliente
            </button>
            <a href="{{ route('clients.index') }}"
               class="px-6 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Cancelar
            </a>
        </div>
    </form>

</div>
</x-app-layout>
