@extends('admin.layout')
@section('title', 'Nueva Obligación')
@section('page-title', 'Nueva Obligación Tributaria')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <form method="POST" action="{{ route('admin.tax-obligations.store') }}" class="p-6 space-y-5">
            @csrf
            @include('admin.tax-obligations._form')
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-200">
                <a href="{{ route('admin.tax-obligations.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 font-medium px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">Cancelar</a>
                <button type="submit"
                        class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors shadow-sm">
                    Crear obligación
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
