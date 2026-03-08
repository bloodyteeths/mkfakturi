<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BillPaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'bill_id' => $this->bill_id,
            'company_id' => $this->company_id,
            'creator_id' => $this->creator_id,
            'payment_number' => $this->payment_number,
            'payment_date' => $this->payment_date,
            'amount' => $this->amount,
            'exchange_rate' => $this->exchange_rate,
            'base_amount' => $this->base_amount,
            'payment_method_id' => $this->payment_method_id,
            'notes' => $this->notes,
            'formatted_payment_date' => $this->formattedPaymentDate,
            'formatted_created_at' => $this->formattedCreatedAt,
            'payment_method' => $this->whenLoaded('paymentMethod', function () {
                return new PaymentMethodResource($this->paymentMethod);
            }),
            'bill' => $this->whenLoaded('bill', function () {
                $bill = $this->bill;
                $supplier = $bill->relationLoaded('supplier') ? $bill->supplier : null;

                return [
                    'id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'supplier' => $supplier ? [
                        'id' => $supplier->id,
                        'name' => $supplier->name,
                        'currency' => $supplier->relationLoaded('currency') && $supplier->currency ? [
                            'id' => $supplier->currency->id,
                            'symbol' => $supplier->currency->symbol,
                            'code' => $supplier->currency->code,
                        ] : null,
                    ] : null,
                ];
            }),
        ];
    }
    // CLAUDE-CHECKPOINT
}
