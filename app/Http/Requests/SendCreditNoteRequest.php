<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Send Credit Note Request Validation
 *
 * Validates email data when sending credit notes to customers.
 * Mirrors SendInvoiceRequest pattern.
 */
class SendCreditNoteRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'subject' => [
                'required',
                'string',
                'max:255',
            ],
            'body' => [
                'required',
                'string',
            ],
            'from' => [
                'required',
                'email',
            ],
            'to' => [
                'required',
                'email',
            ],
            'cc' => [
                'nullable',
                'array',
            ],
            'cc.*' => [
                'email',
            ],
            'bcc' => [
                'nullable',
                'array',
            ],
            'bcc.*' => [
                'email',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'Email subject is required.',
            'body.required' => 'Email body is required.',
            'from.required' => 'Sender email address is required.',
            'from.email' => 'Sender must be a valid email address.',
            'to.required' => 'Recipient email address is required.',
            'to.email' => 'Recipient must be a valid email address.',
            'cc.*.email' => 'All CC recipients must be valid email addresses.',
            'bcc.*.email' => 'All BCC recipients must be valid email addresses.',
        ];
    }
}

// CLAUDE-CHECKPOINT
