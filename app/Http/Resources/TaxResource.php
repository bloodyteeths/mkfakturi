<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaxResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        $taxType = $this->whenLoaded('taxType');

        return [
            'id' => $this->id,
            'tax_type_id' => $this->tax_type_id,
            'invoice_id' => $this->invoice_id,
            'estimate_id' => $this->estimate_id,
            'invoice_item_id' => $this->invoice_item_id,
            'estimate_item_id' => $this->estimate_item_id,
            'item_id' => $this->item_id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'amount' => $this->amount,
            'percent' => $this->percent,
            'calculation_type' => $this->calculation_type,
            'fixed_amount' => $this->fixed_amount,
            'compound_tax' => $this->compound_tax,
            'base_amount' => $this->base_amount,
            'currency_id' => $this->currency_id,
            'type' => optional($taxType)->type,
            'recurring_invoice_id' => $this->recurring_invoice_id,
            'tax_type' => TaxTypeResource::make($taxType),
            'currency' => CurrencyResource::make($this->whenLoaded('currency')),
        ];
    }
}
