<x-app-layout>
    <x-slot name="title">Calendario Tributario</x-slot>

    <div class="max-w-7xl mx-auto">

        {{-- Header --}}
        <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] p-6 mb-6">
            <div class="flex items-start justify-between flex-wrap gap-4">
                <div>
                    <div class="flex items-center gap-2 text-[14px] text-[var(--text-500)] mb-1">
                        <a href="{{ route('clients.show', $client) }}" class="hover:text-[var(--color-primary)]">{{ $client->name }}</a>
                        <span>/</span>
                        <span>Calendario Tributario {{ $year }}</span>
                    </div>
                    <h1 class="text-[22px] font-semibold text-[var(--text-900)]">Calendario Tributario</h1>
                    <p class="text-[14px] text-[var(--text-500)] mt-0.5">
                        NIT: {{ $client->document_number }}-{{ $client->dv }} ·
                        {{ $client->tax_regime === 'regimen_simple' ? 'Régimen SIMPLE' : 'Régimen Ordinario' }}
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('tax-events.export-pdf', [$client, 'year' => $year]) }}"
                       class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                        <x-lucide-download class="w-4 h-4" />
                        Exportar PDF
                    </a>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        @php $selectClass = 'h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3 text-[14px] bg-[var(--surface-card)] text-[var(--text-700)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)]'; @endphp
        <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] p-4 mb-6">
            <form method="GET" class="flex items-end gap-3 flex-wrap">
                <div>
                    <label class="block text-[12px] text-[var(--text-400)] mb-1">Año</label>
                    <select name="year" onchange="this.form.submit()" class="{{ $selectClass }}">
                        @foreach ($years as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[12px] text-[var(--text-400)] mb-1">Obligación</label>
                    <select name="obligation" onchange="this.form.submit()" class="{{ $selectClass }}">
                        <option value="">Todas</option>
                        @foreach ($obligationCodes as $code => $name)
                            <option value="{{ $code }}" {{ $filterCode === $code ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[12px] text-[var(--text-400)] mb-1">Mes</label>
                    <select name="month" onchange="this.form.submit()" class="{{ $selectClass }}">
                        <option value="">Todos</option>
                        @foreach ($months as $num => $name)
                            <option value="{{ $num }}" {{ $filterMonth == $num ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[12px] text-[var(--text-400)] mb-1">Estado</label>
                    <select name="status" onchange="this.form.submit()" class="{{ $selectClass }}">
                        <option value="">Todos</option>
                        <option value="pendiente" {{ $filterStatus === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="proximo"   {{ $filterStatus === 'proximo'   ? 'selected' : '' }}>Próximo (≤15 días)</option>
                        <option value="vencido"   {{ $filterStatus === 'vencido'   ? 'selected' : '' }}>Vencido</option>
                    </select>
                </div>
                @if ($filterCode || $filterMonth || $filterStatus)
                    <a href="{{ route('tax-events.client-calendar', $client) }}"
                       class="h-10 flex items-center text-[13px] text-[var(--color-primary)] hover:underline px-3">Limpiar filtros</a>
                @endif
            </form>
        </div>

        {{-- Tabla de eventos --}}
        @if (empty($events))
            <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-12 text-center">
                <x-lucide-calendar-check class="w-12 h-12 text-[var(--text-400)] mx-auto mb-3" />
                <p class="text-[var(--text-700)] font-semibold">No hay obligaciones para los filtros aplicados.</p>
                <p class="text-[var(--text-400)] text-[14px] mt-1">
                    Verifique las responsabilidades tributarias del cliente y el calendario configurado
                    en el panel administrativo.
                </p>
            </div>
        @else
            {{-- KPIs rápidos --}}
            @php
                $total    = count($events);
                $pending  = count(array_filter($events, fn($e) => $e['status'] === 'pendiente'));
                $upcoming = count(array_filter($events, fn($e) => $e['status'] === 'proximo'));
                $overdue  = count(array_filter($events, fn($e) => $e['status'] === 'vencido'));
            @endphp
            <div class="grid grid-cols-4 gap-4 mb-6">
                @foreach ([['Total', $total, 'text-[var(--text-900)]'],['Pendientes', $pending, 'text-[var(--text-700)]'],['Próximas', $upcoming, 'text-[var(--color-warning)]'],['Vencidas', $overdue, 'text-[var(--color-danger)]']] as [$lbl, $val, $cls])
                    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] px-5 py-4">
                        <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] mb-1">{{ $lbl }}</p>
                        <p class="text-[22px] font-bold {{ $cls }}">{{ $val }}</p>
                    </div>
                @endforeach
            </div>

            <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-[var(--border-default)]">
                            <th class="text-left px-6 py-3 text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Obligación</th>
                            <th class="text-left px-6 py-3 text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Período</th>
                            <th class="text-left px-6 py-3 text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Vencimiento</th>
                            <th class="text-left px-6 py-3 text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Días</th>
                            <th class="text-left px-6 py-3 text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr class="border-b border-[var(--surface-muted)] hover:bg-[var(--surface-subtle)]">
                                <td class="px-6 py-[14px]">
                                    <p class="font-medium text-[14px] text-[var(--text-900)]">{{ $event['name'] }}</p>
                                    <p class="text-[12px] text-[var(--text-400)] font-mono">{{ $event['code'] }}</p>
                                </td>
                                <td class="px-6 py-[14px] text-[14px] text-[var(--text-700)]">{{ $event['period_label'] }}</td>
                                <td class="px-6 py-[14px]">
                                    <span class="font-mono text-[14px] {{ $event['status'] === 'vencido' ? 'text-[var(--color-danger)]' : 'text-[var(--text-700)]' }}">
                                        {{ \Illuminate\Support\Carbon::parse($event['due_date'])->format('d/m/Y') }}
                                    </span>
                                </td>
                                <td class="px-6 py-[14px]">
                                    @php $days = $event['days_left']; @endphp
                                    @if ($days < 0)
                                        <span class="text-[14px] text-[var(--color-danger)] font-medium">{{ abs($days) }}d vencida</span>
                                    @elseif ($days === 0)
                                        <span class="text-[14px] font-bold" style="color: #EA580C;">Hoy</span>
                                    @else
                                        <span class="text-[14px] {{ $days <= 15 ? 'font-medium text-[var(--color-warning)]' : 'text-[var(--text-500)]' }}">{{ $days }}d</span>
                                    @endif
                                </td>
                                <td class="px-6 py-[14px]">
                                    @php
                                        $variants = ['pendiente' => 'neutral', 'proximo' => 'warning', 'vencido' => 'danger'];
                                        $labels = ['pendiente'=>'Pendiente','proximo'=>'Próxima','vencido'=>'Vencida'];
                                    @endphp
                                    <x-status-badge :variant="$variants[$event['status']] ?? 'neutral'">
                                        {{ $labels[$event['status']] ?? $event['status'] }}
                                    </x-status-badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</x-app-layout>
