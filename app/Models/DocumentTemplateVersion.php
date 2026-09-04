<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTemplateVersion extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'template_id', 'version_number', 'clauses_snapshot', 'change_summary', 'created_by',
    ];

    protected $casts = [
        'clauses_snapshot' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
