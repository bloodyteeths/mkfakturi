<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartnerAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    public function rules(): array
    {
        $companyId = $this->route('company');
        $accountId = $this->route('account');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('accounts', 'code')
                    ->where('company_id', $companyId)
                    ->ignore($accountId),
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id' => [
                'nullable',
                'exists:accounts,id',
                // Prevent self-parenting
                function ($attribute, $value, $fail) use ($accountId) {
                    if ($value && $accountId && $value == $accountId) {
                        $fail('An account cannot be its own parent.');
                    }
                },
            ],
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }
}

// CLAUDE-CHECKPOINT
