<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use App\Rules\RelationNotExist;
use App\Services\PeriodLockService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteInvoiceRequest extends FormRequest
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
                Rule::exists('invoices', 'id'),
                new RelationNotExist(Invoice::class, 'payments'),
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
     * Validate that none of the invoices being deleted are in a locked period.
     */
    protected function validatePeriodLock($validator): void
    {
        $companyId = $this->header('company');
        $ids = $this->input('ids', []);

        if (! $companyId || empty($ids)) {
            return;
        }

        $lockService = app(PeriodLockService::class);
        $invoices = Invoice::whereIn('id', $ids)->get();

        foreach ($invoices as $invoice) {
            $invoiceDate = Carbon::parse($invoice->invoice_date);
            if ($lockService->isDateLocked($companyId, $invoiceDate)) {
                $lockReason = $lockService->getLockReason($companyId, $invoiceDate);
                $validator->errors()->add(
                    'ids',
                    __('period_lock.cannot_delete_locked', [
                        'document' => $invoice->invoice_number,
                        'date' => $invoiceDate->format('Y-m-d'),
                        'reason' => $lockReason['message'] ?? '',
                    ])
                );
            }
        }
    }
}
