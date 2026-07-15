<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'user_id',
        'client_id',
        'number',
        'issue_date',
        'due_date',
        'subtotal',
        'vat_amount',
        'discount_amount',
        'withholding_rate',
        'withholding_amount',
        'total',
        'status',
        'notes',
        'payment_method',
    ];

    protected $casts = [
        'issue_date'         => 'date',
        'due_date'           => 'date',
        'subtotal'           => 'decimal:2',
        'vat_amount'         => 'decimal:2',
        'discount_amount'    => 'decimal:2',
        'withholding_rate'   => 'decimal:2',
        'withholding_amount' => 'decimal:2',
        'total'              => 'decimal:2',
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
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments->sum('amount');
    }

    public function getBalanceAttribute(): float
    {
        return (float) $this->total - $this->paid_amount;
    }

    public static function syncOverdueForUser(int $userId): void
    {
        static::where('user_id', $userId)
            ->where('status', 'sent')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);
    }
}
