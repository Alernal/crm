@extends('admin.layout')
@section('title', 'Nuevo rol')
@section('page-title', 'Nuevo rol')

@section('content')
<div class="max-w-3xl">
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <form method="POST" action="{{ route('admin.roles.store') }}" class="divide-y divide-gray-200">
            @csrf

            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-2">Nombre del rol <span class="text-red-400">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="ej: Contador Senior"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-2">Descripción</label>
                        <input type="text" name="description" value="{{ old('description') }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
                    </div>
                </div>
            </div>

            <div class="p-6">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Permisos por módulo</p>
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">
                                <th class="text-left px-4 py-3">Módulo</th>
                                <th class="text-center px-3 py-3 w-20">Ver</th>
                                <th class="text-center px-3 py-3 w-20">Crear</th>
                                <th class="text-center px-3 py-3 w-20">Editar</th>
                                <th class="text-center px-3 py-3 w-20">Eliminar</th>
                                <th class="text-center px-3 py-3 w-16">Todo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($modules as $mod)
                                <tr class="hover:bg-gray-50/50" x-data="permRow()">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-700 text-sm">{{ $mod->name }}</p>
                                        @unless ($mod->active)
                                            <span class="text-[10px] text-gray-400">(inactivo)</span>
                                        @endunless
                                    </td>
                                    @foreach (['view','create','edit','delete'] as $action)
                                        <td class="px-3 py-3 text-center">
                                            <input type="checkbox"
                                                   name="permissions[{{ $mod->key }}][{{ $action }}]"
                                                   value="1"
                                                   x-model="perms.{{ $action }}"
                                                   {{ old("permissions.{$mod->key}.{$action}") ? 'checked' : '' }}
                                                   class="w-4 h-4 rounded border-gray-300 text-blue-700 focus:ring-blue-400/40">
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-3 text-center">
                                        <input type="checkbox" x-model="all" @change="toggleAll()"
                                               class="w-4 h-4 rounded border-gray-300 text-blue-700 focus:ring-blue-400/40">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 px-6 py-4">
                <a href="{{ route('admin.roles.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 font-medium px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">Cancelar</a>
                <button type="submit"
                        class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors shadow-sm">
                    Crear rol
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function permRow() {
    return {
        perms: { view: false, create: false, edit: false, delete: false },
        get all() { return this.perms.view && this.perms.create && this.perms.edit && this.perms.delete; },
        set all(v) {},
        toggleAll() { const v = !this.all; this.perms = { view: v, create: v, edit: v, delete: v }; }
    };
}
</script>
@endsection
