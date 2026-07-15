@extends('admin.layout')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- KPIs --}}
<div class="grid grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
    @php
        $kpis = [
            ['label'=>'Usuarios CRM',              'value'=>$stats['users'],          'icon'=>'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z', 'color'=>'violet'],
            ['label'=>'Módulos activos',           'value'=>$stats['modules_active'].'/'.$stats['modules_total'], 'icon'=>'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z', 'color'=>'sky'],
            ['label'=>'Campos personalizados',     'value'=>$stats['fields_total'],   'icon'=>'M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75', 'color'=>'amber'],
            ['label'=>'Roles definidos',           'value'=>$stats['roles_total'],    'icon'=>'M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z', 'color'=>'emerald'],
            ['label'=>'Vencimientos DIAN '.date('Y'), 'value'=>$stats['tax_due_dates'], 'icon'=>'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5', 'color'=>'rose'],
        ];
        $palette = [
            'violet' =>'bg-blue-500/10 text-blue-400',
            'sky'    =>'bg-sky-500/10 text-sky-400',
            'amber'  =>'bg-amber-500/10 text-amber-400',
            'emerald'=>'bg-emerald-500/10 text-emerald-400',
            'rose'   =>'bg-rose-500/10 text-rose-400',
        ];
    @endphp

    @foreach ($kpis as $kpi)
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <div class="flex items-start justify-between mb-3">
                <p class="text-xs font-medium text-gray-400 leading-none">{{ $kpi['label'] }}</p>
                <div class="w-8 h-8 rounded-lg {{ $palette[$kpi['color']] }} flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $kpi['icon'] }}"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900 tracking-tight">{{ $kpi['value'] }}</p>
        </div>
    @endforeach
</div>

{{-- Audit log --}}
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
        <div>
            <h2 class="text-sm font-semibold text-gray-800">Actividad reciente</h2>
            <p class="text-xs text-gray-400 mt-0.5">Últimas 10 acciones registradas</p>
        </div>
        <a href="{{ route('admin.logs.index') }}"
           class="text-xs text-blue-700 hover:text-blue-600 font-medium">Ver todos →</a>
    </div>

    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 bg-gray-50/60">
                <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Acción</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Módulo</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">IP</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Fecha</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse ($recentLogs as $log)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-5 py-3">
                        @php
                            $actionStyle = match(true) {
                                str_contains($log->action, 'login')  => 'bg-blue-50 text-blue-700 border-blue-100',
                                str_contains($log->action, 'delete') => 'bg-red-50 text-red-700 border-red-100',
                                str_contains($log->action, 'create') => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                str_contains($log->action, 'import') => 'bg-blue-50 text-blue-700 border-blue-100',
                                default                               => 'bg-gray-100 text-gray-600 border-gray-200',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium border {{ $actionStyle }}">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-500 text-xs">{{ $log->module ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-400 text-xs mono">{{ $log->ip_address ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-400 text-xs">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-5 py-12 text-center text-gray-400 text-sm">Sin actividad registrada aún.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
