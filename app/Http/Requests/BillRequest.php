<?php

namespace App\Http\Requests;

use App\Models\Bill;
use App\Models\CompanySetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'bill_date' => ['required'],
            'due_date' => ['nullable'],
            'supplier_id' => ['required', 'integer'],
            'bill_number' => [
                'required',
                Rule::unique('bills')->where('company_id', $this->header('company')),
            ],
            'exchange_rate' => ['nullable'],
            'discount' => ['numeric', 'required'],
            'discount_val' => ['integer', 'required'],
            'sub_total' => ['numeric', 'required'],
            'total' => ['numeric', 'max:999999999999', 'required'],
            'tax' => ['required'],
            'items' => ['required', 'array'],
            'items.*' => ['required'],
            'items.*.description' => ['nullable'],
            'items.*.name' => ['required'],
            'items.*.quantity' => ['numeric', 'required'],
            'items.*.price' => ['numeric', 'required'],
            'currency_id' => ['required', 'integer'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'allow_duplicate' => ['nullable', 'boolean'],
        ];

        if ($this->isMethod('PUT') && $this->route('bill')) {
            $rules['bill_number'] = [
                'required',
                Rule::unique('bills')
                    ->where('company_id', $this->header('company'))
                    ->ignore($this->route('bill')->id),
            ];
        }

        $companyCurrency = CompanySetting::getSetting('currency', $this->header('company'));

        if ($companyCurrency && $this->currency_id) {
            if ((string) $this->currency_id !== $companyCurrency) {
                $rules['exchange_rate'] = ['required'];
            }
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateTaxAmounts($validator);
        });
    }

    /**
     * Validate that tax amounts don't exceed item subtotals.
     */
    protected function validateTaxAmounts($validator): void
    {
        $items = $this->input('items', []);
        if (empty($items)) {
            return;
        }

        foreach ($items as $index => $itemData) {
            $quantity = (float) ($itemData['quantity'] ?? 0);
            $price = (float) ($itemData['price'] ?? 0);
            $itemSubTotal = $quantity * $price;

            if ($itemSubTotal <= 0) {
                continue;
            }

            $taxes = $itemData['taxes'] ?? [];
            $totalTaxAmount = 0;

            foreach ($taxes as $tax) {
                $totalTaxAmount += (float) ($tax['amount'] ?? 0);
            }

            if ($totalTaxAmount > $itemSubTotal) {
                $validator->errors()->add(
                    "items.{$index}.taxes",
                    __('validation.tax_exceeds_subtotal', [
                        'item' => $itemData['name'] ?? "#{$index}",
                    ])
                );
            }
        }
    }

    public function getBillPayload(): array
    {
        $companyCurrency = CompanySetting::getSetting('currency', $this->header('company'));
        $currentCurrency = $this->currency_id;
        $exchangeRate = $companyCurrency != $currentCurrency ? $this->exchange_rate : 1;

        return collect($this->except('items', 'taxes'))
            ->merge([
                'creator_id' => $this->user()->id ?? null,
                'status' => Bill::STATUS_DRAFT,
                'paid_status' => Bill::PAID_STATUS_UNPAID,
                'company_id' => $this->header('company'),
                'due_amount' => $this->total,
                'exchange_rate' => $exchangeRate,
                'base_total' => $this->total * $exchangeRate,
                'base_discount_val' => $this->discount_val * $exchangeRate,
                'base_sub_total' => $this->sub_total * $exchangeRate,
                'base_tax' => $this->tax * $exchangeRate,
                'base_due_amount' => $this->total * $exchangeRate,
                'project_id' => $this->project_id,
            ])
            ->toArray();
    }

    public function allowsDuplicate(): bool
    {
        return (bool) $this->input('allow_duplicate', false);
    }
}
