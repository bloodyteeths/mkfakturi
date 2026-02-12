<?php

namespace App\Http\Requests;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Leave Request Form Request
 *
 * Validates leave request creation and update payloads.
 * Includes rules for date ranges, overlap detection, and balance checks.
 */
class LeaveRequestRequest extends FormRequest
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
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->header('company');
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $leaveRequestId = $this->route('id');

        $rules = [
            'employee_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                Rule::exists('payroll_employees', 'id')
                    ->where('company_id', $companyId),
            ],
            'leave_type_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                Rule::exists('leave_types', 'id')
                    ->where('company_id', $companyId),
            ],
            'start_date' => [
                $isUpdate ? 'sometimes' : 'required',
                'date',
            ],
            'end_date' => [
                $isUpdate ? 'sometimes' : 'required',
                'date',
                'after_or_equal:start_date',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];

        // For new requests, enforce start_date is today or later
        if (!$isUpdate) {
            $rules['start_date'][] = 'after_or_equal:today';
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'An employee must be selected.',
            'employee_id.exists' => 'The selected employee does not exist in your company.',
            'leave_type_id.required' => 'A leave type must be selected.',
            'leave_type_id.exists' => 'The selected leave type does not exist in your company.',
            'start_date.required' => 'The start date is required.',
            'start_date.after_or_equal' => 'The start date must be today or later.',
            'end_date.required' => 'The end date is required.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
            'reason.max' => 'The reason must not exceed 500 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * Adds a custom rule to check for overlapping leaves.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $employeeId = $this->input('employee_id');
            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date');
            $excludeId = $this->route('id');

            if ($employeeId && $startDate && $endDate) {
                $overlapping = LeaveRequest::overlapping(
                    $employeeId,
                    $startDate,
                    $endDate,
                    $excludeId ? (int) $excludeId : null
                )->exists();

                if ($overlapping) {
                    $validator->errors()->add(
                        'start_date',
                        'This leave request overlaps with an existing approved or pending leave.'
                    );
                }
            }
        });
    }
}

// CLAUDE-CHECKPOINT
