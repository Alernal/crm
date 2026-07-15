@extends('admin.layout')
@section('title', 'Módulos CRM')
@section('page-title', 'Módulos CRM')

@section('content')

<div class="mb-5">
    <p class="text-sm text-gray-500">Activa o desactiva módulos del CRM para todos los usuarios. Los módulos inactivos no aparecen en la navegación.</p>
</div>

<div class="space-y-2">
    @foreach ($modules as $module)
        <div class="bg-white border border-gray-200 rounded-xl px-5 py-4 flex items-center gap-5 shadow-sm hover:border-gray-300 transition-colors">

            {{-- Icon --}}
            <div class="w-10 h-10 rounded-xl {{ $module->active ? 'bg-blue-50' : 'bg-gray-100' }} flex items-center justify-center shrink-0 text-lg">
                @php $icons=['users'=>'👥','briefcase'=>'💼','document-text'=>'📄','credit-card'=>'💳','calendar'=>'📅','folder'=>'📁','cube'=>'📦']; echo $icons[$module->icon] ?? '📦'; @endphp
            </div>

            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                    <p class="text-sm font-semibold text-gray-800">{{ $module->name }}</p>
                    <span class="mono text-[10px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">{{ $module->key }}</span>
                </div>
                <p class="text-xs text-gray-400">{{ $module->description }}</p>
            </div>

            {{-- Status badge --}}
            <div class="shrink-0">
                <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full
                    {{ $module->active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-400 border border-gray-200' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $module->active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                    {{ $module->active ? 'Activo' : 'Inactivo' }}
                </span>
            </div>

            {{-- Toggle --}}
            <form method="POST" action="{{ route('admin.modules.toggle', $module) }}" class="shrink-0">
                @csrf @method('PATCH')
                <button type="submit"
                        class="text-sm font-medium px-4 py-1.5 rounded-lg border transition-colors
                               {{ $module->active
                                  ? 'border-red-200 text-red-600 hover:bg-red-50 hover:border-red-300'
                                  : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50 hover:border-emerald-300' }}">
                    {{ $module->active ? 'Desactivar' : 'Activar' }}
                </button>
            </form>
        </div>
    @endforeach
</div>

@endsection
