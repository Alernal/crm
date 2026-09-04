<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\PayrollLegalSetting;
use App\Services\Payroll\Concerns\CalculatesLegalDays;
use App\Services\Payroll\Concerns\CalculatesSolidarityFund;
use Illuminate\Support\Carbon;

/**
 * Replica, celda por celda, las hojas "Liquidación al terminar contrato" e
 * "Indemnización" del liquidador de Actualícese
 * (Documentos/VA26-Liquidacion-al-terminar-el-contrato.xlsm). Deliberadamente
 * NO incluye la hoja "Aportes y provisiones empleador" — esa hoja es
 * información contable interna del empleador, no hace parte de lo que se le
 * liquida al trabajador. No persiste nada: recibe los datos crudos del
 * wizard y retorna todos los conceptos ya calculados, listos para llenar un
 * registro de `contract_settlements`.
 */
class ContractSettlementCalculator
{
    use CalculatesLegalDays;
    use CalculatesSolidarityFund;

    /**
     * @param  array<string, mixed>  $data  smlv, last_salary, basic_salary,
     *         transport_allowance_input, worked_days_month,
     *         indemnification_applies, contract_type,
     *         year_start_date, vacation_period_start, prima_period_start,
     *         contract_end_date, contract_reference_date (todas Carbon),
     *         overtime_value, recargos_value, commissions, bonuses_salarial,
     *         per_diem_salarial, other_salarial, occasional_bonuses,
     *         extralegal_premiums, per_diem_no_salarial, other_no_salarial,
     *         withholding_tax, other_deductions
     */
    public function calculate(Employee $employee, array $data): array
    {
        $data = array_merge([
            'overtime_value' => 0, 'recargos_value' => 0, 'commissions' => 0, 'bonuses_salarial' => 0,
            'per_diem_salarial' => 0, 'other_salarial' => 0, 'occasional_bonuses' => 0,
            'extralegal_premiums' => 0, 'per_diem_no_salarial' => 0, 'other_no_salarial' => 0,
            'withholding_tax' => 0, 'other_deductions' => 0,
        ], $data);

        $smlv = (float) $data['smlv'];
        $lastSalary = (float) $data['last_salary'];
        $basicSalary = (float) $data['basic_salary'];
        $transportAllowanceInput = (float) $data['transport_allowance_input'];
        $workedDaysMonth = (float) $data['worked_days_month'];

        /** @var Carbon $yearStart */
        $yearStart = $data['year_start_date'];
        /** @var Carbon $vacationStart */
        $vacationStart = $data['vacation_period_start'];
        /** @var Carbon $primaStart */
        $primaStart = $data['prima_period_start'];
        /** @var Carbon $contractEnd */
        $contractEnd = $data['contract_end_date'];
        /** @var Carbon $contractReference */
        $contractReference = $data['contract_reference_date'];

        $settings = PayrollLegalSetting::forDate($contractEnd);

        // --- Prestaciones sociales ---
        $benefitsBase = $lastSalary <= ($smlv * 2) ? $lastSalary + $transportAllowanceInput : $lastSalary;

        $primaDays = $this->daysBase30($primaStart, $contractEnd);
        $primaValue = round(($benefitsBase * $primaDays) / 360, 2);

        $cesantiasDays = $this->daysBase30($yearStart, $contractEnd);
        $cesantiasValue = round(($benefitsBase * $cesantiasDays) / 360, 2);
        $interestCesantiasValue = round(($cesantiasValue * $cesantiasDays * 0.12) / 360, 2);

        $vacationDays = $this->daysBase30($vacationStart, $contractEnd);
        $vacationValue = round(($lastSalary * $vacationDays) / 720, 2);

        // --- Pagos que constituyen salario en el último período ---
        $basicSalaryPay = round(($basicSalary / 30) * $workedDaysMonth, 2);
        $subtotalSalarial = round(
            $basicSalaryPay + (float) $data['overtime_value'] + (float) $data['recargos_value']
                + (float) $data['commissions'] + (float) $data['bonuses_salarial']
                + (float) $data['per_diem_salarial'] + (float) $data['other_salarial'],
            2
        );

        // --- Auxilio de transporte del último período ---
        $transportCheckBase = $basicSalaryPay + (float) $data['recargos_value']
            + (float) $data['commissions'] + (float) $data['bonuses_salarial']
            + (float) $data['per_diem_salarial'] + (float) $data['other_salarial'];
        $transportAllowanceValue = $transportCheckBase <= ($smlv * 2)
            ? round(($transportAllowanceInput / 30) * $workedDaysMonth, 2)
            : 0.0;

        // --- Pagos que no constituyen salario en el último período ---
        $subtotalNoSalarial = round(
            (float) $data['occasional_bonuses'] + (float) $data['extralegal_premiums']
                + (float) $data['per_diem_no_salarial'] + $transportAllowanceValue
                + (float) $data['other_no_salarial'],
            2
        );

        // --- IBC (artículo 30 de la Ley 1393 de 2010) ---
        $ibcExcess = 0.0;
        if ($subtotalNoSalarial > (($subtotalSalarial + $subtotalNoSalarial) * 0.4)) {
            $ibcExcess = round($subtotalNoSalarial - (($subtotalSalarial + $subtotalNoSalarial) * 0.4), 2);
        }
        $ibc = round($subtotalSalarial + $ibcExcess, 2);
        $ibcCapped = min($ibc, $smlv * 25);

        // --- Deducciones del trabajador ---
        $pensionExempt = (bool) $employee->pension_exempt;
        $healthEmployee = round($ibcCapped * (float) $settings->pct_health_employee, 2);
        $pensionEmployee = $pensionExempt ? 0.0 : round($ibcCapped * (float) $settings->pct_pension_employee, 2);
        $fspEmployee = $pensionExempt ? 0.0 : $this->solidarityFundContribution($ibc, $smlv);
        $totalDeductions = round(
            $healthEmployee + $pensionEmployee + $fspEmployee
                + (float) $data['withholding_tax'] + (float) $data['other_deductions'],
            2
        );

        // --- Indemnización por despido sin justa causa ---
        $indemnificationValue = ((bool) $data['indemnification_applies'])
            ? $this->indemnification((string) $data['contract_type'], $lastSalary, $smlv, $contractReference, $contractEnd)
            : 0.0;

        // --- Resumen ---
        $totalToPay = round(
            $primaValue + $cesantiasValue + $interestCesantiasValue + $vacationValue
                + $subtotalSalarial + $subtotalNoSalarial + $indemnificationValue,
            2
        );
        $netPay = round($totalToPay - $totalDeductions, 2);

        return [
            'smlv' => $smlv,
            'last_salary' => $lastSalary,
            'basic_salary' => $basicSalary,
            'transport_allowance_input' => $transportAllowanceInput,
            'worked_days_month' => $workedDaysMonth,
            'indemnification_applies' => (bool) $data['indemnification_applies'],
            'contract_type' => $data['contract_type'],
            'year_start_date' => $yearStart,
            'vacation_period_start' => $vacationStart,
            'prima_period_start' => $primaStart,
            'contract_end_date' => $contractEnd,
            'contract_reference_date' => $contractReference,

            'prima_base' => $benefitsBase,
            'prima_days' => $primaDays,
            'prima_value' => $primaValue,
            'cesantias_base' => $benefitsBase,
            'cesantias_days' => $cesantiasDays,
            'cesantias_value' => $cesantiasValue,
            'interest_cesantias_value' => $interestCesantiasValue,
            'vacation_base' => $lastSalary,
            'vacation_days' => $vacationDays,
            'vacation_value' => $vacationValue,

            'basic_salary_pay' => $basicSalaryPay,
            'overtime_value' => (float) $data['overtime_value'],
            'recargos_value' => (float) $data['recargos_value'],
            'commissions' => (float) $data['commissions'],
            'bonuses_salarial' => (float) $data['bonuses_salarial'],
            'per_diem_salarial' => (float) $data['per_diem_salarial'],
            'other_salarial' => (float) $data['other_salarial'],

            'occasional_bonuses' => (float) $data['occasional_bonuses'],
            'extralegal_premiums' => (float) $data['extralegal_premiums'],
            'per_diem_no_salarial' => (float) $data['per_diem_no_salarial'],
            'transport_allowance_value' => $transportAllowanceValue,
            'other_no_salarial' => (float) $data['other_no_salarial'],

            'ibc_salarial' => $subtotalSalarial,
            'ibc_no_salarial' => $subtotalNoSalarial,
            'ibc_excess' => $ibcExcess,
            'ibc' => $ibc,

            'health_employee' => $healthEmployee,
            'pension_employee' => $pensionEmployee,
            'fsp_employee' => $fspEmployee,
            'withholding_tax' => (float) $data['withholding_tax'],
            'other_deductions' => (float) $data['other_deductions'],

            'indemnification_value' => $indemnificationValue,

            'total_to_pay' => $totalToPay,
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay,
        ];
    }

    /**
     * Tres ramas según el tipo de contrato (hoja "Indemnización"), activa
     * solo si el retiro fue sin justa causa. Réplica exacta de las fórmulas
     * del Excel de referencia — el contrato a término fijo no aplica ningún
     * piso mínimo de días, tal como está en la fuente.
     */
    private function indemnification(string $contractType, float $lastSalary, float $smlv, Carbon $referenceDate, Carbon $endDate): float
    {
        $dailySalary = $lastSalary / 30;

        if ($contractType === 'indefinido') {
            $isHighSalary = $lastSalary > ($smlv * 10);
            $days = $referenceDate->diffInDays($endDate) + 1;
            $baseYearsAfter = $days < 365 ? 0 : $days - 365;

            $firstYear = $isHighSalary ? 20 * $dailySalary : 30 * $dailySalary;
            $subsequentYears = $isHighSalary
                ? ((15 * $dailySalary) / 365) * $baseYearsAfter
                : ((20 * $dailySalary) / 365) * $baseYearsAfter;

            return round($firstYear + $subsequentYears, 2);
        }

        if ($contractType === 'fijo') {
            $daysPending = $endDate->diffInDays($referenceDate, false);

            return $daysPending > 0 ? round($daysPending * $dailySalary, 2) : 0.0;
        }

        // obra_labor: 15 días fijos, sin importar la duración del contrato.
        return round(15 * $dailySalary, 2);
    }
}
