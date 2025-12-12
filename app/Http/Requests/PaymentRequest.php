<?php

namespace App\Http\Requests;

use App\Models\CompanySetting;
use App\Models\Customer;
use App\Services\PeriodLockService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
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
            'payment_date' => [
                'required',
            ],
            'customer_id' => [
                'required',
            ],
            'exchange_rate' => [
                'nullable',
            ],
            'amount' => [
                'required',
            ],
            'payment_number' => [
                'required',
                Rule::unique('payments')->where('company_id', $this->header('company')),
            ],
            'invoice_id' => [
                'nullable',
            ],
            'payment_method_id' => [
                'nullable',
            ],
            'notes' => [
                'nullable',
            ],
            'project_id' => [
                'nullable',
                'integer',
                'exists:projects,id',
            ],
        ];

        if ($this->isMethod('PUT')) {
            $rules['payment_number'] = [
                'required',
                Rule::unique('payments')
                    ->ignore($this->route('payment')->id)
                    ->where('company_id', $this->header('company')),
            ];
        }

        $companyCurrency = CompanySetting::getSetting('currency', $this->header('company'));

        $customer = Customer::find($this->customer_id);

        if ($customer && $companyCurrency) {
            if ((string) $customer->currency_id !== $companyCurrency) {
                $rules['exchange_rate'] = [
                    'required',
                ];
            }
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     * Adds period lock validation.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validatePeriodLock($validator);
        });
    }

    /**
     * Validate that the payment date is not in a locked period.
     */
    protected function validatePeriodLock($validator): void
    {
        $companyId = $this->header('company');
        $paymentDate = $this->input('payment_date');

        if (! $companyId || ! $paymentDate) {
            return;
        }

        $lockService = app(PeriodLockService::class);

        // Check if the new date is locked
        if ($lockService->isDateLocked($companyId, $paymentDate)) {
            $lockReason = $lockService->getLockReason($companyId, $paymentDate);
            $validator->errors()->add(
                'payment_date',
                __('period_lock.date_is_locked', [
                    'date' => $paymentDate,
                    'reason' => $lockReason['message'] ?? '',
                ])
            );
        }

        // For updates, also check if the original date was locked
        if ($this->isMethod('PUT') && $this->route('payment')) {
            $originalDate = $this->route('payment')->payment_date;
            if ($originalDate && $lockService->isDateLocked($companyId, $originalDate)) {
                $lockReason = $lockService->getLockReason($companyId, $originalDate);
                $validator->errors()->add(
                    'payment_date',
                    __('period_lock.original_date_locked', [
                        'date' => $originalDate->format('Y-m-d'),
                        'reason' => $lockReason['message'] ?? '',
                    ])
                );
            }
        }
    }

    public function getPaymentPayload()
    {
        $company_currency = CompanySetting::getSetting('currency', $this->header('company'));
        $current_currency = $this->currency_id;
        $exchange_rate = $company_currency != $current_currency ? $this->exchange_rate : 1;
        $currency = Customer::find($this->customer_id)->currency_id;

        return collect($this->validated())
            ->merge([
                'creator_id' => $this->user()->id,
                'company_id' => $this->header('company'),
                'exchange_rate' => $exchange_rate,
                'base_amount' => $this->amount * $exchange_rate,
                'currency_id' => $currency,
                'project_id' => $this->project_id,
            ])
            ->toArray();
    }
}
