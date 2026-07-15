@extends('admin.layout')
@section('title', 'Nuevo campo')
@section('page-title', 'Nuevo campo personalizado')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <p class="text-sm text-gray-500">Los campos personalizados amplían el modelo de datos de cada módulo del CRM.</p>
        </div>
        <form method="POST" action="{{ route('admin.fields.store') }}" class="p-6 space-y-5"
              x-data="{ type: '{{ old('type', 'text') }}' }">
            @csrf
            @include('admin.fields._form')
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-200">
                <a href="{{ route('admin.fields.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 font-medium px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">Cancelar</a>
                <button type="submit"
                        class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors shadow-sm">
                    Crear campo
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
