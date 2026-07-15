<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CesantiaSettlement extends Model
{
    protected $fillable = [
        'user_id',
        'client_id',
        'year',
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
        return $this->hasMany(CesantiaSettlementItem::class);
    }

    public function getTotalCesantiasAttribute(): float
    {
        return (float) $this->items->sum('cesantias_value');
    }

    public function getTotalInterestAttribute(): float
    {
        return (float) $this->items->sum('interest_value');
    }
}
