<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemCategoryRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $data = [
            'name' => [
                'required',
                'max:100',
                Rule::unique('item_categories')
                    ->where('company_id', $this->header('company')),
            ],
            'description' => 'nullable|max:255',
        ];

        if ($this->getMethod() == 'PUT') {
            $data['name'] = [
                'required',
                'max:100',
                Rule::unique('item_categories')
                    ->ignore($this->route('item_category'), 'id')
                    ->where('company_id', $this->header('company')),
            ];
        }

        return $data;
    }
}
// CLAUDE-CHECKPOINT
