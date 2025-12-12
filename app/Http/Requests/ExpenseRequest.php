<?php

namespace App\Http\Requests;

use App\Models\CompanySetting;
use App\Models\Expense;
use App\Services\PeriodLockService;
use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
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
        $companyCurrency = CompanySetting::getSetting('currency', $this->header('company'));

        $rules = [
            'expense_date' => [
                'required',
            ],
            'expense_category_id' => [
                'required',
            ],
            'exchange_rate' => [
                'nullable',
            ],
            'payment_method_id' => [
                'nullable',
            ],
            'amount' => [
                'required',
            ],
            'customer_id' => [
                'nullable',
            ],
            'supplier_id' => [
                'nullable',
                'integer',
                'exists:suppliers,id',
            ],
            'invoice_number' => [
                'nullable',
                'string',
                'max:100',
            ],
            'project_id' => [
                'nullable',
                'integer',
                'exists:projects,id',
            ],
            'allow_duplicate' => [
                'nullable',
                'boolean',
            ],
            'notes' => [
                'nullable',
            ],
            'currency_id' => [
                'required',
            ],
            'attachment_receipt' => [
                'nullable',
                'file',
                'mimes:jpg,png,pdf,doc,docx,xls,xlsx,ppt,pptx',
                'max:20000',
            ],
        ];

        if ($companyCurrency && $this->currency_id) {
            if ($companyCurrency !== $this->currency_id) {
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
     * Validate that the expense date is not in a locked period.
     */
    protected function validatePeriodLock($validator): void
    {
        $companyId = $this->header('company');
        $expenseDate = $this->input('expense_date');

        if (! $companyId || ! $expenseDate) {
            return;
        }

        $lockService = app(PeriodLockService::class);

        // Check if the new date is locked
        if ($lockService->isDateLocked($companyId, $expenseDate)) {
            $lockReason = $lockService->getLockReason($companyId, $expenseDate);
            $validator->errors()->add(
                'expense_date',
                __('period_lock.date_is_locked', [
                    'date' => $expenseDate,
                    'reason' => $lockReason['message'] ?? '',
                ])
            );
        }

        // For updates, also check if the original date was locked
        if ($this->isMethod('PUT') && $this->route('expense')) {
            $originalDate = $this->route('expense')->expense_date;
            if ($originalDate && $lockService->isDateLocked($companyId, $originalDate)) {
                $lockReason = $lockService->getLockReason($companyId, $originalDate);
                $validator->errors()->add(
                    'expense_date',
                    __('period_lock.original_date_locked', [
                        'date' => $originalDate->format('Y-m-d'),
                        'reason' => $lockReason['message'] ?? '',
                    ])
                );
            }
        }
    }

    public function getExpensePayload()
    {
        $company_currency = CompanySetting::getSetting('currency', $this->header('company'));
        $current_currency = $this->currency_id;
        $exchange_rate = $company_currency != $current_currency ? $this->exchange_rate : 1;

        return collect($this->validated())
            ->except(['allow_duplicate']) // Remove non-model field
            ->merge([
                'creator_id' => $this->user()->id,
                'company_id' => $this->header('company'),
                'exchange_rate' => $exchange_rate,
                'base_amount' => $this->amount * $exchange_rate,
                'currency_id' => $current_currency,
            ])
            ->toArray();
    }

    /**
     * Check if user wants to allow duplicate
     */
    public function allowsDuplicate(): bool
    {
        return (bool) $this->input('allow_duplicate', false);
    }
}
