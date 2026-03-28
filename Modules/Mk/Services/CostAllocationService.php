<?php

namespace Modules\Mk\Services;

use Modules\Mk\Models\Manufacturing\CoProductionOutput;
use Modules\Mk\Models\Manufacturing\ProductionOrder;

/**
 * Handles co-production cost allocation across multiple outputs.
 *
 * Four allocation methods supported:
 * - weight: by output quantity (mass)
 * - market_value: by expected selling price × quantity
 * - fixed_ratio: by user-defined percentages
 * - manual: user sets allocated_cost directly
 */
class CostAllocationService
{
    /**
     * Allocate total production cost across co-production outputs.
     *
     * @param  ProductionOrder  $order  The completed production order
     * @return array<int, array{allocated_cost: int, cost_per_unit: int, allocation_percent: float}>
     */
    public function allocate(ProductionOrder $order): array
    {
        $outputs = $order->coProductionOutputs()->get();
        $totalCost = (int) $order->total_production_cost;

        if ($outputs->isEmpty() || $totalCost <= 0) {
            return [];
        }

        // Group by allocation method — all outputs should use the same method
        $method = $outputs->first()->allocation_method;

        return match ($method) {
            'weight' => $this->allocateByWeight($outputs, $totalCost),
            'market_value' => $this->allocateByMarketValue($outputs, $totalCost),
            'fixed_ratio' => $this->allocateByFixedRatio($outputs, $totalCost),
            'manual' => $this->validateManualAllocation($outputs, $totalCost),
            default => $this->allocateByWeight($outputs, $totalCost),
        };
    }

    /**
     * Allocate by output quantity (weight/mass proportional).
     */
    public function allocateByWeight($outputs, int $totalCost): array
    {
        $totalQuantity = $outputs->sum(fn ($o) => (float) $o->quantity);
        $result = [];
        $allocated = 0;

        foreach ($outputs as $i => $output) {
            $qty = (float) $output->quantity;
            $percent = $totalQuantity > 0 ? ($qty / $totalQuantity) * 100 : 0;

            // Last item gets remainder to avoid rounding drift
            if ($i === $outputs->count() - 1) {
                $cost = $totalCost - $allocated;
            } else {
                $cost = $totalQuantity > 0
                    ? (int) round($totalCost * $qty / $totalQuantity)
                    : 0;
                $allocated += $cost;
            }

            $costPerUnit = $qty > 0 ? (int) round($cost / $qty) : 0;

            $result[$output->id] = [
                'allocated_cost' => $cost,
                'cost_per_unit' => $costPerUnit,
                'allocation_percent' => round($percent, 4),
            ];
        }

        return $result;
    }

    /**
     * Allocate by market value (expected selling price × quantity).
     *
     * Expects each output's item to have a `selling_price` attribute (in cents).
     */
    public function allocateByMarketValue($outputs, int $totalCost): array
    {
        $totalMarketValue = $outputs->sum(function ($output) {
            $item = $output->item;
            $price = $item ? (int) ($item->selling_price ?? 0) : 0;

            return $price * (float) $output->quantity;
        });

        $result = [];
        $allocated = 0;

        foreach ($outputs as $i => $output) {
            $item = $output->item;
            $price = $item ? (int) ($item->selling_price ?? 0) : 0;
            $marketValue = $price * (float) $output->quantity;
            $percent = $totalMarketValue > 0 ? ($marketValue / $totalMarketValue) * 100 : 0;

            if ($i === $outputs->count() - 1) {
                $cost = $totalCost - $allocated;
            } else {
                $cost = $totalMarketValue > 0
                    ? (int) round($totalCost * $marketValue / $totalMarketValue)
                    : 0;
                $allocated += $cost;
            }

            $qty = (float) $output->quantity;
            $costPerUnit = $qty > 0 ? (int) round($cost / $qty) : 0;

            $result[$output->id] = [
                'allocated_cost' => $cost,
                'cost_per_unit' => $costPerUnit,
                'allocation_percent' => round($percent, 4),
            ];
        }

        return $result;
    }

    /**
     * Allocate by fixed user-defined ratios (from allocation_percent field).
     */
    public function allocateByFixedRatio($outputs, int $totalCost): array
    {
        $totalPercent = $outputs->sum(fn ($o) => (float) $o->allocation_percent);
        $result = [];
        $allocated = 0;

        foreach ($outputs as $i => $output) {
            $percent = (float) $output->allocation_percent;

            if ($i === $outputs->count() - 1) {
                $cost = $totalCost - $allocated;
            } else {
                $cost = $totalPercent > 0
                    ? (int) round($totalCost * $percent / $totalPercent)
                    : 0;
                $allocated += $cost;
            }

            $qty = (float) $output->quantity;
            $costPerUnit = $qty > 0 ? (int) round($cost / $qty) : 0;

            $result[$output->id] = [
                'allocated_cost' => $cost,
                'cost_per_unit' => $costPerUnit,
                'allocation_percent' => round($percent, 4),
            ];
        }

        return $result;
    }

    /**
     * Validate manual allocation — ensure total matches production cost.
     */
    public function validateManualAllocation($outputs, int $totalCost): array
    {
        $result = [];
        $totalAllocated = 0;

        foreach ($outputs as $output) {
            $cost = (int) $output->allocated_cost;
            $totalAllocated += $cost;

            $qty = (float) $output->quantity;
            $costPerUnit = $qty > 0 ? (int) round($cost / $qty) : 0;

            $totalPercent = $totalCost > 0 ? ($cost / $totalCost) * 100 : 0;

            $result[$output->id] = [
                'allocated_cost' => $cost,
                'cost_per_unit' => $costPerUnit,
                'allocation_percent' => round($totalPercent, 4),
            ];
        }

        // Flag if manual allocation doesn't sum to total
        if ($totalAllocated !== $totalCost) {
            $result['_warning'] = "Manual allocation total ({$totalAllocated}) differs from production cost ({$totalCost})";
        }

        return $result;
    }
}

// CLAUDE-CHECKPOINT
