<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrimaSettlement extends Model
{
    const SEMESTERS = [
        1 => '1er semestre (ene-jun)',
        2 => '2do semestre (jul-dic)',
    ];

    protected $fillable = [
        'user_id',
        'client_id',
        'year',
        'semester',
        'start_date',
        'end_date',
        'payment_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrimaSettlementItem::class);
    }

    public function getTotalPrimaAttribute(): float
    {
        return (float) $this->items->sum('prima_value');
    }
}
