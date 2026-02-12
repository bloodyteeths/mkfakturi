<?php

namespace App\Http\Requests;

use App\Models\ClientDocument;
use Illuminate\Foundation\Http\FormRequest;

class ClientDocumentUploadRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB in kilobytes
                'mimetypes:application/pdf,image/png,image/jpeg,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,text/plain',
            ],
            'category' => [
                'required',
                'in:invoice,receipt,contract,bank_statement,other',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
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
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded item must be a valid file.',
            'file.max' => 'The file size must not exceed 10MB.',
            'file.mimetypes' => 'Only PDF, PNG, JPEG, XLSX, and CSV files are allowed.',
            'category.required' => 'Please select a document category.',
            'category.in' => 'Invalid document category.',
            'notes.max' => 'Notes must not exceed 500 characters.',
        ];
    }
}

// CLAUDE-CHECKPOINT
