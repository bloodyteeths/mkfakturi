<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight resource for bill list view.
 */
class BillListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'bill_date' => $this->bill_date,
            'due_date' => $this->due_date,
            'bill_number' => $this->bill_number,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'paid_status' => $this->paid_status,
            'total' => $this->total,
            'sub_total' => $this->sub_total,
            'tax' => $this->tax,
            'due_amount' => $this->due_amount,
            'discount_val' => $this->discount_val,
            'exchange_rate' => $this->exchange_rate,
            'base_total' => $this->base_total,
            'base_due_amount' => $this->base_due_amount,
            'unique_hash' => $this->unique_hash,
            'supplier_id' => $this->supplier_id,
            'company_id' => $this->company_id,
            'currency_id' => $this->currency_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'formatted_bill_date' => $this->formattedBillDate,
            'formatted_due_date' => $this->formattedDueDate,
            'overdue' => $this->overdue,
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
            ]),
            'currency' => $this->whenLoaded('currency', fn () => CurrencyResource::make($this->currency)),
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ]),
        ];
    }
}
