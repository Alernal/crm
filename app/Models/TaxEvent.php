<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxEvent extends Model
{
    protected $fillable = [
        'user_id',
        'client_id',
        'title',
        'obligation_type',
        'due_date',
        'period',
        'alert_days',
        'status',
        'is_recurring',
        'recurrence_pattern',
        'notes',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'is_recurring' => 'boolean',
        'alert_days'   => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'pending'
            && $this->due_date->diffInDays(now(), false) <= 0
            && $this->due_date->diffInDays(now()) <= $this->alert_days;
    }
}
