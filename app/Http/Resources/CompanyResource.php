<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'vat_id' => $this->vat_id,
            'vat_number' => $this->vat_number ?? $this->vat_id, // Fallback to vat_id for compatibility
            'tax_id' => $this->tax_id,
            'logo' => $this->logo,
            'logo_path' => $this->logo_path,
            'unique_hash' => $this->unique_hash,
            'owner_id' => $this->owner_id,
            'slug' => $this->slug,
            'address' => $this->whenLoaded('address', function () {
                return new AddressResource($this->address);
            }),
            'roles' => $this->whenLoaded('roles', function () {
                return RoleResource::collection($this->roles);
            }),
            // FG-01-12: Include subscription data for feature gating
            'subscription' => $this->whenLoaded('subscription', function () {
                return [
                    'plan' => $this->subscription->plan ?? 'free',
                    'status' => $this->subscription->status ?? 'inactive',
                    'trial_ends_at' => $this->subscription->trial_ends_at,
                    'on_trial' => $this->subscription->onTrial() ?? false,
                ];
            }),
        ];

        return $data;
    }
}
