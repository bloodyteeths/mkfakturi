<?php

namespace App\Observers;

use App\Models\InvoiceItem;
use App\Services\StockService;
use Illuminate\Support\Facades\Log;

/**
 * Stock Observer for Invoice Items
 *
 * Automatically processes stock OUT movements when invoice items
 * are created (sales deduct from inventory).
 *
 * Only active when FACTURINO_STOCK_V1_ENABLED is true.
 */
class StockInvoiceItemObserver
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Handle the InvoiceItem "created" event.
     *
     * When an invoice item is created, deduct stock from inventory.
     */
    public function created(InvoiceItem $invoiceItem): void
    {
        if (! StockService::isEnabled()) {
            return;
        }

        try {
            $movement = $this->stockService->processStockFromInvoiceItem($invoiceItem);

            if ($movement) {
                Log::info('StockInvoiceItemObserver: Stock OUT movement created', [
                    'invoice_item_id' => $invoiceItem->id,
                    'item_id' => $invoiceItem->item_id,
                    'quantity' => $invoiceItem->quantity,
                    'movement_id' => $movement->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('StockInvoiceItemObserver: Failed to create stock movement', [
                'invoice_item_id' => $invoiceItem->id,
                'item_id' => $invoiceItem->item_id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - we don't want to block invoice creation
        }
    }

    /**
     * Handle the InvoiceItem "deleted" event.
     *
     * When an invoice item is deleted, reverse the stock movement.
     */
    public function deleted(InvoiceItem $invoiceItem): void
    {
        if (! StockService::isEnabled()) {
            return;
        }

        try {
            // Find the original stock movement for this invoice item
            $originalMovement = \App\Models\StockMovement::where('source_type', \App\Models\StockMovement::SOURCE_INVOICE_ITEM)
                ->where('source_id', $invoiceItem->id)
                ->first();

            if ($originalMovement) {
                $reversal = $this->stockService->reverseMovement(
                    $originalMovement,
                    "Reversal: Invoice item deleted (ID: {$invoiceItem->id})"
                );

                Log::info('StockInvoiceItemObserver: Stock movement reversed', [
                    'invoice_item_id' => $invoiceItem->id,
                    'original_movement_id' => $originalMovement->id,
                    'reversal_movement_id' => $reversal->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('StockInvoiceItemObserver: Failed to reverse stock movement', [
                'invoice_item_id' => $invoiceItem->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
// CLAUDE-CHECKPOINT
