<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollOvertimeItem extends Model
{
    const TYPES = [
        'extra_diurna' => 'Hora extra diurna',
        'recargo_nocturno' => 'Recargo nocturno',
        'dominical_festivo' => 'Recargo dominical o festivo',
        'extra_nocturna' => 'Hora extra nocturna',
        'extra_diurna_dominical_festivo' => 'Hora extra diurna dominical o festiva',
        'dominical_festivo_nocturno' => 'Recargo dominical o festivo nocturno',
        'extra_nocturna_dominical_festivo' => 'Hora extra nocturna dominical o festiva',
    ];

    /** Factor field on PayrollLegalSetting for each overtime type. */
    const FACTOR_FIELDS = [
        'extra_diurna' => 'factor_overtime_day',
        'recargo_nocturno' => 'factor_night_surcharge',
        'dominical_festivo' => 'factor_sunday_holiday',
        'extra_nocturna' => 'factor_overtime_night',
        'extra_diurna_dominical_festivo' => 'factor_overtime_day_sunday_holiday',
        'dominical_festivo_nocturno' => 'factor_sunday_holiday_night',
        'extra_nocturna_dominical_festivo' => 'factor_overtime_night_sunday_holiday',
    ];

    protected $fillable = [
        'payroll_id',
        'type',
        'hours',
        'hourly_rate',
        'total',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
