<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Add ~711 four-digit analytical accounts (UKLO/Proagens standard)
 * across all 10 classes of the Macedonian chart of accounts.
 *
 * VAT sub-accounts (parents 130/230) are excluded — already seeded
 * by 2026_03_28_000100_add_4digit_vat_subaccounts.
 *
 * Loads from database/data/analytical_accounts.php.
 * IDEMPOTENT: skips accounts that already exist, fixes names on existing system_defined accounts.
 */
return new class extends Migration
{
    public function up(): void
    {
        $dataFile = database_path('data/analytical_accounts.php');
        if (! file_exists($dataFile)) {
            Log::warning('[AnalyticalAccounts] data/analytical_accounts.php not found, skipping');
            return;
        }

        $rows = require $dataFile;
        Log::info('[AnalyticalAccounts] Loaded ' . count($rows) . ' analytical accounts from data file');

        $companyIds = DB::table('accounts')
            ->distinct()
            ->pluck('company_id');

        $totalCreated = 0;
        $totalUpdated = 0;

        foreach ($companyIds as $companyId) {
            [$created, $updated] = $this->seedForCompany($companyId, $rows);
            $totalCreated += $created;
            $totalUpdated += $updated;
        }

        Log::info("[AnalyticalAccounts] Created {$totalCreated}, updated {$totalUpdated} accounts across {$companyIds->count()} companies");
    }

    private function seedForCompany(int $companyId, array $rows): array
    {
        $created = 0;
        $updated = 0;

        // Pre-load all existing accounts for this company (code => row)
        $existing = DB::table('accounts')
            ->where('company_id', $companyId)
            ->get(['id', 'code', 'name', 'parent_id', 'system_defined'])
            ->keyBy('code');

        foreach ($rows as [$code, $parentCode, $type, $name]) {
            $account = $existing->get($code);

            if ($account) {
                // Fix name + parent on existing system_defined accounts
                if ($account->system_defined) {
                    $updates = [];
                    if ($account->name !== $name) {
                        $updates['name'] = $name;
                    }
                    if (! $account->parent_id) {
                        $parent = $existing->get($parentCode);
                        if ($parent) {
                            $updates['parent_id'] = $parent->id;
                        }
                    }
                    if (! empty($updates)) {
                        $updates['updated_at'] = now();
                        DB::table('accounts')->where('id', $account->id)->update($updates);
                        $updated++;
                    }
                }
                continue;
            }

            // Resolve parent_id
            $parentId = null;
            $parent = $existing->get($parentCode);
            if ($parent) {
                $parentId = $parent->id;
            }

            $id = DB::table('accounts')->insertGetId([
                'company_id' => $companyId,
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'parent_id' => $parentId,
                'is_active' => true,
                'system_defined' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Add to local cache so subsequent accounts can reference as parent
            $existing->put($code, (object) [
                'id' => $id,
                'code' => $code,
                'name' => $name,
                'parent_id' => $parentId,
                'system_defined' => true,
            ]);

            $created++;
        }

        return [$created, $updated];
    }

    public function down(): void
    {
        $dataFile = database_path('data/analytical_accounts.php');
        if (! file_exists($dataFile)) {
            return;
        }

        $rows = require $dataFile;
        $codes = array_map(fn($row) => $row[0], $rows);

        // Delete in batches to avoid huge IN clause
        foreach (array_chunk($codes, 100) as $chunk) {
            DB::table('accounts')
                ->whereIn('code', $chunk)
                ->where('system_defined', true)
                ->delete();
        }

        Log::info('[AnalyticalAccounts] Rolled back — deleted 4-digit analytical accounts');
    }
}; // CLAUDE-CHECKPOINT
