<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\TaxEvent;
use App\Models\TaxObligationType;
use App\Services\TaxCalendarService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TaxCalendarController extends Controller
{
    public function index()
    {
        $user    = Auth::user();
        $clients = $user->clients()->where('status', 'active')->orderBy('name')->get();

        $service     = new TaxCalendarService();
        $currentYear = (int) date('Y');
        $horizonYear = (int) now()->addDays(30)->format('Y');

        // Combina manual + calculado por cliente (mismo criterio que allCalendarItems()) —
        // antes solo se contaban las filas TaxEvent manuales, así que un cliente con
        // obligaciones calculadas vencidas mostraba "sin vencimientos" en este listado.
        $eventsByClient = $clients->mapWithKeys(function ($client) use ($service, $currentYear, $horizonYear) {
            $items = $service->combinedEvents($client, $currentYear);
            if ($horizonYear !== $currentYear) {
                $items = $items->concat($service->combinedEvents($client, $horizonYear));
            }

            return [$client->id => $items->unique('id')];
        });

        return view('tax-calendar.index', compact('clients', 'eventsByClient'));
    }

    public function show(Client $client)
    {
        $this->authorizeClient($client);

        $items = $this->allCalendarItems($client, (int) date('Y'))
            ->merge($this->allCalendarItems($client, (int) now()->addDays(30)->format('Y')))
            ->unique('id');

        $pending  = $items->where('status', 'pending')->count();
        $overdue  = $items->where('status', 'overdue')->count();
        $upcoming = $items->where('status', 'pending')
            ->whereBetween('due_date', [now(), now()->addDays(30)])
            ->count();

        $upcomingList = $items->where('status', 'pending')
            ->whereBetween('due_date', [now(), now()->addDays(30)])
            ->sortBy('due_date')
            ->take(10)
            ->map(fn (array $e) => (object) $e)
            ->values();

        return view('tax-calendar.show', compact(
            'client', 'pending', 'overdue', 'upcoming', 'upcomingList'
        ));
    }

    public function events(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        $start = $request->query('start') ? Carbon::parse($request->query('start')) : now()->startOfMonth();
        $end   = $request->query('end')   ? Carbon::parse($request->query('end'))   : now()->endOfMonth();

        $items  = $this->allCalendarItems($client, $start->year)
            ->when($end->year !== $start->year, fn ($c) => $c->merge($this->allCalendarItems($client, $end->year)))
            ->filter(fn ($e) => $e['due_date']->between($start, $end));

        $colors = TaxObligationType::getColorMap();

        return response()->json($items->map(function (array $e) use ($colors) {
            $color = $colors[$e['obligation_type']] ?? '#6B7280';

            if ($e['status'] === 'completed') {
                $color = '#9CA3AF';
            } elseif ($e['status'] === 'overdue') {
                $color = '#DC2626';
            }

            return [
                'id'              => $e['id'],
                'title'           => $e['title'],
                'start'           => $e['due_date']->toDateString(),
                'allDay'          => true,
                'backgroundColor' => $color,
                'borderColor'     => $color,
                'textColor'       => '#FFFFFF',
                'extendedProps'   => [
                    'obligation_type' => $e['obligation_type'],
                    'period'          => $e['period'],
                    'status'          => $e['status'],
                    'source'          => $e['source'],
                    'notes'           => $e['notes'],
                    'alert_days'      => $e['alert_days'],
                    'editable'        => $e['editable'],
                ],
            ];
        })->values());
    }

    /**
     * Une las obligaciones manuales (TaxEvent: manual/ICA) con las calculadas
     * automáticamente a partir de los impuestos que el cliente tiene marcados
     * (TaxObligationType + TaxDueDate, vía TaxCalendarService).
     */
    private function allCalendarItems(Client $client, int $year): Collection
    {
        return (new TaxCalendarService())->combinedEvents($client, $year);
    }

    /**
     * Obligaciones manuales (TaxEvent: manual/ica/dian — nunca 'calculado') del cliente para
     * un año, en el mismo formato de array que generateClientCalendar() para que
     * clientCalendar() pueda mostrarlas en la misma tabla que las calculadas — antes esa
     * vista solo llamaba a generateClientCalendar() y por eso obligaciones puramente
     * manuales como ICA (sin TaxDueDate propio) nunca aparecían ahí, aunque sí las contara
     * la campana de notificaciones (desincronización que originó este fix).
     */
    private function manualEventsForYear(Client $client, int $year): array
    {
        return $client->taxEvents()
            ->whereYear('due_date', $year)
            ->where('source', '!=', 'calculado')
            ->get()
            ->map(function (TaxEvent $e) {
                $status = $e->status === 'completed'
                    ? 'completado'
                    : (($e->status === 'overdue' || $e->due_date->isPast())
                        ? 'vencido'
                        : ($e->due_date->diffInDays(now()) <= 15 ? 'proximo' : 'pendiente'));

                return [
                    'id'           => $e->id,
                    'code'         => $e->obligation_type,
                    'name'         => $e->title,
                    'period_label' => $e->period ?: $e->title,
                    'due_date'     => $e->due_date->toDateString(),
                    'periodicity'  => null,
                    'regime'       => null,
                    'status'       => $status,
                    'days_left'    => (int) now()->diffInDays($e->due_date, false),
                    'editable'     => true,
                    'source'       => $e->source,
                ];
            })
            ->all();
    }

    /**
     * Mapa obligation_type|due_date => true de las ocurrencias CALCULADAS que el usuario ya
     * marcó como cumplidas para este cliente — usado por clientCalendar() para que la vista
     * de calendario anual muestre "Cumplida" en vez de "Vencida" una vez gestionada.
     */
    private function completedCalculatedKeys(Client $client): array
    {
        return $client->taxEvents()
            ->where('source', 'calculado')
            ->where('status', 'completed')
            ->get(['obligation_type', 'due_date'])
            ->map(fn (TaxEvent $e) => $e->obligation_type.'|'.$e->due_date->toDateString())
            ->flip()
            ->map(fn () => true)
            ->all();
    }

    /**
     * Sobreescribe status='vencido' → 'completado' en la lista de $events (formato
     * TaxCalendarService) para las ocurrencias que el cliente ya marcó como cumplidas.
     * Usado por clientCalendar()/exportPdf() — el mismo criterio de reconciliación que
     * allCalendarItems() aplica para el calendario mensual de show().
     */
    private function applyCompletions(array $events, Client $client): array
    {
        $completed = $this->completedCalculatedKeys($client);

        return array_map(function (array $e) use ($completed) {
            if (isset($completed[$e['code'].'|'.$e['due_date']])) {
                $e['status'] = 'completado';
            }

            return $e;
        }, $events);
    }

    public function storeICA(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        $data = $request->validate([
            'municipality' => 'required|string|max:100',
            'type'         => 'required|in:bimonthly,annual',
            'dates'        => 'required|array|min:1|max:6',
            'dates.*'      => 'required|date',
            'alert_days'   => 'integer|min:1|max:90',
        ]);

        $alertDays    = $data['alert_days'] ?? 10;
        $periodLabels = ['1er Bim', '2do Bim', '3er Bim', '4to Bim', '5to Bim', '6to Bim'];
        $now          = now()->toDateTimeString();
        $toInsert     = [];

        foreach ($data['dates'] as $index => $date) {
            $label  = $data['type'] === 'annual'
                ? "ICA {$data['municipality']} Anual 2026"
                : "ICA {$data['municipality']} " . ($periodLabels[$index] ?? ($index + 1) . '° Bim') . ' 2026';

            $period = $data['type'] === 'annual'
                ? "ICA Anual 2026 — {$data['municipality']}"
                : 'ICA ' . ($periodLabels[$index] ?? ($index + 1) . '° Bimestre') . " 2026 — {$data['municipality']}";

            $toInsert[] = [
                'user_id'         => Auth::id(),
                'client_id'       => $client->id,
                'title'           => $label,
                'obligation_type' => 'ICA',
                'source'          => 'ica',
                'due_date'        => $date,
                'period'          => $period,
                'alert_days'      => $alertDays,
                'status'          => 'pending',
                'notes'           => "ICA municipio: {$data['municipality']}",
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        TaxEvent::insert($toInsert);

        return response()->json([
            'success' => true,
            'message' => "Se crearon " . count($toInsert) . " vencimiento(s) de ICA para {$client->name} en {$data['municipality']}.",
            'created' => count($toInsert),
        ]);
    }

    public function store(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        $data = $request->validate([
            'title'           => 'required|string|max:200',
            'obligation_type' => 'required|in:' . self::VALID_OBLIGATION_TYPES,
            'due_date'        => 'required|date',
            'period'          => 'nullable|string|max:100',
            'alert_days'      => 'integer|min:1|max:90',
            'notes'           => 'nullable|string|max:500',
        ]);

        $event = TaxEvent::create(array_merge($data, [
            'user_id'    => Auth::id(),
            'client_id'  => $client->id,
            'source'     => 'manual',
            'status'     => 'pending',
            'alert_days' => $data['alert_days'] ?? 10,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Obligación creada correctamente.',
            'event'   => $event,
        ]);
    }

    public function update(Request $request, TaxEvent $taxEvent)
    {
        abort_unless($taxEvent->user_id === Auth::id(), 403);

        $data = $request->validate([
            'title'           => 'required|string|max:200',
            'obligation_type' => 'required|in:' . self::VALID_OBLIGATION_TYPES,
            'due_date'        => 'required|date',
            'period'          => 'nullable|string|max:100',
            'alert_days'      => 'integer|min:1|max:90',
            'notes'           => 'nullable|string|max:500',
        ]);

        $taxEvent->update($data);

        return response()->json(['success' => true, 'message' => 'Obligación actualizada.']);
    }

    public function complete(Request $request, TaxEvent $taxEvent)
    {
        abort_unless($taxEvent->user_id === Auth::id(), 403);

        $taxEvent->update(['status' => 'completed']);

        // El modal de show.blade.php llama este endpoint por fetch (Accept: application/json);
        // el formulario plano de client-calendar.blade.php (obligaciones manuales, ej. ICA)
        // es una navegación normal de navegador — sin esta rama volvía a una página JSON en
        // blanco en vez de refrescar la tabla (mismo bug ya corregido antes en completeOccurrence()).
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Marcada como cumplida.']);
        }

        return back()->with('status', 'Obligación marcada como cumplida.');
    }

    /**
     * Marca como cumplida una ocurrencia CALCULADA (TaxCalendarService) — a diferencia de
     * complete(), que actúa sobre una fila TaxEvent que ya existe, aquí la fila no existe
     * todavía: se identifica la ocurrencia por obligation_type+due_date (no hay id) y se crea
     * o reutiliza la fila-marca (source=calculado) que allCalendarItems()/clientCalendar()
     * usan para sobreescribir el estado "vencido" calculado al vuelo.
     */
    public function completeOccurrence(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        $data = $request->validate([
            'obligation_type' => 'required|string|max:30',
            'due_date'        => 'required|date',
            'title'           => 'nullable|string|max:200',
        ]);

        // No se usa firstOrNew() con un array de atributos: el cast 'date' de due_date
        // persiste la columna como datetime completo ('Y-m-d H:i:s'), así que comparar
        // contra el string plano 'Y-m-d' recibido del form nunca hace match y crea una
        // fila duplicada en cada clic — whereDate() compara solo la parte de fecha.
        $event = TaxEvent::where('user_id', Auth::id())
            ->where('client_id', $client->id)
            ->where('obligation_type', $data['obligation_type'])
            ->where('source', 'calculado')
            ->whereDate('due_date', $data['due_date'])
            ->first() ?? new TaxEvent([
                'user_id'         => Auth::id(),
                'client_id'       => $client->id,
                'obligation_type' => $data['obligation_type'],
                'due_date'        => $data['due_date'],
                'source'          => 'calculado',
            ]);

        $event->title      = $data['title'] ?? $data['obligation_type'];
        $event->period     = $event->period ?? null;
        $event->alert_days = $event->alert_days ?? 10;
        $event->status     = 'completed';
        $event->save();

        // El modal de show.blade.php llama este endpoint por fetch (Accept: application/json);
        // el formulario plano de client-calendar.blade.php es una navegación normal de
        // navegador — sin esta rama volvía a la pantalla como una página JSON en blanco
        // en vez de refrescar la tabla.
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Marcada como cumplida.']);
        }

        return back()->with('status', 'Obligación marcada como cumplida.');
    }

    public function destroy(TaxEvent $taxEvent)
    {
        abort_unless($taxEvent->user_id === Auth::id(), 403);

        $taxEvent->delete();

        return response()->json(['success' => true, 'message' => 'Obligación eliminada.']);
    }

    // ─── Vista calendario por cliente ────────────────────────────────────────

    public function clientCalendar(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        $year        = $request->integer('year', date('Y'));
        $filterCode  = $request->get('obligation');
        $filterMonth = $request->get('month');
        $filterStatus= $request->get('status');

        $service = new TaxCalendarService();
        $manual  = $this->manualEventsForYear($client, $year);
        $events  = array_merge(
            $this->applyCompletions($service->generateClientCalendar($client->id, $year), $client),
            $manual
        );
        usort($events, fn($a, $b) => strcmp($a['due_date'], $b['due_date']));

        if ($filterCode) {
            $events = array_filter($events, fn($e) => $e['code'] === $filterCode);
        }
        if ($filterMonth) {
            $events = array_filter($events, fn($e) => Carbon::parse($e['due_date'])->month == $filterMonth);
        }
        if ($filterStatus) {
            $events = array_filter($events, fn($e) => $e['status'] === $filterStatus);
        }

        $events = array_values($events);
        $years  = range(date('Y') - 1, date('Y') + 1);
        $months = ['1'=>'Enero','2'=>'Febrero','3'=>'Marzo','4'=>'Abril','5'=>'Mayo','6'=>'Junio',
                   '7'=>'Julio','8'=>'Agosto','9'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];

        $obligationCodes = collect($service->generateClientCalendar($client->id, $year))
            ->concat($manual)
            ->pluck('name', 'code')->unique()->toArray();

        return view('tax-calendar.client-calendar', compact(
            'client', 'events', 'year', 'years', 'months',
            'filterCode', 'filterMonth', 'filterStatus', 'obligationCodes'
        ));
    }

    public function exportPdf(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        $year    = $request->integer('year', date('Y'));
        $service = new TaxCalendarService();
        $events  = $this->applyCompletions($service->generateClientCalendar($client->id, $year), $client);

        $pdf = Pdf::loadView('tax-calendar.client-calendar-pdf', compact('client', 'events', 'year'));

        return $pdf->download("calendario-{$client->name}-{$year}.pdf");
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private function authorizeClient(Client $client): void
    {
        abort_unless($client->user_id === Auth::id(), 403);
    }

    private function sectionToObligationType(string $section): string
    {
        return match (true) {
            $section === 'iva_anual'                  => 'IVA_anual',
            str_starts_with($section, 'iva')          => 'IVA',
            str_starts_with($section, 'retefuente')   => 'Retefuente',
            str_starts_with($section, 'renta')        => 'Renta',
            str_starts_with($section, 'impoconsumo')  => 'INC',
            str_starts_with($section, 'patrimonio')   => 'Patrimonio',
            str_starts_with($section, 'exogena')      => 'Exogena',
            $section === 'simple_bimestral'            => 'SIMPLE_anticipo',
            str_starts_with($section, 'simple_anual') => 'SIMPLE_anual',
            default                                    => 'Otro',
        };
    }

    private const VALID_OBLIGATION_TYPES = 'IVA,IVA_anual,Retefuente,Renta,ICA,Patrimonio,Activos_Exterior,INC,Exogena,SIMPLE_anticipo,SIMPLE_anual,Otro,IVA_BI,IVA_C4,RTEFTE,RENTA_NAT,RENTA_JUR,EXOGENA,SIMPLE_ANT,SIMPLE_IVA,SIMPLE_DEC';
}
