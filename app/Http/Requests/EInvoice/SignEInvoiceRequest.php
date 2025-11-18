<?php

namespace App\Http\Requests\EInvoice;

use App\Models\EInvoice;
use Illuminate\Foundation\Http\FormRequest;

/**
 * SignEInvoiceRequest
 *
 * Validates request to sign an e-invoice with QES certificate.
 *
 * Validation rules:
 * - Passphrase must be provided
 * - E-invoice must have UBL XML
 * - E-invoice must not already be signed
 */
class SignEInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by the controller's authorize() call
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
            'passphrase' => [
                'required',
                'string',
                'min:1',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'passphrase.required' => 'Certificate passphrase is required to sign the e-invoice.',
            'passphrase.string' => 'Passphrase must be a string.',
            'passphrase.min' => 'Passphrase cannot be empty.',
        ];
    }

    /**
     * Validate that the e-invoice has UBL XML.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateEInvoice(EInvoice $eInvoice): void
    {
        if (empty($eInvoice->ubl_xml)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'e_invoice' => ['E-invoice has no UBL XML. Please generate the e-invoice first.'],
            ]);
        }

        if (! empty($eInvoice->ubl_xml_signed)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'e_invoice' => ['E-invoice is already signed.'],
            ]);
        }
    }
}

// CLAUDE-CHECKPOINT
