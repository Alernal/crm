<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CesantiaSettlementItem extends Model
{
    protected $fillable = [
        'cesantia_settlement_id',
        'employee_id',
        'worked_days',
        'cesantias_value',
        'interest_value',
    ];

    protected $casts = [
        'worked_days' => 'decimal:2',
        'cesantias_value' => 'decimal:2',
        'interest_value' => 'decimal:2',
    ];

    public function cesantiaSettlement(): BelongsTo
    {
        return $this->belongsTo(CesantiaSettlement::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
