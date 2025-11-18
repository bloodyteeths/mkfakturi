<?php

namespace App\Http\Requests;

use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\ProformaInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProformaInvoiceRequest extends FormRequest
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
            'proforma_invoice_date' => [
                'required',
                'date',
            ],
            'expiry_date' => [
                'required',
                'date',
                'after_or_equal:proforma_invoice_date',
            ],
            'customer_id' => [
                'required',
                'exists:customers,id',
            ],
            'proforma_invoice_number' => [
                'required',
                Rule::unique('proforma_invoices')->where('company_id', $this->header('company')),
            ],
            'reference_number' => [
                'nullable',
                'string',
                'max:255',
            ],
            'customer_po_number' => [
                'nullable',
                'string',
                'max:255',
            ],
            'exchange_rate' => [
                'nullable',
                'numeric',
            ],
            'discount' => [
                'numeric',
                'required',
            ],
            'discount_val' => [
                'integer',
                'required',
            ],
            'sub_total' => [
                'numeric',
                'required',
            ],
            'total' => [
                'numeric',
                'max:999999999999',
                'required',
            ],
            'tax' => [
                'required',
                'numeric',
            ],
            'template_name' => [
                'required',
                'string',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
            'terms' => [
                'nullable',
                'string',
            ],
            'private_notes' => [
                'nullable',
                'string',
            ],
            'items' => [
                'required',
                'array',
            ],
            'items.*' => [
                'required',
            ],
            'items.*.description' => [
                'nullable',
            ],
            'items.*.name' => [
                'required',
            ],
            'items.*.quantity' => [
                'numeric',
                'required',
            ],
            'items.*.price' => [
                'numeric',
                'required',
            ],
        ];

        // Check if exchange rate is required based on customer currency
        $companyCurrency = CompanySetting::getSetting('currency', $this->header('company'));
        $customer = Customer::find($this->customer_id);

        if ($customer && $companyCurrency) {
            if ((string) $customer->currency_id !== $companyCurrency) {
                $rules['exchange_rate'] = [
                    'required',
                    'numeric',
                ];
            }
        }

        // Update validation for PUT requests
        if ($this->isMethod('PUT')) {
            $rules['proforma_invoice_number'] = [
                'required',
                Rule::unique('proforma_invoices')
                    ->ignore($this->route('proformaInvoice')->id)
                    ->where('company_id', $this->header('company')),
            ];
        }

        return $rules;
    }

    /**
     * Get the proforma invoice payload from request
     */
    public function getProformaInvoicePayload(): array
    {
        $company_currency = CompanySetting::getSetting('currency', $this->header('company'));
        $current_currency = $this->currency_id;
        $exchange_rate = $company_currency != $current_currency ? $this->exchange_rate : 1;
        $currency = Customer::find($this->customer_id)->currency_id;

        return collect($this->except('items', 'taxes'))
            ->merge([
                'created_by' => $this->user()->id ?? null,
                'status' => $this->has('proformaInvoiceSend') ? ProformaInvoice::STATUS_SENT : ProformaInvoice::STATUS_DRAFT,
                'company_id' => $this->header('company'),
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
