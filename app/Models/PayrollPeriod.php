<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    const PERIOD_TYPES = [
        'mensual' => 'Mensual',
        'quincenal' => 'Quincenal',
    ];

    const STATUSES = [
        'borrador' => 'Borrador',
        'procesada' => 'Procesada',
        'pagada' => 'Pagada',
        'anulada' => 'Anulada',
    ];

    protected $fillable = [
        'user_id',
        'client_id',
        'number',
        'period_type',
        'start_date',
        'end_date',
        'payment_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function getTotalNetPayAttribute(): float
    {
        return (float) $this->payrolls->sum('net_pay');
    }

    public function getTotalEarnedAttribute(): float
    {
        return (float) $this->payrolls->sum('total_earned');
    }

    public function getTotalDeductionsAttribute(): float
    {
        return (float) $this->payrolls->sum('total_deductions');
    }

    public function getTotalEmployerContributionsAttribute(): float
    {
        return (float) $this->payrolls->sum(
            fn (Payroll $payroll) => $payroll->total_employer_contributions
                + $payroll->prima_provision
                + $payroll->cesantias_provision
                + $payroll->interest_cesantias_provision
                + $payroll->vacation_provision
        );
    }
}
