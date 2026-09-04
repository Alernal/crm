<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetSection extends Model
{
    protected $fillable = ['budget_id', 'name', 'sort_order', 'is_outflow', 'statement_role'];

    protected $casts = ['sort_order' => 'integer', 'is_outflow' => 'boolean'];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class, 'section_id')->orderBy('sort_order');
    }
}
