<?php

namespace App\Observers;

use App\Models\Bill;
use App\Domain\Accounting\IfrsAdapter;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;

/**
 * Bill Observer
 *
 * Automatically posts bill transactions to the IFRS ledger
 * when FEATURE_ACCOUNTING_BACKBONE is enabled.
 *
 * @package App\Observers
 */
class BillObserver
{
    protected IfrsAdapter $ifrsAdapter;

    public function __construct(IfrsAdapter $ifrsAdapter)
    {
        $this->ifrsAdapter = $ifrsAdapter;
    }

    /**
     * Handle the Bill "created" event.
     *
     * Generate bill number and unique hash if not set
     *
     * @param Bill $bill
     * @return void
     */
    public function created(Bill $bill): void
    {
        // Generate unique hash if not set
        if (!$bill->unique_hash) {
            try {
                // Use dedicated connection when configured, otherwise fall back to default
                $connection = config('hashids.connections.'.Bill::class) ? Bill::class : null;

                if ($connection) {
                    $bill->unique_hash = Hashids::connection($connection)->encode($bill->id);
                } else {
                    $bill->unique_hash = Hashids::encode($bill->id);
                }

                $bill->saveQuietly();
            } catch (\Throwable $e) {
                Log::error('BillObserver: Failed to generate unique hash', [
                    'bill_id' => $bill->id,
                    'error' => $e->getMessage(),
                ]);
                // Do not block bill creation on hash generation failures
            }
        }

        // Post to ledger only when bill is marked as COMPLETED
        if ($this->shouldPostToLedger($bill)) {
            try {
                $this->ifrsAdapter->postBill($bill);
            } catch (\Exception $e) {
                Log::error('BillObserver: Failed to post bill to ledger', [
                    'bill_id' => $bill->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't throw - we don't want to block bill creation
            }
        }
    }

    /**
     * Handle the Bill "updating" event.
     *
     * Prevent updates if the bill falls within a locked tax period.
     *
     * @param Bill $bill
     * @return bool|null
     */
    public function updating(Bill $bill): ?bool
    {
        // Check if bill date falls within a locked tax period
        if ($this->isInLockedPeriod($bill)) {
            throw new \Exception('Cannot edit bill. Tax period is locked.');
        }

        return true;
    }

    /**
     * Handle the Bill "updated" event.
     *
     * If status changes to COMPLETED, post to ledger.
     * We don't re-post if already posted (idempotent).
     *
     * @param Bill $bill
     * @return void
     */
    public function updated(Bill $bill): void
    {
        // Check if status changed to COMPLETED
        if ($bill->wasChanged('status') &&
            $bill->status === Bill::STATUS_COMPLETED &&
            $this->shouldPostToLedger($bill) &&
            !$bill->ifrs_transaction_id) {

            try {
                $this->ifrsAdapter->postBill($bill);
            } catch (\Exception $e) {
                Log::error('BillObserver: Failed to post updated bill to ledger', [
                    'bill_id' => $bill->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the Bill "deleting" event.
     *
     * Prevent deletion if the bill falls within a locked tax period.
     *
     * @param Bill $bill
     * @return bool|null
     */
    public function deleting(Bill $bill): ?bool
    {
        // Check if bill date falls within a locked tax period
        if ($this->isInLockedPeriod($bill)) {
            throw new \Exception('Cannot delete bill. Tax period is locked.');
        }

        return true;
    }

    /**
     * Determine if bill should be posted to ledger
     *
     * @param Bill $bill
     * @return bool
     */
    protected function shouldPostToLedger(Bill $bill): bool
    {
        // Check if feature is enabled
        if (!$this->isFeatureEnabled()) {
            return false;
        }

        // Only post completed bills
        return $bill->status === Bill::STATUS_COMPLETED;
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

    /**
     * Check if bill falls within a locked tax period.
     *
     * @param Bill $bill
     * @return bool
     */
    protected function isInLockedPeriod(Bill $bill): bool
    {
        // Check if tax period locking is enabled
        if (!config('tax.period_locking_enabled', true)) {
            return false;
        }

        // Find locked periods that contain this bill date
        $lockedPeriod = \App\Models\TaxReportPeriod::where('company_id', $bill->company_id)
            ->where('start_date', '<=', $bill->bill_date)
            ->where('end_date', '>=', $bill->bill_date)
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
