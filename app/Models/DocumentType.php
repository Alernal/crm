<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    protected $fillable = [
        'key', 'label', 'icon', 'default_prefix', 'requires_dual_signature', 'is_active',
    ];

    protected $casts = [
        'requires_dual_signature' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(DocumentTemplate::class);
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function counters(): HasMany
    {
        return $this->hasMany(DocumentTypeCounter::class);
    }
}
