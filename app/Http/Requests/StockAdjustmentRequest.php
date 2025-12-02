<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Stock Adjustment Request
 *
 * Validates stock adjustment creation/update requests.
 * Supports both positive (add) and negative (remove) adjustments.
 */
class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|numeric|not_in:0',
            'unit_cost' => 'required_if:quantity,>,0|nullable|numeric|min:0',
            'adjustment_date' => 'nullable|date',
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.not_in' => 'Quantity cannot be zero.',
            'unit_cost.required_if' => 'Unit cost is required when adding stock.',
        ];
    }

    /**
     * Get validated adjustment data with defaults.
     */
    public function getAdjustmentData(): array
    {
        return [
            'warehouse_id' => $this->warehouse_id,
            'item_id' => $this->item_id,
            'quantity' => (float) $this->quantity,
            'unit_cost' => $this->quantity > 0 ? (int) ($this->unit_cost * 100) : null,
            'adjustment_date' => $this->adjustment_date ?? now()->format('Y-m-d'),
            'reason' => $this->reason,
            'notes' => $this->notes ?? $this->reason,
        ];
    }
}
// CLAUDE-CHECKPOINT
