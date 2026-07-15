<?php

namespace App\Http\Controllers;

use App\Models\CesantiaSettlement;
use App\Models\Client;
use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CesantiaSettlementController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->user()->cesantiaSettlements()->with('client');

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        $settlements = $query->orderByDesc('year')->paginate(20)->withQueryString();

        $clients = $request->user()->clients()->orderBy('name')->get(['id', 'name']);

        return view('payroll.cesantia-index', compact('settlements', 'clients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'payment_date' => ['required', 'date'],
        ]);

        $client = Client::where('id', $data['client_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        abort_if(
            $request->user()->cesantiaSettlements()
                ->where('client_id', $client->id)
                ->where('year', $data['year'])
                ->exists(),
            422,
            'Ya existe una liquidación de cesantías generada para este cliente y año.'
        );

        $startDate = Carbon::create((int) $data['year'], 1, 1);
        $endDate = Carbon::create((int) $data['year'], 12, 31);

        $settlement = DB::transaction(function () use ($data, $request, $client, $startDate, $endDate) {
            $settlement = $request->user()->cesantiaSettlements()->create([
                'client_id' => $client->id,
                'year' => $data['year'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'payment_date' => $data['payment_date'],
            ]);

            $this->generateItems($settlement, $client);

            return $settlement;
        });

        return redirect()->route('cesantia-settlements.show', $settlement)
            ->with('success', 'Liquidación de cesantías generada.');
    }

    public function show(Request $request, CesantiaSettlement $cesantiaSettlement): View
    {
        abort_if($cesantiaSettlement->user_id !== $request->user()->id, 403);

        $cesantiaSettlement->load(['client', 'items' => function ($q) {
            $q->with('employee')
                ->join('employees', 'employees.id', '=', 'cesantia_settlement_items.employee_id')
                ->orderBy('employees.first_name')
                ->select('cesantia_settlement_items.*');
        }]);

        return view('payroll.cesantia-show', compact('cesantiaSettlement'));
    }

    public function printView(Request $request, CesantiaSettlement $cesantiaSettlement): View
    {
        abort_if($cesantiaSettlement->user_id !== $request->user()->id, 403);

        $cesantiaSettlement = $this->loadForPrint($cesantiaSettlement);

        return view('payroll.cesantia-print', compact('cesantiaSettlement'));
    }

    public function pdf(Request $request, CesantiaSettlement $cesantiaSettlement): Response
    {
        abort_if($cesantiaSettlement->user_id !== $request->user()->id, 403);

        $cesantiaSettlement = $this->loadForPrint($cesantiaSettlement);

        $pdf = Pdf::loadView('payroll.cesantia-pdf', compact('cesantiaSettlement'))->setPaper('letter', 'portrait');

        return $pdf->download("cesantias-{$cesantiaSettlement->client->name}-{$cesantiaSettlement->year}.pdf");
    }

    public function destroy(Request $request, CesantiaSettlement $cesantiaSettlement): RedirectResponse
    {
        abort_if($cesantiaSettlement->user_id !== $request->user()->id, 403);

        $cesantiaSettlement->delete();

        return redirect()->route('cesantia-settlements.index')
            ->with('success', 'Liquidación de cesantías eliminada.');
    }

    private function loadForPrint(CesantiaSettlement $cesantiaSettlement): CesantiaSettlement
    {
        $cesantiaSettlement->load(['client', 'items' => function ($q) {
            $q->with('employee')
                ->join('employees', 'employees.id', '=', 'cesantia_settlement_items.employee_id')
                ->orderBy('employees.first_name')
                ->select('cesantia_settlement_items.*');
        }]);

        return $cesantiaSettlement;
    }

    /**
     * Suma las provisiones de cesantías e intereses ya calculadas mes a mes
     * en `payrolls` (ver PayrollCalculator::calculate) para cada nómina del
     * cliente cuyo período esté totalmente contenido en el año de la
     * liquidación. No recalcula nada: la liquidación anual es exactamente la
     * suma de las provisiones mensuales, según el mismo liquidador de
     * referencia (storage/app/Nómina/8. Liquidador de cesantías.xlsx).
     */
    private function generateItems(CesantiaSettlement $settlement, Client $client): void
    {
        $rows = Payroll::query()
            ->join('payroll_periods', 'payroll_periods.id', '=', 'payrolls.payroll_period_id')
            ->where('payroll_periods.client_id', $client->id)
            ->where('payroll_periods.status', '!=', 'anulada')
            ->where('payrolls.status', '!=', 'anulada')
            ->whereDate('payroll_periods.start_date', '>=', $settlement->start_date)
            ->whereDate('payroll_periods.end_date', '<=', $settlement->end_date)
            ->selectRaw('payrolls.employee_id as employee_id, SUM(payrolls.worked_days) as worked_days, SUM(payrolls.cesantias_provision) as cesantias_value, SUM(payrolls.interest_cesantias_provision) as interest_value')
            ->groupBy('payrolls.employee_id')
            ->get();

        foreach ($rows as $row) {
            $settlement->items()->create([
                'employee_id' => $row->employee_id,
                'worked_days' => $row->worked_days,
                'cesantias_value' => $row->cesantias_value,
                'interest_value' => $row->interest_value,
            ]);
        }
    }
}
