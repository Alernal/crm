<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetInvestmentAsset extends Model
{
    protected $fillable = ['budget_id', 'name', 'value', 'useful_life_years', 'purchase_period_index'];

    protected $casts = [
        'value'                 => 'float',
        'useful_life_years'     => 'integer',
        'purchase_period_index' => 'integer',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function monthlyDepreciation(): float
    {
        return $this->useful_life_years > 0
            ? round($this->value / $this->useful_life_years / 12, 2)
            : 0.0;
    }
}
