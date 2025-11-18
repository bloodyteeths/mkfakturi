<?php

namespace App\Http\Requests\EInvoice;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

/**
 * GenerateEInvoiceRequest
 *
 * Validates request to generate UBL XML from an invoice.
 *
 * Validation rules:
 * - Invoice must exist
 * - Invoice must belong to the current company
 * - Invoice must be in SENT status (finalized)
 */
class GenerateEInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by the controller's authorize() call
        // This just validates the data
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
            'invoice_id' => [
                'required',
                'integer',
                'exists:invoices,id',
                function ($attribute, $value, $fail) {
                    $invoice = Invoice::find($value);

                    if (! $invoice) {
                        $fail('The selected invoice does not exist.');

                        return;
                    }

                    // Check company scope
                    $companyId = request()->header('company');
                    if ($invoice->company_id != $companyId) {
                        $fail('The selected invoice does not belong to your company.');

                        return;
                    }

                    // Check invoice status
                    if ($invoice->status !== Invoice::STATUS_SENT) {
                        $fail('Invoice must be in SENT status to generate e-invoice. Current status: '.$invoice->status);
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'invoice_id.required' => 'Invoice ID is required.',
            'invoice_id.integer' => 'Invoice ID must be an integer.',
            'invoice_id.exists' => 'The selected invoice does not exist.',
        ];
    }

    /**
     * Get validated invoice instance.
     */
    public function getInvoice(): Invoice
    {
        return Invoice::findOrFail($this->validated('invoice_id'));
    }
}

// CLAUDE-CHECKPOINT
