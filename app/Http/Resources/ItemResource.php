<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
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
            'description' => $this->description,
            'price' => $this->price,
            'cost' => $this->cost,
            'unit_id' => $this->unit_id,
            'company_id' => $this->company_id,
            'creator_id' => $this->creator_id,
            'currency_id' => $this->currency_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'tax_per_item' => $this->tax_per_item,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'track_quantity' => $this->track_quantity,
            'allow_negative_stock' => $this->allow_negative_stock,
            'quantity' => $this->quantity,
            'minimum_quantity' => $this->minimum_quantity,
            'category' => $this->category,
            'category_id' => $this->category_id,
            'formatted_created_at' => $this->formattedCreatedAt,
            'unit' => $this->whenLoaded('unit', function () {
                return UnitResource::make($this->unit);
            }),
            'company' => $this->whenLoaded('company', function () {
                return CompanyResource::make($this->company);
            }),
            'taxes' => $this->whenLoaded('taxes', function () {
                return TaxResource::collection($this->taxes);
            }),
            'currency' => $this->whenLoaded('currency', function () {
                return CurrencyResource::make($this->currency);
            }),
        ];
    }

    // CLAUDE-CHECKPOINT
}
