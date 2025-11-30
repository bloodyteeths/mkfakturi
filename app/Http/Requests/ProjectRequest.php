<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Project Form Request
 *
 * Validates project creation and update requests.
 * Part of Phase 1.1 - Project Dimension feature for accountants.
 */
class ProjectRequest extends FormRequest
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
        $projectId = $this->route('project')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('projects', 'code')
                    ->where('company_id', $companyId)
                    ->ignore($projectId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'status' => ['nullable', 'string', 'in:open,in_progress,completed,on_hold,cancelled'],
            'budget_amount' => ['nullable', 'integer', 'min:0'],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.required', ['attribute' => __('projects.name')]),
            'name.max' => __('validation.max.string', ['attribute' => __('projects.name'), 'max' => 255]),
            'code.unique' => __('validation.unique', ['attribute' => __('projects.code')]),
            'code.max' => __('validation.max.string', ['attribute' => __('projects.code'), 'max' => 50]),
            'customer_id.exists' => __('validation.exists', ['attribute' => __('projects.customer')]),
            'status.in' => __('validation.in', ['attribute' => __('projects.status')]),
            'budget_amount.min' => __('validation.min.numeric', ['attribute' => __('projects.budget'), 'min' => 0]),
            'currency_id.exists' => __('validation.exists', ['attribute' => __('projects.currency')]),
            'end_date.after_or_equal' => __('validation.after_or_equal', ['attribute' => __('projects.end_date'), 'date' => __('projects.start_date')]),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert budget from display format to cents if needed
        if ($this->has('budget_amount') && is_numeric($this->budget_amount)) {
            // If budget is provided in major currency units, convert to minor units
            // For now, assume it's already in minor units (cents)
        }
    }
}

// CLAUDE-CHECKPOINT
