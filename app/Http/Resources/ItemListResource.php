<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "description" => $this->description,
            "price" => $this->price,
            "cost" => $this->cost,
            "unit_id" => $this->unit_id,
            "company_id" => $this->company_id,
            "currency_id" => $this->currency_id,
            "sku" => $this->sku,
            "barcode" => $this->barcode,
            "track_quantity" => $this->track_quantity,
            "quantity" => $this->quantity,
            "category" => $this->category,
            "category_id" => $this->category_id,
            "image_url" => $this->image_url,
            "retail_price" => $this->retail_price,
            "wholesale_price" => $this->wholesale_price,
            "tax_per_item" => $this->tax_per_item,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "unit" => $this->whenLoaded("unit", fn () => ["id" => $this->unit->id, "name" => $this->unit->name]),
            "currency" => $this->whenLoaded("currency", fn () => CurrencyResource::make($this->currency)),
            "taxes" => $this->whenLoaded("taxes", fn () => TaxResource::collection($this->taxes)),
        ];
    }
}
