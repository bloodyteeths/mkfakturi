<?php

namespace Modules\Mk\Http\Requests\Manufacturing;

use Illuminate\Foundation\Http\FormRequest;

class LaborEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'required|string|max:255',
            'hours' => 'required|numeric|min:0.01',
            'rate_per_hour' => 'required|integer|min:0',
            'work_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}

// CLAUDE-CHECKPOINT
