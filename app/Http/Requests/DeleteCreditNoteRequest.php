<?php

namespace App\Http\Requests;

use App\Models\CreditNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Delete Credit Note Request Validation
 *
 * Validates bulk deletion of credit notes.
 * Ensures credit notes exist and are not posted to IFRS.
 *
 * @package App\Http\Requests
 */
class DeleteCreditNoteRequest extends FormRequest
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
        return [
            'ids' => [
                'required',
                'array',
                'min:1',
            ],
            'ids.*' => [
                'required',
                'integer',
                Rule::exists('credit_notes', 'id')->where(function ($query) {
                    // Only allow deletion of credit notes in the current company
                    $query->where('company_id', request()->header('company'));
                }),
                // Custom validation to prevent deletion of posted credit notes
                function ($attribute, $value, $fail) {
                    $creditNote = CreditNote::find($value);
                    if ($creditNote && $creditNote->ifrs_transaction_id) {
                        $fail("Credit note {$creditNote->credit_note_number} cannot be deleted because it has been posted to accounting.");
                    }
                },
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'No credit notes selected for deletion.',
            'ids.min' => 'At least one credit note must be selected.',
            'ids.*.exists' => 'One or more selected credit notes do not exist.',
        ];
    }
}

// CLAUDE-CHECKPOINT
