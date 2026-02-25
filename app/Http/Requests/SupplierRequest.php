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

    private const MACEDONIA_COUNTRY_ID = 129;

    public function rules(): array
    {
        $isMacedonian = (int) $this->input('country_id') === self::MACEDONIA_COUNTRY_ID;

        $taxIdRules = $isMacedonian
            ? ['required', 'string', 'regex:/^\d{7}$/']
            : ['nullable', 'string', 'max:255'];

        $vatRules = $isMacedonian
            ? ['nullable', 'string', 'regex:/^\d{13}$/']
            : ['nullable', 'string', 'max:255'];

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('suppliers')->where('company_id', $this->header('company')),
            ],
            'phone' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => $taxIdRules,
            'vat_number' => $vatRules,
            'company_registration_number' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'currency_id' => ['nullable', 'integer'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'integer'],
            'zip' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->isMethod('PUT') && $this->route('supplier')) {
            $rules['email'] = [
                'required',
                'email',
                Rule::unique('suppliers')
                    ->where('company_id', $this->header('company'))
                    ->ignore($this->route('supplier')->id),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'tax_id.required' => __('validation.tax_id_required_mk'),
            'tax_id.regex' => __('validation.tax_id_format_mk'),
            'vat_number.regex' => __('validation.vat_number_format_mk'),
        ];
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
