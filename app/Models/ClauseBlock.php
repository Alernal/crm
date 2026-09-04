<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClauseBlock extends Model
{
    const STRATEGY_STATIC = 'static';
    const STRATEGY_COMPUTED = 'computed';
    const STRATEGY_BUILDER = 'builder';

    protected $fillable = [
        'key', 'label', 'resolver_strategy', 'resolver_class',
        'default_title', 'default_content', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function templateClauses(): HasMany
    {
        return $this->hasMany(TemplateClause::class);
    }
}
