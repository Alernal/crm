<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentTemplate extends Model
{
    use SoftDeletes;

    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'user_id', 'document_type_id', 'name', 'description',
        'current_version_id', 'is_system_default', 'status',
    ];

    protected $casts = [
        'is_system_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentTemplateVersion::class, 'template_id');
    }

    /** Copia editable "en construcción" — no confundir con el historial inmutable (versions()). */
    public function clauses(): HasMany
    {
        return $this->hasMany(TemplateClause::class, 'template_id')->orderBy('position');
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class, 'template_id');
    }
}
