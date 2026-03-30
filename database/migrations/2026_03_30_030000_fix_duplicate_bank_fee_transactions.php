<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Fix duplicate bank fee transactions created by KomercijalnaStatementParser.
 *
 * Bug: The parser extracted the пров. (fee) column from each bank statement row
 * as a separate debit transaction. But in Komercijalna bank statements, the пров.
 * column is informational — fees are charged as batch transactions on decade
 * boundaries (e.g. "Пров. ПП за втора декада"). This caused double-counting
 * of fees, making the system balance lower than the real bank balance.
 *
 * Fix: Delete the fake fee transactions and their linked expenses + IFRS entries.
 * Recalculate bank account balances.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_transactions')) {
            return;
        }

        // Find all parser-generated fee transactions.
        // These have descriptions starting with "Провизија - " (the prefix the parser adds)
        // and are debit transactions. Real bank fee transactions use different descriptions
        // like "Пров. ПП за..." or "Пров. ПП (нал.+ген.извод)..."
        $feeTxs = DB::table('bank_transactions')
            ->where('description', 'like', 'Провизија - %')
            ->where('transaction_type', 'debit')
            ->get(['id', 'bank_account_id', 'linked_type', 'linked_id', 'amount', 'description']);

        $deletedTxCount = 0;
        $deletedExpCount = 0;
        $deletedIfrsCount = 0;
        $affectedAccounts = [];

        foreach ($feeTxs as $tx) {
            Log::info('[Migration] Removing duplicate fee transaction', [
                'bank_tx_id' => $tx->id,
                'amount' => $tx->amount,
                'description' => $tx->description,
            ]);

            // Clean up linked expense and its IFRS entries
            if ($tx->linked_type === 'expense' && $tx->linked_id) {
                $expense = DB::table('expenses')->where('id', $tx->linked_id)->first();
                if ($expense) {
                    // Delete IFRS entries for this expense
                    if ($expense->ifrs_transaction_id) {
                        DB::table('ifrs_ledgers')
                            ->where('transaction_id', $expense->ifrs_transaction_id)
                            ->delete();
                        DB::table('ifrs_assignments')
                            ->where('transaction_id', $expense->ifrs_transaction_id)
                            ->delete();
                        DB::table('ifrs_line_items')
                            ->where('transaction_id', $expense->ifrs_transaction_id)
                            ->delete();
                        DB::table('ifrs_transactions')
                            ->where('id', $expense->ifrs_transaction_id)
                            ->delete();
                        $deletedIfrsCount++;
                    }

                    // Delete the expense
                    DB::table('expenses')->where('id', $tx->linked_id)->delete();
                    $deletedExpCount++;
                }
            }

            // Track affected bank accounts for balance recalculation
            $affectedAccounts[$tx->bank_account_id] = true;

            // Delete the fake fee transaction
            DB::table('bank_transactions')->where('id', $tx->id)->delete();
            $deletedTxCount++;
        }

        // Recalculate balance for affected bank accounts
        foreach (array_keys($affectedAccounts) as $accountId) {
            $account = DB::table('bank_accounts')->where('id', $accountId)->first();
            if ($account) {
                $credits = DB::table('bank_transactions')
                    ->where('bank_account_id', $accountId)
                    ->where('transaction_type', 'credit')
                    ->sum('amount');

                $debits = DB::table('bank_transactions')
                    ->where('bank_account_id', $accountId)
                    ->where('transaction_type', 'debit')
                    ->sum('amount');

                $balance = (float) $account->opening_balance + (float) $credits - (float) $debits;

                DB::table('bank_accounts')
                    ->where('id', $accountId)
                    ->update(['current_balance' => round($balance, 2)]);

                Log::info('[Migration] Recalculated bank account balance', [
                    'account_id' => $accountId,
                    'old_balance' => $account->current_balance,
                    'new_balance' => round($balance, 2),
                ]);
            }
        }

        if ($deletedTxCount > 0) {
            Log::warning("[Migration] Cleaned up {$deletedTxCount} fake fee transactions, {$deletedExpCount} expenses, {$deletedIfrsCount} IFRS entries");
        }
    }

    public function down(): void
    {
        // Cannot reverse — deleted data was incorrectly imported
    }
};
// CLAUDE-CHECKPOINT
