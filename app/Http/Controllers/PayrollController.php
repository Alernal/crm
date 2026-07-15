<?php

namespace App\Http\Controllers;

use App\Mail\PayslipMail;
use App\Models\Payroll;
use App\Models\PayrollLegalSetting;
use App\Models\PayrollOvertimeItem;
use App\Services\Payroll\OvertimeCalculator;
use App\Services\Payroll\PayrollCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function update(Request $request, Payroll $payroll): RedirectResponse
    {
        abort_if($payroll->user_id !== $request->user()->id, 403);
        abort_if(
            ! in_array($payroll->payrollPeriod->status, ['borrador', 'procesada'], true),
            422,
            'Este período ya no se puede editar.'
        );

        $data = $request->validate([
            'worked_days' => ['required', 'numeric', 'min:0', 'max:31'],
            'commissions' => ['nullable', 'numeric', 'min:0'],
            'bonuses_salarial' => ['nullable', 'numeric', 'min:0'],
            'per_diem_salarial' => ['nullable', 'numeric', 'min:0'],
            'other_salarial' => ['nullable', 'numeric', 'min:0'],
            'occasional_bonuses' => ['nullable', 'numeric', 'min:0'],
            'extralegal_premiums' => ['nullable', 'numeric', 'min:0'],
            'per_diem_no_salarial' => ['nullable', 'numeric', 'min:0'],
            'other_no_salarial' => ['nullable', 'numeric', 'min:0'],
            'loans_deduction' => ['nullable', 'numeric', 'min:0'],
            'withholding_tax' => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->recalculate($payroll, (float) $data['worked_days'], $data);

        return redirect()->route('payroll-periods.show', $payroll->payroll_period_id)
            ->with('success', 'Desprendible actualizado.');
    }

    public function updateOvertime(Request $request, Payroll $payroll): RedirectResponse
    {
        abort_if($payroll->user_id !== $request->user()->id, 403);
        abort_if(
            ! in_array($payroll->payrollPeriod->status, ['borrador', 'procesada'], true),
            422,
            'Este período ya no se puede editar.'
        );

        $data = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.type' => ['required', 'in:' . implode(',', array_keys(PayrollOvertimeItem::TYPES))],
            'items.*.hours' => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($payroll, $data) {
            $payroll->overtimeItems()->delete();

            $settings = PayrollLegalSetting::forDate($payroll->payrollPeriod->start_date);
            $overtimeCalculator = new OvertimeCalculator();

            foreach ($data['items'] ?? [] as $item) {
                $line = $overtimeCalculator->lineTotal($item['type'], (float) $item['hours'], $payroll->employee, $settings);

                $payroll->overtimeItems()->create([
                    'type' => $item['type'],
                    'hours' => $item['hours'],
                    'hourly_rate' => $line['hourly_rate'],
                    'total' => $line['total'],
                ]);
            }

            $this->recalculate($payroll, (float) $payroll->worked_days, $this->manualConceptsFrom($payroll));
        });

        return redirect()->route('payroll-periods.show', $payroll->payroll_period_id)
            ->with('success', 'Horas extra actualizadas.');
    }

    public function printView(Request $request, Payroll $payroll): View
    {
        abort_if($payroll->user_id !== $request->user()->id, 403);

        $payroll->load('employee', 'client', 'payrollPeriod', 'overtimeItems');
        $user = $request->user();

        return view('payroll.print', compact('payroll', 'user'));
    }

    public function pdf(Request $request, Payroll $payroll): Response
    {
        abort_if($payroll->user_id !== $request->user()->id, 403);

        $payroll->load('employee', 'client', 'payrollPeriod', 'overtimeItems');
        $user = $request->user();

        $pdf = Pdf::loadView('payroll.pdf', compact('payroll', 'user'))
            ->setPaper('letter', 'portrait');

        return $pdf->download("desprendible-{$payroll->payrollPeriod->number}-{$payroll->employee->document_number}.pdf");
    }

    public function sendEmail(Request $request, Payroll $payroll): RedirectResponse
    {
        abort_if($payroll->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $payroll->load('employee', 'client', 'payrollPeriod', 'overtimeItems');

        Mail::to($data['email'])->send(
            new PayslipMail($payroll, $request->user(), $data['message'] ?? null)
        );

        return back()->with('success', "Desprendible enviado a {$data['email']}.");
    }

    private function manualConceptsFrom(Payroll $payroll): array
    {
        return $payroll->only([
            'commissions', 'bonuses_salarial', 'per_diem_salarial', 'other_salarial',
            'occasional_bonuses', 'extralegal_premiums', 'per_diem_no_salarial', 'other_no_salarial',
            'loans_deduction', 'withholding_tax', 'other_deductions',
        ]);
    }

    private function recalculate(Payroll $payroll, float $workedDays, array $manual): void
    {
        $overtimeByType = $payroll->overtimeItems()
            ->selectRaw('type, sum(total) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->map(fn ($v) => (float) $v)
            ->toArray();

        $result = (new PayrollCalculator())->calculate(
            $payroll->employee,
            $payroll->client,
            $payroll->payrollPeriod,
            $workedDays,
            $manual,
            $overtimeByType,
        );

        $payroll->update($result);
    }
}
