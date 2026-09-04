<?php

namespace App\Services\Payroll\Concerns;

use Illuminate\Support\Carbon;

/**
 * Cuenta días entre dos fechas bajo la convención colombiana de mes de 30
 * días (base 360): el último día calendario de cada mes siempre se trata
 * como día 30, sin importar si el mes tiene 28, 29, 30 o 31 días. Usada para
 * cesantías, prima, vacaciones, auxilio de transporte y la liquidación al
 * terminar el contrato.
 */
trait CalculatesLegalDays
{
    protected function daysBase30(Carbon $start, Carbon $end): float
    {
        $day30 = fn (Carbon $d) => $d->day === $d->daysInMonth ? 30 : min($d->day, 30);

        $months = ($end->year - $start->year) * 12 + ($end->month - $start->month);

        return (float) ($months * 30 + $day30($end) - $day30($start) + 1);
    }
}
