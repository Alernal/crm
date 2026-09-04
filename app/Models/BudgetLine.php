<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetLine extends Model
{
    protected $fillable = [
        'budget_id', 'section_id', 'service_id', 'name', 'sort_order',
        'projection_driver', 'legal_factor_key', 'include_in_prestacional_base',
        'custom_rate', 'is_subtotal', 'sign_negative',
    ];

    protected $casts = [
        'sort_order'                    => 'integer',
        'custom_rate'                   => 'float',
        'is_subtotal'                   => 'boolean',
        'sign_negative'                 => 'boolean',
        'include_in_prestacional_base'  => 'boolean',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(BudgetSection::class, 'section_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(BudgetValue::class, 'line_id')->orderBy('period_index');
    }

    public function valueEntries(): HasMany
    {
        return $this->hasMany(BudgetValueEntry::class)->orderBy('entry_date');
    }

    public function getValueForPeriod(int $periodIndex, string $valueType = 'budgeted'): float
    {
        $val = $this->values->first(
            fn (BudgetValue $v) => $v->period_index === $periodIndex && $v->value_type === $valueType
        );
        return $val ? (float) $val->value : 0.0;
    }

    public function getQuantityForPeriod(int $periodIndex, string $valueType = 'budgeted'): float
    {
        $val = $this->values->first(
            fn (BudgetValue $v) => $v->period_index === $periodIndex && $v->value_type === $valueType
        );
        return $val && $val->quantity !== null ? (float) $val->quantity : 0.0;
    }
}
