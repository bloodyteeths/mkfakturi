<?php

namespace App\Observers;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

/**
 * Invoice Observer
 *
 * Automatically posts invoice transactions to the IFRS ledger
 * when FEATURE_ACCOUNTING_BACKBONE is enabled.
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
     */
    public function created(Invoice $invoice): void
    {
        Log::debug('InvoiceObserver::created', [
            'invoice_id' => $invoice->id,
            'status' => $invoice->status,
            'should_post' => $this->shouldPostToLedger($invoice),
        ]);

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
     * Handle the Invoice "updating" event.
     *
     * Prevent updates if the invoice falls within a locked tax period.
     */
    public function updating(Invoice $invoice): ?bool
    {
        // Check if invoice date falls within a locked tax period
        if ($this->isInLockedPeriod($invoice)) {
            throw new \Exception('Cannot edit invoice. Tax period is locked.');
        }

        return true;
    }

    /**
     * Handle the Invoice "updated" event.
     *
     * If status changes from DRAFT to SENT/VIEWED/COMPLETED, post to ledger.
     * We don't re-post if already posted (idempotent).
     */
    public function updated(Invoice $invoice): void
    {
        $statusChanged = $invoice->wasChanged('status');
        $wasFromDraft = $invoice->getOriginal('status') === Invoice::STATUS_DRAFT;

        Log::debug('InvoiceObserver::updated', [
            'invoice_id' => $invoice->id,
            'status' => $invoice->status,
            'original_status' => $invoice->getOriginal('status'),
            'status_changed' => $statusChanged,
            'was_from_draft' => $wasFromDraft,
            'ifrs_transaction_id' => $invoice->ifrs_transaction_id,
        ]);

        // Check if status changed from DRAFT to a posted status
        if ($statusChanged && $wasFromDraft &&
            $this->shouldPostToLedger($invoice) &&
            ! $invoice->ifrs_transaction_id) {

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
     * Handle the Invoice "deleting" event.
     *
     * Prevent deletion if the invoice falls within a locked tax period.
     */
    public function deleting(Invoice $invoice): ?bool
    {
        // Check if invoice date falls within a locked tax period
        if ($this->isInLockedPeriod($invoice)) {
            throw new \Exception('Cannot delete invoice. Tax period is locked.');
        }

        return true;
    }

    /**
     * Determine if invoice should be posted to ledger
     */
    protected function shouldPostToLedger(Invoice $invoice): bool
    {
        // Check if feature is enabled
        if (! $this->isFeatureEnabled()) {
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
     * Note: We only check config/env here, NOT Pennant.
     * Pennant requires user context which may not be reliable in observers.
     * Per-company feature check is done in IfrsAdapter::isEnabled().
     */
    protected function isFeatureEnabled(): bool
    {
        // Only check global config/env - Pennant check is done in IfrsAdapter
        return config('ifrs.enabled', false) ||
               env('FEATURE_ACCOUNTING_BACKBONE', false);
    }

    /**
     * Check if invoice falls within a locked tax period.
     */
    protected function isInLockedPeriod(Invoice $invoice): bool
    {
        // Check if tax period locking is enabled
        if (! config('tax.period_locking_enabled', true)) {
            return false;
        }

        // Find locked periods that contain this invoice date
        $lockedPeriod = \App\Models\TaxReportPeriod::where('company_id', $invoice->company_id)
            ->where('start_date', '<=', $invoice->invoice_date)
            ->where('end_date', '>=', $invoice->invoice_date)
            ->where(function ($query) {
                $query->where('status', \App\Models\TaxReportPeriod::STATUS_CLOSED)
                    ->orWhere('status', \App\Models\TaxReportPeriod::STATUS_FILED)
                    ->orWhere('status', \App\Models\TaxReportPeriod::STATUS_AMENDED);
            })
            ->exists();

        return $lockedPeriod;
    }
}

// CLAUDE-CHECKPOINT
