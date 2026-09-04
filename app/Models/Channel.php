<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Channel extends Model
{
    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'is_active',
        'created_by_type',
        'created_by_id',
        'channel_type',
        'context_type',
        'context_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function context(): MorphTo
    {
        return $this->morphTo();
    }

    public function members(): HasMany
    {
        return $this->hasMany(ChannelMember::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function themes(): HasMany
    {
        return $this->hasMany(MessageTheme::class)->orderBy('order');
    }

    public function isGeneral(): bool
    {
        return $this->channel_type === 'general';
    }

    public function isContextual(): bool
    {
        return $this->channel_type === 'contextual';
    }

    public function unreadCountFor(Model&HasCommunicationOwner $actor): int
    {
        $membership = $this->members()
            ->where('member_type', $actor::class)
            ->where('member_id', $actor->getKey())
            ->first();

        if (! $membership) {
            return 0;
        }

        $query = $this->messages()->where(function ($q) use ($actor) {
            $q->where('author_type', '!=', $actor::class)
              ->orWhere('author_id', '!=', $actor->getKey());
        });

        if ($membership->last_read_at) {
            $query->where('created_at', '>', $membership->last_read_at);
        }

        return $query->count();
    }

    public static function findOrCreateForContext(User $owner, Model $context, string $name): self
    {
        return static::firstOrCreate(
            [
                'owner_id' => $owner->id,
                'context_type' => $context::class,
                'context_id' => $context->getKey(),
            ],
            [
                'name' => $name,
                'slug' => static::generateUniqueSlug($owner->id, $name),
                'channel_type' => 'contextual',
                'created_by_type' => $owner::class,
                'created_by_id' => $owner->id,
            ]
        );
    }

    public static function generateUniqueSlug(int $ownerId, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (static::where('owner_id', $ownerId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
