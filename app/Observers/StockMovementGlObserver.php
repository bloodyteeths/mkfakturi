<?php

namespace App\Observers;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Log;

/**
 * GL Observer for Stock Movements
 *
 * Automatically posts stock movements to the general ledger via IfrsAdapter
 * when a StockMovement is created. Follows the same defensive pattern as
 * StockBillItemObserver: catches all exceptions and never throws, so that
 * GL posting failures do not block stock operations.
 *
 * Only posts when:
 * 1. IfrsAdapter is enabled for the movement's company
 * 2. The item has track_quantity enabled
 */
class StockMovementGlObserver
{
    protected IfrsAdapter $ifrsAdapter;

    public function __construct(IfrsAdapter $ifrsAdapter)
    {
        $this->ifrsAdapter = $ifrsAdapter;
    }

    /**
     * Handle the StockMovement "created" event.
     *
     * Posts the stock movement to the general ledger if IFRS is enabled
     * for the company and the item tracks quantity.
     */
    public function created(StockMovement $movement): void
    {
        try {
            // Load the item relationship if not already loaded
            if (! $movement->relationLoaded('item')) {
                $movement->load('item');
            }

            // Only post GL entries for items that track quantity
            if (! $movement->item || ! $movement->item->track_quantity) {
                return;
            }

            $this->ifrsAdapter->postStockMovement($movement);

            Log::info('StockMovementGlObserver: GL entry posted for stock movement', [
                'movement_id' => $movement->id,
                'source_type' => $movement->source_type,
                'item_id' => $movement->item_id,
                'quantity' => $movement->quantity,
                'ifrs_transaction_id' => $movement->ifrs_transaction_id,
            ]);
        } catch (\Exception $e) {
            // Never throw - GL posting must not block stock operations
            Log::warning('StockMovementGlObserver: Failed to post GL entry for stock movement', [
                'movement_id' => $movement->id,
                'source_type' => $movement->source_type,
                'item_id' => $movement->item_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
// CLAUDE-CHECKPOINT
