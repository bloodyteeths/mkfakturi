<?php

namespace App\Observers;

use App\Models\BillItem;
use App\Services\StockService;
use Illuminate\Support\Facades\Log;

/**
 * Stock Observer for Bill Items
 *
 * Automatically processes stock IN movements when bill items
 * are created (purchases add to inventory).
 *
 * Only active when FACTURINO_STOCK_V1_ENABLED is true.
 */
class StockBillItemObserver
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Handle the BillItem "created" event.
     *
     * When a bill item is created, add stock to inventory.
     */
    public function created(BillItem $billItem): void
    {
        if (! StockService::isEnabled()) {
            return;
        }

        try {
            $movement = $this->stockService->processStockFromBillItem($billItem);

            if ($movement) {
                Log::info('StockBillItemObserver: Stock IN movement created', [
                    'bill_item_id' => $billItem->id,
                    'item_id' => $billItem->item_id,
                    'quantity' => $billItem->quantity,
                    'unit_cost' => $movement->unit_cost,
                    'movement_id' => $movement->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('StockBillItemObserver: Failed to create stock movement', [
                'bill_item_id' => $billItem->id,
                'item_id' => $billItem->item_id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - we don't want to block bill creation
        }
    }

    /**
     * Handle the BillItem "deleted" event.
     *
     * When a bill item is deleted, reverse the stock movement.
     */
    public function deleted(BillItem $billItem): void
    {
        if (! StockService::isEnabled()) {
            return;
        }

        try {
            // Find the original stock movement for this bill item
            $originalMovement = \App\Models\StockMovement::where('source_type', \App\Models\StockMovement::SOURCE_BILL_ITEM)
                ->where('source_id', $billItem->id)
                ->first();

            if ($originalMovement) {
                $reversal = $this->stockService->reverseMovement(
                    $originalMovement,
                    "Reversal: Bill item deleted (ID: {$billItem->id})"
                );

                Log::info('StockBillItemObserver: Stock movement reversed', [
                    'bill_item_id' => $billItem->id,
                    'original_movement_id' => $originalMovement->id,
                    'reversal_movement_id' => $reversal->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('StockBillItemObserver: Failed to reverse stock movement', [
                'bill_item_id' => $billItem->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
// CLAUDE-CHECKPOINT
