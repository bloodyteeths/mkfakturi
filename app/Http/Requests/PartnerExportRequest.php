<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PartnerExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:csv,pantheon,zonel',
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
            'format.in' => 'Export format must be: csv, pantheon, or zonel.',
        ];
    }
}

// CLAUDE-CHECKPOINT
