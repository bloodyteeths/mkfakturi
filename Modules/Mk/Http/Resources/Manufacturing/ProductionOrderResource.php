<?php

namespace Modules\Mk\Http\Resources\Manufacturing;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductionOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'currency_id' => $this->currency_id,
            'bom_id' => $this->bom_id,
            'order_number' => $this->order_number,
            'order_date' => $this->order_date,
            'expected_completion_date' => $this->expected_completion_date,
            'completed_at' => $this->completed_at,
            'status' => $this->status,
            'output_item_id' => $this->output_item_id,
            'planned_quantity' => $this->planned_quantity,
            'actual_quantity' => $this->actual_quantity,
            'output_warehouse_id' => $this->output_warehouse_id,
            'total_material_cost' => $this->total_material_cost,
            'total_labor_cost' => $this->total_labor_cost,
            'total_overhead_cost' => $this->total_overhead_cost,
            'total_wastage_cost' => $this->total_wastage_cost,
            'total_production_cost' => $this->total_production_cost,
            'cost_per_unit' => $this->cost_per_unit,
            'material_variance' => $this->material_variance,
            'labor_variance' => $this->labor_variance,
            'total_variance' => $this->total_variance,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'output_item' => $this->whenLoaded('outputItem', fn () => [
                'id' => $this->outputItem->id,
                'name' => $this->outputItem->name,
                'unit_id' => $this->outputItem->unit_id ?? null,
            ]),
            'bom' => $this->whenLoaded('bom', fn () => [
                'id' => $this->bom->id,
                'name' => $this->bom->name,
                'code' => $this->bom->code,
            ]),
            'currency' => $this->whenLoaded('currency', fn () => [
                'id' => $this->currency->id,
                'name' => $this->currency->name,
                'code' => $this->currency->code,
                'symbol' => $this->currency->symbol,
            ]),
            'output_warehouse' => $this->whenLoaded('outputWarehouse', fn () => [
                'id' => $this->outputWarehouse->id,
                'name' => $this->outputWarehouse->name,
            ]),
            'materials' => $this->whenLoaded('materials', fn () => ProductionOrderMaterialResource::collection($this->materials)),
            'labor_entries' => $this->whenLoaded('laborEntries', fn () => $this->laborEntries->toArray()),
            'overhead_entries' => $this->whenLoaded('overheadEntries', fn () => $this->overheadEntries->toArray()),
            'co_production_outputs' => $this->whenLoaded('coProductionOutputs', fn () => $this->coProductionOutputs->map(fn ($o) => [
                'id' => $o->id,
                'item_id' => $o->item_id,
                'quantity' => $o->quantity,
                'allocation_percent' => $o->allocation_percent,
                'allocated_cost' => $o->allocated_cost,
                'cost_per_unit' => $o->cost_per_unit,
                'is_primary' => $o->is_primary,
                'item' => $o->relationLoaded('item') && $o->item ? [
                    'id' => $o->item->id,
                    'name' => $o->item->name,
                ] : null,
            ])),
            'created_by' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'approved_by' => $this->whenLoaded('approvedBy', fn () => [
                'id' => $this->approvedBy->id,
                'name' => $this->approvedBy->name,
            ]),
        ];
    }
}

// CLAUDE-CHECKPOINT
