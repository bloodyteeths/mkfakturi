<?php

namespace Modules\Mk\Http\Requests\Manufacturing;

use Illuminate\Foundation\Http\FormRequest;

class CompleteProductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_quantity' => 'required|numeric|min:0',
            'co_outputs' => 'nullable|array',
            'co_outputs.*.item_id' => 'required|integer|exists:items,id',
            'co_outputs.*.quantity' => 'required|numeric|min:0.0001',
            'co_outputs.*.warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'co_outputs.*.is_primary' => 'nullable|boolean',
            'co_outputs.*.allocation_method' => 'nullable|in:weight,market_value,fixed_ratio,manual',
            'co_outputs.*.allocation_percent' => 'nullable|numeric|min:0|max:100',
        ];
    }
}

// CLAUDE-CHECKPOINT
