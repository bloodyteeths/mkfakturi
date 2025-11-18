<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Credit Note API Resource
 *
 * Transforms credit note model into JSON response.
 * Based on InvoiceResource pattern.
 */
class CreditNoteResource extends JsonResource
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
            'credit_note_date' => $this->credit_note_date,
            'credit_note_number' => $this->credit_note_number,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'tax_per_item' => $this->tax_per_item,
            'discount_per_item' => $this->discount_per_item,
            'notes' => $this->notes,
            'discount_type' => $this->discount_type,
            'discount' => $this->discount,
            'discount_val' => $this->discount_val,
            'sub_total' => $this->sub_total,
            'total' => $this->total,
            'tax' => $this->tax,
            'unique_hash' => $this->unique_hash,
            'template_name' => $this->template_name,
            'customer_id' => $this->customer_id,
            'invoice_id' => $this->invoice_id,
            'sequence_number' => $this->sequence_number,
            'customer_sequence_number' => $this->customer_sequence_number,
            'exchange_rate' => $this->exchange_rate,
            'base_discount_val' => $this->base_discount_val,
            'base_sub_total' => $this->base_sub_total,
            'base_total' => $this->base_total,
            'base_tax' => $this->base_tax,
            'creator_id' => $this->creator_id,
            'currency_id' => $this->currency_id,
            'company_id' => $this->company_id,
            'ifrs_transaction_id' => $this->ifrs_transaction_id,
            'formatted_created_at' => $this->formattedCreatedAt,
            'formatted_credit_note_date' => $this->formattedCreditNoteDate,
            'credit_note_pdf_url' => $this->creditNotePdfUrl,
            'allow_edit' => $this->allow_edit,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'items' => $this->whenLoaded('items', function () {
                return CreditNoteItemResource::collection($this->items);
            }),
            'customer' => $this->whenLoaded('customer', function () {
                return CustomerResource::make($this->customer);
            }),
            'invoice' => $this->whenLoaded('invoice', function () {
                return new InvoiceResource($this->invoice);
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
        ];
    }
}

// CLAUDE-CHECKPOINT
