<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Follow-up migration: merge duplicate old/new account codes.
 *
 * The seeder created official codes (130, 230, 740, 741, 742) with wrong names,
 * and the previous migration couldn't remap old codes (131, 231, 720, 721, 722)
 * because the target codes already existed.
 *
 * This migration:
 * 1. Reassigns journal entries from old account IDs to new account IDs
 * 2. Updates 130/230/740/741/742 to official names
 * 3. Deletes orphaned old codes (131/231/720/721/722) that now have no data
 *
 * IDEMPOTENT: Safe to re-run.
 */
return new class extends Migration
{
    /**
     * old_code => [new_code, official_name_for_new_code]
     */
    private array $merges = [
        '131' => ['130', 'Данок на додадена вредност'],
        '231' => ['230', 'Обврски за данокот на додадена вредност'],
        '720' => ['740', 'Приходи од продажба на добра (производи) и услуги во земјата'],
        '721' => ['741', 'Приходи од продажба на добра (стоки) во земјата'],
        '722' => ['742', 'Приходи од продажба на добра и услуги во странство'],
    ];

    public function up(): void
    {
        $companyIds = DB::table('accounts')
            ->distinct()
            ->pluck('company_id');

        $totalReassigned = 0;
        $totalDeleted = 0;
        $totalRenamed = 0;

        foreach ($companyIds as $companyId) {
            foreach ($this->merges as $oldCode => [$newCode, $officialName]) {
                $oldAccount = DB::table('accounts')
                    ->where('company_id', $companyId)
                    ->where('code', $oldCode)
                    ->first();

                $newAccount = DB::table('accounts')
                    ->where('company_id', $companyId)
                    ->where('code', $newCode)
                    ->first();

                // Update name on new code account to official name
                if ($newAccount) {
                    DB::table('accounts')
                        ->where('id', $newAccount->id)
                        ->update(['name' => $officialName]);
                    $totalRenamed++;
                }

                // If old code doesn't exist, nothing to merge
                if (! $oldAccount) {
                    continue;
                }

                // If new code doesn't exist either, the previous migration already handled it
                if (! $newAccount) {
                    continue;
                }

                // Both exist — reassign journal entries from old → new
                $reassigned = DB::table('ifrs_line_items')
                    ->where('account_id', $oldAccount->id)
                    ->update(['account_id' => $newAccount->id]);

                if ($reassigned > 0) {
                    Log::info("[AccountMerge] Company {$companyId}: Reassigned {$reassigned} line items from {$oldCode} (id:{$oldAccount->id}) → {$newCode} (id:{$newAccount->id})");
                    $totalReassigned += $reassigned;
                }

                // Also check ifrs_ledgers (some IFRS tables reference account_id)
                if (DB::getSchemaBuilder()->hasTable('ifrs_ledgers')) {
                    $ledgerReassigned = DB::table('ifrs_ledgers')
                        ->where('post_account', $oldAccount->id)
                        ->update(['post_account' => $newAccount->id]);

                    if ($ledgerReassigned > 0) {
                        Log::info("[AccountMerge] Company {$companyId}: Reassigned {$ledgerReassigned} ledger entries for {$oldCode} → {$newCode}");
                        $totalReassigned += $ledgerReassigned;
                    }
                }

                // Now delete the old account if it has no remaining references
                $remainingRefs = DB::table('ifrs_line_items')
                    ->where('account_id', $oldAccount->id)
                    ->count();

                if ($remainingRefs === 0) {
                    DB::table('accounts')
                        ->where('id', $oldAccount->id)
                        ->delete();
                    $totalDeleted++;
                } else {
                    Log::warning("[AccountMerge] Company {$companyId}: Cannot delete {$oldCode} (id:{$oldAccount->id}), still has {$remainingRefs} references");
                }
            }
        }

        Log::info("[AccountMerge] Done: {$totalRenamed} names updated, {$totalReassigned} entries reassigned, {$totalDeleted} old accounts deleted across {$companyIds->count()} companies");
    }

    public function down(): void
    {
        Log::info('[AccountMerge] down() called — no-op. Old codes cannot be restored automatically.');
    }
}; // CLAUDE-CHECKPOINT
