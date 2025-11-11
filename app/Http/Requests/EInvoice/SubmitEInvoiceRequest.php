<?php

namespace App\Http\Requests\EInvoice;

use App\Models\EInvoice;
use Illuminate\Foundation\Http\FormRequest;

/**
 * SubmitEInvoiceRequest
 *
 * Validates request to submit an e-invoice to the tax authority.
 *
 * Validation rules:
 * - E-invoice must be signed
 * - E-invoice must not already be accepted
 * - E-invoice must have signed UBL XML
 */
class SubmitEInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
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
        // No request body parameters needed for submission
        // Validation is done on the e-invoice state
        return [];
    }

    /**
     * Validate that the e-invoice is ready for submission.
     *
     * @param EInvoice $eInvoice
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateEInvoice(EInvoice $eInvoice): void
    {
        $errors = [];

        // Check if e-invoice is signed
        if (!$eInvoice->isSigned()) {
            $errors['e_invoice'][] = 'E-invoice must be signed before submission.';
        }

        // Check if has signed XML
        if (empty($eInvoice->ubl_xml_signed)) {
            $errors['e_invoice'][] = 'E-invoice has no signed UBL XML.';
        }

        // Check if already accepted
        if ($eInvoice->isAccepted()) {
            $errors['e_invoice'][] = 'E-invoice has already been accepted by the tax authority.';
        }

        // Check if has active certificate
        if (!$eInvoice->certificate_id) {
            $errors['e_invoice'][] = 'E-invoice has no associated certificate.';
        }

        if (!empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [];
    }
}

// CLAUDE-CHECKPOINT
