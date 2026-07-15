<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxCalendarImport extends Model
{
    protected $table = 'tax_calendar_imports';

    protected $fillable = [
        'year', 'original_name', 'path', 'status',
        'parsed_rows', 'summary', 'parse_notes', 'imported_at',
    ];

    protected $casts = [
        'parsed_rows' => 'array',
        'summary'     => 'array',
        'imported_at' => 'datetime',
    ];
}
