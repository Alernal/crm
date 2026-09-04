<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DocumentShare extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'uuid', 'document_id', 'token', 'expires_at', 'password_hash', 'created_by', 'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = ['password_hash', 'token'];

    protected static function booted(): void
    {
        static::creating(function (self $share) {
            $share->uuid ??= (string) Str::uuid();
            $share->token ??= Str::random(48);
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(GeneratedDocument::class, 'document_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValid(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
