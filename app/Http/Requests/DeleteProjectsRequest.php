<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Delete Projects Form Request
 *
 * Validates bulk delete requests for projects.
 * Part of Phase 1.1 - Project Dimension feature for accountants.
 */
class DeleteProjectsRequest extends FormRequest
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
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'integer', 'exists:projects,id'],
        ];
    }
}

// CLAUDE-CHECKPOINT
