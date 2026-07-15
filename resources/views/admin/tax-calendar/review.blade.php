@extends('admin.layout')
@section('title', 'Revisar importación')
@section('page-title', 'Revisar Importación — ' . $import->year)

@section('header-actions')
    <a href="{{ route('admin.tax-calendar.import') }}"
       class="text-xs font-medium text-gray-500 hover:text-gray-700 px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
        ← Cargas
    </a>
@endsection

@section('content')

<div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 mb-5 flex items-center justify-between flex-wrap gap-3">
    <div>
        <p class="text-sm font-semibold text-gray-800">{{ $import->original_name }}</p>
        <p class="text-xs text-gray-400 mt-0.5">{{ $import->parse_notes }}</p>
    </div>
    @if ($import->status === 'imported')
        <span class="text-[11px] font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
            Importado el {{ $import->imported_at?->format('d/m/Y H:i') }}
        </span>
    @else
        <form method="POST" action="{{ route('admin.tax-calendar.confirm', $import) }}"
              onsubmit="return confirm('¿Importar {{ count($import->parsed_rows ?? []) }} fechas para {{ $import->year }}? Esto sobreescribe las fechas existentes de ese año para las obligaciones detectadas.')">
            @csrf
            <button type="submit"
                    class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition-colors shadow-sm">
                Confirmar e importar {{ count($import->parsed_rows ?? []) }} fechas
            </button>
        </form>
    @endif
</div>

@if (!empty($import->summary['skipped']))
<div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 mb-5">
    <p class="text-xs font-semibold text-amber-800 uppercase tracking-wide mb-2">Hojas no importadas</p>
    <ul class="text-sm text-amber-700 space-y-1">
        @foreach ($import->summary['skipped'] as $note)
            <li>• {{ $note }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    @forelse ($rowsByCode as $code => $rows)
        @php $ob = $obligations->get($code); @endphp
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50/80 flex items-center justify-between">
                <div>
                    <span class="mono text-[11px] font-bold bg-gray-900 text-gray-100 px-2 py-0.5 rounded-md">{{ $code }}</span>
                    <p class="text-xs font-semibold text-gray-700 mt-1">{{ $ob->name ?? $code }}</p>
                </div>
                <span class="text-xs font-bold text-blue-700">{{ $rows->count() }}</span>
            </div>
            <div class="max-h-64 overflow-y-auto divide-y divide-gray-50">
                @foreach ($rows->take(30) as $row)
                    <div class="px-4 py-2 text-xs flex items-center justify-between gap-2">
                        <span class="text-gray-500 truncate">{{ $row['period_label'] }} · NIT {{ $row['nit_key'] }}</span>
                        <span class="font-mono text-gray-800 flex-shrink-0">{{ \Illuminate\Support\Carbon::parse($row['due_date'])->format('d/m/Y') }}</span>
                    </div>
                @endforeach
                @if ($rows->count() > 30)
                    <div class="px-4 py-2 text-[11px] text-gray-400 text-center">+ {{ $rows->count() - 30 }} más</div>
                @endif
            </div>
        </div>
    @empty
        <div class="col-span-full bg-white border border-gray-200 rounded-xl p-14 text-center text-gray-400 shadow-sm">
            No se detectaron fechas para importar.
        </div>
    @endforelse
</div>

@endsection
