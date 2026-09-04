<?php

namespace Tests\Unit;

use App\Services\Payroll\ColombianHolidayCalculator;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Verifica los festivos calculados contra la hoja "Festivos" del formato de
 * Actualícese (Documentos/VA26-Formato-vacaciones-empleados.xlsx), que trae
 * los 18 festivos oficiales de 2026 ya validados por la fuente.
 */
class ColombianHolidayCalculatorTest extends TestCase
{
    public function test_2026_holidays_match_actualicese_reference(): void
    {
        $expected = [
            '2026-01-01' => 'Año Nuevo',
            '2026-01-12' => 'Día de los Reyes Magos',
            '2026-03-23' => 'Día de San José',
            '2026-04-02' => 'Jueves Santo',
            '2026-04-03' => 'Viernes Santo',
            '2026-05-01' => 'Día del Trabajo',
            '2026-05-18' => 'Ascensión de Jesús',
            '2026-06-08' => 'Corpus Christi',
            '2026-06-15' => 'Sagrado Corazón de Jesús',
            '2026-06-29' => 'Día de San Pedro y San Pablo',
            '2026-07-20' => 'Día de la Independencia',
            '2026-08-07' => 'Batalla de Boyacá',
            '2026-08-17' => 'Asunción de la Virgen',
            '2026-10-12' => 'Día de la Raza',
            '2026-11-02' => 'Día de Todos los Santos',
            '2026-11-16' => 'Independencia de Cartagena',
            '2026-12-08' => 'Inmaculada Concepción',
            '2026-12-25' => 'Navidad',
        ];

        $holidays = (new ColombianHolidayCalculator())->holidaysForYear(2026);

        $this->assertCount(18, $holidays);

        $byDate = [];
        foreach ($holidays as $holiday) {
            $byDate[$holiday['date']->format('Y-m-d')] = $holiday['name'];
        }

        ksort($byDate);
        $this->assertSame($expected, $byDate);
    }

    public function test_fixed_holidays_never_shift_regardless_of_weekday(): void
    {
        // 20 de julio de 2026 cae martes — debe permanecer en su fecha exacta.
        $holidays = (new ColombianHolidayCalculator())->holidaysForYear(2026);
        $independencia = collect($holidays)->firstWhere('name', 'Día de la Independencia');

        $this->assertSame('2026-07-20', $independencia['date']->format('Y-m-d'));
    }

    public function test_emiliani_holiday_already_on_monday_does_not_shift(): void
    {
        // 12 de octubre de 2026 ya cae lunes — no debe correrse.
        $holidays = (new ColombianHolidayCalculator())->holidaysForYear(2026);
        $raza = collect($holidays)->firstWhere('name', 'Día de la Raza');

        $this->assertSame('2026-10-12', $raza['date']->format('Y-m-d'));
        $this->assertTrue($raza['date']->isMonday());
    }

    public function test_is_holiday_detects_a_known_date(): void
    {
        $calculator = new ColombianHolidayCalculator();

        $this->assertTrue($calculator->isHoliday(Carbon::parse('2026-12-25')));
        $this->assertFalse($calculator->isHoliday(Carbon::parse('2026-12-24')));
    }
}
