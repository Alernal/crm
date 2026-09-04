<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetValue extends Model
{
    protected $fillable = [
        'budget_id', 'line_id', 'value_type', 'period_label', 'period_index',
        'value', 'quantity', 'is_manual_override',
    ];

    protected $casts = [
        'value'              => 'float',
        'quantity'           => 'float',
        'period_index'       => 'integer',
        'is_manual_override' => 'boolean',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class, 'line_id');
    }
}
