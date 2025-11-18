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
            ])
            ->toArray();
    }
}
