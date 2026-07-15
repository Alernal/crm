@extends('admin.layout')
@section('title', 'Logs de Auditoría')
@section('page-title', 'Logs de Auditoría')

@section('content')

<form method="GET" class="flex items-end gap-3 flex-wrap mb-5">
    <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1.5">Módulo</label>
        <select name="module"
                class="border border-gray-200 bg-white rounded-lg px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
            <option value="">Todos</option>
            @foreach ($modules as $mod)
                <option value="{{ $mod }}" {{ request('module') === $mod ? 'selected' : '' }}>{{ $mod }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1.5">Acción</label>
        <select name="action"
                class="border border-gray-200 bg-white rounded-lg px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
            <option value="">Todas</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ $action }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1.5">Desde</label>
        <input type="date" name="from" value="{{ request('from') }}"
               class="border border-gray-200 bg-white rounded-lg px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1.5">Hasta</label>
        <input type="date" name="to" value="{{ request('to') }}"
               class="border border-gray-200 bg-white rounded-lg px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
    </div>
    <button type="submit"
            class="bg-gray-700 hover:bg-gray-600 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors">
        Filtrar
    </button>
    @if (request()->hasAny(['module','action','from','to']))
        <a href="{{ route('admin.logs.index') }}" class="text-xs text-gray-400 hover:text-gray-600 font-medium self-end pb-2">Limpiar</a>
    @endif
</form>

<div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 bg-gray-50/80 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">
                <th class="text-left px-5 py-3">Acción</th>
                <th class="text-left px-5 py-3">Módulo</th>
                <th class="text-left px-4 py-3">ID</th>
                <th class="text-left px-4 py-3">IP</th>
                <th class="text-left px-4 py-3">Fecha</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse ($logs as $log)
                <tr class="hover:bg-gray-50/50 transition-colors" x-data="{ open: false }">
                    <td class="px-5 py-3">
                        @php
                            $actionStyle = match(true) {
                                str_contains($log->action, 'login')  => 'bg-blue-50 text-blue-700 border-blue-100',
                                str_contains($log->action, 'delete') => 'bg-red-50 text-red-700 border-red-100',
                                str_contains($log->action, 'create') => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                str_contains($log->action, 'toggle') => 'bg-amber-50 text-amber-700 border-amber-100',
                                str_contains($log->action, 'import') => 'bg-blue-50 text-blue-700 border-blue-100',
                                default                               => 'bg-gray-100 text-gray-600 border-gray-200',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border {{ $actionStyle }}">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-500 text-xs">{{ $log->module ?? '—' }}</td>
                    <td class="px-4 py-3 mono text-xs text-gray-400">{{ $log->record_id ?? '—' }}</td>
                    <td class="px-4 py-3 mono text-xs text-gray-400">{{ $log->ip_address ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-gray-400">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td class="px-4 py-3 text-right">
                        @if ($log->old_values || $log->new_values)
                            <button @click="open = !open"
                                    class="text-xs font-medium text-blue-700 hover:text-blue-600 transition-colors">
                                Detalle
                            </button>
                        @endif
                    </td>
                </tr>
                @if ($log->old_values || $log->new_values)
                    <tr x-show="open" x-cloak class="bg-gray-50/60">
                        <td colspan="6" class="px-5 py-4">
                            <div class="grid grid-cols-2 gap-4 text-xs mono">
                                @if ($log->old_values)
                                    <div>
                                        <p class="text-red-500 font-sans font-semibold mb-1.5 text-xs not-mono">Antes</p>
                                        <pre class="bg-white border border-red-100 rounded-xl p-3 text-red-700 overflow-auto max-h-32 text-[11px]">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                @endif
                                @if ($log->new_values)
                                    <div>
                                        <p class="text-emerald-600 font-sans font-semibold mb-1.5 text-xs not-mono">Después</p>
                                        <pre class="bg-white border border-emerald-100 rounded-xl p-3 text-emerald-700 overflow-auto max-h-32 text-[11px]">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-14 text-center text-gray-400 text-sm">No hay registros con esos filtros.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($logs->hasPages())
        <div class="px-5 py-4 border-t border-gray-200">
            {{ $logs->withQueryString()->links() }}
        </div>
    @endif
</div>

@endsection
