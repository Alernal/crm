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

        $eventsByClient = $user->taxEvents()
            ->select('client_id', 'status', 'due_date')
            ->get()
            ->groupBy('client_id');

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
        $manual = collect($client->taxEvents()->whereYear('due_date', $year)->get())
            ->map(fn (TaxEvent $e) => [
                'id'              => $e->id,
                'title'           => $e->title,
                'due_date'        => $e->due_date,
                'obligation_type' => $e->obligation_type,
                'period'          => $e->period,
                'status'          => $e->status === 'completed' ? 'completed' : ($e->status === 'overdue' || ($e->status === 'pending' && $e->due_date->isPast()) ? 'overdue' : 'pending'),
                'source'          => $e->source,
                'notes'           => $e->notes,
                'alert_days'      => $e->alert_days,
                'editable'        => true,
            ]);

        $calculated = collect((new TaxCalendarService())->generateClientCalendar($client->id, $year))
            ->values()
            ->map(fn (array $e, int $i) => [
                'id'              => "calc-{$e['code']}-{$year}-{$i}",
                'title'           => $e['name'] . ' — ' . $e['period_label'],
                'due_date'        => Carbon::parse($e['due_date']),
                'obligation_type' => $e['code'],
                'period'          => $e['period_label'],
                'status'          => $e['status'] === 'vencido' ? 'overdue' : 'pending',
                'source'          => 'calculado',
                'notes'           => null,
                'alert_days'      => 15,
                'editable'        => false,
            ]);

        return $manual->concat($calculated);
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

    public function complete(TaxEvent $taxEvent)
    {
        abort_unless($taxEvent->user_id === Auth::id(), 403);

        $taxEvent->update(['status' => 'completed']);

        return response()->json(['success' => true, 'message' => 'Marcada como cumplida.']);
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
        $events  = $service->generateClientCalendar($client->id, $year);

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
        $events  = $service->generateClientCalendar($client->id, $year);

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
