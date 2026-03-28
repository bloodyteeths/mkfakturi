<?php

namespace Modules\Mk\Http\Requests\Manufacturing;

use Illuminate\Foundation\Http\FormRequest;

class MaterialConsumptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'material_id' => 'required|integer',
            'actual_quantity' => 'required|numeric|min:0',
            'wastage_quantity' => 'nullable|numeric|min:0',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}

// CLAUDE-CHECKPOINT
