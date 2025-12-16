<?php

namespace App\Observers;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\BillPayment;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;

/**
 * BillPayment Observer
 *
 * Automatically posts bill payment transactions to the IFRS ledger
 * when FEATURE_ACCOUNTING_BACKBONE is enabled.
 */
class BillPaymentObserver
{
    protected IfrsAdapter $ifrsAdapter;

    public function __construct(IfrsAdapter $ifrsAdapter)
    {
        $this->ifrsAdapter = $ifrsAdapter;
    }

    /**
     * Handle the BillPayment "created" event.
     *
     * Generate unique hash and post to ledger
     */
    public function created(BillPayment $billPayment): void
    {
        // Generate unique hash if not set
        if (! $billPayment->unique_hash) {
            try {
                // Use dedicated connection when configured, otherwise fall back to default
                $connection = config('hashids.connections.'.BillPayment::class) ? BillPayment::class : null;

                if ($connection) {
                    $billPayment->unique_hash = Hashids::connection($connection)->encode($billPayment->id);
                } else {
                    $billPayment->unique_hash = Hashids::encode($billPayment->id);
                }

                $billPayment->saveQuietly();
            } catch (\Throwable $e) {
                Log::error('BillPaymentObserver: Failed to generate unique hash', [
                    'bill_payment_id' => $billPayment->id,
                    'error' => $e->getMessage(),
                ]);
                // Do not block bill payment creation on hash generation failures
            }
        }

        // Update bill's paid status
        if ($billPayment->bill) {
            $billPayment->bill->updatePaidStatus();
        }

        // Post to ledger
        if ($this->shouldPostToLedger($billPayment)) {
            try {
                $this->ifrsAdapter->postBillPayment($billPayment);
            } catch (\Exception $e) {
                Log::error('BillPaymentObserver: Failed to post bill payment to ledger', [
                    'bill_payment_id' => $billPayment->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't throw - we don't want to block bill payment creation
            }
        }
    }

    /**
     * Handle the BillPayment "updating" event.
     *
     * Prevent updates if the bill payment falls within a locked tax period.
     */
    public function updating(BillPayment $billPayment): ?bool
    {
        // Check if bill payment date falls within a locked tax period
        if ($this->isInLockedPeriod($billPayment)) {
            throw new \Exception('Cannot edit bill payment. Tax period is locked.');
        }

        return true;
    }

    /**
     * Handle the BillPayment "updated" event.
     *
     * Update bill's paid status when payment amount changes
     */
    public function updated(BillPayment $billPayment): void
    {
        // If amount changed, update bill's paid status
        if ($billPayment->wasChanged('amount') && $billPayment->bill) {
            $billPayment->bill->updatePaidStatus();
        }
    }

    /**
     * Handle the BillPayment "deleting" event.
     *
     * Prevent deletion if the bill payment falls within a locked tax period.
     */
    public function deleting(BillPayment $billPayment): ?bool
    {
        // Check if bill payment date falls within a locked tax period
        if ($this->isInLockedPeriod($billPayment)) {
            throw new \Exception('Cannot delete bill payment. Tax period is locked.');
        }

        return true;
    }

    /**
     * Handle the BillPayment "deleted" event.
     *
     * Update bill's paid status when payment is deleted
     */
    public function deleted(BillPayment $billPayment): void
    {
        // Update bill's paid status
        if ($billPayment->bill) {
            $billPayment->bill->updatePaidStatus();
        }
    }

    /**
     * Determine if bill payment should be posted to ledger
     */
    protected function shouldPostToLedger(BillPayment $billPayment): bool
    {
        // Check if feature is enabled
        if (! $this->isFeatureEnabled()) {
            return false;
        }

        // Always post bill payments (they are created in completed state)
        return true;
    }

    /**
     * Check if accounting backbone feature is enabled
     */
    protected function isFeatureEnabled(): bool
    {
        // Check Laravel Pennant feature flag or config
        if (function_exists('feature')) {
            return feature('accounting-backbone');
        }

        return config('ifrs.enabled', false) ||
               env('FEATURE_ACCOUNTING_BACKBONE', false);
    }

    /**
     * Check if bill payment falls within a locked tax period.
     */
    protected function isInLockedPeriod(BillPayment $billPayment): bool
    {
        // Check if tax period locking is enabled
        if (! config('tax.period_locking_enabled', true)) {
            return false;
        }

        // Find locked periods that contain this bill payment date
        $lockedPeriod = \App\Models\TaxReportPeriod::where('company_id', $billPayment->company_id)
            ->where('start_date', '<=', $billPayment->payment_date)
            ->where('end_date', '>=', $billPayment->payment_date)
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
