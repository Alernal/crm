<x-app-layout>
<x-slot name="title">Dashboard — {{ $budget->name }}</x-slot>

{{-- Volver --}}
<a href="{{ route('financial.show', $budget) }}"
   class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[14px] font-medium text-[var(--text-700)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-900)] mb-5">
    <x-lucide-arrow-left class="w-4 h-4" />
    Volver al presupuesto
</a>

<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">

    <div class="flex items-center gap-2.5 px-6 py-5 border-b border-[var(--border-default)]">
        <div class="w-9 h-9 bg-[var(--color-primary-light)] rounded-[var(--radius-control)] flex items-center justify-center">
            <x-lucide-bar-chart-2 class="w-5 h-5 text-[var(--color-primary)]" />
        </div>
        <div>
            <h2 class="text-[16px] font-bold text-[var(--text-900)]">Dashboard del presupuesto</h2>
            <p class="text-[12px] text-[var(--text-400)] mt-0.5">Comportamiento de las cifras · Presupuestado vs. Real</p>
        </div>
    </div>

    <div class="p-6 space-y-6">

        {{-- KPIs --}}
        @php
            $cumplimiento = $cashFlowDashboard['cumplimiento'];
            $cumplimientoColor = $cumplimiento === null ? 'var(--text-400)' : ($cumplimiento >= 100 ? 'var(--color-success)' : ($cumplimiento >= 90 ? 'var(--color-warning)' : 'var(--color-danger)'));
        @endphp
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-4">
                <p class="text-[11px] text-[var(--text-400)] mb-1">Total entradas (Ppto)</p>
                <p class="text-[18px] font-bold text-[var(--text-900)] tabular-nums">$ {{ number_format($cashFlowDashboard['totalEntradasPpto'], 0, ',', '.') }}</p>
                <p class="text-[12px] text-[var(--color-success)] mt-0.5">Real: $ {{ number_format($cashFlowDashboard['totalEntradasReal'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-4">
                <p class="text-[11px] text-[var(--text-400)] mb-1">Total salidas (Ppto)</p>
                <p class="text-[18px] font-bold text-[var(--text-900)] tabular-nums">$ {{ number_format($cashFlowDashboard['totalSalidasPpto'], 0, ',', '.') }}</p>
                <p class="text-[12px] text-[var(--color-danger)] mt-0.5">Real: $ {{ number_format($cashFlowDashboard['totalSalidasReal'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-4">
                <p class="text-[11px] text-[var(--text-400)] mb-1">Disponible final (último período)</p>
                <p class="text-[18px] font-bold text-[var(--text-900)] tabular-nums">$ {{ number_format($cashFlowDashboard['finalPpto'], 0, ',', '.') }}</p>
                <p class="text-[12px] text-[var(--text-500)] mt-0.5">
                    @if($cashFlowDashboard['finalReal'] === null)
                        Real: sin datos aún
                    @else
                        Real (a {{ $cashFlowDashboard['lastRealPeriodLabel'] }}): $ {{ number_format($cashFlowDashboard['finalReal'], 0, ',', '.') }}
                    @endif
                </p>
            </div>
            <div class="bg-[var(--surface-subtle)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-4">
                <p class="text-[11px] text-[var(--text-400)] mb-1">Cumplimiento del saldo final</p>
                <p class="text-[18px] font-bold tabular-nums" style="color: {{ $cumplimientoColor }};">
                    {{ $cumplimiento === null ? '—' : number_format($cumplimiento, 1) . '%' }}
                </p>
                <p class="text-[12px] text-[var(--text-500)] mt-0.5">
                    Real / Ppto{{ $cashFlowDashboard['lastRealPeriodLabel'] ? ' · '.$cashFlowDashboard['lastRealPeriodLabel'] : '' }}
                </p>
            </div>
        </div>

        {{-- Gráficas --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <div class="flex items-center gap-4 text-[12px] mb-2">
                    <p class="text-[12px] font-semibold text-[var(--text-700)] flex-1">Disponible al final del período</p>
                    <span class="flex items-center gap-1.5 text-[var(--text-500)]">
                        <span class="w-3 h-[2px] rounded-full" style="background: var(--color-primary)"></span> Ppto
                    </span>
                    <span class="flex items-center gap-1.5 text-[var(--text-500)]">
                        <span class="w-3 h-[2px] rounded-full" style="background: var(--color-success)"></span> Real
                    </span>
                </div>
                <div style="height: 240px;">
                    <canvas id="cashFlowChart" role="img" aria-label="Disponible al final del período: presupuestado versus real"></canvas>
                </div>
            </div>
            <div>
                <p class="text-[12px] font-semibold text-[var(--text-700)] mb-2">Entradas vs. salidas por período (Ppto)</p>
                <div style="height: 240px;">
                    <canvas id="cashFlowFlowsChart" role="img" aria-label="Entradas versus salidas presupuestadas por período"></canvas>
                </div>
            </div>
        </div>

        {{-- % Cumplimiento por período --}}
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-[var(--border-default)]">
                        <th class="text-left px-2 py-2 text-[10px] font-semibold text-[var(--text-400)] uppercase tracking-[0.04em]">% Cumplimiento</th>
                        @foreach($chartLabels as $label)
                        <th class="text-right px-2 py-2 text-[10px] font-semibold text-[var(--text-400)] uppercase tracking-[0.04em]">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="px-2 py-2 text-[12px] text-[var(--text-500)]">Real / Ppto</td>
                        @foreach($chartCumplimiento as $c)
                        <td class="px-2 py-2 text-right">
                            @if($c === null)
                            <span class="text-[var(--border-strong)] text-[12px]">—</span>
                            @else
                            <x-status-badge :variant="$c >= 100 ? 'success' : ($c >= 90 ? 'warning' : 'danger')" class="!text-[10px] !px-[6px] !py-0">
                                {{ number_format($c, 1) }}%
                            </x-status-badge>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Mayores variaciones --}}
        @if(!empty($cashFlowDashboard['topVariances']))
        <div>
            <p class="text-[12px] font-semibold text-[var(--text-700)] mb-2">Mayores variaciones (Ppto vs. Real, acumulado del período)</p>
            <div class="border border-[var(--border-default)] rounded-[var(--radius-control)] overflow-hidden">
                <table class="w-full text-[13px]">
                    <tbody>
                        @foreach($cashFlowDashboard['topVariances'] as $v)
                        <tr class="border-b border-[var(--surface-muted)] last:border-b-0 hover:bg-[var(--surface-subtle)]">
                            <td class="px-4 py-2.5 text-[var(--text-500)]">{{ $v['label'] }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold text-[var(--color-warning)] tabular-nums">
                                $ {{ number_format($v['diff'], 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function formatCOP(value) {
        return '$ ' + Math.round(value).toLocaleString('es-CO');
    }

    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.color = '#6B7280';

    const balanceCtx = document.getElementById('cashFlowChart');
    if (balanceCtx) {
        new Chart(balanceCtx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: 'Presupuestado',
                        data: @json($chartPpto),
                        borderColor: '#2563EB',
                        backgroundColor: '#2563EB',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#2563EB',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        tension: 0.3,
                        fill: false,
                    },
                    {
                        label: 'Real',
                        data: @json($chartReal),
                        borderColor: '#059669',
                        backgroundColor: '#059669',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#059669',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        tension: 0.3,
                        fill: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#ffffff',
                        titleColor: '#111827',
                        bodyColor: '#111827',
                        borderColor: '#E5E7EB',
                        borderWidth: 1,
                        padding: 10,
                        titleFont: { weight: '600' },
                        callbacks: {
                            label: (c) => ' ' + c.dataset.label + ': ' + formatCOP(c.parsed.y),
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false }, border: { display: false } },
                    y: {
                        grid: { color: '#F3F4F6' },
                        border: { display: false },
                        ticks: { callback: (v) => formatCOP(v), maxTicksLimit: 5 },
                    },
                },
            },
        });
    }

    const flowsCtx = document.getElementById('cashFlowFlowsChart');
    if (flowsCtx) {
        new Chart(flowsCtx, {
            type: 'bar',
            data: {
                labels: @json($cashFlowDashboard['chartLabels']),
                datasets: [
                    {
                        label: 'Entradas',
                        data: @json($cashFlowDashboard['entradasPptoSerie']),
                        backgroundColor: '#059669',
                        borderRadius: 4,
                        maxBarThickness: 22,
                        categoryPercentage: 0.6,
                        barPercentage: 0.85,
                    },
                    {
                        label: 'Salidas',
                        data: @json($cashFlowDashboard['salidasPptoSerie']),
                        backgroundColor: '#DC2626',
                        borderRadius: 4,
                        maxBarThickness: 22,
                        categoryPercentage: 0.6,
                        barPercentage: 0.85,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        display: true, position: 'top', align: 'end',
                        labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, pointStyle: 'rect', font: { size: 11 } },
                    },
                    tooltip: {
                        backgroundColor: '#ffffff',
                        titleColor: '#111827',
                        bodyColor: '#111827',
                        borderColor: '#E5E7EB',
                        borderWidth: 1,
                        padding: 10,
                        titleFont: { weight: '600' },
                        callbacks: {
                            label: (c) => ' ' + c.dataset.label + ': ' + formatCOP(c.parsed.y),
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false }, border: { display: false } },
                    y: {
                        grid: { color: '#F3F4F6' },
                        border: { display: false },
                        ticks: { callback: (v) => formatCOP(v), maxTicksLimit: 5 },
                    },
                },
            },
        });
    }
});
</script>

</x-app-layout>
