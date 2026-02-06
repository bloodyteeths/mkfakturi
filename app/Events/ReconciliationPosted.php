<?php

namespace App\Events;

use App\Models\Payment;
use App\Models\Reconciliation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Reconciliation Posted
 *
 * Dispatched AFTER a matched reconciliation has been successfully
 * posted as a Payment record. The payment has been created, the
 * invoice paid status updated, and the reconciliation linked.
 *
 * Listeners can use this to:
 * - Send payment confirmation emails
 * - Post journal entries to the general ledger
 * - Update dashboards and analytics
 * - Trigger downstream accounting workflows
 *
 * P0-12: Reconciliation Posting Service
 */
class ReconciliationPosted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * The reconciliation that was posted.
     *
     * @var Reconciliation
     */
    public Reconciliation $reconciliation;

    /**
     * The payment that was created.
     *
     * @var Payment
     */
    public Payment $payment;

    /**
     * Create a new event instance.
     *
     * @param  Reconciliation  $reconciliation  The posted reconciliation record
     * @param  Payment  $payment  The newly created payment record
     */
    public function __construct(Reconciliation $reconciliation, Payment $payment)
    {
        $this->reconciliation = $reconciliation;
        $this->payment = $payment;
    }
}

// CLAUDE-CHECKPOINT
