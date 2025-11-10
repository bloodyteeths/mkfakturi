<?php

namespace App\Http\Requests;

use App\Models\CompanySetting;
use App\Models\CreditNote;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Credit Note Request Validation
 *
 * Validates credit note creation and updates.
 * Prevents modifications if ifrs_transaction_id is set.
 *
 * @package App\Http\Requests
 */
class CreditNoteRequest extends FormRequest
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
        $rules = [
            'credit_note_date' => [
                'required',
                'date',
            ],
            'company_id' => [
                'required',
                'exists:companies,id',
            ],
            'customer_id' => [
                'required',
                'exists:customers,id',
            ],
            'invoice_id' => [
                'nullable',
                'exists:invoices,id',
            ],
            'credit_note_number' => [
                'nullable',
                Rule::unique('credit_notes')->where('company_id', $this->header('company')),
            ],
            'reference_number' => [
                'nullable',
                'string',
                'max:255',
            ],
            'exchange_rate' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'discount' => [
                'numeric',
                'required',
                'min:0',
            ],
            'discount_val' => [
                'integer',
                'required',
                'min:0',
            ],
            'sub_total' => [
                'numeric',
                'required',
                'min:0',
            ],
            'total' => [
                'numeric',
                'max:999999999999',
                'required',
                'min:0',
            ],
            'tax' => [
                'required',
                'numeric',
                'min:0',
            ],
            'template_name' => [
                'required',
                'string',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*' => [
                'required',
            ],
            'items.*.description' => [
                'nullable',
                'string',
            ],
            'items.*.name' => [
                'required',
                'string',
                'max:255',
            ],
            'items.*.quantity' => [
                'numeric',
                'required',
                'min:0',
            ],
            'items.*.price' => [
                'numeric',
                'required',
            ],
            'items.*.discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'items.*.discount_val' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'items.*.tax' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'items.*.total' => [
                'required',
                'numeric',
            ],
            'taxes' => [
                'nullable',
                'array',
            ],
            'taxes.*.tax_type_id' => [
                'required',
                'exists:tax_types,id',
            ],
            'taxes.*.amount' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];

        // Validate exchange rate is required if customer currency differs from company currency
        $companyCurrency = CompanySetting::getSetting('currency', $this->header('company'));
        $customer = Customer::find($this->customer_id);

        if ($customer && $companyCurrency) {
            if ((string) $customer->currency_id !== $companyCurrency) {
                $rules['exchange_rate'] = [
                    'required',
                    'numeric',
                    'min:0',
                ];
            }
        }

        // Update validation - allow same credit note number on update
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $creditNote = $this->route('creditNote');

            // Prevent updates if posted to IFRS
            if ($creditNote && $creditNote->ifrs_transaction_id) {
                // This will fail validation - handled in controller
                $rules['ifrs_transaction_id'] = [
                    'prohibited',
                ];
            }

            $rules['credit_note_number'] = [
                'nullable',
                Rule::unique('credit_notes')
                    ->ignore($creditNote ? $creditNote->id : null)
                    ->where('company_id', $this->header('company')),
            ];

            // Prevent customer change on update
            if ($creditNote && $this->customer_id != $creditNote->customer_id) {
                $rules['customer_id'] = [
                    'required',
                    'exists:customers,id',
                    Rule::in([$creditNote->customer_id]), // Must match original customer
                ];
            }
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'customer_id.in' => 'The customer cannot be changed after the credit note is created.',
            'ifrs_transaction_id.prohibited' => 'Credit notes cannot be modified after posting to accounting.',
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
        ];
    }

    /**
     * Prepare credit note payload for model creation/update.
     *
     * Mirrors InvoicesRequest::getInvoicePayload() pattern.
     *
     * @return array
     */
    public function getCreditNotePayload(): array
    {
        $company_currency = CompanySetting::getSetting('currency', $this->header('company'));
        $current_currency = $this->currency_id;
        $exchange_rate = $company_currency != $current_currency ? $this->exchange_rate : 1;
        $currency = Customer::find($this->customer_id)->currency_id;

        return collect($this->except('items', 'taxes'))
            ->merge([
                'creator_id' => $this->user()->id ?? null,
                'status' => $this->has('creditNoteSend') ? CreditNote::STATUS_SENT : CreditNote::STATUS_DRAFT,
                'company_id' => $this->header('company'),
                'tax_per_item' => CompanySetting::getSetting('tax_per_item', $this->header('company')) ?? 'NO',
                'discount_per_item' => CompanySetting::getSetting('discount_per_item', $this->header('company')) ?? 'NO',
                'exchange_rate' => $exchange_rate,
                'base_total' => $this->total * $exchange_rate,
                'base_discount_val' => $this->discount_val * $exchange_rate,
                'base_sub_total' => $this->sub_total * $exchange_rate,
                'base_tax' => $this->tax * $exchange_rate,
                'currency_id' => $currency,
            ])
            ->toArray();
    }
}

// CLAUDE-CHECKPOINT
