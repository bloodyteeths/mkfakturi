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
            'attachments' => $this->when(
                method_exists($this->resource, 'getMedia'),
                function () {
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
                },
                []
            ),
        ];
    }
}
// CLAUDE-CHECKPOINT
