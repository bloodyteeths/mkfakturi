<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Initial Stock Request
 *
 * Validates initial stock entry for items.
 * Used when first setting up inventory tracking.
 */
class InitialStockRequest extends FormRequest
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
            'quantity' => 'required|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'stock_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get validated initial stock data.
     */
    public function getInitialStockData(): array
    {
        return [
            'warehouse_id' => (int) $this->warehouse_id,
            'item_id' => (int) $this->item_id,
            'quantity' => (float) $this->quantity,
            'unit_cost' => (int) ($this->unit_cost * 100), // Convert to cents
            'stock_date' => $this->stock_date ?? now()->format('Y-m-d'),
            'notes' => $this->notes ?? 'Initial stock entry',
        ];
    }
}
// CLAUDE-CHECKPOINT
