@extends('admin.layout')
@section('title', 'Roles y Permisos')
@section('page-title', 'Roles y Permisos')

@section('header-actions')
    <a href="{{ route('admin.roles.create') }}"
       class="inline-flex items-center gap-1.5 bg-blue-800 hover:bg-blue-700 text-white text-xs font-semibold px-3.5 py-2 rounded-lg transition-colors shadow-sm">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Nuevo rol
    </a>
@endsection

@section('content')

<div class="mb-5">
    <p class="text-sm text-gray-500">Los roles controlan qué acciones pueden realizar los usuarios del CRM en cada módulo.</p>
</div>

<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 bg-gray-50/80">
                <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Nombre</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Slug</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Descripción</th>
                <th class="text-center px-4 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Permisos</th>
                <th class="text-center px-4 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Estado</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse ($roles as $role)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-5 py-4 font-semibold text-gray-800">{{ $role->name }}</td>
                    <td class="px-5 py-4"><span class="mono text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-md">{{ $role->slug }}</span></td>
                    <td class="px-5 py-4 text-gray-500 text-xs max-w-xs">{{ $role->description ?? '—' }}</td>
                    <td class="px-4 py-4 text-center">
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-100 px-2.5 py-1 rounded-full">
                            {{ $role->permissions_count }}
                            <span class="font-normal text-blue-600">módulos</span>
                        </span>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-medium px-2.5 py-1 rounded-full border
                            {{ $role->active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-400 border-gray-200' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $role->active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                            {{ $role->active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.roles.edit', $role) }}"
                               class="text-xs font-medium text-blue-700 hover:text-blue-600">Editar</a>
                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                                  onsubmit="return confirm('¿Eliminar el rol «{{ $role->name }}»?')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-medium text-gray-400 hover:text-red-500 transition-colors">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-14 text-center text-gray-400 text-sm">No hay roles definidos.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
