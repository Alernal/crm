<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollLegalSetting;
use App\Models\PayrollOvertimeItem;
use App\Models\PayrollPeriod;
use App\Services\Payroll\OvertimeCalculator;
use App\Services\Payroll\PayrollCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayrollPeriodController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->user()->payrollPeriods()->with('client');

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $periods = $query->orderByDesc('start_date')->orderByDesc('id')->paginate(20)->withQueryString();

        $clients = $request->user()->clients()->orderBy('name')->get(['id', 'name', 'payroll_periodicity', 'payroll_prefix']);

        $periodsForCopy = $request->user()->payrollPeriods()
            ->orderByDesc('start_date')
            ->get(['id', 'client_id', 'number', 'start_date', 'end_date']);

        return view('payroll.index', compact('periods', 'clients', 'periodsForCopy'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'payment_date' => ['required', 'date'],
            'copy_from_period_id' => ['nullable', 'exists:payroll_periods,id'],
        ]);

        $period = DB::transaction(function () use ($data, $request) {
            $client = Client::where('id', $data['client_id'])
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                blank($client->payroll_periodicity),
                422,
                'Configura la periodicidad de pago de nómina del cliente antes de generar un período.'
            );

            $sourcePeriod = null;
            if (! empty($data['copy_from_period_id'])) {
                $sourcePeriod = PayrollPeriod::where('id', $data['copy_from_period_id'])
                    ->where('user_id', $request->user()->id)
                    ->where('client_id', $client->id)
                    ->first();

                abort_if($sourcePeriod === null, 422, 'El período de origen seleccionado no es válido para este cliente.');
            }

            $number = $this->nextNumberForClient($client);

            $period = $request->user()->payrollPeriods()->create([
                'client_id' => $client->id,
                'number' => $number,
                'period_type' => $client->payroll_periodicity,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'payment_date' => $data['payment_date'],
                'status' => 'borrador',
            ]);

            $this->generatePayrolls($period, $client, $request->user()->id, $sourcePeriod);

            return $period;
        });

        return redirect()->route('payroll-periods.show', $period)
            ->with('success', "Período de nómina {$period->number} generado.");
    }

    public function show(Request $request, PayrollPeriod $payrollPeriod): View
    {
        abort_if($payrollPeriod->user_id !== $request->user()->id, 403);

        $payrollPeriod->load(['client', 'payrolls' => function ($q) {
            $q->with(['employee', 'overtimeItems'])
                ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
                ->orderBy('employees.first_name')
                ->select('payrolls.*');
        }]);

        $settings = PayrollLegalSetting::forDate($payrollPeriod->start_date);
        $overtimeCalculator = new OvertimeCalculator();

        $overtimeRatesByPayroll = [];
        foreach ($payrollPeriod->payrolls as $payroll) {
            $rates = [];
            foreach (PayrollOvertimeItem::TYPES as $type => $label) {
                $ordinary = $overtimeCalculator->ordinaryHourlyRate($payroll->employee, $settings);
                $rates[$type] = round($overtimeCalculator->rateForType($type, $ordinary, $settings), 2);
            }
            $overtimeRatesByPayroll[$payroll->id] = $rates;
        }

        return view('payroll.show', compact('payrollPeriod', 'overtimeRatesByPayroll'));
    }

    public function printView(Request $request, PayrollPeriod $payrollPeriod): View
    {
        abort_if($payrollPeriod->user_id !== $request->user()->id, 403);

        [$payrollPeriod, $devengoFields, $descuentoFields] = $this->loadForSummaryPrint($payrollPeriod);
        $user = $request->user();

        return view('payroll.period-print', compact('payrollPeriod', 'devengoFields', 'descuentoFields', 'user'));
    }

    public function pdf(Request $request, PayrollPeriod $payrollPeriod): Response
    {
        abort_if($payrollPeriod->user_id !== $request->user()->id, 403);

        [$payrollPeriod, $devengoFields, $descuentoFields] = $this->loadForSummaryPrint($payrollPeriod);
        $user = $request->user();

        $pdf = Pdf::loadView('payroll.period-pdf', compact('payrollPeriod', 'devengoFields', 'descuentoFields', 'user'))
            ->setPaper('letter', 'landscape');

        return $pdf->download("nomina-{$payrollPeriod->number}.pdf");
    }

    /**
     * Carga el período con sus nóminas y calcula qué columnas de devengo/
     * descuento tienen al menos un valor distinto de cero en el período,
     * para no imprimir columnas vacías. Excluye deliberadamente seguridad
     * social y prestaciones sociales (a pedido del usuario, alargan
     * demasiado la tabla y no son el foco de este resumen).
     *
     * @return array{0: PayrollPeriod, 1: array<string,string>, 2: array<string,string>}
     */
    private function loadForSummaryPrint(PayrollPeriod $payrollPeriod): array
    {
        $payrollPeriod->load(['client', 'payrolls' => function ($q) {
            $q->with('employee')
                ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
                ->orderBy('employees.first_name')
                ->select('payrolls.*');
        }]);

        $devengoFields = collect(Payroll::PRINT_DEVENGO_FIELDS)
            ->filter(fn ($label, $field) => $payrollPeriod->payrolls->sum($field) > 0)
            ->all();

        $descuentoFields = collect(Payroll::PRINT_DESCUENTO_FIELDS)
            ->filter(fn ($label, $field) => $payrollPeriod->payrolls->sum($field) > 0)
            ->all();

        return [$payrollPeriod, $devengoFields, $descuentoFields];
    }

    public function close(Request $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        abort_if($payrollPeriod->user_id !== $request->user()->id, 403);
        abort_if($payrollPeriod->status !== 'borrador', 422, 'Este período ya fue procesado.');

        DB::transaction(function () use ($payrollPeriod) {
            $payrollPeriod->update(['status' => 'procesada']);
            $payrollPeriod->payrolls()->update(['status' => 'emitida']);
        });

        return redirect()->route('payroll-periods.show', $payrollPeriod)
            ->with('success', 'Período de nómina procesado. Ya puedes descargar y enviar los desprendibles.');
    }

    public function destroy(Request $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        abort_if($payrollPeriod->user_id !== $request->user()->id, 403);
        abort_if(
            ! in_array($payrollPeriod->status, ['borrador', 'procesada'], true),
            422,
            'Solo se pueden eliminar períodos en borrador o procesados.'
        );

        $payrollPeriod->delete();

        return redirect()->route('payroll-periods.index')
            ->with('success', 'Período de nómina eliminado.');
    }

    /**
     * @param  PayrollPeriod|null  $sourcePeriod  Si se indica, cada nómina del
     *                                            período nuevo copia los
     *                                            conceptos manuales (comisiones,
     *                                            bonificaciones, descuentos) y
     *                                            las horas extra del mismo
     *                                            trabajador en ese período,
     *                                            como punto de partida editable.
     *                                            Los días trabajados y toda la
     *                                            seguridad social/provisiones
     *                                            se calculan siempre de cero
     *                                            para el período nuevo.
     */
    private function generatePayrolls(PayrollPeriod $period, Client $client, int $userId, ?PayrollPeriod $sourcePeriod = null): void
    {
        $calculator = new PayrollCalculator();
        $overtimeCalculator = new OvertimeCalculator();
        $settings = PayrollLegalSetting::forDate($period->start_date);

        $employees = $client->employees()->where('status', 'active')->get();

        foreach ($employees as $employee) {
            /** @var Employee $employee */
            if ($employee->hire_date && $employee->hire_date->gt($period->end_date)) {
                // El trabajador aún no había ingresado en este período.
                continue;
            }

            if ($employee->termination_date && $employee->termination_date->lt($period->start_date)) {
                // El trabajador ya se había retirado antes de este período.
                continue;
            }

            $workedDays = $this->workedDaysForEmployee($employee, $period);

            $sourcePayroll = $sourcePeriod
                ? Payroll::with('overtimeItems')
                    ->where('payroll_period_id', $sourcePeriod->id)
                    ->where('employee_id', $employee->id)
                    ->first()
                : null;

            $manual = $sourcePayroll ? $sourcePayroll->only([
                'commissions', 'bonuses_salarial', 'per_diem_salarial', 'other_salarial',
                'occasional_bonuses', 'extralegal_premiums', 'per_diem_no_salarial', 'other_no_salarial',
                'loans_deduction', 'withholding_tax', 'other_deductions',
            ]) : [];

            $overtimeLines = [];
            $overtimeByType = [];
            foreach ($sourcePayroll?->overtimeItems ?? [] as $item) {
                $line = $overtimeCalculator->lineTotal($item->type, (float) $item->hours, $employee, $settings);
                $overtimeLines[] = ['type' => $item->type, 'hours' => $item->hours, 'hourly_rate' => $line['hourly_rate'], 'total' => $line['total']];
                $overtimeByType[$item->type] = ($overtimeByType[$item->type] ?? 0) + $line['total'];
            }

            $result = $calculator->calculate($employee, $client, $period, $workedDays, $manual, $overtimeByType);

            $payroll = Payroll::create(array_merge($result, [
                'payroll_period_id' => $period->id,
                'employee_id' => $employee->id,
                'user_id' => $userId,
                'client_id' => $client->id,
                'status' => 'borrador',
            ]));

            foreach ($overtimeLines as $line) {
                $payroll->overtimeItems()->create($line);
            }
        }
    }

    /**
     * Días a liquidar en el período según la fecha de ingreso y de retiro del
     * trabajador. Si ingresó antes del inicio del período (o no se ha
     * retirado antes del fin), se liquida el período completo (15 o 30 según
     * periodicidad). Si el ingreso o el retiro caen dentro del período, los
     * días se cuentan bajo la convención colombiana de mes de 30 días (cada
     * mes vale 30 días independientemente del número real de días
     * calendario).
     */
    private function workedDaysForEmployee(Employee $employee, PayrollPeriod $period): float
    {
        $effectiveStart = $employee->hire_date && $employee->hire_date->gt($period->start_date)
            ? $employee->hire_date
            : $period->start_date;

        $effectiveEnd = $employee->termination_date && $employee->termination_date->lt($period->end_date)
            ? $employee->termination_date
            : $period->end_date;

        return $this->daysBase30($effectiveStart, $effectiveEnd);
    }

    /**
     * Cuenta los días entre dos fechas bajo la convención de mes de 30 días
     * (base 360), la misma usada en el resto del liquidador para cesantías,
     * auxilio de transporte, etc. El último día calendario de cada mes
     * siempre se trata como día 30, sin importar si el mes tiene 28, 29, 30
     * o 31 días.
     */
    private function daysBase30(Carbon $start, Carbon $end): float
    {
        $day30 = fn (Carbon $d) => $d->day === $d->daysInMonth ? 30 : min($d->day, 30);

        $months = ($end->year - $start->year) * 12 + ($end->month - $start->month);

        return (float) ($months * 30 + $day30($end) - $day30($start) + 1);
    }

    private function nextNumberForClient(Client $client): string
    {
        $client->increment('payroll_consecutive');
        $client->refresh();

        $consecutive = str_pad($client->payroll_consecutive, 4, '0', STR_PAD_LEFT);
        $prefix = trim($client->payroll_prefix ?? '') ?: 'NOM';

        return "{$prefix}-{$consecutive}";
    }
}
