<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MessageReaction extends Model
{
    const QUICK_EMOJIS = ['👍', '❤️', '😂', '👀', '✅'];

    protected $fillable = [
        'message_id',
        'emoji',
        'actor_type',
        'actor_id',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
