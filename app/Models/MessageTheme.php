<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MessageTheme extends Model
{
    protected $fillable = [
        'channel_id',
        'name',
        'description',
        'order',
        'is_collapsed',
        'created_by_type',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'is_collapsed' => 'boolean',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function messageCount(): int
    {
        return $this->messages()->count();
    }

    public function latestMessage(): ?Message
    {
        return $this->messages()->latest('id')->first();
    }
}
