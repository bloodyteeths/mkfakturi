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
            'vat_number' => $this->vat_number,
            'company_registration_number' => $this->company_registration_number,
            'website' => $this->website,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'notes' => $this->notes,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'country' => $this->country,
            'country_id' => $this->country_id,
            'currency_id' => $this->currency_id,
            'company_id' => $this->company_id,
            'formatted_created_at' => $this->formattedCreatedAt,
            'full_address' => $this->fullAddress,
            'due_amount' => $this->due_amount,
            'currency' => $this->whenLoaded('currency'),
            'bills' => $this->whenLoaded('bills', function () {
                return BillResource::collection($this->bills);
            }),
            'linked_customer' => $this->whenLoaded('linkedCustomer', function () {
                return [
                    'id' => $this->linkedCustomer->id,
                    'name' => $this->linkedCustomer->name,
                    'tax_id' => $this->linkedCustomer->tax_id,
                ];
            }),
        ];
    }
}
