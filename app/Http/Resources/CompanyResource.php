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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'vat_id' => $this->vat_id,
            'tax_id' => $this->tax_id,
            'logo' => $this->logo,
            'logo_path' => $this->logo_path,
            'unique_hash' => $this->unique_hash,
            'owner_id' => $this->owner_id,
            'slug' => $this->slug,
            'address' => $this->when($this->relationLoaded('address') && $this->address, function () {
                return new AddressResource($this->address);
            }),
            'roles' => $this->when($this->relationLoaded('roles'), function () {
                return RoleResource::collection($this->roles);
            }, function () {
                // Fall back to fetching roles if not eager loaded
                return RoleResource::collection($this->roles);
            }),
        ];
    }
}
