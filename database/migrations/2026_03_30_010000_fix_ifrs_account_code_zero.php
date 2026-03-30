<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fix IFRS accounts with code "0" caused by mapUserAccountToIfrs() falling back
 * to the first user account (code "000" / R&D) instead of using the proper fallback code.
 *
 * Root cause: English specificName ("Receivable", "Sales", "Tax") never matched MK Cyrillic
 * account names → fell back to first account of type → code "000" → IFRS stored as "0".
 *
 * This migration:
 * 1. Finds all IFRS accounts with code "0"
 * 2. Determines the correct code based on account_type
 * 3. Reassigns journal line_items from code "0" account to the correct account
 * 4. Deletes the orphaned code "0" account
 */
return new class extends Migration
{
    public function up(): void
    {
        // Map account_type to correct code based on what mapUserAccountToIfrs should have used
        $typeToCorrectCode = [
            'RECEIVABLE' => ['code' => '120', 'name' => 'Побарувања од купувачи'],
            'OPERATING_REVENUE' => ['code' => '740', 'name' => 'Приходи од продажба'],
            'BANK' => ['code' => '102', 'name' => 'Готовина'],
            'PAYABLE' => ['code' => '220', 'name' => 'Обврски кон добавувачи'],
            'CONTROL' => ['code' => '230', 'name' => 'Обврски за ДДВ'],
            'OPERATING_EXPENSE' => ['code' => '445', 'name' => 'Трошоци за провизии'],
            'CURRENT_ASSET' => ['code' => '130', 'name' => 'Претходен данок - ДДВ'],
        ];

        // Find all IFRS accounts with code "0"
        $brokenAccounts = DB::table('ifrs_accounts')
            ->where('code', '0')
            ->get();

        if ($brokenAccounts->isEmpty()) {
            Log::info('[IFRS Fix] No accounts with code "0" found — nothing to fix.');
            return;
        }

        Log::info("[IFRS Fix] Found {$brokenAccounts->count()} accounts with code '0'");

        foreach ($brokenAccounts as $broken) {
            $mapping = $typeToCorrectCode[$broken->account_type] ?? null;

            if (! $mapping) {
                Log::warning("[IFRS Fix] Unknown account_type '{$broken->account_type}' for account #{$broken->id} (entity {$broken->entity_id}) — skipping");
                continue;
            }

            $correctCode = $mapping['code'];
            $correctName = $mapping['name'];

            // Check if the correct account already exists for this entity
            $existingCorrect = DB::table('ifrs_accounts')
                ->where('entity_id', $broken->entity_id)
                ->where('code', $correctCode)
                ->first();

            if ($existingCorrect) {
                // Correct account exists — move all references from broken to correct
                $targetId = $existingCorrect->id;

                // Move line_items
                $movedLines = DB::table('ifrs_line_items')
                    ->where('account_id', $broken->id)
                    ->update(['account_id' => $targetId]);

                // Move transactions referencing this account
                $movedTxAccounts = DB::table('ifrs_transactions')
                    ->where('account_id', $broken->id)
                    ->update(['account_id' => $targetId]);

                // Move ledger entries (post_account and folio_account)
                $movedPostAccounts = DB::table('ifrs_ledgers')
                    ->where('post_account', $broken->id)
                    ->update(['post_account' => $targetId]);

                $movedFolioAccounts = DB::table('ifrs_ledgers')
                    ->where('folio_account', $broken->id)
                    ->update(['folio_account' => $targetId]);

                Log::info("[IFRS Fix] Entity {$broken->entity_id}: Moved line_items={$movedLines}, tx_accounts={$movedTxAccounts}, post={$movedPostAccounts}, folio={$movedFolioAccounts} from code '0' (#{$broken->id}) to code '{$correctCode}' (#{$existingCorrect->id})");

                // Delete the broken account (now has no references)
                DB::table('ifrs_accounts')->where('id', $broken->id)->delete();
                Log::info("[IFRS Fix] Deleted orphaned code '0' account #{$broken->id}");
            } else {
                // No correct account exists — just update the code and name
                DB::table('ifrs_accounts')
                    ->where('id', $broken->id)
                    ->update([
                        'code' => $correctCode,
                        'name' => $correctName,
                    ]);

                Log::info("[IFRS Fix] Entity {$broken->entity_id}: Renamed code '0' account #{$broken->id} to code '{$correctCode}' ({$correctName})");
            }
        }

        Log::info('[IFRS Fix] Complete — all code "0" accounts resolved.');

        // Fix 2: Wrong GL codes from reconciliation mapping
        // 3010 was used for "Основна главнина" but MK chart 3010 = raw materials cost.
        // Correct code is 900 (Основна главнина - запишан и уплатен капитал).
        $wrongCodeMappings = [
            ['wrong' => '3010', 'correct' => '900', 'name' => 'Основна главнина', 'type' => 'EQUITY'],
            ['wrong' => '3220', 'correct' => '950', 'name' => 'Задржана добивка', 'type' => 'EQUITY'],
            ['wrong' => '2900', 'correct' => '2810', 'name' => 'Долгорочни кредити од банки', 'type' => 'NON_CURRENT_LIABILITY'],
        ];

        foreach ($wrongCodeMappings as $mapping) {
            // Only fix accounts that were created by reconciliation (check narration pattern)
            $wrongAccounts = DB::table('ifrs_accounts')
                ->where('code', $mapping['wrong'])
                ->get();

            foreach ($wrongAccounts as $acc) {
                // Check if this account was used in reconciliation-created journal entries
                $hasReconTx = DB::table('ifrs_line_items')
                    ->join('ifrs_transactions', 'ifrs_transactions.id', '=', 'ifrs_line_items.transaction_id')
                    ->where('ifrs_line_items.account_id', $acc->id)
                    ->where('ifrs_transactions.reference', 'like', 'BANK-TX-%')
                    ->exists();

                if (! $hasReconTx) {
                    continue; // Not created by reconciliation — leave it alone
                }

                // Check if correct code already exists for this entity
                $correct = DB::table('ifrs_accounts')
                    ->where('entity_id', $acc->entity_id)
                    ->where('code', $mapping['correct'])
                    ->first();

                if ($correct) {
                    // Move references to correct account
                    DB::table('ifrs_line_items')->where('account_id', $acc->id)->update(['account_id' => $correct->id]);
                    DB::table('ifrs_transactions')->where('account_id', $acc->id)->update(['account_id' => $correct->id]);
                    DB::table('ifrs_ledgers')->where('post_account', $acc->id)->update(['post_account' => $correct->id]);
                    DB::table('ifrs_ledgers')->where('folio_account', $acc->id)->update(['folio_account' => $correct->id]);
                    DB::table('ifrs_accounts')->where('id', $acc->id)->delete();
                    Log::info("[IFRS Fix] Entity {$acc->entity_id}: Moved code '{$mapping['wrong']}' → '{$mapping['correct']}' (merged into existing)");
                } else {
                    // Just rename
                    DB::table('ifrs_accounts')->where('id', $acc->id)->update([
                        'code' => $mapping['correct'],
                        'name' => $mapping['name'],
                        'account_type' => $mapping['type'],
                    ]);
                    Log::info("[IFRS Fix] Entity {$acc->entity_id}: Renamed code '{$mapping['wrong']}' → '{$mapping['correct']}' ({$mapping['name']})");
                }
            }
        }

        Log::info('[IFRS Fix] GL code corrections complete.');
    }

    public function down(): void
    {
        // Not reversible — the code "0" accounts were wrong and should not be restored.
        // Journal entries have been correctly reassigned to proper accounts.
    }
};
