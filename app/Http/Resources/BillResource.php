<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BillResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'bill_number' => $this->bill_number,
            'bill_date' => $this->bill_date,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'paid_status' => $this->paid_status,
            'sub_total' => $this->sub_total,
            'discount' => $this->discount,
            'discount_val' => $this->discount_val,
            'tax' => $this->tax,
            'total' => $this->total,
            'due_amount' => $this->dueAmount,
            'company_id' => $this->company_id,
            'supplier_id' => $this->supplier_id,
            'currency_id' => $this->currency_id,
            'creator_id' => $this->creator_id,
            'formatted_created_at' => $this->formattedCreatedAt,
            'formatted_bill_date' => $this->formattedBillDate,
            'formatted_due_date' => $this->formattedDueDate,
            'exchange_rate' => $this->exchange_rate,
            'base_total' => $this->base_total,
            'base_sub_total' => $this->base_sub_total,
            'base_tax' => $this->base_tax,
            'base_due_amount' => $this->base_due_amount,
            'supplier' => $this->whenLoaded('supplier', function () {
                return new SupplierResource($this->supplier);
            }),
            'items' => $this->whenLoaded('items', function () {
                return BillItemResource::collection($this->items);
            }),
            'payments' => $this->whenLoaded('payments', function () {
                return BillPaymentResource::collection($this->payments);
            }),
            'currency' => $this->whenLoaded('currency', function () {
                return new CurrencyResource($this->currency);
            }),
            'company' => $this->whenLoaded('company', function () {
                return new CompanyResource($this->company);
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return new UserResource($this->creator);
            }),
        ];
    }
}

