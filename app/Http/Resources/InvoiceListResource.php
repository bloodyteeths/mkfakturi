<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            "id" => $this->id,
            "invoice_date" => $this->invoice_date,
            "due_date" => $this->due_date,
            "invoice_number" => $this->invoice_number,
            "reference_number" => $this->reference_number,
            "status" => $this->status,
            "paid_status" => $this->paid_status,
            "total" => $this->total,
            "sub_total" => $this->sub_total,
            "tax" => $this->tax,
            "due_amount" => $this->due_amount,
            "discount_val" => $this->discount_val,
            "exchange_rate" => $this->exchange_rate,
            "base_total" => $this->base_total,
            "base_due_amount" => $this->base_due_amount,
            "unique_hash" => $this->unique_hash,
            "customer_id" => $this->customer_id,
            "company_id" => $this->company_id,
            "currency_id" => $this->currency_id,
            "type" => $this->type ?? "standard",
            "is_reverse_charge" => (bool) ($this->is_reverse_charge ?? false),
            "sent" => $this->sent,
            "viewed" => $this->viewed,
            "sequence_number" => $this->sequence_number,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "formatted_invoice_date" => $this->formattedInvoiceDate,
            "formatted_due_date" => $this->formattedDueDate,
            "overdue" => $this->overdue,
            "customer" => $this->whenLoaded("customer", fn () => [
                "id" => $this->customer->id,
                "name" => $this->customer->name,
                "email" => $this->customer->email ?? null,
            ]),
            "currency" => $this->whenLoaded("currency", fn () => CurrencyResource::make($this->currency)),
            "company" => $this->whenLoaded("company", fn () => [
                "id" => $this->company->id,
                "name" => $this->company->name,
            ]),
        ];
    }
}
