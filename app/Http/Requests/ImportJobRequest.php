<?php

namespace App\Http\Requests;

use App\Models\ImportJob;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportJobRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'max:100000', // 100MB max file size
                'mimes:csv,xlsx,xls,xml',
            ],
            'type' => [
                'sometimes',
                Rule::in([
                    ImportJob::TYPE_CUSTOMERS,
                    ImportJob::TYPE_INVOICES,
                    ImportJob::TYPE_ITEMS,
                    ImportJob::TYPE_PAYMENTS,
                    ImportJob::TYPE_EXPENSES,
                    ImportJob::TYPE_COMPLETE,
                ]),
            ],
            'source_system' => [
                'sometimes',
                'string',
                'max:100',
                Rule::in([
                    'onivo',
                    'megasoft',
                    'pantheon',
                    'syntegra',
                    'excel',
                    'csv',
                    'xml',
                    'other',
                    'unknown',
                ]),
            ],
            'description' => [
                'sometimes',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.max' => 'File size cannot exceed 100MB.',
            'file.mimes' => 'Only CSV, Excel (xlsx, xls), and XML files are supported.',
            'type.in' => 'Invalid import type selected.',
            'source_system.in' => 'Invalid source system selected.',
        ];
    }

    /**
     * Get the payload for creating the import job.
     */
    public function getImportJobPayload(): array
    {
        return collect($this->validated())
            ->except(['file'])
            ->merge([
                'company_id' => $this->header('company'),
                'creator_id' => auth()->id(),
                'type' => $this->type ?? ImportJob::TYPE_COMPLETE,
                'source_system' => $this->source_system ?? 'unknown',
                'status' => ImportJob::STATUS_PENDING,
            ])
            ->toArray();
    }
}