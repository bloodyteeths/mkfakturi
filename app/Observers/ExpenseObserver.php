<?php

namespace App\Observers;

use App\Models\Expense;
use App\Domain\Accounting\IfrsAdapter;
use Illuminate\Support\Facades\Log;

/**
 * Expense Observer
 *
 * Automatically posts expense transactions to the IFRS ledger
 * when FEATURE_ACCOUNTING_BACKBONE is enabled.
 *
 * @package App\Observers
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
     *
     * @param Expense $expense
     * @return void
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
     * Handle the Expense "updated" event.
     *
     * Re-post to ledger if not already posted (idempotent).
     *
     * @param Expense $expense
     * @return void
     */
    public function updated(Expense $expense): void
    {
        // Only post if not already posted and feature is enabled
        if ($this->isFeatureEnabled() && !$expense->ifrs_transaction_id) {
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
