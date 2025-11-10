<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProformaInvoiceItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'discount_type' => $this->discount_type,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'unit_name' => $this->unit_name,
            'discount' => $this->discount,
            'discount_val' => $this->discount_val,
            'tax' => $this->tax,
            'total' => $this->total,
            'proforma_invoice_id' => $this->proforma_invoice_id,
            'item_id' => $this->item_id,
            'company_id' => $this->company_id,
            'exchange_rate' => $this->exchange_rate,
            'base_price' => $this->base_price,
            'base_discount_val' => $this->base_discount_val,
            'base_tax' => $this->base_tax,
            'base_total' => $this->base_total,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'taxes' => $this->whenLoaded('taxes', function () {
                return TaxResource::collection($this->taxes);
            }),
            'fields' => $this->whenLoaded('fields', function () {
                return CustomFieldValueResource::collection($this->fields);
            }),
            'item' => $this->whenLoaded('item', function () {
                return ItemResource::make($this->item);
            }),
        ];
    }
}

// CLAUDE-CHECKPOINT
