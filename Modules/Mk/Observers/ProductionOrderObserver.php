<?php

namespace Modules\Mk\Observers;

use App\Domain\Accounting\IfrsAdapter;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Models\Manufacturing\ProductionOrder;

/**
 * Observes ProductionOrder status changes to trigger GL postings.
 *
 * GL posting happens on completion (WIP → Finished Goods).
 * Material/labor/overhead GL posting is handled at recording time,
 * not via observer, because those are additive during production.
 */
class ProductionOrderObserver
{
    public function __construct(
        protected IfrsAdapter $ifrsAdapter,
    ) {}

    /**
     * Handle the ProductionOrder "updated" event.
     *
     * Posts GL entries when order transitions to 'completed'.
     */
    public function updated(ProductionOrder $order): void
    {
        // Only act on status changes to completed
        if (! $order->wasChanged('status')) {
            return;
        }

        if ($order->status === ProductionOrder::STATUS_COMPLETED) {
            $this->postCompletionToLedger($order);
        }

        if ($order->status === ProductionOrder::STATUS_CANCELLED && $order->getOriginal('status') === ProductionOrder::STATUS_IN_PROGRESS) {
            $this->reverseLedgerEntries($order);
        }
    }

    /**
     * Post production completion GL entries.
     *
     * DR 620/630 (Finished Goods) / CR 600 (WIP)
     * For co-production: multiple DR lines with allocated costs.
     */
    protected function postCompletionToLedger(ProductionOrder $order): void
    {
        try {
            $this->ifrsAdapter->postProductionCompletion($order);

            Log::info('ProductionOrderObserver: Posted completion GL entries', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_cost' => $order->total_production_cost,
            ]);
        } catch (\Exception $e) {
            Log::error('ProductionOrderObserver: Failed to post completion to ledger', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw — don't block order completion
        }
    }

    /**
     * Reverse GL entries when an in-progress order is cancelled.
     */
    protected function reverseLedgerEntries(ProductionOrder $order): void
    {
        try {
            $this->ifrsAdapter->reverseProductionOrder($order);

            Log::info('ProductionOrderObserver: Reversed GL entries for cancelled order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        } catch (\Exception $e) {
            Log::error('ProductionOrderObserver: Failed to reverse ledger entries', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

// CLAUDE-CHECKPOINT
