<?php

namespace Modules\Mk\Http\Resources\Manufacturing;

use App\Http\Resources\ItemResource;
use App\Http\Resources\UnitResource;
use Illuminate\Http\Resources\Json\JsonResource;

class BomLineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'bom_id' => $this->bom_id,
            'item_id' => $this->item_id,
            'quantity' => $this->quantity,
            'unit_id' => $this->unit_id,
            'wastage_percent' => $this->wastage_percent,
            'notes' => $this->notes,
            'sort_order' => $this->sort_order,
            'item' => $this->whenLoaded('item', fn () => [
                'id' => $this->item->id,
                'name' => $this->item->name,
                'unit_id' => $this->item->unit_id,
            ]),
            'unit' => $this->whenLoaded('unit', fn () => [
                'id' => $this->unit->id,
                'name' => $this->unit->name,
            ]),
        ];
    }
}

// CLAUDE-CHECKPOINT
