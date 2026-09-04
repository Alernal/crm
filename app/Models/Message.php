<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Message extends Model
{
    protected $fillable = [
        'channel_id',
        'message_theme_id',
        'thread_id',
        'author_type',
        'author_id',
        'content',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(MessageTheme::class, 'message_theme_id');
    }

    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(MessageMention::class);
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'thread_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'thread_id');
    }

    public function isThreadReply(): bool
    {
        return $this->thread_id !== null;
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }
}
