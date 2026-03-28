<?php

namespace App\Http\Requests;

use App\Models\Address;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    private const MACEDONIA_COUNTRY_ID = 129;

    public function rules(): array
    {
        $isMacedonian = (int) $this->input('billing.country_id') === self::MACEDONIA_COUNTRY_ID;

        $taxIdRules = $isMacedonian
            ? ['nullable', 'string', 'regex:/^\d{7}$/']
            : ['nullable', 'string', 'max:255'];

        $rules = [
            'name' => [
                'required',
            ],
            'email' => [
                'email',
                'nullable',
                Rule::unique('customers')->where('company_id', $this->header('company')),
            ],
            'password' => [
                'nullable',
            ],
            'phone' => [
                'nullable',
            ],
            'viber_phone' => [
                'nullable',
                'string',
                'max:20',
            ],
            'company_name' => [
                'nullable',
            ],
            'contact_name' => [
                'nullable',
            ],
            'website' => [
                'nullable',
            ],
            'prefix' => [
                'nullable',
            ],
            'tax_id' => $taxIdRules,
            'vat_number' => [
                'nullable',
                'string',
                'max:20',
            ],
            'bank_account' => [
                'nullable',
                'string',
                'max:50',
            ],
            'bank_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'enable_portal' => [
                'boolean',
            ],
            'currency_id' => [
                'nullable',
            ],
            'billing.name' => [
                'nullable',
            ],
            'billing.address_street_1' => [
                'nullable',
            ],
            'billing.address_street_2' => [
                'nullable',
            ],
            'billing.city' => [
                'nullable',
            ],
            'billing.state' => [
                'nullable',
            ],
            'billing.country_id' => [
                'nullable',
            ],
            'billing.zip' => [
                'nullable',
            ],
            'billing.phone' => [
                'nullable',
            ],
            'billing.fax' => [
                'nullable',
            ],
            'allow_duplicate' => [
                'nullable',
                'boolean',
            ],
            'shipping.name' => [
                'nullable',
            ],
            'shipping.address_street_1' => [
                'nullable',
            ],
            'shipping.address_street_2' => [
                'nullable',
            ],
            'shipping.city' => [
                'nullable',
            ],
            'shipping.state' => [
                'nullable',
            ],
            'shipping.country_id' => [
                'nullable',
            ],
            'shipping.zip' => [
                'nullable',
            ],
            'shipping.phone' => [
                'nullable',
            ],
            'shipping.fax' => [
                'nullable',
            ],
        ];

        if ($this->isMethod('PUT') && $this->email != null) {
            $rules['email'] = [
                'email',
                'nullable',
                Rule::unique('customers')->where('company_id', $this->header('company'))->ignore($this->route('customer')->id),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'tax_id.required' => __('validation.tax_id_required_mk'),
            'tax_id.regex' => __('validation.tax_id_format_mk'),
        ];
    }

    public function getCustomerPayload()
    {
        return collect($this->validated())
            ->only([
                'name',
                'email',
                'currency_id',
                'password',
                'phone',
                'viber_phone',
                'prefix',
                'tax_id',
                'vat_number',
                'bank_account',
                'bank_name',
                'company_name',
                'contact_name',
                'website',
                'enable_portal',
                'estimate_prefix',
                'payment_prefix',
                'invoice_prefix',
            ])
            ->merge([
                'creator_id' => $this->user()->id,
                'company_id' => $this->header('company'),
            ])
            ->toArray();
    }

    public function getShippingAddress()
    {
        return collect($this->shipping)
            ->merge([
                'type' => Address::SHIPPING_TYPE,
            ])
            ->toArray();
    }

    public function getBillingAddress()
    {
        return collect($this->billing)
            ->merge([
                'type' => Address::BILLING_TYPE,
            ])
            ->toArray();
    }

    public function hasAddress(array $address)
    {
        $data = Arr::where($address, function ($value, $key) {
            return isset($value);
        });

        return $data;
    }

    public function allowsDuplicate(): bool
    {
        return (bool) $this->input('allow_duplicate', false);
    }
}
