<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProformaInvoiceResource extends JsonResource
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
            'proforma_invoice_date' => $this->proforma_invoice_date,
            'expiry_date' => $this->expiry_date,
            'proforma_invoice_number' => $this->proforma_invoice_number,
            'proforma_invoice_prefix' => $this->proforma_invoice_prefix,
            'reference_number' => $this->reference_number,
            'customer_po_number' => $this->customer_po_number,
            'status' => $this->status,
            'notes' => $this->notes,
            'terms' => $this->terms,
            'private_notes' => $this->private_notes,
            'discount_type' => $this->discount_type,
            'discount' => $this->discount,
            'discount_val' => $this->discount_val,
            'sub_total' => $this->sub_total,
            'total' => $this->total,
            'tax' => $this->tax,
            'unique_hash' => $this->unique_hash,
            'template_name' => $this->template_name,
            'currency_id' => $this->currency_id,
            'exchange_rate' => $this->exchange_rate,
            'base_discount_val' => $this->base_discount_val,
            'base_sub_total' => $this->base_sub_total,
            'base_total' => $this->base_total,
            'base_tax' => $this->base_tax,
            'customer_id' => $this->customer_id,
            'company_id' => $this->company_id,
            'created_by' => $this->created_by,
            'converted_invoice_id' => $this->converted_invoice_id,
            'sequence_number' => $this->sequence_number,
            'customer_sequence_number' => $this->customer_sequence_number,
            'formatted_created_at' => $this->formattedCreatedAt,
            'formatted_proforma_invoice_date' => $this->formattedProformaInvoiceDate,
            'formatted_expiry_date' => $this->formattedExpiryDate,
            'is_expired' => $this->isExpired,
            'allow_edit' => $this->allow_edit,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'items' => $this->whenLoaded('items', function () {
                return ProformaInvoiceItemResource::collection($this->items);
            }),
            'customer' => $this->whenLoaded('customer', function () {
                return CustomerResource::make($this->customer);
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return UserResource::make($this->creator);
            }),
            'taxes' => $this->whenLoaded('taxes', function () {
                return TaxResource::collection($this->taxes);
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
            'converted_invoice' => $this->whenLoaded('convertedInvoice', function () {
                return InvoiceResource::make($this->convertedInvoice);
            }),
        ];
    }
}

// CLAUDE-CHECKPOINT
