<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'contact_name' => $this->contact_name,
            'tax_id' => $this->tax_id,
            'website' => $this->website,
            'currency_id' => $this->currency_id,
            'company_id' => $this->company_id,
            'formatted_created_at' => $this->formattedCreatedAt,
            'full_address' => $this->fullAddress,
            'bills' => $this->whenLoaded('bills', function () {
                return BillResource::collection($this->bills);
            }),
        ];
    }
}

