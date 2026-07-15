<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetLine extends Model
{
    protected $fillable = [
        'budget_id', 'section_id', 'name', 'sort_order',
        'projection_driver', 'custom_rate', 'is_subtotal', 'sign_negative',
    ];

    protected $casts = [
        'sort_order'     => 'integer',
        'custom_rate'    => 'float',
        'is_subtotal'    => 'boolean',
        'sign_negative'  => 'boolean',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(BudgetSection::class, 'section_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(BudgetValue::class, 'line_id')->orderBy('period_index');
    }

    public function getValueForPeriod(int $periodIndex): float
    {
        $val = $this->values->firstWhere('period_index', $periodIndex);
        return $val ? (float) $val->value : 0.0;
    }
}
