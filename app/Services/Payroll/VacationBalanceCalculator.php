<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcula el saldo de vacaciones pendientes de un empleado a partir de un
 * saldo inicial (conocido a una fecha de referencia) más 15 días hábiles por
 * cada año completo cumplido desde esa fecha, menos los días efectivamente
 * disfrutados desde entonces — reemplaza la "fecha del próximo período
 * adicional" que el liquidador de Actualícese
 * (Documentos/VA26-Formato-vacaciones-empleados.xlsx) obliga a actualizar a
 * mano cada año: aquí se calcula siempre en vivo a partir de la fecha de
 * ingreso, sin campos que se puedan quedar desactualizados. No persiste
 * nada — recibe el empleado y sus períodos ya tomados y retorna todo
 * calculado.
 */
class VacationBalanceCalculator
{
    public function __construct(private readonly ColombianHolidayCalculator $holidays = new ColombianHolidayCalculator()) {}

    /**
     * @param  Collection<int, \App\Models\EmployeeVacationPeriod>  $periods  Todos los períodos tomados por el empleado, sin filtrar.
     */
    public function calculate(Employee $employee, Collection $periods, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? Carbon::today())->copy()->startOfDay();
        $openingDate = ($employee->vacation_opening_balance_date ?? $employee->hire_date)?->copy()->startOfDay();
        $openingBalance = (float) $employee->vacation_opening_balance_days;

        $effectiveAsOf = $employee->termination_date && $employee->termination_date->lt($asOf)
            ? $employee->termination_date->copy()->startOfDay()
            : $asOf;

        $accruedYears = 0;
        if ($openingDate && $openingDate->lte($effectiveAsOf)) {
            $accruedYears = (int) $openingDate->diffInYears($effectiveAsOf);
        }
        $accruedDays = (float) ($accruedYears * 15);

        $periodsSinceOpening = $openingDate
            ? $periods->filter(fn ($p) => $p->start_date->gte($openingDate))
            : $periods;
        $takenDaysSinceOpening = (float) $periodsSinceOpening->sum(fn ($p) => (float) $p->business_days);

        $pendingBalance = round($openingBalance + $accruedDays - $takenDaysSinceOpening, 2);

        $nextAccrualDate = $openingDate ? $openingDate->copy()->addYears($accruedYears + 1) : null;

        $currentYear = Carbon::today()->year;
        $takenDaysCurrentYear = (float) $periods
            ->filter(fn ($p) => $p->start_date->year === $currentYear)
            ->sum(fn ($p) => (float) $p->business_days);

        return [
            'opening_balance' => $openingBalance,
            'opening_date' => $openingDate,
            'accrued_years' => $accruedYears,
            'accrued_days' => $accruedDays,
            'taken_days_since_opening' => $takenDaysSinceOpening,
            'pending_balance' => $pendingBalance,
            'next_accrual_date' => $nextAccrualDate,
            'taken_days_current_year' => $takenDaysCurrentYear,
            'complies_minimum_current_year' => $takenDaysCurrentYear >= 6,
        ];
    }

    /**
     * Días hábiles entre dos fechas (ambas incluidas), excluyendo sábados,
     * domingos y festivos colombianos — sugerencia editable para el campo
     * "días hábiles" de un período, réplica más precisa que la del Excel
     * (que no descuenta festivos, solo lo digita el usuario a mano).
     */
    public function suggestedBusinessDays(Carbon $start, Carbon $end): float
    {
        $days = 0;
        $cursor = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        while ($cursor->lte($endDay)) {
            if (! $cursor->isWeekend() && ! $this->holidays->isHoliday($cursor)) {
                $days++;
            }
            $cursor->addDay();
        }

        return (float) $days;
    }
}
