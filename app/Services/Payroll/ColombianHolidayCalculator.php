<?php

namespace App\Services\Payroll;

use Illuminate\Support\Carbon;

/**
 * Calcula los 18 festivos oficiales de Colombia para cualquier año, sin
 * depender de una tabla sembrada ni de una fuente externa — función pura
 * basada en la Ley 51 de 1983 ("Ley Emiliani", festivos de fecha fija que se
 * corren al lunes siguiente si no caen en lunes) y en el algoritmo de Gauss
 * para calcular el Domingo de Pascua (del cual dependen Jueves y Viernes
 * Santo, Ascensión, Corpus Christi y Sagrado Corazón). Verificado celda por
 * celda contra los 18 festivos de 2026 de la hoja "Festivos" del formato de
 * Actualícese (Documentos/VA26-Formato-vacaciones-empleados.xlsx).
 */
class ColombianHolidayCalculator
{
    /** Fecha fija, nunca se corre de día — no está sujeta a la Ley Emiliani. */
    private const FIXED = [
        '01-01' => 'Año Nuevo',
        '05-01' => 'Día del Trabajo',
        '07-20' => 'Día de la Independencia',
        '08-07' => 'Batalla de Boyacá',
        '12-08' => 'Inmaculada Concepción',
        '12-25' => 'Navidad',
    ];

    /** Fecha fija que se corre al lunes siguiente si no cae en lunes (Ley 51 de 1983). */
    private const EMILIANI = [
        '01-06' => 'Día de los Reyes Magos',
        '03-19' => 'Día de San José',
        '06-29' => 'Día de San Pedro y San Pablo',
        '08-15' => 'Asunción de la Virgen',
        '10-12' => 'Día de la Raza',
        '11-01' => 'Día de Todos los Santos',
        '11-11' => 'Independencia de Cartagena',
    ];

    /** Offset en días desde el Domingo de Pascua; con corrimiento a lunes o no. */
    private const EASTER_BASED = [
        ['offset' => -3, 'name' => 'Jueves Santo', 'shift' => false],
        ['offset' => -2, 'name' => 'Viernes Santo', 'shift' => false],
        ['offset' => 39, 'name' => 'Ascensión de Jesús', 'shift' => true],
        ['offset' => 60, 'name' => 'Corpus Christi', 'shift' => true],
        ['offset' => 68, 'name' => 'Sagrado Corazón de Jesús', 'shift' => true],
    ];

    /**
     * @return array<int, array{date: Carbon, name: string}> ordenado por fecha
     */
    public function holidaysForYear(int $year): array
    {
        $holidays = [];

        foreach (self::FIXED as $monthDay => $name) {
            $holidays[] = ['date' => Carbon::createFromFormat('Y-m-d', "{$year}-{$monthDay}")->startOfDay(), 'name' => $name];
        }

        foreach (self::EMILIANI as $monthDay => $name) {
            $date = Carbon::createFromFormat('Y-m-d', "{$year}-{$monthDay}")->startOfDay();
            $holidays[] = ['date' => $this->shiftToMonday($date), 'name' => $name];
        }

        $easter = $this->easterSunday($year);
        foreach (self::EASTER_BASED as $item) {
            $date = $easter->copy()->addDays($item['offset']);
            $holidays[] = ['date' => $item['shift'] ? $this->shiftToMonday($date) : $date, 'name' => $item['name']];
        }

        usort($holidays, fn ($a, $b) => $a['date']->timestamp <=> $b['date']->timestamp);

        return $holidays;
    }

    /** @return array<int, Carbon> */
    public function holidayDatesForYear(int $year): array
    {
        return array_map(fn ($h) => $h['date'], $this->holidaysForYear($year));
    }

    public function isHoliday(Carbon $date): bool
    {
        foreach ($this->holidayDatesForYear($date->year) as $holiday) {
            if ($holiday->isSameDay($date)) {
                return true;
            }
        }

        return false;
    }

    private function shiftToMonday(Carbon $date): Carbon
    {
        return $date->dayOfWeekIso === Carbon::MONDAY ? $date : $date->next(Carbon::MONDAY);
    }

    /**
     * Domingo de Pascua vía el algoritmo de Gauss (método anónimo gregoriano,
     * Meeus/Jones/Butcher) — matemática pura, sin depender de la extensión
     * `calendar` de PHP (no siempre está compilada) ni de sus límites de rango.
     */
    private function easterSunday(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day)->startOfDay();
    }
}
