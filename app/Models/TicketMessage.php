<?php

namespace App\Models;

use Coderflex\LaravelTicket\Models\Message as BaseMessage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Custom TicketMessage model extending the LaravelTicket package model.
 * Adds media/attachment support using Spatie Media Library.
 */
class TicketMessage extends BaseMessage implements HasMedia
{
    use InteractsWithMedia;

    /**
     * Register media collections for attachments.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'application/pdf',
            ]);
    }

    /**
     * Get all attachments for this message.
     */
    public function getAttachmentsAttribute()
    {
        return $this->getMedia('attachments')->map(function ($media) {
            return [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => $media->getUrl(),
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'human_readable_size' => $media->human_readable_size,
            ];
        });
    }
}
// CLAUDE-CHECKPOINT
