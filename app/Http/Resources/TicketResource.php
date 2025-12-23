<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
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
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'company_id' => $this->company_id,
            'title' => $this->title,
            'message' => $this->message,
            'priority' => $this->priority,
            'status' => $this->status,
            'is_resolved' => $this->is_resolved,
            'is_locked' => $this->is_locked,
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
            'categories' => $this->whenLoaded('categories', function () {
                return TicketCategoryResource::collection($this->categories);
            }),
            'labels' => $this->whenLoaded('labels', function () {
                return TicketLabelResource::collection($this->labels);
            }),
            'messages' => $this->whenLoaded('messages', function () {
                return TicketMessageResource::collection($this->messages);
            }),
            // Use messages_count from withCount() if available, otherwise count loaded relation
            'messages_count' => $this->messages_count ?? ($this->relationLoaded('messages') ? $this->messages->count() : 0),
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                ];
            }),
        ];
    }
}
// CLAUDE-CHECKPOINT
