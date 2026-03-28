<?php

namespace Modules\Mk\Http\Requests\Manufacturing;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bom_id' => 'required|integer|exists:boms,id',
            'planned_quantity' => 'required|numeric|min:0.0001',
            'order_date' => 'nullable|date',
            'expected_completion_date' => 'nullable|date',
            'output_warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}

// CLAUDE-CHECKPOINT
