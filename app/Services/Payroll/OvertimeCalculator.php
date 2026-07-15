<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\PayrollLegalSetting;
use App\Models\PayrollOvertimeItem;

/**
 * Valor de la hora ordinaria y de cada tipo de hora extra/recargo, según el
 * Concepto 16177 de 2023 del Ministerio del Trabajo: horas diarias = horas
 * semanales máximas / 6; horas mes = horas diarias × 30; valor hora = salario
 * / horas mes. Los factores de recargo (25%, 35%, 80%, etc.) vienen de
 * PayrollLegalSetting, vigente a la fecha del período liquidado.
 */
class OvertimeCalculator
{
    public function weeklyHours(Employee $employee, PayrollLegalSetting $settings): float
    {
        return (float) ($employee->weekly_hours_override ?? $settings->weekly_hours);
    }

    public function monthlyHours(Employee $employee, PayrollLegalSetting $settings): float
    {
        return ($this->weeklyHours($employee, $settings) / 6) * 30;
    }

    public function ordinaryHourlyRate(Employee $employee, PayrollLegalSetting $settings): float
    {
        $monthlyHours = $this->monthlyHours($employee, $settings);

        if ($monthlyHours <= 0) {
            return 0;
        }

        return (float) $employee->base_salary / $monthlyHours;
    }

    public function rateForType(string $type, float $ordinaryHourlyRate, PayrollLegalSetting $settings): float
    {
        $factorField = PayrollOvertimeItem::FACTOR_FIELDS[$type] ?? null;

        if ($factorField === null) {
            throw new \InvalidArgumentException("Tipo de hora extra desconocido: {$type}");
        }

        return $ordinaryHourlyRate * (1 + (float) $settings->{$factorField});
    }

    /**
     * @return array{hourly_rate: float, total: float}
     */
    public function lineTotal(string $type, float $hours, Employee $employee, PayrollLegalSetting $settings): array
    {
        $ordinaryRate = $this->ordinaryHourlyRate($employee, $settings);
        $rate = round($this->rateForType($type, $ordinaryRate, $settings), 2);

        return [
            'hourly_rate' => $rate,
            'total' => round($rate * $hours, 2),
        ];
    }
}
