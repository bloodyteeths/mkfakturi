<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fix JN (Journal Entry) transactions to use compound posting mode.
 *
 * The IFRS package's postBasic() creates double-entry pairs where every line item
 * is paired against the main account. For multi-line journal entries, this means
 * the main account gets phantom ledger entries equal to the sum of ALL line items,
 * distorting its balance and breaking trial balance, balance sheet, etc.
 *
 * Fix: Convert all JN transactions to compound mode, where debits are matched
 * directly against credits without routing through a single main account.
 *
 * For each JN transaction:
 * 1. Delete existing (incorrect) ledger entries
 * 2. Move the first line item to become the main account (compound mode requires it)
 * 3. Set compound=true and main_account_amount
 * 4. Re-post using compound allocation
 */
return new class extends Migration
{
    public function up(): void
    {
        // Find all JN transactions across all entities
        $transactions = DB::table('ifrs_transactions')
            ->where('transaction_type', 'JN')
            ->get();

        if ($transactions->isEmpty()) {
            return;
        }

        // We need a user with the correct entity_id for EntityScope
        // Group transactions by entity to minimize user switches
        $byEntity = $transactions->groupBy('entity_id');

        foreach ($byEntity as $entityId => $entityTransactions) {
            $entity = DB::table('ifrs_entities')->where('id', $entityId)->first();
            if (! $entity) {
                continue;
            }

            // Find a user linked to this entity's company
            $company = DB::table('companies')->where('ifrs_entity_id', $entityId)->first();
            if (! $company) {
                continue;
            }

            $user = DB::table('users')->where('id', $company->creator_id ?? 0)->first();
            if (! $user) {
                // Try any user with this entity_id
                $user = DB::table('users')->where('entity_id', $entityId)->first();
            }
            if (! $user) {
                // Try the company owner
                $user = \App\Models\User::whereHas('companies', function ($q) use ($company) {
                    $q->where('companies.id', $company->id);
                })->first();
            }
            if (! $user) {
                Log::warning("JN compound fix: no user found for entity {$entityId}, skipping");
                continue;
            }

            // Set entity context for EntityScope
            $userModel = \App\Models\User::find($user->id ?? $user);
            $originalEntityId = $userModel->entity_id;
            $userModel->entity_id = $entityId;
            $userModel->saveQuietly();
            auth()->loginUsingId($userModel->id);

            $fixed = 0;
            foreach ($entityTransactions as $txn) {
                // Skip if already compound
                if ($txn->compound) {
                    continue;
                }

                // Get line items ordered by id
                $lineItems = DB::table('ifrs_line_items')
                    ->where('transaction_id', $txn->id)
                    ->orderBy('id')
                    ->get();

                if ($lineItems->isEmpty()) {
                    continue;
                }

                $firstItem = $lineItems->first();

                DB::beginTransaction();
                try {
                    // 1. Delete existing (incorrect) ledger entries
                    DB::table('ifrs_ledgers')->where('transaction_id', $txn->id)->delete();

                    // 2. Update transaction to compound mode
                    //    Main account = first line item's account
                    DB::table('ifrs_transactions')->where('id', $txn->id)->update([
                        'account_id' => $firstItem->account_id,
                        'compound' => true,
                        'main_account_amount' => $firstItem->amount,
                        'credited' => $firstItem->credited,
                        'updated_at' => now(),
                    ]);

                    // 3. Remove the first line item (it's now the main account)
                    DB::table('ifrs_line_items')->where('id', $firstItem->id)->delete();

                    // 4. Re-post using compound allocation
                    $transaction = \IFRS\Models\Transaction::find($txn->id);
                    $transaction->load('lineItems');
                    $transaction->post();

                    DB::commit();
                    $fixed++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("JN compound fix: failed txn {$txn->id} (ref: {$txn->reference}): {$e->getMessage()}");
                    throw $e; // Fail visibly
                }
            }

            // Restore user's original entity_id
            $userModel->entity_id = $originalEntityId;
            $userModel->saveQuietly();

            Log::info("JN compound fix: fixed {$fixed} transactions for entity {$entityId}");
        }
    }

    public function down(): void
    {
    }
};
