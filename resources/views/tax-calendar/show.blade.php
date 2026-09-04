<x-app-layout title="Calendario Tributario">

{{-- FullCalendar v6 --}}
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/es.global.min.js"></script>

<div x-data="taxCalendar()" x-init="init()" class="space-y-5">

{{-- ══════════════════════════════════════════════════════════════
     HEADER
══════════════════════════════════════════════════════════════ --}}
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
    <div class="flex items-start gap-3">
        <a href="{{ route('tax-events.index') }}"
           class="w-8 h-8 rounded-[var(--radius-control)] bg-[var(--surface-muted)] hover:bg-[var(--border-default)] text-[var(--text-500)] flex items-center justify-center flex-shrink-0 mt-0.5">
            <x-lucide-chevron-left class="w-4 h-4" />
        </a>
        <div>
            <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $client->name }}</p>
            <p class="text-[12px] text-[var(--text-400)] mt-0.5">
                Calendario Tributario · {{ $client->document_type }} {{ $client->document_number }}@if($client->dv)-{{ $client->dv }}@endif
            </p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 sm:flex-nowrap">
        <button @click="openModal('ica')"
            class="inline-flex items-center gap-[6px] h-10 px-4 rounded-[var(--radius-control)] bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
            <x-lucide-building class="w-4 h-4" />
            ICA Municipal
        </button>
        <button @click="openModal('manual')"
            class="inline-flex items-center gap-[6px] h-10 px-4 text-[var(--color-primary)] text-[14px] font-medium hover:underline">
            <x-lucide-plus class="w-4 h-4" />
            Nueva
        </button>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     KPI CARDS
══════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

    {{-- Pendientes --}}
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Pendientes</p>
            <x-lucide-clock class="w-5 h-5 text-[var(--color-primary)]" />
        </div>
        <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $pending }}</p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">obligaciones activas</p>
    </div>

    {{-- Vencidas --}}
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Vencidas</p>
            <x-lucide-alert-triangle class="w-5 h-5 text-[var(--color-danger)]" />
        </div>
        <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $overdue }}</p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">requieren atención</p>
    </div>

    {{-- Próximos 30d --}}
    <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Próximos 30d</p>
            <x-lucide-calendar class="w-5 h-5" style="color: #EA580C;" />
        </div>
        <p class="text-[22px] font-bold text-[var(--text-900)]">{{ $upcoming }}</p>
        <p class="text-[12px] text-[var(--text-400)] mt-1">vencen este mes</p>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     CALENDARIO + PANEL LATERAL
══════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 xl:grid-cols-[1fr_280px] gap-5">

    {{-- Bloque principal: calendario con leyenda integrada --}}
    <div>
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5">
            <div id="fullcalendar"></div>
        </div>
    </div>

    {{-- Panel lateral: próximos vencimientos --}}
    <div class="space-y-3">

        {{-- Título panel --}}
        <h2 class="text-[16px] font-bold text-[var(--text-900)]">Próximos 30 días</h2>

        @if($upcomingList->isEmpty())
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-6 text-center">
            <div class="w-12 h-12 rounded-[var(--radius-card)] bg-[var(--color-success-bg)] flex items-center justify-center mx-auto mb-3">
                <x-lucide-check-circle class="w-6 h-6 text-[var(--color-success)]" />
            </div>
            <p class="text-[14px] font-semibold text-[var(--text-700)]">Todo al día</p>
            <p class="text-[12px] text-[var(--text-400)] mt-1">Sin vencimientos este mes</p>
        </div>
        @else
        <div class="space-y-2">
            @foreach($upcomingList as $event)
            @php
                $allColors = \App\Models\TaxObligationType::getColorMap();
                $color    = $allColors[$event->obligation_type] ?? '#6B7280';
                $diff     = (int) now()->diffInDays($event->due_date, false);
                $isToday  = $diff === 0;
                $isUrgent = $diff <= 3;
                $label    = $isToday ? 'Hoy' : ($diff === 1 ? 'Mañana' : "en {$diff}d");
            @endphp
            <div class="relative bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)]
                        hover:shadow-[var(--shadow-card-hover)] overflow-hidden cursor-pointer">
                {{-- Borde izquierdo de color --}}
                <div class="absolute left-0 inset-y-0 w-[3px]" style="background-color: {{ $color }}"></div>

                <div class="flex items-start gap-3 pl-4 pr-3.5 py-3">
                    {{-- Chip fecha --}}
                    <div class="flex-shrink-0 text-center w-10">
                        <p class="text-[20px] font-bold text-[var(--text-900)] leading-none">{{ $event->due_date->format('d') }}</p>
                        <p class="text-[10px] text-[var(--text-400)] uppercase font-medium tracking-wide leading-tight mt-0.5">
                            {{ $event->due_date->translatedFormat('M') }}
                        </p>
                    </div>

                    {{-- Separador vertical --}}
                    <div class="w-px self-stretch bg-[var(--border-default)] flex-shrink-0"></div>

                    {{-- Contenido --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-medium text-[var(--text-900)] truncate leading-tight">{{ $event->title }}</p>
                        <p class="text-[11px] text-[var(--text-400)] truncate mt-0.5">{{ $event->period }}</p>
                        <span class="inline-block mt-1.5 px-[10px] py-[3px] rounded-[var(--radius-badge)] text-[11px] font-medium
                            {{ $isToday   ? 'bg-[var(--color-danger-bg)] text-[var(--color-danger-text)]' :
                               ($isUrgent  ? 'bg-[var(--color-warning-bg)] text-[var(--color-warning-text)]' :
                                             'bg-[var(--surface-muted)] text-[var(--text-500)]') }}">
                            {{ $label }}
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     MODAL: DETALLE DEL EVENTO
══════════════════════════════════════════════════════════════ --}}
<div x-show="modal === 'event'" x-cloak
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-gray-900/50">
    <div @click.outside="modal = null"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-md overflow-hidden">

        {{-- Header --}}
        <div class="px-6 pt-5 pb-4 border-b border-[var(--border-default)]">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <p class="text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] mb-1"
                       x-text="selectedEvent?.extendedProps?.obligation_type"></p>
                    <h3 class="text-[16px] font-bold text-[var(--text-900)] leading-tight"
                        x-text="selectedEvent?.title || '—'"></h3>
                    <p class="text-[14px] text-[var(--text-500)] mt-0.5"
                       x-text="selectedEvent?.extendedProps?.period || '—'"></p>
                </div>
                <button @click="modal = null"
                    class="flex-shrink-0 w-7 h-7 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] flex items-center justify-center text-[var(--text-400)] hover:text-[var(--text-700)]">
                    <x-lucide-x class="w-4 h-4" />
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="px-6 py-4 space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-[var(--surface-subtle)] rounded-[var(--radius-control)] p-3">
                    <p class="text-[10px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Estado</p>
                    <span x-text="statusLabel(selectedEvent?.extendedProps?.status)"
                        :class="statusClass(selectedEvent?.extendedProps?.status)"
                        class="inline-block mt-1 px-[10px] py-[3px] rounded-[var(--radius-badge)] text-[12px] font-medium"></span>
                </div>
                <div class="bg-[var(--surface-subtle)] rounded-[var(--radius-control)] p-3">
                    <p class="text-[10px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Origen</p>
                    <p class="text-[14px] font-semibold text-[var(--text-900)] mt-1"
                       x-text="sourceLabel(selectedEvent?.extendedProps?.source)"></p>
                </div>
                <div class="bg-[var(--surface-subtle)] rounded-[var(--radius-control)] p-3 col-span-2">
                    <p class="text-[10px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Alerta configurada</p>
                    <p class="text-[14px] font-semibold text-[var(--text-900)] mt-1"
                       x-text="(selectedEvent?.extendedProps?.alert_days ?? 10) + ' días de anticipación'"></p>
                </div>
            </div>
            <div x-show="selectedEvent?.extendedProps?.notes"
                class="bg-[var(--color-warning-bg)] border border-[#FCD34D] rounded-[var(--radius-control)] p-3">
                <p class="text-[10px] font-medium text-[var(--color-warning-text)] uppercase tracking-[0.06em] mb-1">Nota</p>
                <p class="text-[13px] text-[var(--color-warning-text)]" x-text="selectedEvent?.extendedProps?.notes"></p>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex gap-2 px-6 pb-4">
            <button @click="completeEvent()"
                x-show="selectedEvent?.extendedProps?.status !== 'completed'"
                class="flex-1 h-10 bg-[var(--color-success)] hover:opacity-90 text-white text-[14px] font-medium rounded-[var(--radius-control)]">
                Marcar cumplida
            </button>
            <button @click="deleteEvent()"
                x-show="selectedEvent?.extendedProps?.editable"
                class="flex-1 h-10 border bg-[var(--color-danger-bg)]/50 border-[var(--color-danger)]/30 text-[var(--color-danger)] hover:bg-[var(--color-danger-bg)] text-[14px] font-medium rounded-[var(--radius-control)]">
                Eliminar
            </button>
        </div>
        <p x-show="!selectedEvent?.extendedProps?.editable" class="text-[12px] text-[var(--text-400)] text-center px-6 pb-5 -mt-1">
            Calculada a partir de las obligaciones tributarias del cliente — no se puede eliminar.
        </p>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     MODAL: ICA MUNICIPAL
══════════════════════════════════════════════════════════════ --}}
<div x-show="modal === 'ica'" x-cloak
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-gray-900/50">
    <div @click.outside="modal = null"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-lg max-h-[92vh] flex flex-col overflow-hidden">

        <div class="px-6 py-5 border-b border-[var(--border-default)] flex-shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-[var(--radius-control)] bg-[var(--color-primary-light)] flex items-center justify-center">
                        <x-lucide-building class="w-4 h-4 text-[var(--color-primary)]" />
                    </div>
                    <div>
                        <h3 class="text-[16px] font-bold text-[var(--text-900)]">ICA Municipal</h3>
                        <p class="text-[12px] text-[var(--text-400)]">{{ $client->name }} — Industria y Comercio</p>
                    </div>
                </div>
                <button @click="modal = null"
                    class="w-7 h-7 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] flex items-center justify-center text-[var(--text-400)] hover:text-[var(--text-700)]">
                    <x-lucide-x class="w-4 h-4" />
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
            <div>
                <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">Municipio</label>
                <input type="text" x-model="icaForm.municipality" placeholder="Ej: Bogotá D.C."
                    class="w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] bg-[var(--surface-card)] text-[var(--text-700)]
                           focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none"/>
            </div>

            {{-- Toggle periodicidad --}}
            <div>
                <label class="block text-[13px] font-medium text-[var(--text-700)] mb-2">Periodicidad</label>
                <div class="flex gap-2 p-1 bg-[var(--surface-muted)] rounded-[var(--radius-control)]">
                    <button @click="icaForm.type = 'bimonthly'; buildICADates()"
                        :class="icaForm.type === 'bimonthly'
                            ? 'bg-[var(--surface-card)] text-[var(--color-primary)] shadow-[var(--shadow-card)] font-semibold'
                            : 'text-[var(--text-500)] font-medium hover:text-[var(--text-700)]'"
                        class="flex-1 py-2 text-[14px] rounded-[var(--radius-control)]">
                        Bimestral <span class="text-[10px] opacity-70">(6 pagos)</span>
                    </button>
                    <button @click="icaForm.type = 'annual'; buildICADates()"
                        :class="icaForm.type === 'annual'
                            ? 'bg-[var(--surface-card)] text-[var(--color-primary)] shadow-[var(--shadow-card)] font-semibold'
                            : 'text-[var(--text-500)] font-medium hover:text-[var(--text-700)]'"
                        class="flex-1 py-2 text-[14px] rounded-[var(--radius-control)]">
                        Anual <span class="text-[10px] opacity-70">(1 pago)</span>
                    </button>
                </div>
            </div>

            <template x-if="icaForm.type === 'bimonthly'">
                <div class="space-y-3">
                    <div>
                        <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">
                            1er vencimiento
                            <span class="text-[var(--text-400)] font-normal ml-1">— los otros 5 se calculan solos</span>
                        </label>
                        <input type="date" x-model="icaForm.firstDate" @change="buildICADates()"
                            class="w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] bg-[var(--surface-card)] text-[var(--text-700)]
                                   focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none"/>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="(date, i) in icaForm.dates" :key="i">
                            <div>
                                <label class="block text-[11px] font-medium text-[var(--text-400)] mb-1"
                                       x-text="icaLabels[i]"></label>
                                <input type="date" x-model="icaForm.dates[i]"
                                    class="w-full h-9 border border-[var(--border-default)] rounded-[var(--radius-control)] px-2.5 text-[14px] bg-[var(--surface-card)] text-[var(--text-700)]
                                           focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none"/>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="icaForm.type === 'annual'">
                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">Fecha de vencimiento</label>
                    <input type="date" x-model="icaForm.dates[0]"
                        class="w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] bg-[var(--surface-card)] text-[var(--text-700)]
                               focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none"/>
                </div>
            </template>

            <div>
                <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">Anticipación de alerta</label>
                <select x-model="icaForm.alertDays"
                    class="w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] bg-[var(--surface-card)] text-[var(--text-700)]
                           focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none">
                    <option value="5">5 días antes</option>
                    <option value="10">10 días antes</option>
                    <option value="15">15 días antes</option>
                    <option value="30">30 días antes</option>
                </select>
            </div>

            <div x-show="icaResult" x-transition
                :class="icaResult?.success
                    ? 'bg-[var(--color-success-bg)] border-[var(--color-success)]/30 text-[var(--color-success-text)]'
                    : 'bg-[var(--color-danger-bg)] border-[var(--color-danger)]/30 text-[var(--color-danger-text)]'"
                class="border rounded-[var(--radius-control)] px-4 py-3 text-[14px] font-medium">
                <p x-text="icaResult?.message"></p>
            </div>
        </div>

        <div class="flex gap-2 px-6 py-4 border-t border-[var(--border-default)] flex-shrink-0">
            <button @click="saveICA()"
                :disabled="!icaForm.municipality || icaLoading"
                class="flex-1 h-10 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] disabled:opacity-40
                       text-white text-[14px] font-medium rounded-[var(--radius-control)]
                       flex items-center justify-center gap-2">
                <svg x-show="icaLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="icaLoading ? 'Guardando...' : 'Guardar ICA'"></span>
            </button>
            <button @click="modal = null"
                class="px-5 h-10 bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)]">
                Cancelar
            </button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     MODAL: NUEVA OBLIGACIÓN MANUAL
══════════════════════════════════════════════════════════════ --}}
<div x-show="modal === 'manual'" x-cloak
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-gray-900/50">
    <div @click.outside="modal = null"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-md overflow-hidden">

        {{-- Header --}}
        <div class="px-6 py-5 border-b border-[var(--border-default)]">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-[var(--radius-control)] bg-[var(--color-primary-light)] flex items-center justify-center flex-shrink-0">
                        <x-lucide-plus class="w-4 h-4 text-[var(--color-primary)]" />
                    </div>
                    <div>
                        <h3 class="text-[16px] font-bold text-[var(--text-900)]">Nueva obligación</h3>
                        <p class="text-[12px] text-[var(--text-400)]">{{ $client->name }} — registro manual</p>
                    </div>
                </div>
                <button @click="modal = null"
                    class="w-7 h-7 rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)] flex items-center justify-center text-[var(--text-400)] hover:text-[var(--text-700)]">
                    <x-lucide-x class="w-4 h-4" />
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="px-6 py-5 space-y-4">

            {{-- Tipo + Fecha --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] mb-1.5">Tipo</label>
                    <select x-model="manualForm.obligation_type"
                        class="w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] bg-[var(--surface-card)] text-[var(--text-700)]
                               focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none">
                        @foreach(\App\Models\TaxEvent::OBLIGATION_TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] mb-1.5">Vencimiento</label>
                    <input type="date" x-model="manualForm.due_date"
                        class="w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] bg-[var(--surface-card)] text-[var(--text-700)]
                               focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none"/>
                </div>
            </div>

            {{-- Descripción --}}
            <div>
                <label class="block text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] mb-1.5">Descripción</label>
                <input type="text" x-model="manualForm.title" placeholder="Ej: IVA Ene-Feb 2026"
                    class="w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] bg-[var(--surface-card)] text-[var(--text-700)]
                           focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none"/>
            </div>

            {{-- Período + Alerta --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] mb-1.5">Período</label>
                    <input type="text" x-model="manualForm.period" placeholder="Ej: Ene-Feb 2026"
                        class="w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] bg-[var(--surface-card)] text-[var(--text-700)]
                               focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none"/>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] mb-1.5">Alerta</label>
                    <select x-model="manualForm.alert_days"
                        class="w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] bg-[var(--surface-card)] text-[var(--text-700)]
                               focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none">
                        <option value="5">5 días antes</option>
                        <option value="10">10 días antes</option>
                        <option value="15">15 días antes</option>
                        <option value="30">30 días antes</option>
                    </select>
                </div>
            </div>

            {{-- Notas --}}
            <div>
                <label class="block text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] mb-1.5">Notas <span class="font-normal normal-case text-[var(--text-400)]">(opcional)</span></label>
                <textarea x-model="manualForm.notes" rows="2" placeholder="Observaciones adicionales..."
                    class="w-full border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 py-2.5 text-[14px] bg-[var(--surface-card)] text-[var(--text-700)]
                           focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none resize-none"></textarea>
            </div>

            {{-- Resultado --}}
            <div x-show="manualResult" x-transition
                :class="manualResult?.success
                    ? 'bg-[var(--color-success-bg)] border-[var(--color-success)]/30 text-[var(--color-success-text)]'
                    : 'bg-[var(--color-danger-bg)] border-[var(--color-danger)]/30 text-[var(--color-danger-text)]'"
                class="border rounded-[var(--radius-control)] px-4 py-3 text-[14px] font-medium">
                <p x-text="manualResult?.message"></p>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex gap-2 px-6 pb-5">
            <button @click="saveManual()"
                :disabled="!manualForm.title || !manualForm.due_date || manualLoading"
                class="flex-1 h-10 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] disabled:opacity-40
                       text-white text-[14px] font-medium rounded-[var(--radius-control)]
                       flex items-center justify-center gap-2">
                <svg x-show="manualLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="manualLoading ? 'Guardando...' : 'Guardar obligación'"></span>
            </button>
            <button @click="modal = null"
                class="px-5 h-10 bg-[var(--surface-subtle)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)]">
                Cancelar
            </button>
        </div>
    </div>
</div>

</div>{{-- /x-data --}}

{{-- ══════════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════════ --}}
<script>
function taxCalendar() {
    return {
        modal: null,
        selectedEvent:   null,
        selectedEventId: null,
        calendar:        null,

        icaForm: {
            municipality: '', type: 'bimonthly',
            firstDate: '', dates: ['','','','','',''], alertDays: 10,
        },
        icaLabels:  ['1er Bimestre','2do Bimestre','3er Bimestre','4to Bimestre','5to Bimestre','6to Bimestre'],
        icaLoading: false,
        icaResult:  null,

        manualForm: {
            title: '', obligation_type: 'IVA',
            due_date: '', period: '', alert_days: 10, notes: '',
        },
        manualLoading: false,
        manualResult:  null,

        init() { this.$nextTick(() => this.mountCalendar()); },

        mountCalendar() {
            const el = document.getElementById('fullcalendar');
            if (!el) return;

            this.calendar = new FullCalendar.Calendar(el, {
                initialView:  'dayGridMonth',
                locale:       'es',
                height:       'auto',
                firstDay:     1,
                buttonText:   { today:'Hoy', month:'Mes', week:'Semana', day:'Día', list:'Agenda' },
                headerToolbar: {
                    left:   'prev,next today',
                    center: 'title',
                    right:  'dayGridMonth,timeGridWeek,timeGridDay,listMonth',
                },
                views: { listMonth: { buttonText: 'Agenda' } },
                eventSources: [{
                    url:     '{{ route("tax-events.events", $client) }}',
                    method:  'GET',
                    failure: () => console.warn('Error cargando eventos'),
                }],
                eventClick: (info) => {
                    this.selectedEvent   = info.event;
                    this.selectedEventId = info.event.id;
                    this.modal = 'event';
                },
                eventDidMount: (info) => {
                    const p = info.event.extendedProps;
                    info.el.title = `${info.event.title}\n${p.period || ''}\nEstado: ${p.status}`;
                },
                dayMaxEvents: 3,
                nowIndicator: true,
                navLinks:     true,
            });
            this.calendar.render();
        },

        openModal(type) {
            this.icaResult = this.manualResult = null;
            this.modal = type;
        },

        buildICADates() {
            if (this.icaForm.type === 'annual') { this.icaForm.dates = ['','','','','','']; return; }
            if (!this.icaForm.firstDate) return;
            const d = new Date(this.icaForm.firstDate);
            for (let i = 0; i < 6; i++) {
                const dt = new Date(d.getFullYear(), d.getMonth() + i * 2, d.getDate());
                this.icaForm.dates[i] = dt.toISOString().split('T')[0];
            }
        },

        async saveICA() {
            if (!this.icaForm.municipality) return;
            this.icaLoading = true; this.icaResult = null;
            const dates = this.icaForm.type === 'annual'
                ? [this.icaForm.dates[0]].filter(Boolean)
                : this.icaForm.dates.filter(Boolean);
            try {
                const r = await fetch('{{ route("tax-events.ica", $client) }}', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrfToken(), Accept:'application/json' },
                    body: JSON.stringify({
                        municipality: this.icaForm.municipality,
                        type: this.icaForm.type, dates, alert_days: this.icaForm.alertDays,
                    }),
                });
                this.icaResult = await r.json();
                if (this.icaResult.success) this.calendar?.refetchEvents();
            } catch { this.icaResult = { success:false, message:'Error de conexión.' }; }
            finally  { this.icaLoading = false; }
        },

        async saveManual() {
            this.manualLoading = true; this.manualResult = null;
            try {
                const r = await fetch('{{ route("tax-events.store", $client) }}', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrfToken(), Accept:'application/json' },
                    body: JSON.stringify(this.manualForm),
                });
                this.manualResult = await r.json();
                if (this.manualResult.success) {
                    this.calendar?.refetchEvents();
                    this.manualForm = { title:'', obligation_type:'IVA', due_date:'', period:'', alert_days:10, notes:'' };
                }
            } catch { this.manualResult = { success:false, message:'Error de conexión.' }; }
            finally  { this.manualLoading = false; }
        },

        async completeEvent() {
            if (!this.selectedEventId || !this.selectedEvent) return;
            const props = this.selectedEvent.extendedProps;

            if (props.editable) {
                await fetch(`/tax-events/${this.selectedEventId}/complete`, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN':csrfToken(), Accept:'application/json' },
                }).catch(() => {});
            } else {
                // Ocurrencia calculada: no tiene fila propia todavía, se identifica por
                // obligation_type + fecha (ver TaxCalendarController::completeOccurrence()).
                await fetch('{{ route("tax-events.complete-occurrence", $client) }}', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrfToken(), Accept:'application/json' },
                    body: JSON.stringify({
                        obligation_type: props.obligation_type,
                        due_date: this.selectedEvent.startStr,
                        title: this.selectedEvent.title,
                    }),
                }).catch(() => {});
            }
            this.modal = null;
            this.calendar?.refetchEvents();
        },

        async deleteEvent() {
            if (!confirm('¿Eliminar esta obligación?')) return;
            await fetch(`/tax-events/${this.selectedEventId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN':csrfToken(), Accept:'application/json' },
            }).catch(() => {});
            this.modal = null;
            this.calendar?.refetchEvents();
        },

        statusLabel: s => ({ pending:'Pendiente', completed:'Cumplida', overdue:'Vencida' }[s] || s),
        statusClass:  s => ({
            pending:   'bg-[var(--surface-muted)] text-[var(--text-700)]',
            completed: 'bg-[var(--color-success-bg)] text-[var(--color-success-text)]',
            overdue:   'bg-[var(--color-danger-bg)] text-[var(--color-danger-text)]',
        }[s] || 'bg-[var(--surface-muted)] text-[var(--text-500)]'),
        sourceLabel:  s => ({ dian:'Vencimientos administrativos', auto:'Vencimientos administrativos', ica:'ICA municipal', manual:'Manual', calculado:'Calculado por obligaciones del cliente' }[s] || s),
    };
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}
</script>

{{-- ══════════════════════════════════════════════════════════════
     CSS — FullCalendar, alineado a los tokens del sistema
══════════════════════════════════════════════════════════════ --}}
<style>
/* ─── Scrollgrid: quitar bordes externos ─── */
.fc .fc-scrollgrid          { border: none !important; }
.fc .fc-scrollgrid-section > * { border-left: none !important; border-right: none !important; }
.fc-theme-standard td,
.fc-theme-standard th       { border-color: var(--surface-muted) !important; }

/* ─── Toolbar ─── */
.fc .fc-toolbar               { padding-bottom: 1rem; border-bottom: 1px solid var(--border-default); margin-bottom: 1rem; }
.fc .fc-toolbar-title         { font-size: 16px; font-weight: 600; color: var(--text-900); letter-spacing: -0.01em; }

/* ─── Botones ─── */
.fc .fc-button-group          { gap: 3px; }
.fc .fc-button                {
    background: var(--surface-subtle) !important;
    border: 1px solid var(--border-default) !important;
    color: var(--text-700) !important;
    font-size: 12px !important;
    font-weight: 500 !important;
    border-radius: var(--radius-control) !important;
    padding: 5px 11px !important;
    box-shadow: none !important;
    transition: all 120ms ease !important;
}
.fc .fc-button:hover          { background: var(--color-primary-light) !important; border-color: var(--color-primary) !important; color: var(--color-primary) !important; }
.fc .fc-button-active,
.fc .fc-button-primary:not(:disabled).fc-button-active {
    background: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
    color: #fff !important;
    box-shadow: none !important;
}
.fc .fc-today-button          {
    background: var(--color-primary-light) !important; border-color: var(--border-default) !important;
    color: var(--color-primary) !important; font-weight: 500 !important;
}
.fc .fc-prev-button,
.fc .fc-next-button           { padding: 5px 9px !important; }

/* ─── Cabecera días ─── */
.fc .fc-col-header-cell       { background: var(--surface-subtle); border-bottom: 1px solid var(--border-default) !important; }
.fc .fc-col-header-cell-cushion {
    color: var(--text-400); font-size: 11px; font-weight: 500;
    text-transform: uppercase; letter-spacing: 0.06em; padding: 10px 6px;
}

/* ─── Números de día ─── */
.fc .fc-daygrid-day-number    { color: var(--text-400); font-size: 12px; font-weight: 500; padding: 6px 8px; }
.fc .fc-day-today             { background: var(--color-primary-light) !important; }
.fc .fc-day-today .fc-daygrid-day-number {
    background: var(--color-primary); color: #fff;
    width: 24px; height: 24px; border-radius: 999px;
    display: flex; align-items: center; justify-content: center;
    margin: 4px; font-weight: 600; font-size: 12px; padding: 0;
}

/* ─── Eventos ─── */
.fc-event                     {
    border-radius: 4px !important;
    font-size: 11px !important;
    padding: 2px 6px !important;
    cursor: pointer;
    border: none !important;
    font-weight: 500 !important;
    box-shadow: none !important;
    transition: opacity 120ms ease !important;
    margin-bottom: 2px !important;
}
.fc-event:hover               { opacity: .85 !important; }
.fc .fc-daygrid-more-link     { color: var(--text-500); font-size: 11px; font-weight: 500; margin-top: 1px; }

/* ─── Vista lista ─── */
.fc .fc-list-table td         { padding: 9px 14px; }
.fc .fc-list-day-cushion      { background: var(--surface-subtle); }
.fc .fc-list-day-text,
.fc .fc-list-day-side-text    { font-size: 13px; font-weight: 600; color: var(--text-700); }
.fc .fc-list-event:hover td   { background: var(--surface-subtle) !important; }
.fc .fc-list-event-dot        { border-radius: 50%; }

/* ─── Vista semana/día ─── */
.fc .fc-timegrid-slot         { height: 36px !important; }
.fc .fc-timegrid-axis-cushion { font-size: 11px; color: var(--text-400); font-weight: 500; }

/* Alpine cloak */
[x-cloak]                     { display: none !important; }
</style>

</x-app-layout>
