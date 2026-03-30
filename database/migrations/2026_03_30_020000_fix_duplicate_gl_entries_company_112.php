<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Fix duplicate GL entries for company 112 (and any other affected companies).
 *
 * Bug: ReconciliationController::recordAsExpense() was calling postJournalEntry()
 * manually AFTER ExpenseObserver::created() already posted via postExpense().
 * This created duplicate GL entries for every reconciled expense:
 *   1. Transaction::CP from observer (legitimate, linked via expense.ifrs_transaction_id)
 *   2. Transaction::JN with reference "BANK-TX-{id}" from controller (duplicate)
 *
 * Also backfills NULL base_amount on expenses created via reconciliation.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ifrs_transactions') || ! Schema::hasTable('bank_transactions')) {
            return; // IFRS or banking not set up — nothing to fix
        }

        // Step 1: Find all duplicate BANK-TX-* journal entries linked to expenses
        // These are JN-type transactions whose BANK-TX reference points to a
        // bank_transaction that is linked to an expense (linked_type = 'expense')
        // AND that expense already has its own ifrs_transaction_id from the observer.

        $duplicates = DB::select("
            SELECT
                t.id AS ifrs_transaction_id,
                t.reference,
                t.entity_id,
                bt.id AS bank_transaction_id,
                bt.linked_id AS expense_id,
                e.ifrs_transaction_id AS observer_transaction_id
            FROM ifrs_transactions t
            INNER JOIN bank_transactions bt
                ON bt.id = CAST(REPLACE(t.reference, 'BANK-TX-', '') AS UNSIGNED)
            INNER JOIN expenses e
                ON e.id = bt.linked_id
            WHERE t.reference LIKE 'BANK-TX-%'
              AND t.transaction_type = 'JN'
              AND bt.linked_type = 'expense'
              AND e.ifrs_transaction_id IS NOT NULL
              AND e.ifrs_transaction_id != t.id
        ");

        $deletedCount = 0;

        foreach ($duplicates as $dup) {
            Log::info('[Migration] Removing duplicate GL entry', [
                'ifrs_transaction_id' => $dup->ifrs_transaction_id,
                'reference' => $dup->reference,
                'expense_id' => $dup->expense_id,
                'observer_transaction_id' => $dup->observer_transaction_id,
            ]);

            // Delete in FK-safe order: ledgers → assignments → line_items → transaction
            DB::table('ifrs_ledgers')
                ->where('transaction_id', $dup->ifrs_transaction_id)
                ->delete();

            DB::table('ifrs_assignments')
                ->where('transaction_id', $dup->ifrs_transaction_id)
                ->delete();

            DB::table('ifrs_line_items')
                ->where('transaction_id', $dup->ifrs_transaction_id)
                ->delete();

            DB::table('ifrs_transactions')
                ->where('id', $dup->ifrs_transaction_id)
                ->delete();

            $deletedCount++;
        }

        if ($deletedCount > 0) {
            Log::warning("[Migration] Deleted {$deletedCount} duplicate GL entries from reconciliation double-posting bug");
        }

        // Step 2: Handle edge case — BANK-TX-* entries where expense has NO
        // observer transaction (observer failed or feature was disabled).
        // In this case, adopt the BANK-TX-* transaction as the expense's GL entry.
        $orphans = DB::select("
            SELECT
                t.id AS ifrs_transaction_id,
                t.reference,
                bt.linked_id AS expense_id
            FROM ifrs_transactions t
            INNER JOIN bank_transactions bt
                ON bt.id = CAST(REPLACE(t.reference, 'BANK-TX-', '') AS UNSIGNED)
            INNER JOIN expenses e
                ON e.id = bt.linked_id
            WHERE t.reference LIKE 'BANK-TX-%'
              AND t.transaction_type = 'JN'
              AND bt.linked_type = 'expense'
              AND e.ifrs_transaction_id IS NULL
        ");

        foreach ($orphans as $orphan) {
            DB::table('expenses')
                ->where('id', $orphan->expense_id)
                ->update(['ifrs_transaction_id' => $orphan->ifrs_transaction_id]);

            Log::info('[Migration] Adopted orphan BANK-TX GL entry for expense', [
                'expense_id' => $orphan->expense_id,
                'ifrs_transaction_id' => $orphan->ifrs_transaction_id,
            ]);
        }

        // Step 3: Backfill NULL base_amount on expenses (dashboard shows 0 without it)
        $backfilled = DB::table('expenses')
            ->whereNull('base_amount')
            ->whereNotNull('amount')
            ->where('amount', '>', 0)
            ->update(['base_amount' => DB::raw('amount')]);

        if ($backfilled > 0) {
            Log::info("[Migration] Backfilled base_amount on {$backfilled} expenses");
        }
    }

    public function down(): void
    {
        // Cannot reverse deleted GL entries — they were duplicates
        // base_amount backfill is harmless and correct
    }
};
// CLAUDE-CHECKPOINT
