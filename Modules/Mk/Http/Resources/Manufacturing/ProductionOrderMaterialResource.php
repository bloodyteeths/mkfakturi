<?php

namespace Modules\Mk\Http\Resources\Manufacturing;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductionOrderMaterialResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'production_order_id' => $this->production_order_id,
            'item_id' => $this->item_id,
            'warehouse_id' => $this->warehouse_id,
            'planned_quantity' => $this->planned_quantity,
            'planned_unit_cost' => $this->planned_unit_cost,
            'actual_quantity' => $this->actual_quantity,
            'actual_unit_cost' => $this->actual_unit_cost,
            'actual_total_cost' => $this->actual_total_cost,
            'wastage_quantity' => $this->wastage_quantity,
            'wastage_cost' => $this->wastage_cost,
            'quantity_variance' => $this->quantity_variance,
            'cost_variance' => $this->cost_variance,
            'notes' => $this->notes,
            'item' => $this->whenLoaded('item', fn () => [
                'id' => $this->item->id,
                'name' => $this->item->name,
                'unit_id' => $this->item->unit_id ?? null,
            ]),
            'warehouse' => $this->whenLoaded('warehouse', fn () => [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
            ]),
        ];
    }
}

// CLAUDE-CHECKPOINT
