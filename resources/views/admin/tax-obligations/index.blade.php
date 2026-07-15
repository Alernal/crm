@extends('admin.layout')
@section('title', 'Tipos de Obligación')
@section('page-title', 'Tipos de Obligación Tributaria')

@section('header-actions')
    <a href="{{ route('admin.tax-obligations.create') }}"
       class="inline-flex items-center gap-1.5 bg-blue-800 hover:bg-blue-700 text-white text-xs font-semibold px-3.5 py-2 rounded-lg transition-colors shadow-sm">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Nueva obligación
    </a>
@endsection

@section('content')

<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 bg-gray-50/80">
                <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Código</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Nombre</th>
                <th class="text-center px-4 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Periodicidad</th>
                <th class="text-center px-4 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Ref. NIT</th>
                <th class="text-center px-4 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Régimen</th>
                <th class="text-center px-4 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Estado</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse ($obligations as $ob)
                <tr class="hover:bg-gray-50/50 transition-colors {{ !$ob->active ? 'opacity-50' : '' }}">
                    <td class="px-5 py-3.5">
                        <span class="mono text-[11px] font-bold bg-gray-900 text-gray-100 px-2.5 py-1 rounded-md">{{ $ob->code }}</span>
                    </td>
                    <td class="px-5 py-3.5">
                        <p class="font-semibold text-gray-800 text-sm">{{ $ob->name }}</p>
                        @if ($ob->description)
                            <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($ob->description, 65) }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        <span class="text-xs bg-blue-50 text-blue-700 border border-blue-100 px-2 py-0.5 rounded-md font-medium">
                            {{ \App\Models\TaxObligationType::$periodicities[$ob->periodicity] ?? $ob->periodicity }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        <span class="text-xs bg-amber-50 text-amber-700 border border-amber-100 px-2 py-0.5 rounded-md font-medium">
                            {{ \App\Models\TaxObligationType::$nitReferences[$ob->nit_reference] ?? $ob->nit_reference }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5 text-center text-xs text-gray-500 font-medium">
                        {{ \App\Models\TaxObligationType::$regimes[$ob->regime] ?? $ob->regime }}
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-medium px-2.5 py-1 rounded-full border
                            {{ $ob->active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-400 border-gray-200' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $ob->active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                            {{ $ob->active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.tax-obligations.edit', $ob) }}"
                               class="text-xs font-medium text-blue-700 hover:text-blue-600">Editar</a>
                            <form method="POST" action="{{ route('admin.tax-obligations.destroy', $ob) }}"
                                  onsubmit="return confirm('¿Eliminar «{{ $ob->name }}»?')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-medium text-gray-400 hover:text-red-500 transition-colors">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-5 py-14 text-center text-gray-400 text-sm">No hay tipos de obligación configurados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
