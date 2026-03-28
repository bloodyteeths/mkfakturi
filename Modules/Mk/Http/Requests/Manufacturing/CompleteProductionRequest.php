<?php

namespace Modules\Mk\Http\Requests\Manufacturing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Mk\Models\Manufacturing\ProductionOrder;

class CompleteProductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_quantity' => 'required|numeric|min:0',
            'co_outputs' => 'nullable|array',
            'co_outputs.*.item_id' => 'required|integer|exists:items,id',
            'co_outputs.*.quantity' => 'required|numeric|min:0.0001',
            'co_outputs.*.warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'co_outputs.*.is_primary' => 'nullable|boolean',
            'co_outputs.*.allocation_method' => 'nullable|in:weight,market_value,fixed_ratio,manual',
            'co_outputs.*.allocation_percent' => 'nullable|numeric|min:0|max:100',
        ];
    }

    /**
     * Validate actual_quantity is within reasonable range of planned_quantity.
     * Variance > 200% (3x planned) is rejected to catch data entry errors.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $actualQty = (float) $this->input('actual_quantity');
            $orderId = (int) $this->route('id');
            $companyId = (int) $this->header('company');

            $order = ProductionOrder::where('company_id', $companyId)->find($orderId);
            if (! $order || (float) $order->planned_quantity <= 0) {
                return;
            }

            $plannedQty = (float) $order->planned_quantity;
            $variance = abs($actualQty - $plannedQty) / $plannedQty * 100;

            if ($variance > 200) {
                $validator->errors()->add(
                    'actual_quantity',
                    "Actual quantity ({$actualQty}) deviates more than 200% from planned quantity ({$plannedQty}). Please verify the value."
                );
            }
        });
    }
}

// CLAUDE-CHECKPOINT: actual_quantity reasonableness validation (>200% variance rejected)
