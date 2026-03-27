<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Add 4-digit analytical VAT sub-accounts (UKLO/Proagens standard).
 *
 * Input VAT under 130: 1300 (18%), 1301 (5%), 1302 (reverse charge),
 *   1303 (correction), 1304/1305 (period claims), 1306 (10%), 1309 (other)
 * Output VAT under 230: 2300 (18%), 2301 (5%), 2302 (reverse charge), 2306 (10%)
 *
 * IDEMPOTENT: skips accounts that already exist.
 */
return new class extends Migration
{
    private array $inputVatAccounts = [
        ['code' => '1300', 'name' => 'Претходен данок по влезни фактури по стапка од 18%', 'parent_code' => '130'],
        ['code' => '1301', 'name' => 'Претходен данок со право на одбивка по стапка од 5%', 'parent_code' => '130'],
        ['code' => '1302', 'name' => 'Претходен данок за промет извршен од странски субјект', 'parent_code' => '130'],
        ['code' => '1303', 'name' => 'Исправка на претходен данок поради пренамена на добро', 'parent_code' => '130'],
        ['code' => '1304', 'name' => 'Побарување на претходен данок за пресметковниот период', 'parent_code' => '130'],
        ['code' => '1305', 'name' => 'Побарување на претходен данок за даночниот период', 'parent_code' => '130'],
        ['code' => '1306', 'name' => 'Претходен данок по стапка од 10% (угостителство)', 'parent_code' => '130'],
        ['code' => '1309', 'name' => 'Друг претходен данок', 'parent_code' => '130'],
    ];

    private array $outputVatAccounts = [
        ['code' => '2300', 'name' => 'Обврски за пресметан даночен долг по стапка од 18%', 'parent_code' => '230'],
        ['code' => '2301', 'name' => 'Обврски за пресметан даночен долг по стапка од 5%', 'parent_code' => '230'],
        ['code' => '2302', 'name' => 'Даночен долг за промет извршен од странски субјект', 'parent_code' => '230'],
        ['code' => '2306', 'name' => 'Обврски за пресметан даночен долг по стапка од 10%', 'parent_code' => '230'],
    ];

    public function up(): void
    {
        $companyIds = DB::table('accounts')
            ->distinct()
            ->pluck('company_id');

        $totalCreated = 0;

        foreach ($companyIds as $companyId) {
            $totalCreated += $this->seedForCompany($companyId, $this->inputVatAccounts, 'asset');
            $totalCreated += $this->seedForCompany($companyId, $this->outputVatAccounts, 'liability');
        }

        Log::info("[VatSubAccounts] Created {$totalCreated} 4-digit VAT accounts across {$companyIds->count()} companies");
    }

    private function seedForCompany(int $companyId, array $accounts, string $type): int
    {
        $created = 0;

        foreach ($accounts as $acct) {
            // Skip if already exists
            $exists = DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('code', $acct['code'])
                ->exists();

            if ($exists) {
                continue;
            }

            // Resolve parent_id
            $parentId = null;
            $parent = DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('code', $acct['parent_code'])
                ->first();

            if ($parent) {
                $parentId = $parent->id;
            }

            DB::table('accounts')->insert([
                'company_id' => $companyId,
                'code' => $acct['code'],
                'name' => $acct['name'],
                'type' => $type,
                'parent_id' => $parentId,
                'is_active' => true,
                'system_defined' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $created++;
        }

        return $created;
    }

    public function down(): void
    {
        $codes = array_merge(
            array_column($this->inputVatAccounts, 'code'),
            array_column($this->outputVatAccounts, 'code')
        );

        DB::table('accounts')
            ->whereIn('code', $codes)
            ->where('system_defined', true)
            ->delete();

        Log::info('[VatSubAccounts] Rolled back — deleted 4-digit VAT sub-accounts');
    }
}; // CLAUDE-CHECKPOINT
