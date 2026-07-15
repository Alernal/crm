@extends('admin.layout')
@section('title', 'Fechas por Dígito NIT')
@section('page-title', 'Fechas de Vencimiento por Dígito NIT')

@section('content')

<form method="GET" class="flex items-end gap-3 mb-5 flex-wrap">
    <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1.5">Obligación</label>
        <select name="obligation_id" onchange="this.form.submit()"
                class="border border-gray-200 bg-white rounded-lg px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
            @foreach ($obligations as $ob)
                <option value="{{ $ob->id }}" {{ $obligation?->id == $ob->id ? 'selected' : '' }}>
                    {{ $ob->code }} — {{ $ob->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1.5">Año</label>
        <select name="year" onchange="this.form.submit()"
                class="border border-gray-200 bg-white rounded-lg px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
            @foreach ($years as $y)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
    </div>
    @if ($obligation)
        <a href="{{ route('admin.tax-obligations.index') }}"
           class="text-xs font-medium text-gray-500 hover:text-gray-700 px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
            ← Obligaciones
        </a>
    @endif
</form>

@if (! $obligation)
    <div class="bg-white border border-gray-200 rounded-xl p-14 text-center text-gray-400 shadow-sm">
        Selecciona una obligación para gestionar sus fechas de vencimiento.
    </div>
@else

<div class="bg-blue-50 border border-blue-100 rounded-xl px-5 py-3 mb-5 flex items-center justify-between flex-wrap gap-3">
    <div class="flex items-center gap-3 flex-wrap">
        <span class="mono font-bold text-blue-700 text-sm bg-blue-100 px-2.5 py-1 rounded-md">{{ $obligation->code }}</span>
        <span class="text-gray-700 text-sm font-medium">{{ $obligation->name }}</span>
        <span class="text-xs border border-blue-200 text-blue-700 px-2 py-0.5 rounded-full bg-white">
            {{ \App\Models\TaxObligationType::$periodicities[$obligation->periodicity] }}
        </span>
        <span class="text-xs border border-gray-200 text-gray-500 px-2 py-0.5 rounded-full bg-white">
            {{ $obligation->nit_reference === 'ultimo_digito' ? 'Último dígito (0-9)' : 'Dos últimos dígitos' }}
        </span>
    </div>
    <form method="POST" action="{{ route('admin.tax-due-dates.destroy-year') }}"
          onsubmit="return confirm('¿Eliminar TODAS las fechas de {{ $obligation->code }} para {{ $year }}?')">
        @csrf
        <input type="hidden" name="obligation_id" value="{{ $obligation->id }}">
        <input type="hidden" name="year" value="{{ $year }}">
        <button class="text-xs font-medium text-red-500 hover:text-red-700 transition-colors">Limpiar año {{ $year }}</button>
    </form>
</div>

<form method="POST" action="{{ route('admin.tax-due-dates.save') }}">
    @csrf
    <input type="hidden" name="obligation_id" value="{{ $obligation->id }}">
    <input type="hidden" name="year" value="{{ $year }}">

    <div class="bg-white border border-gray-200 rounded-xl overflow-x-auto shadow-sm">
        <table class="text-xs">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50">
                    <th class="text-left px-4 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide w-36 sticky left-0 bg-gray-50">Período</th>
                    @foreach ($nitKeys as $nitKey)
                        <th class="text-center px-2 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide min-w-[112px]">
                            {{ $obligation->nit_reference === 'ultimo_digito' ? "Dígito {$nitKey}" : $nitKey }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach ($periods as $idx => $periodLabel)
                    @php $pNum = $idx + 1; @endphp
                    <tr class="hover:bg-gray-50/60 transition-colors">
                        <td class="px-4 py-2.5 font-semibold text-gray-700 text-xs sticky left-0 bg-white border-r border-gray-200">
                            {{ $periodLabel }}
                        </td>
                        @foreach ($nitKeys as $nitKey)
                            <td class="px-2 py-2 text-center">
                                <input type="date"
                                       name="dates[{{ $pNum }}][{{ $nitKey }}]"
                                       value="{{ $existingDates[$pNum][$nitKey] ?? '' }}"
                                       class="w-28 border rounded-md px-1.5 py-1 text-xs text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-400 focus:border-blue-400 transition-colors
                                              {{ isset($existingDates[$pNum][$nitKey]) ? 'bg-blue-50 border-blue-200' : 'border-gray-200 bg-white' }}">
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between mt-4">
        <p class="text-xs text-gray-400">
            @php $total = collect($existingDates)->flatten()->filter()->count(); @endphp
            {{ $total }} {{ $total === 1 ? 'fecha guardada' : 'fechas guardadas' }} para {{ $year }}
        </p>
        <button type="submit"
                class="bg-blue-800 hover:bg-blue-700 text-white text-sm font-semibold px-6 py-2 rounded-lg transition-colors shadow-sm">
            Guardar todas las fechas
        </button>
    </div>
</form>

@endif
@endsection
