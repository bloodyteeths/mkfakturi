<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'user_id' => $this->user_id,
            'message' => $this->message,
            'is_internal' => $this->is_internal ?? false,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'formatted_created_at' => $this->created_at ? $this->created_at->diffForHumans() : null,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'avatar' => $this->user->avatar ?? null,
                ];
            }),
            'attachments' => $this->getAttachments(),
        ];
    }

    /**
     * Get attachments from media relation (eager loaded or via getMedia).
     */
    protected function getAttachments(): array
    {
        // If media relation is eager loaded, use it
        if ($this->relationLoaded('media')) {
            return $this->media
                ->filter(fn ($media) => $media->collection_name === 'attachments')
                ->map(fn ($media) => $this->formatMedia($media))
                ->values()
                ->toArray();
        }

        // Fallback to getMedia if model supports it
        if (method_exists($this->resource, 'getMedia')) {
            return $this->getMedia('attachments')
                ->map(fn ($media) => $this->formatMedia($media))
                ->toArray();
        }

        return [];
    }

    /**
     * Format a single media item for the response.
     */
    protected function formatMedia($media): array
    {
        return [
            'id' => $media->id,
            'name' => $media->file_name,
            'url' => $media->getUrl(),
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'human_readable_size' => $media->human_readable_size,
        ];
    }
}
// CLAUDE-CHECKPOINT
