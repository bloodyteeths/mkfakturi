<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Warehouse Form Request
 *
 * Validates warehouse creation and update requests.
 * Part of Phase 2 - Stock Management V1 feature.
 */
class WarehouseRequest extends FormRequest
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
        $warehouseId = $this->route('warehouse')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('warehouses', 'code')
                    ->where('company_id', $companyId)
                    ->ignore($warehouseId),
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
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
            'name.required' => __('validation.required', ['attribute' => __('warehouses.name')]),
            'name.max' => __('validation.max.string', ['attribute' => __('warehouses.name'), 'max' => 255]),
            'code.unique' => __('validation.unique', ['attribute' => __('warehouses.code')]),
            'code.max' => __('validation.max.string', ['attribute' => __('warehouses.code'), 'max' => 50]),
            'address.max' => __('validation.max.string', ['attribute' => __('warehouses.address'), 'max' => 500]),
        ];
    }
}

// CLAUDE-CHECKPOINT
