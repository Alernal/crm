<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateClause extends Model
{
    protected $fillable = [
        'template_id', 'clause_block_id', 'position',
        'title_override', 'content_override',
        'is_required', 'is_editable', 'is_active', 'config',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_editable' => 'boolean',
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    public function clauseBlock(): BelongsTo
    {
        return $this->belongsTo(ClauseBlock::class);
    }

    public function title(): string
    {
        return $this->title_override ?? $this->clauseBlock->default_title;
    }

    public function rawContent(): string
    {
        return $this->content_override ?? $this->clauseBlock->default_content ?? '';
    }
}
