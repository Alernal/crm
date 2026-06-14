<x-app-layout>
<x-slot name="title">Clientes</x-slot>

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg">{{ $totalActive }} activos</span>
            @if($totalInactive)
            <span class="text-sm text-gray-500 bg-gray-100 px-2.5 py-1 rounded-lg">{{ $totalInactive }} inactivos</span>
            @endif
        </div>
    </div>
    <a href="{{ route('clients.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo cliente
    </a>
</div>

{{-- Flash --}}
@if(session('success'))
<div class="mb-4 flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3 rounded-lg">
    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
    </svg>
    {{ session('success') }}
</div>
@endif

{{-- Filtros --}}
<form method="GET" action="{{ route('clients.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-4 mb-5">
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1 relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Buscar por nombre o número de documento..."
                   class="pl-9 w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
        </div>
        <select name="status" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 min-w-[140px]">
            <option value="">Todos los estados</option>
            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Activo</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
        </select>
        <select name="regime" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 min-w-[220px]">
            <option value="">Todas las responsabilidades</option>
            @foreach(\App\Models\Client::TAX_RESPONSIBILITIES as $val => $lbl)
            <option value="{{ $val }}" {{ request('regime') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium rounded-lg transition-colors">
            Buscar
        </button>
        @if(request()->hasAny(['search','status','regime']))
        <a href="{{ route('clients.index') }}" class="px-4 py-2 border border-gray-300 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
            Limpiar
        </a>
        @endif
    </div>
</form>

{{-- Tabla --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    @if($clients->isEmpty())
    <div class="text-center py-16">
        <svg class="w-12 h-12 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        @if(request()->hasAny(['search','status','regime']))
        <p class="text-gray-500 font-medium">No se encontraron clientes con esos filtros</p>
        <a href="{{ route('clients.index') }}" class="mt-2 inline-block text-sm text-blue-600 hover:text-blue-700">Limpiar filtros</a>
        @else
        <p class="text-gray-500 font-medium">Aún no tienes clientes registrados</p>
        <a href="{{ route('clients.create') }}" class="mt-3 inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-700 font-medium">
            + Agregar primer cliente
        </a>
        @endif
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 uppercase tracking-wide bg-gray-50 border-b border-gray-100">
                    <th class="px-5 py-3 text-left font-medium">Nombre / Razón social</th>
                    <th class="px-5 py-3 text-left font-medium hidden md:table-cell">Documento</th>
                    <th class="px-5 py-3 text-left font-medium hidden lg:table-cell">Resp. tributaria</th>
                    <th class="px-5 py-3 text-left font-medium hidden xl:table-cell">Ciudad</th>
                    <th class="px-5 py-3 text-center font-medium">Estado</th>
                    <th class="px-5 py-3 text-right font-medium">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($clients as $client)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3.5">
                        <a href="{{ route('clients.show', $client) }}" class="font-semibold text-gray-900 hover:text-blue-700 transition-colors">
                            {{ $client->name }}
                        </a>
                        <p class="text-xs text-gray-400 mt-0.5 md:hidden">
                            {{ $client->document_type }} {{ $client->full_document }}
                        </p>
                    </td>
                    <td class="px-5 py-3.5 hidden md:table-cell text-gray-600">
                        <span class="text-xs font-medium text-gray-400">{{ $client->document_type }}</span>
                        {{ $client->full_document }}
                    </td>
                    <td class="px-5 py-3.5 hidden lg:table-cell">
                        @php
                            $regimeColors = [
                                'gran_contribuyente'   => 'bg-purple-100 text-purple-700',
                                'autorretenedor'       => 'bg-blue-100 text-blue-700',
                                'agente_retencion_iva' => 'bg-indigo-100 text-indigo-700',
                                'regimen_simple'       => 'bg-amber-100 text-amber-700',
                                'no_aplica'            => 'bg-gray-100 text-gray-600',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $regimeColors[$client->tax_regime] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ \App\Models\Client::TAX_RESPONSIBILITIES[$client->tax_regime] ?? $client->tax_regime }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 hidden xl:table-cell text-gray-600 text-sm">
                        {{ $client->city ?? '—' }}
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        @if($client->status === 'active')
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Activo
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactivo
                        </span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('clients.show', $client) }}"
                               class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-colors" title="Ver detalle">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="{{ route('clients.edit', $client) }}"
                               class="p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-md transition-colors" title="Editar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('clients.destroy', $client) }}"
                                  x-data=""
                                  x-on:submit.prevent="if(confirm('¿Eliminar a {{ addslashes($client->name) }}? Esta acción no se puede deshacer.')) $el.submit()">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($clients->hasPages())
    <div class="px-5 py-4 border-t border-gray-100">
        {{ $clients->links() }}
    </div>
    @endif
    @endif
</div>

</x-app-layout>
