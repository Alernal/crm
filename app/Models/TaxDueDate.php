<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxDueDate extends Model
{
    protected $table = 'tax_due_dates';

    protected $fillable = [
        'obligation_type_id', 'year', 'period_number', 'period_label', 'nit_key', 'due_date',
    ];

    protected $casts = ['due_date' => 'date'];

    public function obligationType(): BelongsTo
    {
        return $this->belongsTo(TaxObligationType::class, 'obligation_type_id');
    }
}
