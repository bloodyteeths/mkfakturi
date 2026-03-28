<?php

namespace Modules\Mk\Http\Requests\Manufacturing;

use Illuminate\Foundation\Http\FormRequest;

class OverheadEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'required|string|max:255',
            'amount' => 'required|integer|min:0',
            'allocation_method' => 'nullable|in:per_unit,percentage,fixed',
            'allocation_base' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}

// CLAUDE-CHECKPOINT
