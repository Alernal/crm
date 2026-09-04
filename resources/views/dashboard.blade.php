<x-app-layout>
<x-slot name="title">Dashboard</x-slot>

{{-- ── Header ── --}}
<div class="flex items-center justify-end mb-6 gap-4 flex-wrap">
    <a href="{{ route('invoices.create') }}"
       class="inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">
        <x-lucide-plus class="w-4 h-4" />
        Nueva cuenta
    </a>
</div>

{{-- ===== KPI CARDS ===== --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    {{-- Cartera por cobrar (destacada) --}}
    <div class="rounded-[var(--radius-card)] p-5 text-white" style="background: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%);">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-white/75 uppercase tracking-[0.06em]">Por cobrar</p>
            <x-lucide-wallet class="w-5 h-5 text-white/60" />
        </div>
        <p class="text-[26px] font-bold text-white">${{ number_format($receivable, 0, ',', '.') }}</p>
        <p class="text-[12px] text-white/75 mt-1">Cartera pendiente de cobro</p>
    </div>

    {{-- Cobrado este mes + tendencia --}}
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-5 shadow-[var(--shadow-card)]">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Cobrado mes</p>
            <x-lucide-check-circle class="w-5 h-5 text-[var(--color-success)]" />
        </div>
        <p class="text-[22px] font-bold text-[var(--text-900)]">${{ number_format($collectedThisMonth, 0, ',', '.') }}</p>
        @if($collectedTrendPct === null)
        <p class="text-[12px] text-[var(--text-400)] mt-1">Pagos recibidos este mes</p>
        @else
        <p class="text-[12px] mt-1 flex items-center gap-1 {{ $collectedTrendPct >= 0 ? 'text-[var(--color-success)]' : 'text-[var(--color-danger)]' }}">
            @if($collectedTrendPct >= 0)
                <x-lucide-trending-up class="w-3.5 h-3.5" />
            @else
                <x-lucide-trending-down class="w-3.5 h-3.5" />
            @endif
            {{ number_format(abs($collectedTrendPct), 0) }}% vs. mes anterior
        </p>
        @endif
    </div>

    {{-- % Cartera vencida --}}
    @php
        $overdueSeverity = $overdueRatio >= 30 ? 'danger' : ($overdueRatio >= 10 ? 'warning' : 'success');
        $overdueColorVar = ['danger' => 'var(--color-danger)', 'warning' => 'var(--color-warning)', 'success' => 'var(--color-success)'][$overdueSeverity];
    @endphp
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-5 shadow-[var(--shadow-card)]">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Cartera vencida</p>
            <x-lucide-alert-triangle class="w-5 h-5" style="color: {{ $overdueColorVar }}" />
        </div>
        <p class="text-[22px] font-bold" style="color: {{ $overdueRatio > 0 ? $overdueColorVar : 'var(--text-900)' }}">{{ number_format($overdueRatio, 0) }}%</p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">Del total por cobrar</p>
    </div>

    {{-- Días promedio de cobro --}}
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] p-5 shadow-[var(--shadow-card)]">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Días de cobro</p>
            <x-lucide-clock class="w-5 h-5 text-[var(--color-primary)]" />
        </div>
        <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $avgCollectionDays !== null ? $avgCollectionDays : '—' }}</p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">Promedio emisión → pago</p>
    </div>

</div>

{{-- ===== GRÁFICOS ===== --}}
<div class="grid grid-cols-1 xl:grid-cols-5 gap-5 mb-5">

    {{-- Cartera por antigüedad (dona) — 2/5 --}}
    <div class="xl:col-span-2 bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-6">
        <h2 class="text-[16px] font-bold text-[var(--text-900)] mb-1">Cartera por antigüedad</h2>
        <p class="text-[12px] text-[var(--text-400)] mb-4">Saldo pendiente según días de vencimiento</p>

        @if($receivable <= 0)
        <div class="flex flex-col items-center justify-center py-10 text-center">
            <div class="w-14 h-14 rounded-[var(--radius-card)] bg-[var(--surface-muted)] flex items-center justify-center mb-3">
                <x-lucide-pie-chart class="w-7 h-7 text-[var(--text-400)]" />
            </div>
            <p class="text-[13px] text-[var(--text-500)]">Sin cartera pendiente por analizar</p>
        </div>
        @else
        <div class="relative mx-auto" style="max-width: 210px; height: 210px;">
            <canvas id="agingChart" role="img" aria-label="Distribución de la cartera por antigüedad"></canvas>
            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                <p class="text-[10px] font-medium text-[var(--text-400)] uppercase tracking-[0.05em]">Total</p>
                <p class="text-[16px] font-bold text-[var(--text-900)] leading-tight">${{ number_format($receivable, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="mt-5 space-y-2.5" id="agingLegend">
            @php
                $agingLabels = [
                    'al_dia'      => ['Al día',        '#059669'],
                    'dias_1_30'   => ['1–30 días',      '#D97706'],
                    'dias_31_60'  => ['31–60 días',     '#EC835A'],
                    'dias_mas60'  => ['+60 días',       '#DC2626'],
                ];
            @endphp
            @foreach($agingLabels as $key => [$label, $color])
            @php $value = $aging[$key]; $pct = $receivable > 0 ? ($value / $receivable) * 100 : 0; @endphp
            <div class="flex items-center gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background: {{ $color }}"></span>
                <span class="text-[13px] text-[var(--text-700)] flex-1">{{ $label }}</span>
                <span class="text-[13px] font-semibold text-[var(--text-900)]">${{ number_format($value, 0, ',', '.') }}</span>
                <span class="text-[12px] text-[var(--text-400)] w-10 text-right">{{ number_format($pct, 0) }}%</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Facturado vs. cobrado (barras) — 3/5 --}}
    <div class="xl:col-span-3 bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-[16px] font-bold text-[var(--text-900)]">Facturado vs. cobrado</h2>
                <p class="text-[12px] text-[var(--text-400)]">Últimos 6 meses</p>
            </div>
            <div class="flex items-center gap-4 text-[12px]">
                <span class="flex items-center gap-1.5 text-[var(--text-500)]">
                    <span class="w-2.5 h-2.5 rounded-[3px]" style="background: #2563EB"></span> Facturado
                </span>
                <span class="flex items-center gap-1.5 text-[var(--text-500)]">
                    <span class="w-2.5 h-2.5 rounded-[3px]" style="background: #059669"></span> Cobrado
                </span>
            </div>
        </div>
        <div style="height: 260px;">
            <canvas id="trendChart" role="img" aria-label="Facturado versus cobrado en los últimos 6 meses"></canvas>
        </div>
    </div>

</div>

{{-- ===== CUERPO: dos columnas ===== --}}
<div class="grid grid-cols-1 xl:grid-cols-5 gap-5">

    {{-- Cuentas de cobro recientes (3/5) --}}
    <div class="xl:col-span-3 bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)]">
        <div class="px-6 py-5 flex items-center justify-between">
            <h2 class="text-[17px] font-bold text-[var(--text-900)]">Cuentas de cobro recientes</h2>
            <a href="{{ Route::has('invoices.index') ? route('invoices.index') : '#' }}"
               class="text-[14px] text-[var(--color-primary)] hover:underline font-medium">Ver todas →</a>
        </div>

        @if($recentInvoices->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-16 h-16 rounded-[var(--radius-card)] bg-[var(--surface-muted)] flex items-center justify-center mb-4">
                <x-lucide-file-text class="w-8 h-8 text-[var(--text-400)]" />
            </div>
            <p class="text-[15px] font-semibold text-[var(--text-700)]">Aún no hay cuentas de cobro</p>
            <p class="text-[13px] text-[var(--text-400)] mt-1">Crea tu primera cuenta para comenzar</p>
            <a href="{{ Route::has('invoices.create') ? route('invoices.create') : '#' }}"
               class="mt-4 inline-flex items-center gap-[6px] h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium">
                <x-lucide-plus class="w-4 h-4" />
                Crear primera cuenta
            </a>
        </div>
        @else
        <div class="overflow-x-auto p-3">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="bg-[var(--surface-card)] border-b border-[var(--border-default)] text-[13px] font-bold text-[var(--text-900)] px-6 py-3.5 text-left">N°</th>
                        <th class="bg-[var(--surface-card)] border-b border-[var(--border-default)] text-[13px] font-bold text-[var(--text-900)] px-6 py-3.5 text-left">Cliente</th>
                        <th class="bg-[var(--surface-card)] border-b border-[var(--border-default)] text-[13px] font-bold text-[var(--text-900)] px-6 py-3.5 text-right">Total</th>
                        <th class="bg-[var(--surface-card)] border-b border-[var(--border-default)] text-[13px] font-bold text-[var(--text-900)] px-6 py-3.5 text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentInvoices as $invoice)
                    <tr class="border-b border-[var(--surface-muted)] border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">
                        <td class="px-6 py-[14px] text-[14px] text-[var(--text-500)]">{{ $invoice->number }}</td>
                        <td class="px-6 py-[14px] text-[14px] text-[var(--text-500)]">{{ $invoice->client->name }}</td>
                        <td class="px-6 py-[14px] text-right text-[14px] text-[var(--text-500)] tabular-nums">
                            ${{ number_format($invoice->total, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-[14px] text-center">
                            @php
                                $badge = match($invoice->status) {
                                    'draft'     => ['text' => 'Borrador',  'variant' => 'neutral'],
                                    'sent'      => ['text' => 'Enviada',   'variant' => 'info'],
                                    'paid'      => ['text' => 'Pagada',    'variant' => 'success'],
                                    'overdue'   => ['text' => 'Vencida',   'variant' => 'danger'],
                                    'cancelled' => ['text' => 'Anulada',   'variant' => 'neutral'],
                                    default     => ['text' => $invoice->status, 'variant' => 'neutral'],
                                };
                            @endphp
                            <x-status-badge :variant="$badge['variant']">{{ $badge['text'] }}</x-status-badge>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Próximos vencimientos tributarios (2/5) --}}
    <div class="xl:col-span-2 bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)]">
        <div class="px-6 py-5 flex items-center justify-between">
            <h2 class="text-[16px] font-bold text-[var(--text-900)]">Próximos vencimientos</h2>
            <a href="{{ Route::has('tax-events.index') ? route('tax-events.index') : '#' }}"
               class="text-[13px] text-[var(--color-primary)] hover:underline font-medium">Ver calendario →</a>
        </div>

        @if($nextTaxEvents->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-16 h-16 rounded-[var(--radius-card)] bg-[var(--surface-muted)] flex items-center justify-center mb-4">
                <x-lucide-calendar-check class="w-8 h-8 text-[var(--text-400)]" />
            </div>
            <p class="text-[14px] font-semibold text-[var(--text-700)]">Sin vencimientos próximos</p>
            <p class="text-[12px] text-[var(--text-400)] mt-1">Todo al día por ahora</p>
        </div>
        @else
        <div class="p-3">
            @foreach($nextTaxEvents as $event)
            @php
                $daysLeft = now()->startOfDay()->diffInDays($event->due_date->startOfDay(), false);
                $urgent   = $daysLeft <= 5;
                $warning  = !$urgent && $daysLeft <= 10;
                $variant  = $daysLeft < 0 ? 'danger' : ($urgent ? 'danger' : ($warning ? 'warning' : 'info'));
                $badgeBg  = match($variant) {
                    'danger'  => 'bg-[var(--color-danger-bg)]',
                    'warning' => 'bg-[var(--color-warning-bg)]',
                    default   => 'bg-[var(--color-primary-light)]',
                };
                $badgeIcon = match($variant) {
                    'danger'  => 'text-[var(--color-danger)]',
                    'warning' => 'text-[var(--color-warning)]',
                    default   => 'text-[var(--color-primary)]',
                };
            @endphp
            <a href="{{ Route::has('tax-events.client-calendar') ? route('tax-events.client-calendar', $event->client) : '#' }}"
               class="flex items-center gap-3 px-6 py-[14px] border-b border-[var(--border-default)] last:border-b-0 border-l-[3px] border-l-transparent hover:border-l-[var(--color-primary)] hover:bg-[var(--surface-subtle)]">
                <div class="w-9 h-9 rounded-full {{ $badgeBg }} flex items-center justify-center flex-shrink-0">
                    <x-lucide-calendar-clock class="w-4 h-4 {{ $badgeIcon }}" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[13.5px] font-medium text-[var(--text-900)] truncate">{{ $event->title }}</p>
                    <p class="text-[12px] text-[var(--text-400)] truncate">{{ $event->client->name }}</p>
                </div>
                <div class="text-right flex-shrink-0 flex flex-col items-end gap-1">
                    <x-status-badge :variant="$variant">
                        @if($daysLeft < 0) Vencido
                        @elseif($daysLeft === 0) Hoy
                        @elseif($daysLeft === 1) Mañana
                        @else {{ $daysLeft }}d
                        @endif
                    </x-status-badge>
                    <p class="text-[11px] text-[var(--text-400)]">{{ $event->due_date->format('d/m/Y') }}</p>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>

</div>

@if($receivable > 0)
<script>
document.addEventListener('DOMContentLoaded', function () {
    function formatCOP(value) {
        return '$ ' + Math.round(value).toLocaleString('es-CO');
    }

    const inkSecondary = '#6B7280';
    const gridColor = '#F3F4F6';

    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.color = inkSecondary;

    const agingCtx = document.getElementById('agingChart');
    if (agingCtx) {
        new Chart(agingCtx, {
            type: 'doughnut',
            data: {
                labels: @json(array_values(array_map(fn($v) => $v[0], $agingLabels))),
                datasets: [{
                    data: @json(array_values($aging)),
                    backgroundColor: @json(array_values(array_map(fn($v) => $v[1], $agingLabels))),
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
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
                            label: (ctx) => ' ' + formatCOP(ctx.parsed),
                        },
                    },
                },
            },
        });
    }

    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: @json(collect($monthlyTrend)->pluck('label')),
                datasets: [
                    {
                        label: 'Facturado',
                        data: @json(collect($monthlyTrend)->pluck('invoiced')),
                        backgroundColor: '#2563EB',
                        borderRadius: 4,
                        maxBarThickness: 22,
                        categoryPercentage: 0.6,
                        barPercentage: 0.85,
                    },
                    {
                        label: 'Cobrado',
                        data: @json(collect($monthlyTrend)->pluck('collected')),
                        backgroundColor: '#059669',
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
                            label: (ctx) => ' ' + ctx.dataset.label + ': ' + formatCOP(ctx.parsed.y),
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        border: { display: false },
                        ticks: {
                            callback: (value) => formatCOP(value),
                            maxTicksLimit: 5,
                        },
                    },
                },
            },
        });
    }
});
</script>
@endif

</x-app-layout>
