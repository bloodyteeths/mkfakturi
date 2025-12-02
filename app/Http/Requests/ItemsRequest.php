<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemsRequest extends FormRequest
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
        $companyId = $this->header('company');
        $itemId = $this->route('item') ? $this->route('item')->id : null;

        return [
            'name' => [
                'required',
            ],
            'price' => [
                'required',
            ],
            'unit_id' => [
                'nullable',
            ],
            'description' => [
                'nullable',
            ],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                'unique:items,sku,' . $itemId . ',id,company_id,' . $companyId,
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                'unique:items,barcode,' . $itemId . ',id,company_id,' . $companyId,
            ],
            'track_quantity' => [
                'nullable',
                'boolean',
            ],
            'minimum_quantity' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'category' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    // CLAUDE-CHECKPOINT
}
