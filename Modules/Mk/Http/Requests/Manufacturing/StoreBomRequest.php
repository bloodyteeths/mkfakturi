<?php

namespace Modules\Mk\Http\Requests\Manufacturing;

use Illuminate\Foundation\Http\FormRequest;

class StoreBomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'output_item_id' => 'required|integer|exists:items,id',
            'output_quantity' => 'required|numeric|min:0.0001',
            'output_unit_id' => 'nullable|integer|exists:units,id',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'description' => 'nullable|string|max:2000',
            'expected_wastage_percent' => 'nullable|numeric|min:0|max:100',
            'labor_cost_per_unit' => 'nullable|integer|min:0',
            'overhead_cost_per_unit' => 'nullable|integer|min:0',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|integer|exists:items,id',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.unit_id' => 'nullable|integer|exists:units,id',
            'lines.*.wastage_percent' => 'nullable|numeric|min:0|max:100',
        ];
    }
}

// CLAUDE-CHECKPOINT
