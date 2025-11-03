<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Domain\Accounting\IfrsAdapter;
use Illuminate\Support\Facades\Log;

/**
 * Invoice Observer
 *
 * Automatically posts invoice transactions to the IFRS ledger
 * when FEATURE_ACCOUNTING_BACKBONE is enabled.
 *
 * @package App\Observers
 */
class InvoiceObserver
{
    protected IfrsAdapter $ifrsAdapter;

    public function __construct(IfrsAdapter $ifrsAdapter)
    {
        $this->ifrsAdapter = $ifrsAdapter;
    }

    /**
     * Handle the Invoice "created" event.
     *
     * Post to ledger only when invoice is marked as SENT, VIEWED, or COMPLETED
     * (not for DRAFT status)
     *
     * @param Invoice $invoice
     * @return void
     */
    public function created(Invoice $invoice): void
    {
        // Only post to ledger if not in draft status and feature is enabled
        if ($this->shouldPostToLedger($invoice)) {
            try {
                $this->ifrsAdapter->postInvoice($invoice);
            } catch (\Exception $e) {
                Log::error('InvoiceObserver: Failed to post invoice to ledger', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't throw - we don't want to block invoice creation
            }
        }
    }

    /**
     * Handle the Invoice "updated" event.
     *
     * If status changes from DRAFT to SENT/VIEWED/COMPLETED, post to ledger.
     * We don't re-post if already posted (idempotent).
     *
     * @param Invoice $invoice
     * @return void
     */
    public function updated(Invoice $invoice): void
    {
        // Check if status changed from DRAFT to a posted status
        if ($invoice->wasChanged('status') &&
            $invoice->getOriginal('status') === Invoice::STATUS_DRAFT &&
            $this->shouldPostToLedger($invoice) &&
            !$invoice->ifrs_transaction_id) {

            try {
                $this->ifrsAdapter->postInvoice($invoice);
            } catch (\Exception $e) {
                Log::error('InvoiceObserver: Failed to post updated invoice to ledger', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Determine if invoice should be posted to ledger
     *
     * @param Invoice $invoice
     * @return bool
     */
    protected function shouldPostToLedger(Invoice $invoice): bool
    {
        // Check if feature is enabled
        if (!$this->isFeatureEnabled()) {
            return false;
        }

        // Only post non-draft invoices
        $postableStatuses = [
            Invoice::STATUS_SENT,
            Invoice::STATUS_VIEWED,
            Invoice::STATUS_COMPLETED,
            Invoice::STATUS_UNPAID,
            Invoice::STATUS_PARTIALLY_PAID,
            Invoice::STATUS_PAID,
        ];

        return in_array($invoice->status, $postableStatuses);
    }

    /**
     * Check if accounting backbone feature is enabled
     *
     * @return bool
     */
    protected function isFeatureEnabled(): bool
    {
        // Check Laravel Pennant feature flag or config
        if (function_exists('feature')) {
            return feature('accounting_backbone');
        }

        return config('ifrs.enabled', false) ||
               env('FEATURE_ACCOUNTING_BACKBONE', false);
    }
}

// CLAUDE-CHECKPOINT
