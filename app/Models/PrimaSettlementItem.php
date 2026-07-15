<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrimaSettlementItem extends Model
{
    protected $fillable = [
        'prima_settlement_id',
        'employee_id',
        'worked_days',
        'prima_value',
    ];

    protected $casts = [
        'worked_days' => 'decimal:2',
        'prima_value' => 'decimal:2',
    ];

    public function primaSettlement(): BelongsTo
    {
        return $this->belongsTo(PrimaSettlement::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
