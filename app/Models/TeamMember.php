<?php

namespace App\Models;

use App\Models\Concerns\ParticipatesInCommunications;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class TeamMember extends Authenticatable implements HasCommunicationOwner
{
    use Notifiable, ParticipatesInCommunications;

    protected $fillable = [
        'owner_id',
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'invited_at',
        'accepted_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'invited_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function communicationOwnerId(): int
    {
        return $this->owner_id;
    }
}
