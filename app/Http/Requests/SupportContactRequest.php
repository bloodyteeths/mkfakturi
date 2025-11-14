<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupportContactRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:technical,billing,feature,general'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'message' => ['required', 'string', 'min:20', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,gif,pdf', 'max:5120'], // 5MB max per file
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
            'name.required' => 'Please enter your name.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'subject.required' => 'Please enter a subject for your inquiry.',
            'category.required' => 'Please select a category.',
            'category.in' => 'Please select a valid category.',
            'priority.required' => 'Please select a priority level.',
            'priority.in' => 'Please select a valid priority level.',
            'message.required' => 'Please enter your message.',
            'message.min' => 'Your message must be at least 20 characters.',
            'message.max' => 'Your message cannot exceed 2000 characters.',
            'attachments.max' => 'You can upload a maximum of 5 files.',
            'attachments.*.mimes' => 'Only JPG, JPEG, PNG, GIF, and PDF files are allowed.',
            'attachments.*.max' => 'Each file must be smaller than 5MB.',
        ];
    }
}
// CLAUDE-CHECKPOINT
