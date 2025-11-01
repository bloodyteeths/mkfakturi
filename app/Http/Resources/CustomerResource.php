<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'contact_name' => $this->contact_name,
            'company_name' => $this->company_name,
            'website' => $this->website,
            'enable_portal' => $this->enable_portal,
            'password_added' => $this->password ? true : false,
            'currency_id' => $this->currency_id,
            'company_id' => $this->company_id,
            'facebook_id' => $this->facebook_id,
            'google_id' => $this->google_id,
            'github_id' => $this->github_id,
            'created_at' => $this->created_at,
            'formatted_created_at' => $this->formattedCreatedAt,
            'updated_at' => $this->updated_at,
            'avatar' => $this->avatar,
            'due_amount' => $this->due_amount,
            'base_due_amount' => $this->base_due_amount,
            'prefix' => $this->prefix,
            'tax_id' => $this->tax_id,
            'billing' => $this->whenLoaded('billingAddress', function () {
                return AddressResource::make($this->billingAddress);
            }),
            'shipping' => $this->whenLoaded('shippingAddress', function () {
                return AddressResource::make($this->shippingAddress);
            }),
            'fields' => $this->whenLoaded('fields', function () {
                return CustomFieldValueResource::collection($this->fields);
            }),
            'company' => $this->whenLoaded('company', function () {
                return CompanyResource::make($this->company);
            }),
            'currency' => $this->whenLoaded('currency', function () {
                return CurrencyResource::make($this->currency);
            }),
        ];
    }
}
