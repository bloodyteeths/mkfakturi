<?php

namespace Modules\Mk\Http\Resources\Manufacturing;

use Illuminate\Http\Resources\Json\JsonResource;

class BomResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'currency_id' => $this->currency_id,
            'name' => $this->name,
            'code' => $this->code,
            'output_item_id' => $this->output_item_id,
            'output_quantity' => $this->output_quantity,
            'output_unit_id' => $this->output_unit_id,
            'description' => $this->description,
            'expected_wastage_percent' => $this->expected_wastage_percent,
            'labor_cost_per_unit' => $this->labor_cost_per_unit,
            'overhead_cost_per_unit' => $this->overhead_cost_per_unit,
            'is_active' => $this->is_active,
            'version' => $this->version,
            'approved_at' => $this->approved_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'output_item' => $this->whenLoaded('outputItem', fn () => [
                'id' => $this->outputItem->id,
                'name' => $this->outputItem->name,
                'unit_id' => $this->outputItem->unit_id ?? null,
            ]),
            'output_unit' => $this->whenLoaded('outputUnit', fn () => [
                'id' => $this->outputUnit->id,
                'name' => $this->outputUnit->name,
            ]),
            'currency' => $this->whenLoaded('currency', fn () => [
                'id' => $this->currency->id,
                'name' => $this->currency->name,
                'code' => $this->currency->code,
                'symbol' => $this->currency->symbol,
            ]),
            'lines' => $this->whenLoaded('lines', fn () => BomLineResource::collection($this->lines)),
            'created_by' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'approved_by' => $this->whenLoaded('approvedBy', fn () => [
                'id' => $this->approvedBy->id,
                'name' => $this->approvedBy->name,
            ]),
            'normative_cost' => $this->when(
                $request->routeIs('*.show') || $this->relationLoaded('lines'),
                fn () => $this->calculateNormativeCost()
            ),
        ];
    }
}

// CLAUDE-CHECKPOINT
