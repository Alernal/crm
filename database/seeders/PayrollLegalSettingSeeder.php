<?php

namespace Database\Seeders;

use App\Models\PayrollLegalSetting;
use Illuminate\Database\Seeder;

class PayrollLegalSettingSeeder extends Seeder
{
    /**
     * Valores verificados celda por celda contra los liquidadores de Actualícese
     * (storage/app/Nómina/) vigentes en 2026. La jornada baja a 42h desde el
     * 15-jul-2026 por la Ley 2101 de 2021; el recargo dominical/festivo del
     * 80% solo está confirmado hasta el 30-jun-2026 (Ley 2466 de 2025) — el
     * Excel fuente aún no publica el valor posterior, así que se mantiene el
     * mismo factor con la advertencia de revisarlo cuando se conozca.
     */
    public function run(): void
    {
        $arlRates = [
            'I' => 0.00522,
            'II' => 0.01044,
            'III' => 0.02436,
            'IV' => 0.0435,
            'V' => 0.0696,
        ];

        $common = [
            'factor_overtime_day' => 0.25,
            'factor_night_surcharge' => 0.35,
            'factor_sunday_holiday' => 0.80,
            'factor_overtime_night' => 0.75,
            'factor_overtime_day_sunday_holiday' => 1.05,
            'factor_sunday_holiday_night' => 1.15,
            'factor_overtime_night_sunday_holiday' => 1.55,
            'night_shift_start_hour' => 19,
            'pct_health_employee' => 0.04,
            'pct_health_employer' => 0.085,
            'pct_pension_employee' => 0.04,
            'pct_pension_employer' => 0.12,
            'pct_caja_compensacion' => 0.04,
            'pct_icbf' => 0.03,
            'pct_sena' => 0.02,
            'pct_prima' => 0.0833333,
            'pct_cesantias' => 0.0833333,
            'pct_vacaciones' => 0.0416666,
            'arl_rates' => $arlRates,
        ];

        $this->upsertByDate('2026-01-01', [...$common, 'smlv' => 1750905, 'transport_allowance' => 249095, 'weekly_hours' => 44]);
        $this->upsertByDate('2026-07-15', [...$common, 'smlv' => 1750905, 'transport_allowance' => 249095, 'weekly_hours' => 42]);
    }

    /**
     * updateOrCreate() no sirve aquí: el cast `date` de `effective_from` se
     * serializa con hora (00:00:00) al guardar, así que una comparación
     * literal por igualdad nunca coincide con una fila ya existente.
     */
    private function upsertByDate(string $effectiveFrom, array $attributes): void
    {
        $existing = PayrollLegalSetting::whereDate('effective_from', $effectiveFrom)->first();

        if ($existing) {
            $existing->update($attributes);
        } else {
            PayrollLegalSetting::create(['effective_from' => $effectiveFrom, ...$attributes]);
        }
    }
}
