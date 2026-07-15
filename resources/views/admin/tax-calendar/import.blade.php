@extends('admin.layout')
@section('title', 'Importar Calendario DIAN')
@section('page-title', 'Importar Calendario Tributario DIAN')

@section('header-actions')
    <a href="{{ route('admin.tax-calendar.import.template') }}"
       class="inline-flex items-center gap-1.5 border border-gray-200 hover:bg-gray-50 text-gray-600 text-xs font-semibold px-3.5 py-2 rounded-lg transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
        </svg>
        Descargar plantilla
    </a>
@endsection

@section('content')

<div class="bg-blue-50 border border-blue-100 rounded-xl px-5 py-4 mb-5 text-sm text-blue-800 leading-relaxed">
    <p class="font-semibold mb-1">Cómo funciona</p>
    <p>Sube el Excel oficial del Calendario Tributario DIAN del año correspondiente (una hoja por impuesto). El sistema reconoce las hojas por su nombre — descarga la plantilla si no estás seguro del formato exacto. Cada carga reemplaza las fechas de ese año para las obligaciones detectadas; los años anteriores no se ven afectados.</p>
</div>

<div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 mb-6">
    <form method="POST" action="{{ route('admin.tax-calendar.import.submit') }}" enctype="multipart/form-data" class="flex items-end gap-4 flex-wrap">
        @csrf
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1.5">Año</label>
            <select name="year" class="border border-gray-200 bg-white rounded-lg px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
                @foreach ($years as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[240px]">
            <label class="block text-xs font-semibold text-gray-500 mb-1.5">Archivo (.xlsx, .xls o .csv, máx. 20MB)</label>
            <input type="file" name="file" required accept=".xlsx,.xls,.csv"
                   class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:text-xs file:font-semibold hover:file:bg-blue-100">
        </div>
        <button type="submit"
                class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition-colors shadow-sm">
            Subir y previsualizar
        </button>
    </form>
</div>

<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-200 bg-gray-50/80">
        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Cargas recientes</h3>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">
                <th class="text-left px-5 py-2.5">Archivo</th>
                <th class="text-center px-4 py-2.5">Año</th>
                <th class="text-center px-4 py-2.5">Estado</th>
                <th class="text-left px-4 py-2.5">Nota</th>
                <th class="px-4 py-2.5"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse ($uploads as $u)
                <tr class="hover:bg-gray-50/50">
                    <td class="px-5 py-3 text-gray-700">{{ $u->original_name }}</td>
                    <td class="px-4 py-3 text-center text-gray-500">{{ $u->year }}</td>
                    <td class="px-4 py-3 text-center">
                        @php
                            $badges = [
                                'pending'  => 'bg-gray-100 text-gray-500',
                                'reviewed' => 'bg-amber-50 text-amber-700 border border-amber-200',
                                'imported' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                                'failed'   => 'bg-red-50 text-red-700 border border-red-200',
                            ];
                            $labels = ['pending' => 'Pendiente', 'reviewed' => 'En revisión', 'imported' => 'Importado', 'failed' => 'Error'];
                        @endphp
                        <span class="text-[11px] font-medium px-2.5 py-1 rounded-full {{ $badges[$u->status] }}">{{ $labels[$u->status] }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-400">{{ Str::limit($u->parse_notes, 80) }}</td>
                    <td class="px-4 py-3 text-right">
                        @if ($u->status === 'reviewed')
                            <a href="{{ route('admin.tax-calendar.review', $u) }}" class="text-xs font-medium text-blue-700 hover:text-blue-600">Revisar →</a>
                        @elseif ($u->status === 'imported')
                            <a href="{{ route('admin.tax-calendar.review', $u) }}" class="text-xs font-medium text-gray-400 hover:text-gray-600">Ver detalle</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400 text-sm">Aún no hay cargas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
