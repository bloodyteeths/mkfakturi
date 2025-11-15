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
                return new BillResource($this->bill);
            }),
        ];
    }
}

