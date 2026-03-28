<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IfrsAuditDuplicatesCommand extends Command
{
    protected $signature = 'ifrs:audit-duplicates
                            {--entity= : Audit a specific entity ID only}
                            {--fix-preview : Show what the merge migration would do (dry-run)}';

    protected $description = 'Audit IFRS accounts for duplicate codes per entity (read-only)';

    public function handle(): int
    {
        $this->info('IFRS Duplicate Account Audit');
        $this->info(str_repeat('=', 60));

        $entityFilter = $this->option('entity');

        // Find all duplicate code groups
        $query = DB::table('ifrs_accounts')
            ->select('entity_id', 'code', DB::raw('COUNT(*) as cnt'), DB::raw('GROUP_CONCAT(id ORDER BY id) as account_ids'))
            ->whereNull('deleted_at')
            ->groupBy('entity_id', 'code')
            ->having('cnt', '>', 1)
            ->orderBy('entity_id')
            ->orderBy('code');

        if ($entityFilter) {
            $query->where('entity_id', (int) $entityFilter);
        }

        $duplicates = $query->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate account codes found. All clean!');

            return self::SUCCESS;
        }

        $this->warn("Found {$duplicates->count()} duplicate code groups");
        $this->newLine();

        $totalEntities = $duplicates->pluck('entity_id')->unique()->count();
        $totalOrphanedEntries = 0;
        $totalDuplicateAccounts = 0;

        // Group by entity for display
        $byEntity = $duplicates->groupBy('entity_id');

        foreach ($byEntity as $entityId => $groups) {
            // Get entity/company name
            $entity = DB::table('ifrs_entities')->where('id', $entityId)->first();
            $companyName = $entity->name ?? "Entity #{$entityId}";
            $this->info("Entity {$entityId}: {$companyName}");
            $this->info(str_repeat('-', 50));

            $rows = [];

            foreach ($groups as $group) {
                $accountIds = explode(',', $group->account_ids);
                $totalDuplicateAccounts += count($accountIds) - 1; // subtract canonical

                // Get details for each account in the duplicate group
                $accounts = DB::table('ifrs_accounts')
                    ->whereIn('id', $accountIds)
                    ->whereNull('deleted_at')
                    ->get();

                foreach ($accounts as $account) {
                    // Count ledger entries for this specific account
                    $ledgerCount = DB::table('ifrs_ledgers')
                        ->where('post_account', $account->id)
                        ->whereNull('deleted_at')
                        ->count();

                    $folioCount = DB::table('ifrs_ledgers')
                        ->where('folio_account', $account->id)
                        ->whereNull('deleted_at')
                        ->count();

                    $lineItemCount = DB::table('ifrs_line_items')
                        ->where('account_id', $account->id)
                        ->whereNull('deleted_at')
                        ->count();

                    $txnCount = DB::table('ifrs_transactions')
                        ->where('account_id', $account->id)
                        ->whereNull('deleted_at')
                        ->count();

                    $rows[] = [
                        $account->code,
                        $account->id,
                        mb_substr($account->name, 0, 40),
                        $account->account_type,
                        $ledgerCount,
                        $folioCount,
                        $lineItemCount,
                        $txnCount,
                    ];

                    $totalOrphanedEntries += $ledgerCount;
                }
            }

            $this->table(
                ['Code', 'Account ID', 'Name', 'Type', 'Ledger(post)', 'Ledger(folio)', 'LineItems', 'Transactions'],
                $rows
            );

            $this->newLine();
        }

        // Summary
        $this->info(str_repeat('=', 60));
        $this->info('SUMMARY');
        $this->info(str_repeat('=', 60));
        $this->info("Entities affected: {$totalEntities}");
        $this->info("Duplicate code groups: {$duplicates->count()}");
        $this->info("Extra duplicate accounts (to be merged): {$totalDuplicateAccounts}");
        $this->info("Total ledger entries across all duplicates: {$totalOrphanedEntries}");

        if ($this->option('fix-preview')) {
            $this->newLine();
            $this->info('MERGE PREVIEW (what the migration would do):');
            $this->info(str_repeat('-', 50));

            foreach ($duplicates as $group) {
                $accountIds = array_map('intval', explode(',', $group->account_ids));

                // Find canonical: most ledger entries, then lowest ID
                $canonical = DB::table('ifrs_accounts')
                    ->select('ifrs_accounts.id', 'ifrs_accounts.name', DB::raw('COUNT(l.id) as entry_count'))
                    ->leftJoin('ifrs_ledgers as l', function ($join) {
                        $join->on('l.post_account', '=', 'ifrs_accounts.id')
                            ->whereNull('l.deleted_at');
                    })
                    ->whereIn('ifrs_accounts.id', $accountIds)
                    ->whereNull('ifrs_accounts.deleted_at')
                    ->groupBy('ifrs_accounts.id', 'ifrs_accounts.name')
                    ->orderByDesc('entry_count')
                    ->orderBy('ifrs_accounts.id')
                    ->first();

                $duplicateIds = array_filter($accountIds, fn ($id) => $id !== $canonical->id);

                $this->line("  Code {$group->code}: KEEP id={$canonical->id} \"{$canonical->name}\" ({$canonical->entry_count} entries), MERGE ids=[" . implode(',', $duplicateIds) . ']');
            }
        }

        return self::SUCCESS;
    }
}
