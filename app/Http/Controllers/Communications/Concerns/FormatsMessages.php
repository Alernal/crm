<?php

namespace App\Http\Controllers\Communications\Concerns;

use App\Models\Message;
use Illuminate\Support\Str;

/**
 * Shared JSON/array shape for a message, used both for the initial
 * server-rendered payload (ChannelController::show) and the AJAX
 * polling/send responses (MessageController) so the Alpine chat component
 * never has to deal with two different shapes for the same thing.
 */
trait FormatsMessages
{
    private function formatMessage(Message $message, $actor): array
    {
        return [
            'id' => $message->id,
            'theme_id' => $message->message_theme_id,
            'thread_id' => $message->thread_id,
            'content' => $message->content,
            'created_at' => $message->created_at->toIso8601String(),
            'created_at_label' => $message->created_at->locale('es')->isoFormat('D MMM, h:mm a'),
            'author_name' => $message->author ? Str::title($message->author->name) : 'Usuario eliminado',
            'is_mine' => $message->author_type === $actor::class && $message->author_id === $actor->getKey(),
            'reply_preview' => $message->thread ? [
                'id' => $message->thread->id,
                'author_name' => $message->thread->author ? Str::title($message->thread->author->name) : 'Usuario eliminado',
                'content' => $message->thread->content,
                'has_attachment' => $message->thread->attachments->isNotEmpty(),
            ] : null,
            'reactions' => $this->formatReactions($message, $actor),
            'attachments' => $message->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'size' => $attachment->humanSize(),
                'mime_type' => $attachment->mime_type,
                'url' => route('communications.attachments.show', $attachment),
            ])->values(),
        ];
    }

    private function formatReactions(Message $message, $actor): array
    {
        return $message->reactions
            ->groupBy('emoji')
            ->map(fn ($group, $emoji) => [
                'emoji' => $emoji,
                'count' => $group->count(),
                'mine' => $group->contains(fn ($reaction) => $reaction->actor_type === $actor::class && $reaction->actor_id === $actor->getKey()),
            ])
            ->values()
            ->all();
    }
}
