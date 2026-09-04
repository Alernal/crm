<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'document_id', 'version_number', 'clauses_data', 'content_html', 'change_summary', 'created_by',
    ];

    protected $casts = [
        'clauses_data' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(GeneratedDocument::class, 'document_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
