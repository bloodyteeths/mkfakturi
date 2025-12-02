<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Stock Transfer Request
 *
 * Validates warehouse-to-warehouse transfer requests.
 */
class StockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_warehouse_id' => 'required|exists:warehouses,id|different:to_warehouse_id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|numeric|min:0.0001',
            'transfer_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'from_warehouse_id.different' => 'Source and destination warehouses must be different.',
            'quantity.min' => 'Transfer quantity must be greater than zero.',
        ];
    }

    /**
     * Get validated transfer data.
     */
    public function getTransferData(): array
    {
        return [
            'from_warehouse_id' => (int) $this->from_warehouse_id,
            'to_warehouse_id' => (int) $this->to_warehouse_id,
            'item_id' => (int) $this->item_id,
            'quantity' => (float) $this->quantity,
            'transfer_date' => $this->transfer_date ?? now()->format('Y-m-d'),
            'notes' => $this->notes,
        ];
    }
}
// CLAUDE-CHECKPOINT
