<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
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
            'expense_date' => $this->expense_date,
            'amount' => $this->amount,
            'notes' => $this->notes,
            'customer_id' => $this->customer_id,
            'attachment_receipt_url' => $this->receipt_url,
            'attachment_receipt' => $this->receipt,
            'attachment_receipt_meta' => $this->receipt_meta,
            'company_id' => $this->company_id,
            'expense_category_id' => $this->expense_category_id,
            'formatted_expense_date' => $this->formattedExpenseDate,
            'formatted_created_at' => $this->formattedCreatedAt,
            'exchange_rate' => $this->exchange_rate,
            'currency_id' => $this->currency_id,
            'base_amount' => $this->base_amount,
            'payment_method_id' => $this->payment_method_id,
            'customer' => $this->whenLoaded('customer', function () {
                return new CustomerResource($this->customer);
            }),
            'expense_category' => $this->whenLoaded('category', function () {
                return new ExpenseCategoryResource($this->category);
            }),
            'fields' => $this->whenLoaded('fields', function () {
                return CustomFieldValueResource::collection($this->fields);
            }),
            'company' => $this->whenLoaded('company', function () {
                return new CompanyResource($this->company);
            }),
            'currency' => $this->whenLoaded('currency', function () {
                return new CurrencyResource($this->currency);
            }),
            'payment_method' => $this->whenLoaded('paymentMethod', function () {
                return new PaymentMethodResource($this->paymentMethod);
            }),
        ];
    }
}
