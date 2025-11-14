<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketLabelResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'is_visible' => $this->is_visible ?? true,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
// CLAUDE-CHECKPOINT
