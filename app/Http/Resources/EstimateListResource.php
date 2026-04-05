<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class EstimateListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            "id" => $this->id,
            "estimate_date" => $this->estimate_date,
            "expiry_date" => $this->expiry_date,
            "estimate_number" => $this->estimate_number,
            "reference_number" => $this->reference_number,
            "status" => $this->status,
            "total" => $this->total,
            "sub_total" => $this->sub_total,
            "tax" => $this->tax,
            "discount_val" => $this->discount_val,
            "exchange_rate" => $this->exchange_rate,
            "base_total" => $this->base_total,
            "unique_hash" => $this->unique_hash,
            "customer_id" => $this->customer_id,
            "company_id" => $this->company_id,
            "currency_id" => $this->currency_id,
            "created_at" => $this->created_at,
            "name" => $this->name,
            "formatted_estimate_date" => $this->formattedEstimateDate,
            "formatted_expiry_date" => $this->formattedExpiryDate,
        ];
    }
}
