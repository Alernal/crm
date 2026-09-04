<?php

namespace App\Services\Communications;

use App\Models\Channel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Lightweight "is typing" presence backed by the cache (no new infra —
 * works with whatever CACHE_STORE the app already has, currently
 * 'database'). Read by the message polling endpoint that channels already
 * hit every ~4s, so no separate polling loop is needed on the frontend.
 */
class TypingPresence
{
    private const TTL_SECONDS = 6;

    public static function heartbeat(Channel $channel, $actor): void
    {
        Cache::put(self::key($channel->id, $actor::class, $actor->getKey()), Str::title($actor->name), now()->addSeconds(self::TTL_SECONDS));
    }

    public static function activeLabelsFor(Channel $channel, $excludingActor): array
    {
        return $channel->members
            ->reject(fn ($member) => $member->member_type === $excludingActor::class && $member->member_id === $excludingActor->getKey())
            ->map(fn ($member) => Cache::get(self::key($channel->id, $member->member_type, $member->member_id)))
            ->filter()
            ->values()
            ->all();
    }

    private static function key(int $channelId, string $actorType, int $actorId): string
    {
        return "typing:{$channelId}:{$actorType}:{$actorId}";
    }
}
