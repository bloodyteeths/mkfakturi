<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight resource for proforma invoice list view.
 * Only includes fields displayed in the table to avoid
 * expensive nested serialization on every page load.
 */
class ProformaInvoiceListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'proforma_invoice_date' => $this->proforma_invoice_date,
            'expiry_date' => $this->expiry_date,
            'proforma_invoice_number' => $this->proforma_invoice_number,
            'status' => $this->status,
            'total' => $this->total,
            'sub_total' => $this->sub_total,
            'tax' => $this->tax,
            'discount_val' => $this->discount_val,
            'exchange_rate' => $this->exchange_rate,
            'base_total' => $this->base_total,
            'unique_hash' => $this->unique_hash,
            'customer_id' => $this->customer_id,
            'company_id' => $this->company_id,
            'currency_id' => $this->currency_id,
            'converted_invoice_id' => $this->converted_invoice_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'formatted_proforma_invoice_date' => $this->formattedProformaInvoiceDate,
            'formatted_expiry_date' => $this->formattedExpiryDate,
            'is_expired' => $this->isExpired,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ]),
            'currency' => $this->whenLoaded('currency', fn () => CurrencyResource::make($this->currency)),
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ]),
        ];
    }
}
