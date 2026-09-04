<?php

namespace App\Services\Payroll;

use App\Models\Client;
use App\Models\Employee;
use App\Models\PayrollLegalSetting;
use App\Models\PayrollPeriod;
use App\Services\Payroll\Concerns\CalculatesSolidarityFund;

/**
 * Replica, celda por celda, la hoja "Liquidación nómina" del liquidador
 * mensual básico de Actualícese (storage/app/Nómina/2. Liquidador y
 * desprendible de nómina básico.xlsm). No persiste nada: recibe los datos
 * crudos de un empleado/período y retorna todos los conceptos ya calculados,
 * listos para llenar un registro de `payrolls`.
 */
class PayrollCalculator
{
    use CalculatesSolidarityFund;

    /**
     * @param  array<string, float>  $manual  commissions, bonuses_salarial, per_diem_salarial, other_salarial,
     *                                         occasional_bonuses, extralegal_premiums, per_diem_no_salarial,
     *                                         other_no_salarial, loans_deduction, withholding_tax, other_deductions
     * @param  array<string, float>  $overtimeByType  monto ya calculado por cada tipo de PayrollOvertimeItem::TYPES
     */
    public function calculate(
        Employee $employee,
        Client $client,
        PayrollPeriod $period,
        float $workedDays,
        array $manual = [],
        array $overtimeByType = [],
    ): array {
        $settings = PayrollLegalSetting::forDate($period->start_date);
        $smlv = (float) $settings->smlv;

        $manual = array_merge([
            'commissions' => 0, 'bonuses_salarial' => 0, 'per_diem_salarial' => 0, 'other_salarial' => 0,
            'occasional_bonuses' => 0, 'extralegal_premiums' => 0, 'per_diem_no_salarial' => 0, 'other_no_salarial' => 0,
            'loans_deduction' => 0, 'withholding_tax' => 0, 'other_deductions' => 0,
        ], $manual);

        // --- Pagos salariales ---
        $basicSalaryPay = round(((float) $employee->base_salary / 30) * $workedDays, 2);

        $overtimePay = round(array_sum($overtimeByType), 2);
        $recargoNocturnoOnly = round($overtimeByType['recargo_nocturno'] ?? 0, 2);
        // "Horas extra, dominicales y festivos" del Excel: todo lo que no sea recargo nocturno ordinario.
        $overtimeExcludedFromBase = round($overtimePay - $recargoNocturnoOnly, 2);

        $subtotalSalarial = round(
            $basicSalaryPay + $overtimePay
                + $manual['commissions'] + $manual['bonuses_salarial']
                + $manual['per_diem_salarial'] + $manual['other_salarial'],
            2
        );

        // --- Auxilio de transporte ---
        $transportCheckBase = $subtotalSalarial - $overtimeExcludedFromBase;
        $transportEligible = match ($employee->transport_allowance_mode) {
            'siempre' => true,
            'nunca' => false,
            default => $transportCheckBase <= ($smlv * 2),
        };
        $transportAllowance = $transportEligible
            ? round(((float) $settings->transport_allowance / 30) * $workedDays, 2)
            : 0.0;

        // --- Pagos que no constituyen salario ---
        $subtotalNoSalarial = round(
            $transportAllowance + $manual['occasional_bonuses'] + $manual['extralegal_premiums']
                + $manual['per_diem_no_salarial'] + $manual['other_no_salarial'],
            2
        );

        $totalEarned = round($subtotalSalarial + $subtotalNoSalarial, 2);

        // --- IBC (Ley 1393 de 2010, art. 30) ---
        $totalPay = $subtotalSalarial + $subtotalNoSalarial;
        $ibcExcess = 0.0;
        if ($totalPay > 0 && $subtotalNoSalarial > ($totalPay * 0.4)) {
            $ibcExcess = round($subtotalNoSalarial - ($totalPay * 0.4), 2);
        }
        $ibc = round($subtotalSalarial + $ibcExcess, 2);
        $ibcCapped = min($ibc, $smlv * 25);

        // --- Seguridad social ---
        // Trabajadores no obligados a cotizar a pensión (ej: pensionados por
        // vejez que siguen trabajando) no tienen aporte de pensión ni de
        // empleado, ni de empleador, ni fondo de solidaridad — la obligación
        // de pensión no existe para esa relación laboral. Salud sí aplica
        // igual.
        $pensionExempt = (bool) $employee->pension_exempt;

        $healthEmployee = round($ibcCapped * (float) $settings->pct_health_employee, 2);
        $pensionEmployee = $pensionExempt ? 0.0 : round($ibcCapped * (float) $settings->pct_pension_employee, 2);
        $fspEmployee = $pensionExempt ? 0.0 : $this->solidarityFundContribution($ibc, $smlv);

        $isExempt = (bool) $client->payroll_pila_exempt;
        $healthEmployer = $isExempt ? 0.0 : round($ibcCapped * (float) $settings->pct_health_employer, 2);
        $pensionEmployer = $pensionExempt ? 0.0 : round($ibcCapped * (float) $settings->pct_pension_employer, 2);
        $arlEmployer = round($ibcCapped * $settings->arlRateFor($employee->arl_risk_level), 2);

        $cajaCompensacion = round($ibc * (float) $settings->pct_caja_compensacion, 2);
        $icbf = $isExempt ? 0.0 : round($ibc * (float) $settings->pct_icbf, 2);
        $sena = $isExempt ? 0.0 : round($ibc * (float) $settings->pct_sena, 2);
        $totalEmployerContributions = round($healthEmployer + $pensionEmployer + $arlEmployer + $cajaCompensacion + $icbf + $sena, 2);

        // --- Deducciones del trabajador ---
        $totalDeductions = round(
            $healthEmployee + $pensionEmployee + $fspEmployee
                + $manual['loans_deduction'] + $manual['withholding_tax'] + $manual['other_deductions'],
            2
        );

        // --- Provisión de prestaciones sociales ---
        $primaProvision = round(($subtotalSalarial + $transportAllowance) * (float) $settings->pct_prima, 2);
        $cesantiasProvision = round(($subtotalSalarial + $transportAllowance) * (float) $settings->pct_cesantias, 2);
        $interestCesantiasProvision = round(($cesantiasProvision * $workedDays * 0.12) / 360, 2);
        $vacationProvision = round(($subtotalSalarial - $overtimeExcludedFromBase) * (float) $settings->pct_vacaciones, 2);

        $netPay = round($totalEarned - $totalDeductions, 2);

        return [
            'worked_days' => $workedDays,
            'basic_salary_pay' => $basicSalaryPay,
            'overtime_pay' => $overtimePay,
            'commissions' => (float) $manual['commissions'],
            'bonuses_salarial' => (float) $manual['bonuses_salarial'],
            'per_diem_salarial' => (float) $manual['per_diem_salarial'],
            'other_salarial' => (float) $manual['other_salarial'],
            'subtotal_salarial' => $subtotalSalarial,
            'transport_allowance' => $transportAllowance,
            'occasional_bonuses' => (float) $manual['occasional_bonuses'],
            'extralegal_premiums' => (float) $manual['extralegal_premiums'],
            'per_diem_no_salarial' => (float) $manual['per_diem_no_salarial'],
            'other_no_salarial' => (float) $manual['other_no_salarial'],
            'subtotal_no_salarial' => $subtotalNoSalarial,
            'total_earned' => $totalEarned,
            'ibc' => $ibc,
            'health_employee' => $healthEmployee,
            'pension_employee' => $pensionEmployee,
            'fsp_employee' => $fspEmployee,
            'loans_deduction' => (float) $manual['loans_deduction'],
            'withholding_tax' => (float) $manual['withholding_tax'],
            'other_deductions' => (float) $manual['other_deductions'],
            'total_deductions' => $totalDeductions,
            'health_employer' => $healthEmployer,
            'pension_employer' => $pensionEmployer,
            'arl_employer' => $arlEmployer,
            'caja_compensacion' => $cajaCompensacion,
            'icbf' => $icbf,
            'sena' => $sena,
            'total_employer_contributions' => $totalEmployerContributions,
            'prima_provision' => $primaProvision,
            'cesantias_provision' => $cesantiasProvision,
            'interest_cesantias_provision' => $interestCesantiasProvision,
            'vacation_provision' => $vacationProvision,
            'net_pay' => $netPay,
        ];
    }
}
