<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChannelMember extends Model
{
    protected $fillable = [
        'channel_id',
        'member_type',
        'member_id',
        'role',
        'last_read_at',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
            'joined_at' => 'datetime',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function member(): MorphTo
    {
        return $this->morphTo();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function markAsRead(): void
    {
        $this->update(['last_read_at' => now()]);
    }
}
