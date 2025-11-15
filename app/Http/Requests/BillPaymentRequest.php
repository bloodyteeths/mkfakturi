<?php

namespace App\Http\Requests;

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\CompanySetting;
use Illuminate\Foundation\Http\FormRequest;

class BillPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric'],
            'payment_method_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function getBillPaymentPayload(Bill $bill): array
    {
        $companyCurrency = CompanySetting::getSetting('currency', $this->header('company'));
        $currentCurrency = $bill->currency_id;
        $exchangeRate = $companyCurrency != $currentCurrency ? ($bill->exchange_rate ?? 1) : 1;

        return [
            'bill_id' => $bill->id,
            'company_id' => $this->header('company'),
            'creator_id' => $this->user()->id ?? null,
            'payment_date' => $this->payment_date,
            'amount' => $this->amount,
            'payment_method_id' => $this->payment_method_id,
            'notes' => $this->notes,
            'exchange_rate' => $exchangeRate,
            'base_amount' => $this->amount * $exchangeRate,
            'posted_to_ifrs' => false,
        ];
    }
}

