<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Payroll;
use App\Models\PrimaSettlement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PrimaSettlementController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->user()->primaSettlements()->with('client');

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        $settlements = $query->orderByDesc('year')->orderByDesc('semester')->paginate(20)->withQueryString();

        $clients = $request->user()->clients()->orderBy('name')->get(['id', 'name']);

        return view('payroll.prima-index', compact('settlements', 'clients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'semester' => ['required', 'in:1,2'],
            'payment_date' => ['required', 'date'],
        ]);

        $client = Client::where('id', $data['client_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        abort_if(
            $request->user()->primaSettlements()
                ->where('client_id', $client->id)
                ->where('year', $data['year'])
                ->where('semester', $data['semester'])
                ->exists(),
            422,
            'Ya existe una liquidación de prima generada para este cliente y semestre.'
        );

        [$startDate, $endDate] = (int) $data['semester'] === 1
            ? [Carbon::create((int) $data['year'], 1, 1), Carbon::create((int) $data['year'], 6, 30)]
            : [Carbon::create((int) $data['year'], 7, 1), Carbon::create((int) $data['year'], 12, 31)];

        $settlement = DB::transaction(function () use ($data, $request, $client, $startDate, $endDate) {
            $settlement = $request->user()->primaSettlements()->create([
                'client_id' => $client->id,
                'year' => $data['year'],
                'semester' => $data['semester'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'payment_date' => $data['payment_date'],
            ]);

            $this->generateItems($settlement, $client);

            return $settlement;
        });

        return redirect()->route('prima-settlements.show', $settlement)
            ->with('success', 'Liquidación de prima generada.');
    }

    public function show(Request $request, PrimaSettlement $primaSettlement): View
    {
        abort_if($primaSettlement->user_id !== $request->user()->id, 403);

        $primaSettlement->load(['client', 'items' => function ($q) {
            $q->with('employee')
                ->join('employees', 'employees.id', '=', 'prima_settlement_items.employee_id')
                ->orderBy('employees.first_name')
                ->select('prima_settlement_items.*');
        }]);

        return view('payroll.prima-show', compact('primaSettlement'));
    }

    public function printView(Request $request, PrimaSettlement $primaSettlement): View
    {
        abort_if($primaSettlement->user_id !== $request->user()->id, 403);

        $primaSettlement = $this->loadForPrint($primaSettlement);

        return view('payroll.prima-print', compact('primaSettlement'));
    }

    public function pdf(Request $request, PrimaSettlement $primaSettlement): Response
    {
        abort_if($primaSettlement->user_id !== $request->user()->id, 403);

        $primaSettlement = $this->loadForPrint($primaSettlement);

        $pdf = Pdf::loadView('payroll.prima-pdf', compact('primaSettlement'))->setPaper('letter', 'portrait');

        return $pdf->download("prima-{$primaSettlement->client->name}-{$primaSettlement->year}-{$primaSettlement->semester}.pdf");
    }

    public function destroy(Request $request, PrimaSettlement $primaSettlement): RedirectResponse
    {
        abort_if($primaSettlement->user_id !== $request->user()->id, 403);

        $primaSettlement->delete();

        return redirect()->route('prima-settlements.index')
            ->with('success', 'Liquidación de prima eliminada.');
    }

    private function loadForPrint(PrimaSettlement $primaSettlement): PrimaSettlement
    {
        $primaSettlement->load(['client', 'items' => function ($q) {
            $q->with('employee')
                ->join('employees', 'employees.id', '=', 'prima_settlement_items.employee_id')
                ->orderBy('employees.first_name')
                ->select('prima_settlement_items.*');
        }]);

        return $primaSettlement;
    }

    /**
     * Suma las provisiones de prima ya calculadas mes a mes en `payrolls`
     * (ver PayrollCalculator::calculate) para cada nómina del cliente cuyo
     * período esté totalmente contenido en el semestre de la liquidación.
     * No recalcula nada: la liquidación semestral es exactamente la suma de
     * las provisiones mensuales, según el mismo liquidador de referencia
     * (storage/app/Nómina/9. Liquidador de prima.xlsx).
     */
    private function generateItems(PrimaSettlement $settlement, Client $client): void
    {
        $rows = Payroll::query()
            ->join('payroll_periods', 'payroll_periods.id', '=', 'payrolls.payroll_period_id')
            ->where('payroll_periods.client_id', $client->id)
            ->where('payroll_periods.status', '!=', 'anulada')
            ->where('payrolls.status', '!=', 'anulada')
            ->whereDate('payroll_periods.start_date', '>=', $settlement->start_date)
            ->whereDate('payroll_periods.end_date', '<=', $settlement->end_date)
            ->selectRaw('payrolls.employee_id as employee_id, SUM(payrolls.worked_days) as worked_days, SUM(payrolls.prima_provision) as prima_value')
            ->groupBy('payrolls.employee_id')
            ->get();

        foreach ($rows as $row) {
            $settlement->items()->create([
                'employee_id' => $row->employee_id,
                'worked_days' => $row->worked_days,
                'prima_value' => $row->prima_value,
            ]);
        }
    }
}
