<?php

namespace App\Http\Requests;

use App\Models\SalaryStructure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalaryStructureRequest extends FormRequest
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
            'employee_id' => [
                'required',
                'integer',
                'exists:payroll_employees,id',
            ],
            'effective_from' => [
                'required',
                'date',
            ],
            'effective_to' => [
                'nullable',
                'date',
                'after:effective_from',
            ],
            'gross_salary' => [
                'required',
                'integer',
                'min:0',
            ],
            'transport_allowance' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'meal_allowance' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'other_allowances' => [
                'nullable',
                'array',
            ],
            'other_allowances.*.name' => [
                'required_with:other_allowances',
                'string',
                'max:100',
            ],
            'other_allowances.*.amount' => [
                'required_with:other_allowances',
                'integer',
                'min:0',
            ],
            'is_current' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateOverlappingPeriods($validator);
        });
    }

    /**
     * Validate that there are no overlapping salary structures for the same employee.
     */
    protected function validateOverlappingPeriods($validator): void
    {
        $employeeId = $this->input('employee_id');
        $effectiveFrom = $this->input('effective_from');
        $effectiveTo = $this->input('effective_to');
        $companyId = $this->header('company');
        $structureId = $this->route('salary_structure') ? $this->route('salary_structure')->id : null;

        if (!$employeeId || !$effectiveFrom || !$companyId) {
            return;
        }

        // Check for overlapping periods
        $overlapping = SalaryStructure::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->when($structureId, function ($query) use ($structureId) {
                $query->where('id', '!=', $structureId);
            })
            ->where(function ($query) use ($effectiveFrom, $effectiveTo) {
                $query->where(function ($q) use ($effectiveFrom, $effectiveTo) {
                    // New period starts within existing period
                    $q->where('effective_from', '<=', $effectiveFrom)
                        ->where(function ($q2) use ($effectiveFrom) {
                            $q2->whereNull('effective_to')
                                ->orWhere('effective_to', '>=', $effectiveFrom);
                        });
                })->orWhere(function ($q) use ($effectiveFrom, $effectiveTo) {
                    // New period ends within existing period (if it has an end date)
                    if ($effectiveTo) {
                        $q->where('effective_from', '<=', $effectiveTo)
                            ->where(function ($q2) use ($effectiveTo) {
                                $q2->whereNull('effective_to')
                                    ->orWhere('effective_to', '>=', $effectiveTo);
                            });
                    }
                })->orWhere(function ($q) use ($effectiveFrom, $effectiveTo) {
                    // New period completely encompasses existing period
                    if ($effectiveTo) {
                        $q->where('effective_from', '>=', $effectiveFrom)
                            ->where('effective_from', '<=', $effectiveTo);
                    }
                });
            })
            ->exists();

        if ($overlapping) {
            $validator->errors()->add(
                'effective_from',
                'This salary structure overlaps with an existing salary structure for this employee.'
            );
        }
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'effective_to.after' => 'The end date must be after the start date.',
            'other_allowances.*.name.required_with' => 'Allowance name is required.',
            'other_allowances.*.amount.required_with' => 'Allowance amount is required.',
        ];
    }

    /**
     * Get the validated data for creating/updating a salary structure.
     */
    public function getSalaryStructurePayload(): array
    {
        return collect($this->validated())
            ->merge([
                'company_id' => $this->header('company'),
                'transport_allowance' => $this->input('transport_allowance', 0),
                'meal_allowance' => $this->input('meal_allowance', 0),
                'other_allowances' => $this->input('other_allowances', []),
                'is_current' => $this->input('is_current', false),
            ])
            ->toArray();
    }
}

// LLM-CHECKPOINT
