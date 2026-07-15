<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollLegalSetting extends Model
{
    protected $fillable = [
        'effective_from',
        'smlv',
        'transport_allowance',
        'weekly_hours',
        'night_shift_start_hour',
        'factor_overtime_day',
        'factor_night_surcharge',
        'factor_sunday_holiday',
        'factor_overtime_night',
        'factor_overtime_day_sunday_holiday',
        'factor_sunday_holiday_night',
        'factor_overtime_night_sunday_holiday',
        'pct_health_employee',
        'pct_health_employer',
        'pct_pension_employee',
        'pct_pension_employer',
        'pct_caja_compensacion',
        'pct_icbf',
        'pct_sena',
        'pct_prima',
        'pct_cesantias',
        'pct_vacaciones',
        'arl_rates',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'smlv' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'weekly_hours' => 'decimal:2',
        'factor_overtime_day' => 'decimal:4',
        'factor_night_surcharge' => 'decimal:4',
        'factor_sunday_holiday' => 'decimal:4',
        'factor_overtime_night' => 'decimal:4',
        'factor_overtime_day_sunday_holiday' => 'decimal:4',
        'factor_sunday_holiday_night' => 'decimal:4',
        'factor_overtime_night_sunday_holiday' => 'decimal:4',
        'pct_health_employee' => 'decimal:7',
        'pct_health_employer' => 'decimal:7',
        'pct_pension_employee' => 'decimal:7',
        'pct_pension_employer' => 'decimal:7',
        'pct_caja_compensacion' => 'decimal:7',
        'pct_icbf' => 'decimal:7',
        'pct_sena' => 'decimal:7',
        'pct_prima' => 'decimal:7',
        'pct_cesantias' => 'decimal:7',
        'pct_vacaciones' => 'decimal:7',
        'arl_rates' => 'array',
    ];

    /**
     * Parámetros legales vigentes para una fecha dada (la fila con
     * effective_from más reciente que no la supere). Lanza si no hay
     * ninguna configuración sembrada aún.
     */
    public static function forDate(\DateTimeInterface $date): self
    {
        return static::whereDate('effective_from', '<=', $date->format('Y-m-d'))
            ->orderByDesc('effective_from')
            ->firstOrFail();
    }

    public function arlRateFor(string $riskLevel): float
    {
        return (float) ($this->arl_rates[$riskLevel] ?? 0);
    }
}
