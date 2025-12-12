<?php

namespace App\Http\Requests;

use App\Models\Expense;
use App\Services\PeriodLockService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteExpensesRequest extends FormRequest
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
            'ids' => [
                'required',
            ],
            'ids.*' => [
                'required',
                Rule::exists('expenses', 'id'),
            ],
        ];
    }

    /**
     * Configure the validator instance.
     * Adds period lock validation for deletions.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validatePeriodLock($validator);
        });
    }

    /**
     * Validate that none of the expenses being deleted are in a locked period.
     */
    protected function validatePeriodLock($validator): void
    {
        $companyId = $this->header('company');
        $ids = $this->input('ids', []);

        if (! $companyId || empty($ids)) {
            return;
        }

        $lockService = app(PeriodLockService::class);
        $expenses = Expense::whereIn('id', $ids)->get();

        foreach ($expenses as $expense) {
            $expenseDate = Carbon::parse($expense->expense_date);
            if ($lockService->isDateLocked($companyId, $expenseDate)) {
                $lockReason = $lockService->getLockReason($companyId, $expenseDate);
                $validator->errors()->add(
                    'ids',
                    __('period_lock.cannot_delete_locked', [
                        'document' => __('expenses.expense').' #'.$expense->id,
                        'date' => $expenseDate->format('Y-m-d'),
                        'reason' => $lockReason['message'] ?? '',
                    ])
                );
            }
        }
    }
}
