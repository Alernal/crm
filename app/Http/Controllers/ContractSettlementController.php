<?php

namespace App\Http\Controllers;

use App\Models\ContractSettlement;
use App\Models\Employee;
use App\Models\PayrollLegalSetting;
use App\Services\Payroll\ContractSettlementCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ContractSettlementController extends Controller
{
    /** Contratos de aprendizaje quedan fuera: sus reglas (apoyo de sostenimiento, sin cesantías ni prima) no están en el Excel de referencia. */
    private const EXCLUDED_CONTRACT_TYPES = ['aprendizaje'];

    public function index(Request $request): View
    {
        $query = $request->user()->contractSettlements()->with(['client', 'employee']);

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        $settlements = $query->orderByDesc('contract_end_date')->orderByDesc('id')->paginate(20)->withQueryString();

        $clients = $request->user()->clients()->orderBy('name')->get(['id', 'name']);

        return view('payroll.contract-settlement-index', compact('settlements', 'clients'));
    }

    public function create(Request $request): View
    {
        $employee = $this->authorizedEmployee($request, (int) $request->get('employee_id'));

        $settings = PayrollLegalSetting::forDate($employee->termination_date);
        $defaults = $this->defaultsFor($employee, $settings);

        return view('payroll.contract-settlement-wizard', compact('employee', 'settings', 'defaults'));
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->authorizedEmployee($request, (int) $request->input('employee_id'));

        $data = $request->validate([
            'smlv' => ['required', 'numeric', 'min:0'],
            'last_salary' => ['required', 'numeric', 'min:0'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'transport_allowance_input' => ['required', 'numeric', 'min:0'],
            'worked_days_month' => ['required', 'numeric', 'min:0', 'max:30'],
            'indemnification_applies' => ['required', 'boolean'],
            'contract_type' => ['required', 'in:indefinido,fijo,obra_labor'],
            'year_start_date' => ['required', 'date'],
            'vacation_period_start' => ['required', 'date'],
            'prima_period_start' => ['required', 'date'],
            'contract_end_date' => ['required', 'date'],
            'contract_reference_date' => ['required', 'date'],
            'overtime_value' => ['nullable', 'numeric', 'min:0'],
            'recargos_value' => ['nullable', 'numeric', 'min:0'],
            'commissions' => ['nullable', 'numeric', 'min:0'],
            'bonuses_salarial' => ['nullable', 'numeric', 'min:0'],
            'per_diem_salarial' => ['nullable', 'numeric', 'min:0'],
            'other_salarial' => ['nullable', 'numeric', 'min:0'],
            'occasional_bonuses' => ['nullable', 'numeric', 'min:0'],
            'extralegal_premiums' => ['nullable', 'numeric', 'min:0'],
            'per_diem_no_salarial' => ['nullable', 'numeric', 'min:0'],
            'other_no_salarial' => ['nullable', 'numeric', 'min:0'],
            'withholding_tax' => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach (['year_start_date', 'vacation_period_start', 'prima_period_start', 'contract_end_date', 'contract_reference_date'] as $field) {
            $data[$field] = Carbon::parse($data[$field]);
        }

        $result = (new ContractSettlementCalculator())->calculate($employee, $data);

        $settlement = $request->user()->contractSettlements()->create(array_merge($result, [
            'client_id' => $employee->client_id,
            'employee_id' => $employee->id,
        ]));

        return redirect()->route('contract-settlements.show', $settlement)
            ->with('success', 'Liquidación de contrato generada.');
    }

    public function show(Request $request, ContractSettlement $contractSettlement): View
    {
        abort_if($contractSettlement->user_id !== $request->user()->id, 403);

        $contractSettlement->load(['client', 'employee']);

        return view('payroll.contract-settlement-show', compact('contractSettlement'));
    }

    public function printView(Request $request, ContractSettlement $contractSettlement): View
    {
        abort_if($contractSettlement->user_id !== $request->user()->id, 403);

        $contractSettlement->load(['client', 'employee']);

        return view('payroll.contract-settlement-print', compact('contractSettlement'));
    }

    public function pdf(Request $request, ContractSettlement $contractSettlement): Response
    {
        abort_if($contractSettlement->user_id !== $request->user()->id, 403);

        $contractSettlement->load(['client', 'employee']);

        $pdf = Pdf::loadView('payroll.contract-settlement-pdf', compact('contractSettlement'))->setPaper('letter', 'portrait');

        return $pdf->download("liquidacion-contrato-{$contractSettlement->employee->full_name}.pdf");
    }

    public function destroy(Request $request, ContractSettlement $contractSettlement): RedirectResponse
    {
        abort_if($contractSettlement->user_id !== $request->user()->id, 403);

        $contractSettlement->delete();

        return redirect()->route('contract-settlements.index')
            ->with('success', 'Liquidación de contrato eliminada.');
    }

    private function authorizedEmployee(Request $request, int $employeeId): Employee
    {
        $employee = Employee::where('id', $employeeId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        abort_if(
            in_array($employee->contract_type, self::EXCLUDED_CONTRACT_TYPES, true),
            422,
            'Los contratos de aprendizaje no se liquidan con este formato — sus reglas propias (apoyo de sostenimiento, sin cesantías ni prima) no están cubiertas todavía.'
        );

        abort_if(
            blank($employee->termination_date) || blank($employee->termination_reason),
            422,
            'Antes de liquidar el contrato, registra la fecha y el motivo de retiro en la ficha del empleado.'
        );

        return $employee;
    }

    /**
     * Valores sugeridos para el wizard, todos editables. Cesantías y prima
     * parten del inicio del año/semestre en curso (o de la fecha de ingreso
     * si el empleado entró después); vacaciones no tiene una liquidación
     * previa registrada en el sistema, así que se sugiere la fecha de
     * ingreso a falta de mejor dato.
     */
    private function defaultsFor(Employee $employee, PayrollLegalSetting $settings): array
    {
        $end = $employee->termination_date;
        $hire = $employee->hire_date;

        $yearStart = Carbon::create($end->year, 1, 1);
        $semesterStart = $end->month <= 6
            ? Carbon::create($end->year, 1, 1)
            : Carbon::create($end->year, 7, 1);

        $baseSalary = (float) $employee->base_salary;
        $transportEligible = match ($employee->transport_allowance_mode) {
            'siempre' => true,
            'nunca' => false,
            default => $baseSalary <= ((float) $settings->smlv * 2),
        };

        $workedDaysMonth = $end->day === $end->daysInMonth ? 30 : min($end->day, 30);

        return [
            'smlv' => (float) $settings->smlv,
            'last_salary' => $baseSalary,
            'basic_salary' => $baseSalary,
            'transport_allowance_input' => $transportEligible ? (float) $settings->transport_allowance : 0.0,
            'worked_days_month' => $workedDaysMonth,
            'indemnification_applies' => $employee->termination_reason === 'despido_sin_justa_causa',
            'contract_type' => $employee->contract_type,
            'year_start_date' => $hire && $hire->gt($yearStart) ? $hire : $yearStart,
            'vacation_period_start' => $hire ?? $yearStart,
            'prima_period_start' => $hire && $hire->gt($semesterStart) ? $hire : $semesterStart,
            'contract_end_date' => $end,
            'contract_reference_date' => in_array($employee->contract_type, ['indefinido', 'obra_labor'], true) ? $hire : null,
        ];
    }
}
