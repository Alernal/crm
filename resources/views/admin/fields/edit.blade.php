@extends('admin.layout')
@section('title', 'Editar campo')
@section('page-title', 'Editar campo · ' . $field->label)

@section('content')
<div class="max-w-2xl">
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <p class="text-sm text-gray-500">Modifica las propiedades del campo. Cambiar el tipo puede afectar datos existentes.</p>
            <span class="mono text-[11px] text-gray-400 bg-gray-100 px-2 py-1 rounded">id: {{ $field->id }}</span>
        </div>
        <form method="POST" action="{{ route('admin.fields.update', $field) }}" class="p-6 space-y-5"
              x-data="{ type: '{{ old('type', $field->type) }}' }">
            @csrf @method('PATCH')
            @include('admin.fields._form', ['ob' => $field])
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-200">
                <a href="{{ route('admin.fields.index', ['module' => $field->module_key]) }}"
                   class="text-sm text-gray-500 hover:text-gray-700 font-medium px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">Cancelar</a>
                <button type="submit"
                        class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors shadow-sm">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
