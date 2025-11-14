<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class CreateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Authorization is handled in controller via policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'categories' => 'sometimes|array',
            'categories.*' => 'exists:ticket_categories,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'title.required' => 'Ticket title is required',
            'title.max' => 'Ticket title cannot exceed 255 characters',
            'message.required' => 'Ticket message is required',
            'message.max' => 'Ticket message cannot exceed 5000 characters',
            'priority.in' => 'Priority must be one of: low, normal, high, urgent',
            'categories.array' => 'Categories must be an array',
            'categories.*.exists' => 'One or more selected categories do not exist',
        ];
    }
}
// CLAUDE-CHECKPOINT
