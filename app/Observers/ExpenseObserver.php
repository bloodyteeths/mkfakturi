<?php

namespace App\Observers;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Expense;
use Illuminate\Support\Facades\Log;

/**
 * Expense Observer
 *
 * Automatically posts expense transactions to the IFRS ledger
 * when FEATURE_ACCOUNTING_BACKBONE is enabled.
 */
class ExpenseObserver
{
    protected IfrsAdapter $ifrsAdapter;

    public function __construct(IfrsAdapter $ifrsAdapter)
    {
        $this->ifrsAdapter = $ifrsAdapter;
    }

    /**
     * Handle the Expense "created" event.
     *
     * Post to ledger when expense is created.
     */
    public function created(Expense $expense): void
    {
        // Only post to ledger if feature is enabled
        if ($this->isFeatureEnabled()) {
            try {
                $this->ifrsAdapter->postExpense($expense);
            } catch (\Exception $e) {
                Log::error('ExpenseObserver: Failed to post expense to ledger', [
                    'expense_id' => $expense->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't throw - we don't want to block expense creation
            }
        }
    }

    /**
     * Handle the Expense "updating" event.
     *
     * Prevent updates if the expense falls within a locked tax period.
     */
    public function updating(Expense $expense): ?bool
    {
        // Check if expense date falls within a locked tax period
        if ($this->isInLockedPeriod($expense)) {
            throw new \Exception('Cannot edit expense. Tax period is locked.');
        }

        return true;
    }

    /**
     * Handle the Expense "updated" event.
     *
     * Re-post to ledger if not already posted (idempotent).
     */
    public function updated(Expense $expense): void
    {
        // Only post if not already posted and feature is enabled
        if ($this->isFeatureEnabled() && ! $expense->ifrs_transaction_id) {
            try {
                $this->ifrsAdapter->postExpense($expense);
            } catch (\Exception $e) {
                Log::error('ExpenseObserver: Failed to post updated expense to ledger', [
                    'expense_id' => $expense->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the Expense "deleting" event.
     *
     * Prevent deletion if the expense falls within a locked tax period.
     */
    public function deleting(Expense $expense): ?bool
    {
        // Check if expense date falls within a locked tax period
        if ($this->isInLockedPeriod($expense)) {
            throw new \Exception('Cannot delete expense. Tax period is locked.');
        }

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
     * Check if expense falls within a locked tax period.
     */
    protected function isInLockedPeriod(Expense $expense): bool
    {
        // Check if tax period locking is enabled
        if (! config('tax.period_locking_enabled', true)) {
            return false;
        }

        // Find locked periods that contain this expense date
        $lockedPeriod = \App\Models\TaxReportPeriod::where('company_id', $expense->company_id)
            ->where('start_date', '<=', $expense->expense_date)
            ->where('end_date', '>=', $expense->expense_date)
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
