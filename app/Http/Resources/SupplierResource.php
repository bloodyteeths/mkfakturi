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
            'address_street_1' => $this->address_street_1,
            'address_street_2' => $this->address_street_2,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'country' => $this->country,
            'currency_id' => $this->currency_id,
            'company_id' => $this->company_id,
            'formatted_created_at' => $this->formattedCreatedAt,
            'full_address' => $this->fullAddress,
            'due_amount' => $this->due_amount,
            'currency' => $this->whenLoaded('currency'),
            'bills' => $this->whenLoaded('bills', function () {
                return BillResource::collection($this->bills);
            }),
        ];
    }
}
// CLAUDE-CHECKPOINT
