<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientBudgetYearlyData extends Model
{
    protected $table = 'client_budget_yearly_data';

    protected $fillable = ['user_id', 'client_id', 'indicator', 'year', 'value'];

    protected $casts = [
        'year'  => 'integer',
        'value' => 'float',
    ];

    const INDICATORS = [
        'inflacion'           => 'Inflación (%)',
        'smmlv'               => 'SMMLV',
        'auxilio_transporte'  => 'Auxilio de transporte',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
