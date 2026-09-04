<x-app-layout>
<x-slot name="title">Control de Vacaciones</x-slot>

{{-- FullCalendar v6 --}}
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/es.global.min.js"></script>

<div x-data="vacationCalendarPage()" x-init="init()">

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
    <div class="flex items-start gap-3">
        <a href="{{ route('vacation-control.index', [], false) }}"
           class="w-8 h-8 rounded-[var(--radius-control)] bg-[var(--surface-muted)] hover:bg-[var(--border-default)] text-[var(--text-500)] flex items-center justify-center flex-shrink-0 mt-0.5">
            <x-lucide-chevron-left class="w-4 h-4" />
        </a>
        <div>
            <div class="flex items-center gap-2">
                <p class="text-[22px] font-bold text-[var(--text-900)]">Cronograma de vacaciones</p>
                <x-help-icon title="Cronograma de vacaciones">
                    Los bloques azules son los períodos de vacaciones registrados por empleado. El fondo gris marca los festivos colombianos (calculados automáticamente) — útil para no programar vacaciones que crucen días ya no hábiles.
                </x-help-icon>
            </div>
            <p class="text-[13px] text-[var(--text-400)] mt-0.5">Períodos de todos los empleados del cliente, con los festivos colombianos marcados de fondo</p>
        </div>
    </div>

    <select x-model="clientId" @change="refetch()" class="h-10 px-3.5 border border-[var(--border-default)] rounded-[var(--radius-control)] text-[15px] bg-[var(--surface-card)] text-[var(--text-700)] focus:ring-1 focus:ring-[var(--color-primary-light)] focus:border-[var(--border-default)] outline-none sm:max-w-[240px]">
        @foreach($clients as $c)
        <option value="{{ $c->id }}">{{ $c->name }}</option>
        @endforeach
    </select>
</div>

<div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card)] p-5">
    <div id="fullcalendar"></div>
</div>

</div>

<script>
function vacationCalendarPage() {
    return {
        clientId: '{{ $selectedClientId }}',
        calendar: null,

        init() { this.$nextTick(() => this.mountCalendar()); },

        mountCalendar() {
            const el = document.getElementById('fullcalendar');
            if (!el) return;

            this.calendar = new FullCalendar.Calendar(el, {
                initialView: 'dayGridMonth',
                locale: 'es',
                height: 'auto',
                firstDay: 1,
                buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Día', list: 'Agenda' },
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listMonth' },
                views: { listMonth: { buttonText: 'Agenda' } },
                eventSources: [{
                    url: '{{ route('vacation-control.calendar.events', [], false) }}',
                    method: 'GET',
                    extraParams: () => ({ client_id: this.clientId }),
                    failure: () => console.warn('Error cargando el cronograma de vacaciones'),
                }],
                eventDidMount: (info) => {
                    const p = info.event.extendedProps;
                    if (p.type === 'period') {
                        info.el.title = `${info.event.title} — ${p.businessDays} días hábiles`;
                    } else {
                        info.el.title = info.event.title;
                    }
                },
                dayMaxEvents: 3,
                navLinks: true,
            });
            this.calendar.render();
        },

        refetch() { this.calendar?.refetchEvents(); },
    };
}
</script>
</x-app-layout>
