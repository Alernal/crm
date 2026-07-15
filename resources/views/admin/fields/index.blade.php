@extends('admin.layout')
@section('title', 'Campos personalizados')
@section('page-title', 'Campos personalizados')

@section('header-actions')
    <a href="{{ route('admin.fields.create') }}"
       class="inline-flex items-center gap-1.5 bg-blue-800 hover:bg-blue-700 text-white text-xs font-semibold px-3.5 py-2 rounded-lg transition-colors shadow-sm">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Nuevo campo
    </a>
@endsection

@section('content')

{{-- Filtro módulo --}}
<div class="flex items-center gap-3 mb-5">
    <form method="GET" class="flex items-center gap-2">
        <label class="text-xs text-gray-500 font-medium">Filtrar por módulo:</label>
        <select name="module" onchange="this.form.submit()"
                class="border border-gray-200 bg-white rounded-lg px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
            <option value="">Todos los módulos</option>
            @foreach ($modules as $mod)
                <option value="{{ $mod->key }}" {{ $moduleKey === $mod->key ? 'selected' : '' }}>{{ $mod->name }}</option>
            @endforeach
        </select>
    </form>
    @if ($moduleKey)
        <a href="{{ route('admin.fields.index') }}" class="text-xs text-gray-400 hover:text-gray-600">Limpiar</a>
    @endif
    <span class="ml-auto text-xs text-gray-400">{{ $fields->count() }} campos</span>
</div>

<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 bg-gray-50/80">
                <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Módulo</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Key / Etiqueta</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Tipo</th>
                <th class="text-center px-4 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Ord.</th>
                <th class="text-center px-4 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Req.</th>
                <th class="text-center px-4 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Estado</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse ($fields as $field)
                <tr class="hover:bg-gray-50/50 transition-colors {{ !$field->active ? 'opacity-40' : '' }}">
                    <td class="px-5 py-3.5">
                        <span class="text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-100 px-2 py-0.5 rounded-md">
                            {{ $field->module->name ?? $field->module_key }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5">
                        <p class="font-medium text-gray-800 text-sm">{{ $field->label }}</p>
                        <p class="mono text-[11px] text-gray-400 mt-0.5">{{ $field->name }}</p>
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md font-medium">
                            {{ \App\Models\AdminCustomField::$types[$field->type] ?? $field->type }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5 text-center text-xs text-gray-400 mono">{{ $field->order }}</td>
                    <td class="px-4 py-3.5 text-center">
                        @if ($field->required)
                            <span class="text-xs font-semibold text-emerald-600">Sí</span>
                        @else
                            <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        <form method="POST" action="{{ route('admin.fields.toggle', $field) }}">
                            @csrf @method('PATCH')
                            <button type="submit" title="{{ $field->active ? 'Desactivar' : 'Activar' }}"
                                    class="inline-flex items-center gap-1.5 text-[11px] font-medium px-2 py-1 rounded-md border transition-colors
                                           {{ $field->active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-gray-50 text-gray-400 border-gray-200 hover:bg-gray-100' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $field->active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                {{ $field->active ? 'Activo' : 'Inactivo' }}
                            </button>
                        </form>
                    </td>
                    <td class="px-4 py-3.5">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.fields.edit', $field) }}"
                               class="text-xs font-medium text-blue-700 hover:text-blue-600">Editar</a>
                            <form method="POST" action="{{ route('admin.fields.destroy', $field) }}"
                                  onsubmit="return confirm('¿Eliminar el campo «{{ $field->label }}»?')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-medium text-gray-400 hover:text-red-500 transition-colors">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-5 py-14 text-center">
                        <p class="text-gray-400 text-sm">No hay campos configurados.</p>
                        <a href="{{ route('admin.fields.create') }}" class="text-blue-700 text-sm hover:underline mt-1 inline-block">Crear el primero →</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
