<?php

namespace App\Http\Requests;

use App\Models\Payment;
use App\Services\PeriodLockService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeletePaymentsRequest extends FormRequest
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
                Rule::exists('payments', 'id'),
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
     * Validate that none of the payments being deleted are in a locked period.
     */
    protected function validatePeriodLock($validator): void
    {
        $companyId = $this->header('company');
        $ids = $this->input('ids', []);

        if (! $companyId || empty($ids)) {
            return;
        }

        $lockService = app(PeriodLockService::class);
        $payments = Payment::whereIn('id', $ids)->get();

        foreach ($payments as $payment) {
            $paymentDate = Carbon::parse($payment->payment_date);
            if ($lockService->isDateLocked($companyId, $paymentDate)) {
                $lockReason = $lockService->getLockReason($companyId, $paymentDate);
                $validator->errors()->add(
                    'ids',
                    __('period_lock.cannot_delete_locked', [
                        'document' => $payment->payment_number,
                        'date' => $paymentDate->format('Y-m-d'),
                        'reason' => $lockReason['message'] ?? '',
                    ])
                );
            }
        }
    }
}
