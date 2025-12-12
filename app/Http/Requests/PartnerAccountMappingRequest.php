<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PartnerAccountMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->route('company');

        return [
            'entity_type' => 'required|in:customer,supplier,expense_category,payment_method,tax,default',
            'entity_id' => 'nullable|integer',
            'transaction_type' => 'required|in:invoice,payment,expense,default',
            'debit_account_id' => [
                'nullable',
                'exists:accounts,id',
            ],
            'credit_account_id' => [
                'nullable',
                'exists:accounts,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'entity_type.in' => 'Entity type must be: customer, supplier, expense_category, payment_method, tax, or default',
            'transaction_type.in' => 'Transaction type must be: invoice, payment, expense, or default',
        ];
    }
}

// CLAUDE-CHECKPOINT
