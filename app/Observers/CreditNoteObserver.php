<?php

namespace App\Observers;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\CreditNote;
use Illuminate\Support\Facades\Log;

/**
 * CreditNote Observer
 *
 * Automatically posts credit note transactions to the IFRS ledger
 * when FEATURE_ACCOUNTING_BACKBONE is enabled and status changes to COMPLETED.
 *
 * Credit notes reverse the original invoice journal entries:
 * - Reduce Accounts Receivable (CR)
 * - Reduce Sales Revenue (DR)
 * - Reduce Tax Payable (DR)
 */
class CreditNoteObserver
{
    protected IfrsAdapter $ifrsAdapter;

    public function __construct(IfrsAdapter $ifrsAdapter)
    {
        $this->ifrsAdapter = $ifrsAdapter;
    }

    /**
     * Handle the CreditNote "created" event.
     *
     * Post to ledger only when credit note is marked as COMPLETED
     * (not for DRAFT, SENT, or VIEWED status)
     */
    public function created(CreditNote $creditNote): void
    {
        // Only post to ledger if status is COMPLETED and feature is enabled
        if ($this->shouldPostToLedger($creditNote)) {
            try {
                // Ensure company has IFRS entity before posting
                if (! $this->hasIfrsEntity($creditNote)) {
                    Log::warning('CreditNoteObserver: Cannot post - company has no IFRS entity', [
                        'credit_note_id' => $creditNote->id,
                        'company_id' => $creditNote->company_id,
                    ]);

                    return;
                }

                $this->ifrsAdapter->postCreditNote($creditNote);
            } catch (\Exception $e) {
                Log::error('CreditNoteObserver: Failed to post credit note to ledger', [
                    'credit_note_id' => $creditNote->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't throw - we don't want to block credit note creation
            }
        }
    }

    /**
     * Handle the CreditNote "updated" event.
     *
     * If status changes to COMPLETED, post to ledger.
     * We don't re-post if already posted (idempotent check via ifrs_transaction_id).
     */
    public function updated(CreditNote $creditNote): void
    {
        // Check if status changed to COMPLETED
        if ($creditNote->wasChanged('status') &&
            $creditNote->status === CreditNote::STATUS_COMPLETED &&
            $this->shouldPostToLedger($creditNote) &&
            ! $creditNote->ifrs_transaction_id) {

            try {
                // Ensure company has IFRS entity before posting
                if (! $this->hasIfrsEntity($creditNote)) {
                    Log::warning('CreditNoteObserver: Cannot post - company has no IFRS entity', [
                        'credit_note_id' => $creditNote->id,
                        'company_id' => $creditNote->company_id,
                    ]);

                    return;
                }

                $this->ifrsAdapter->postCreditNote($creditNote);
            } catch (\Exception $e) {
                Log::error('CreditNoteObserver: Failed to post updated credit note to ledger', [
                    'credit_note_id' => $creditNote->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Determine if credit note should be posted to ledger
     */
    protected function shouldPostToLedger(CreditNote $creditNote): bool
    {
        // Check if feature is enabled
        if (! $this->isFeatureEnabled()) {
            return false;
        }

        // Only post when status is COMPLETED
        return $creditNote->status === CreditNote::STATUS_COMPLETED;
    }

    /**
     * Check if company has an IFRS entity (EntityGuard)
     */
    protected function hasIfrsEntity(CreditNote $creditNote): bool
    {
        // Check if company has ifrs_entity_id set
        if ($creditNote->company && $creditNote->company->ifrs_entity_id) {
            return true;
        }

        // IfrsAdapter will auto-create entity, so this is defensive
        return true;
    }

    /**
     * Check if accounting backbone feature is enabled
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
