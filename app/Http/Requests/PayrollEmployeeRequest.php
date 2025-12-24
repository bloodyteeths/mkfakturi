<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayrollEmployeeRequest extends FormRequest
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
        $companyId = $this->header('company');
        $employeeId = $this->route('payroll_employee') ? $this->route('payroll_employee')->id : null;

        return [
            'employee_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('payroll_employees', 'employee_number')
                    ->where('company_id', $companyId)
                    ->ignore($employeeId),
            ],
            'first_name' => [
                'required',
                'string',
                'max:100',
            ],
            'last_name' => [
                'required',
                'string',
                'max:100',
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],
            'embg' => [
                'required',
                'string',
                'size:13',
                'regex:/^[0-9]{13}$/',
                Rule::unique('payroll_employees', 'embg')
                    ->where('company_id', $companyId)
                    ->ignore($employeeId),
            ],
            'bank_account_iban' => [
                'required',
                'string',
                'max:34',
            ],
            'bank_name' => [
                'nullable',
                'string',
                'max:100',
            ],
            'employment_date' => [
                'required',
                'date',
            ],
            'termination_date' => [
                'nullable',
                'date',
                'after:employment_date',
            ],
            'employment_type' => [
                'required',
                'string',
                Rule::in(['full_time', 'part_time', 'contract']),
            ],
            'department' => [
                'nullable',
                'string',
                'max:100',
            ],
            'position' => [
                'nullable',
                'string',
                'max:100',
            ],
            'base_salary_amount' => [
                'required',
                'integer',
                'min:0',
            ],
            'currency_id' => [
                'required',
                'integer',
                'exists:currencies,id',
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'embg.required' => 'The EMBG (Macedonian personal ID) is required.',
            'embg.size' => 'The EMBG must be exactly 13 digits.',
            'embg.regex' => 'The EMBG must contain only numbers.',
            'embg.unique' => 'An employee with this EMBG already exists in your company.',
            'employee_number.unique' => 'This employee number is already in use.',
            'termination_date.after' => 'Termination date must be after employment date.',
        ];
    }

    /**
     * Get the validated data for creating/updating a payroll employee.
     */
    public function getPayrollEmployeePayload(): array
    {
        return collect($this->validated())
            ->merge([
                'company_id' => $this->header('company'),
                'creator_id' => $this->user()->id,
                'is_active' => $this->input('is_active', true),
            ])
            ->toArray();
    }
}

// LLM-CHECKPOINT
