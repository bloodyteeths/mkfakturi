<?php

namespace App\Events;

use App\Models\Reconciliation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Reconciliation Matched
 *
 * Dispatched when a bank transaction has been matched to an invoice
 * (either automatically or manually), but BEFORE a payment is created.
 *
 * Listeners can use this to:
 * - Log matching activity
 * - Send notifications to accountants
 * - Trigger additional validation workflows
 *
 * P0-12: Reconciliation Posting Service
 */
class ReconciliationMatched
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * The reconciliation that was matched.
     *
     * @var Reconciliation
     */
    public Reconciliation $reconciliation;

    /**
     * Create a new event instance.
     *
     * @param  Reconciliation  $reconciliation  The matched reconciliation record
     */
    public function __construct(Reconciliation $reconciliation)
    {
        $this->reconciliation = $reconciliation;
    }
}

// CLAUDE-CHECKPOINT
