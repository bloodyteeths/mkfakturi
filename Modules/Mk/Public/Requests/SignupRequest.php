<?php

namespace Modules\Mk\Public\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * SignupRequest
 *
 * Validation rules for public company registration
 */
class SignupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Public endpoint - no authorization required
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Company information
            'company_name' => [
                'required',
                'string',
                'max:255',
                'min:2',
            ],
            'vat_number' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Z]{2}[0-9]{8,12}$/', // EU VAT format
            ],
            'tax_id' => [
                'nullable',
                'string',
                'max:20',
            ],

            // User information
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'password_confirmation' => [
                'required',
            ],

            // Subscription plan
            'plan' => [
                'required',
                'string',
                'in:starter,standard,business,max',
            ],
            'billing_period' => [
                'required',
                'string',
                'in:monthly,yearly',
            ],

            // Referral tracking (optional)
            'referral_code' => [
                'nullable',
                'string',
                'max:50',
            ],
            'partner_id' => [
                'nullable',
                'integer',
                'exists:partners,id',
            ],
            'affiliate_link_id' => [
                'nullable',
                'integer',
                'exists:affiliate_links,id',
            ],

            // Terms acceptance
            'accept_terms' => [
                'required',
                'accepted',
            ],
            'accept_privacy' => [
                'required',
                'accepted',
            ],
        ];
    }

    /**
     * Get custom validation messages
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_name.required' => 'Company name is required.',
            'company_name.min' => 'Company name must be at least 2 characters.',
            'vat_number.regex' => 'VAT number must be in EU format (e.g., MK12345678).',
            'name.required' => 'Your name is required.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'plan.required' => 'Please select a subscription plan.',
            'plan.in' => 'Invalid subscription plan selected.',
            'billing_period.required' => 'Please select a billing period.',
            'billing_period.in' => 'Invalid billing period selected.',
            'accept_terms.required' => 'You must accept the terms of service.',
            'accept_terms.accepted' => 'You must accept the terms of service.',
            'accept_privacy.required' => 'You must accept the privacy policy.',
            'accept_privacy.accepted' => 'You must accept the privacy policy.',
        ];
    }

    /**
     * Get custom attribute names for validation errors
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'company_name' => 'company name',
            'vat_number' => 'VAT number',
            'tax_id' => 'tax ID',
            'name' => 'full name',
            'email' => 'email address',
            'password' => 'password',
            'password_confirmation' => 'password confirmation',
            'plan' => 'subscription plan',
            'billing_period' => 'billing period',
            'referral_code' => 'referral code',
            'accept_terms' => 'terms of service',
            'accept_privacy' => 'privacy policy',
        ];
    }
}

// CLAUDE-CHECKPOINT
