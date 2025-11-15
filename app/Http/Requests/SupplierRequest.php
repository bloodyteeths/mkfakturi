<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                Rule::unique('suppliers')->where('company_id', $this->header('company')),
            ],
            'phone' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'currency_id' => ['nullable', 'integer'],
            'address_street_1' => ['nullable', 'string', 'max:255'],
            'address_street_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'integer'],
            'zip' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->isMethod('PUT') && $this->route('supplier')) {
            $rules['email'] = [
                'nullable',
                'email',
                Rule::unique('suppliers')
                    ->where('company_id', $this->header('company'))
                    ->ignore($this->route('supplier')->id),
            ];
        }

        return $rules;
    }

    public function getSupplierPayload(): array
    {
        return collect($this->validated())
            ->merge([
                'creator_id' => $this->user()->id ?? null,
                'company_id' => $this->header('company'),
            ])
            ->toArray();
    }
}

