<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Log;

/**
 * Invoice Profit Service
 *
 * Calculates profit metrics for invoices using WAC (Weighted Average Cost)
 * data from the stock module.
 *
 * Metrics provided:
 * - Cost of Goods Sold (COGS)
 * - Gross Profit Amount
 * - Gross Profit Margin (%)
 *
 * Respects FACTURINO_STOCK_V1_ENABLED feature flag.
 *
 * @version 1.0.0
 */
class InvoiceProfitService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Check if profit calculation is available.
     *
     * Profit requires stock module to be enabled and invoice to have
     * items with stock movements.
     */
    public function isProfitAvailable(Invoice $invoice): bool
    {
        if (! StockService::isEnabled()) {
            return false;
        }

        // Check if any items have stock tracking
        $hasTrackedItems = $invoice->items()
            ->whereHas('item', function ($query) {
                $query->where('track_quantity', true);
            })
            ->exists();

        return $hasTrackedItems;
    }

    /**
     * Get profit metrics for an invoice.
     *
     * Returns:
     * - revenue: Total invoice amount (excluding tax)
     * - cogs: Cost of Goods Sold based on WAC
     * - gross_profit: Revenue - COGS
     * - margin: Gross profit as percentage of revenue
     * - items: Per-item cost breakdown (if requested)
     * - available: Whether profit calculation was possible
     * - reason: If not available, explains why
     *
     * All monetary values are in cents (base currency).
     *
     * @param Invoice $invoice
     * @param bool $includeItemBreakdown Include per-item costs
     * @return array
     */
    public function getInvoiceProfit(Invoice $invoice, bool $includeItemBreakdown = false): array
    {
        // Check if stock module is enabled
        if (! StockService::isEnabled()) {
            return [
                'available' => false,
                'reason' => 'stock_disabled',
                'revenue' => null,
                'cogs' => null,
                'gross_profit' => null,
                'margin' => null,
                'items' => null,
            ];
        }

        // Load invoice items with their items and stock movements
        $invoice->loadMissing(['items.item', 'items.stockMovements']);

        $totalRevenue = 0;
        $totalCogs = 0;
        $itemBreakdown = [];
        $hasStockData = false;

        foreach ($invoice->items as $invoiceItem) {
            $itemResult = $this->calculateItemCost($invoiceItem);

            // Use base_total for multi-currency consistency
            $itemRevenue = $invoiceItem->base_total ?? $invoiceItem->total;
            $totalRevenue += $itemRevenue;

            if ($itemResult['has_cost']) {
                $hasStockData = true;
                $totalCogs += $itemResult['cogs'];
            }

            if ($includeItemBreakdown) {
                $itemBreakdown[] = [
                    'invoice_item_id' => $invoiceItem->id,
                    'item_id' => $invoiceItem->item_id,
                    'name' => $invoiceItem->name,
                    'quantity' => (float) $invoiceItem->quantity,
                    'revenue' => $itemRevenue,
                    'unit_cost' => $itemResult['unit_cost'],
                    'cogs' => $itemResult['cogs'],
                    'gross_profit' => $itemResult['has_cost'] ? $itemRevenue - $itemResult['cogs'] : null,
                    'margin' => $itemResult['has_cost'] && $itemRevenue > 0
                        ? round((($itemRevenue - $itemResult['cogs']) / $itemRevenue) * 100, 2)
                        : null,
                    'has_cost' => $itemResult['has_cost'],
                    'cost_source' => $itemResult['cost_source'],
                ];
            }
        }

        // If no stock data at all, profit is not available
        if (! $hasStockData) {
            return [
                'available' => false,
                'reason' => 'no_stock_data',
                'revenue' => $totalRevenue,
                'cogs' => null,
                'gross_profit' => null,
                'margin' => null,
                'items' => $includeItemBreakdown ? $itemBreakdown : null,
            ];
        }

        $grossProfit = $totalRevenue - $totalCogs;
        $margin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 2) : 0;

        return [
            'available' => true,
            'reason' => null,
            'revenue' => $totalRevenue,
            'cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'margin' => $margin,
            'items' => $includeItemBreakdown ? $itemBreakdown : null,
        ];
    }

    /**
     * Calculate cost for a single invoice item.
     *
     * Cost source priority:
     * 1. Stock movement recorded for this specific invoice item (most accurate)
     * 2. Current WAC from stock service
     * 3. Fallback: no cost available
     *
     * @param InvoiceItem $invoiceItem
     * @return array ['unit_cost' => int|null, 'cogs' => int, 'has_cost' => bool, 'cost_source' => string]
     */
    protected function calculateItemCost(InvoiceItem $invoiceItem): array
    {
        // Skip if no linked item or not trackable
        if (! $invoiceItem->item || ! $invoiceItem->item->track_quantity) {
            return [
                'unit_cost' => null,
                'cogs' => 0,
                'has_cost' => false,
                'cost_source' => 'not_tracked',
            ];
        }

        $quantity = abs((float) $invoiceItem->quantity);

        // Priority 1: Check for stock movement linked to this invoice item
        $stockMovement = $invoiceItem->stockMovements->first();

        if ($stockMovement) {
            // Use the WAC at the time of the movement
            // For outgoing movements, we stored the cost in the movement
            $unitCost = $stockMovement->unit_cost;

            if ($unitCost !== null) {
                return [
                    'unit_cost' => (int) $unitCost,
                    'cogs' => (int) round($unitCost * $quantity),
                    'has_cost' => true,
                    'cost_source' => 'movement',
                ];
            }

            // If unit_cost is null (some outgoing movements), calculate from WAC at that point
            // The balance_value / balance_quantity before this movement gives us the WAC
            $previousMovement = StockMovement::where('company_id', $invoiceItem->company_id)
                ->where('item_id', $invoiceItem->item_id)
                ->where('id', '<', $stockMovement->id)
                ->orderBy('id', 'desc')
                ->first();

            if ($previousMovement && $previousMovement->balance_quantity > 0) {
                $wac = (int) round($previousMovement->balance_value / $previousMovement->balance_quantity);

                return [
                    'unit_cost' => $wac,
                    'cogs' => (int) round($wac * $quantity),
                    'has_cost' => true,
                    'cost_source' => 'movement_wac',
                ];
            }
        }

        // Priority 2: Use current WAC from stock service
        $companyId = $invoiceItem->company_id;
        $itemId = $invoiceItem->item_id;
        $warehouseId = $invoiceItem->warehouse_id;

        $currentStock = $this->stockService->getItemStock($companyId, $itemId, $warehouseId);

        if ($currentStock['weighted_average_cost'] > 0) {
            $unitCost = $currentStock['weighted_average_cost'];

            return [
                'unit_cost' => $unitCost,
                'cogs' => (int) round($unitCost * $quantity),
                'has_cost' => true,
                'cost_source' => 'current_wac',
            ];
        }

        // Priority 3: No cost data available
        return [
            'unit_cost' => null,
            'cogs' => 0,
            'has_cost' => false,
            'cost_source' => 'no_cost',
        ];
    }

    /**
     * Get profit summary for multiple invoices.
     *
     * Useful for reports and dashboards.
     *
     * @param \Illuminate\Database\Eloquent\Collection $invoices
     * @return array ['total_revenue' => int, 'total_cogs' => int, 'total_profit' => int, 'avg_margin' => float]
     */
    public function getInvoicesProfitSummary($invoices): array
    {
        if (! StockService::isEnabled()) {
            return [
                'available' => false,
                'total_revenue' => null,
                'total_cogs' => null,
                'total_profit' => null,
                'avg_margin' => null,
            ];
        }

        $totalRevenue = 0;
        $totalCogs = 0;
        $invoicesWithProfit = 0;

        foreach ($invoices as $invoice) {
            $profit = $this->getInvoiceProfit($invoice);

            if ($profit['available']) {
                $totalRevenue += $profit['revenue'];
                $totalCogs += $profit['cogs'];
                $invoicesWithProfit++;
            }
        }

        if ($invoicesWithProfit === 0) {
            return [
                'available' => false,
                'total_revenue' => null,
                'total_cogs' => null,
                'total_profit' => null,
                'avg_margin' => null,
            ];
        }

        $totalProfit = $totalRevenue - $totalCogs;
        $avgMargin = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 2) : 0;

        return [
            'available' => true,
            'total_revenue' => $totalRevenue,
            'total_cogs' => $totalCogs,
            'total_profit' => $totalProfit,
            'avg_margin' => $avgMargin,
            'invoices_analyzed' => $invoicesWithProfit,
        ];
    }
}
// CLAUDE-CHECKPOINT
