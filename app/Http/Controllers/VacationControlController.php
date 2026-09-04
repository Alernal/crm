<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeVacationPeriod;
use App\Services\Payroll\ColombianHolidayCalculator;
use App\Services\Payroll\VacationBalanceCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class VacationControlController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->user()->employees()->where('status', 'active')->with(['client', 'vacationPeriods']);

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        $employees = $query->orderBy('first_name')->get();

        $calculator = new VacationBalanceCalculator();
        $rows = $employees->map(fn (Employee $employee) => [
            'employee' => $employee,
            'balance' => $calculator->calculate($employee, $employee->vacationPeriods),
        ]);

        $clients = $request->user()->clients()->orderBy('name')->get(['id', 'name']);

        return view('payroll.vacation-control-index', compact('rows', 'clients'));
    }

    public function show(Request $request, Employee $employee): View
    {
        $this->authorizeEmployee($request, $employee);

        $employee->load('client');
        $periods = $employee->vacationPeriods()->orderByDesc('start_date')->get();
        $balance = (new VacationBalanceCalculator())->calculate($employee, $periods);

        return view('payroll.vacation-control-show', compact('employee', 'periods', 'balance'));
    }

    public function calendar(Request $request): View
    {
        $clients = $request->user()->clients()->orderBy('name')->get(['id', 'name']);
        $selectedClientId = (int) ($request->get('client_id') ?: $clients->first()?->id);

        return view('payroll.vacation-control-calendar', compact('clients', 'selectedClientId'));
    }

    public function calendarEvents(Request $request): JsonResponse
    {
        $client = Client::where('id', (int) $request->get('client_id'))
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $start = $request->query('start') ? Carbon::parse($request->query('start')) : now()->startOfMonth();
        $end = $request->query('end') ? Carbon::parse($request->query('end')) : now()->endOfMonth();

        $periods = EmployeeVacationPeriod::where('client_id', $client->id)
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->with('employee:id,first_name,last_name')
            ->get();

        $events = $periods->map(fn (EmployeeVacationPeriod $p) => [
            'id' => 'period-'.$p->id,
            'title' => $p->employee->full_name,
            'start' => $p->start_date->format('Y-m-d'),
            'end' => $p->end_date->copy()->addDay()->format('Y-m-d'),
            'color' => '#2563EB',
            'extendedProps' => [
                'businessDays' => (float) $p->business_days,
                'employeeId' => $p->employee_id,
                'type' => 'period',
            ],
        ]);

        $holidayCalculator = new ColombianHolidayCalculator();
        $holidayEvents = collect();
        foreach (range($start->year, $end->year) as $year) {
            foreach ($holidayCalculator->holidaysForYear($year) as $holiday) {
                if ($holiday['date']->between($start->copy()->subMonth(), $end->copy()->addMonth())) {
                    $holidayEvents->push([
                        'id' => 'holiday-'.$holiday['date']->format('Y-m-d'),
                        'title' => $holiday['name'],
                        'start' => $holiday['date']->format('Y-m-d'),
                        'display' => 'background',
                        'color' => '#F3F4F6',
                        'extendedProps' => ['type' => 'holiday'],
                    ]);
                }
            }
        }

        return response()->json($events->merge($holidayEvents)->values());
    }

    public function suggestBusinessDays(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $days = (new VacationBalanceCalculator())->suggestedBusinessDays(
            Carbon::parse($data['start_date']),
            Carbon::parse($data['end_date']),
        );

        return response()->json(['business_days' => $days]);
    }

    public function updateOpeningBalance(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);

        $data = $request->validate([
            'vacation_opening_balance_days' => ['required', 'numeric', 'min:0'],
            'vacation_opening_balance_date' => ['required', 'date'],
        ]);

        $employee->update($data);

        return redirect()->route('vacation-control.show', $employee)->with('success', 'Saldo inicial actualizado.');
    }

    public function storePeriod(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'business_days' => ['required', 'numeric', 'min:0.5'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $employee->vacationPeriods()->create(array_merge($data, [
            'user_id' => $request->user()->id,
            'client_id' => $employee->client_id,
        ]));

        return redirect()->route('vacation-control.show', $employee)->with('success', 'Período de vacaciones registrado.');
    }

    public function updatePeriod(Request $request, EmployeeVacationPeriod $period): RedirectResponse
    {
        abort_if($period->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'business_days' => ['required', 'numeric', 'min:0.5'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $period->update($data);

        return redirect()->route('vacation-control.show', $period->employee_id)->with('success', 'Período de vacaciones actualizado.');
    }

    public function destroyPeriod(Request $request, EmployeeVacationPeriod $period): RedirectResponse
    {
        abort_if($period->user_id !== $request->user()->id, 403);

        $employeeId = $period->employee_id;
        $period->delete();

        return redirect()->route('vacation-control.show', $employeeId)->with('success', 'Período de vacaciones eliminado.');
    }

    public function printView(Request $request, Employee $employee): View
    {
        $this->authorizeEmployee($request, $employee);

        [$employee, $periods, $balance] = $this->loadForPrint($employee);

        return view('payroll.vacation-control-print', compact('employee', 'periods', 'balance'));
    }

    public function pdf(Request $request, Employee $employee): Response
    {
        $this->authorizeEmployee($request, $employee);

        [$employee, $periods, $balance] = $this->loadForPrint($employee);

        $pdf = Pdf::loadView('payroll.vacation-control-pdf', compact('employee', 'periods', 'balance'))->setPaper('letter', 'portrait');

        return $pdf->download("control-vacaciones-{$employee->full_name}.pdf");
    }

    private function authorizeEmployee(Request $request, Employee $employee): void
    {
        abort_if($employee->user_id !== $request->user()->id, 403);
    }

    private function loadForPrint(Employee $employee): array
    {
        $employee->load('client');
        $periods = $employee->vacationPeriods()->orderByDesc('start_date')->get();
        $balance = (new VacationBalanceCalculator())->calculate($employee, $periods);

        return [$employee, $periods, $balance];
    }
}
