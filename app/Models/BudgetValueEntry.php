<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetValueEntry extends Model
{
    protected $fillable = [
        'budget_line_id', 'period_index', 'entry_date', 'tercero', 'description', 'value',
    ];

    protected $casts = [
        'entry_date'   => 'date',
        'period_index' => 'integer',
        'value'        => 'float',
    ];

    public function line(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class, 'budget_line_id');
    }
}
