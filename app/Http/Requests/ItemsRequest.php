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
            'cost' => [
                'nullable',
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
                'unique:items,sku,'.$itemId.',id,company_id,'.$companyId,
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                'unique:items,barcode,'.$itemId.',id,company_id,'.$companyId,
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
            'preferred_supplier_id' => [
                'nullable',
                'integer',
            ],
            'reorder_quantity' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'lead_time_days' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'category' => [
                'nullable',
                'string',
                'max:255',
            ],
            'category_id' => [
                'nullable',
                'integer',
                'exists:item_categories,id',
            ],
            'currency_id' => [
                'nullable',
                'integer',
                'exists:currencies,id',
            ],
            'inventory_account_id' => [
                'nullable',
                'integer',
                'exists:accounts,id',
            ],
            'cogs_account_id' => [
                'nullable',
                'integer',
                'exists:accounts,id',
            ],
            'purchase_account_id' => [
                'nullable',
                'integer',
                'exists:accounts,id',
            ],
            'retail_price' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'wholesale_price' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'markup_percent' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999.99',
            ],
            'allow_duplicate' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function allowsDuplicate(): bool
    {
        return (bool) $this->input('allow_duplicate', false);
    }
}
