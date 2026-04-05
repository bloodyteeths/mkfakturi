<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            "id" => $this->id,
            "payment_date" => $this->payment_date,
            "payment_number" => $this->payment_number,
            "amount" => $this->amount,
            "exchange_rate" => $this->exchange_rate,
            "base_amount" => $this->base_amount,
            "notes" => $this->notes,
            "unique_hash" => $this->unique_hash,
            "customer_id" => $this->customer_id,
            "company_id" => $this->company_id,
            "invoice_id" => $this->invoice_id,
            "payment_method_id" => $this->payment_method_id,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "formatted_payment_date" => $this->formattedPaymentDate,
            "customer" => $this->whenLoaded("customer", fn () => [
                "id" => $this->customer->id,
                "name" => $this->customer->name,
            ]),
            "invoice" => $this->whenLoaded("invoice", fn () => [
                "id" => $this->invoice->id,
                "invoice_number" => $this->invoice->invoice_number,
            ]),
            "payment_method" => $this->whenLoaded("paymentMethod", fn () => [
                "id" => $this->paymentMethod->id,
                "name" => $this->paymentMethod->name,
            ]),
            "currency" => $this->whenLoaded("currency", fn () => CurrencyResource::make($this->currency)),
        ];
    }
}
