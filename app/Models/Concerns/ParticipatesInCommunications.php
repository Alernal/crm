<?php

namespace App\Models\Concerns;

use App\Models\ChannelMember;
use App\Models\Message;
use App\Models\MessageMention;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

/**
 * Shared by every actor type that can join channels, author messages and be
 * mentioned (User and TeamMember) so the polymorphic relations aren't
 * duplicated per model.
 */
trait ParticipatesInCommunications
{
    public function channelMemberships(): MorphMany
    {
        return $this->morphMany(ChannelMember::class, 'member');
    }

    public function messages(): MorphMany
    {
        return $this->morphMany(Message::class, 'author');
    }

    public function mentions(): MorphMany
    {
        return $this->morphMany(MessageMention::class, 'mentioned');
    }

    public function mentionHandle(): string
    {
        return Str::slug($this->name, '.');
    }
}
