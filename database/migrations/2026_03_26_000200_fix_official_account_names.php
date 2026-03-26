<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Comprehensive migration to align existing company chart of accounts
 * with the official Правилник за сметковниот план (Сл. весник 174/2011).
 *
 * Key code remappings:
 * - 100 (was "Готовина") ↔ 102 (was "Жиро-сметка") — SWAP
 * - 131 (was "ДДВ побарување") → 130 (Данок на додадена вредност)
 * - 231 (was "Обврски за ДДВ") → 230 (Обврски за данокот на додадена вредност)
 * - 720 (was "Приходи од производи") → 740 (Приходи од продажба на добра и услуги)
 * - 721 (was "Приходи од стоки") → 741 (Приходи од продажба на стоки)
 * - 722 (was "Приходи од услуги") → 742 (Приходи од извоз)
 *
 * IDEMPOTENT: Safe to re-run. Each step checks if already applied.
 * Journal entries use account_id (FK), so code changes are safe for existing data.
 */
return new class extends Migration
{
    /**
     * Code remappings: old_code => [new_code, new_name]
     */
    private array $codeRemaps = [
        '131' => ['130', 'Данок на додадена вредност'],
        '231' => ['230', 'Обврски за данокот на додадена вредност'],
        '720' => ['740', 'Приходи од продажба на добра (производи) и услуги во земјата'],
        '721' => ['741', 'Приходи од продажба на добра (стоки) во земјата'],
        '722' => ['742', 'Приходи од продажба на добра и услуги во странство'],
    ];

    /**
     * Name-only updates for accounts whose codes stay the same.
     * code => new_name
     */
    private array $nameUpdates = [
        // Class 0
        '001' => 'Гудвил (Goodwill)',
        '002' => 'Концесии, патенти, лиценци, трговски и услужни марки',
        '003' => 'Софтвер и останати права',
        '013' => 'Алат, погонски и канцелариски инвентар, мебел и транспортни средства',
        // Class 1
        '103' => 'Девизни сметки',
        '105' => 'Парични средства во благајна во странска валута',
        '120' => 'Побарувања од купувачи во земјата',
        '121' => 'Побарувања од купувачи во странство',
        '140' => 'Побарувања од вработени и органи на управување',
        // Class 2
        '200' => 'Долгорочни кредити и заеми од поврзани друштва во земјата',
        '220' => 'Обврски кон добавувачите во земјата',
        '221' => 'Обврски кон добавувачите во странство',
        '240' => 'Обврски за нето-плати и надоместоци на плати',
        '241' => 'Обврски за придонеси од плати и на плати',
        // Class 3
        '300' => 'Суровини и материјали',
        '303' => 'Трговски стоки во магацин и продавници',
        '330' => 'Ситен инвентар, амбалажа и авто гуми во употреба',
        // Class 4
        '400' => 'Трошоци за суровини и материјали',
        '419' => 'Останати нематеријални трошоци',
        '420' => 'Плати - бруто',
        '421' => 'Придонеси на товар на работодавачот',
        '430' => 'Амортизација на нематеријални средства',
        '431' => 'Амортизација на материјални средства',
        '441' => 'Дневници за службени патувања и теренски додаток',
        '442' => 'Трошоци за репрезентација',
        '450' => 'Даноци, придонеси и други давачки',
        '460' => 'Расходи врз основа на камати од работењето со поврзани друштва',
        // Class 6
        '600' => 'Недовршени производи и услуги',
        '630' => 'Стоки во магацин',
        // Class 7
        '700' => 'Расходи врз основа на продадени добра (производи) и услуги',
        '701' => 'Набавна вредност на продадени добра (стоки)',
        '760' => 'Добивки од продажба на нематеријални и материјални средства',
        '770' => 'Приходи врз основа на камати од работењето со поврзани друштва',
        // Class 8
        '800' => 'Добивка пред оданочување',
        '801' => 'Загуба пред оданочување',
        '820' => 'Нето добивка за периодот',
        '821' => 'Нето загуба за периодот',
        // Class 9
        '900' => 'Основна главнина (запишан капитал)',
        '901' => 'Премии на емисија и вложениот капитал',
        '910' => 'Почетна состојба на работењето',
    ];

    public function up(): void
    {
        $companyIds = DB::table('accounts')
            ->distinct()
            ->pluck('company_id');

        $totalUpdated = 0;

        foreach ($companyIds as $companyId) {
            $updated = $this->migrateCompanyAccounts($companyId);
            $totalUpdated += $updated;
        }

        Log::info("[AccountMigration] Updated {$totalUpdated} accounts across {$companyIds->count()} companies");

        // ═══════════════════════════════════════════════════════════
        // Step 4: Swap payment_methods account_code values (100↔102)
        // IDEMPOTENT: Only swap if we detect old-style mapping
        // (Cash methods pointing to 100, Bank methods pointing to 102)
        // ═══════════════════════════════════════════════════════════

        if (Schema::hasColumn('payment_methods', 'account_code')) {
            // Check if swap is needed by looking for "Cash" method with code 100
            // After swap, Cash should be 102 and Bank should be 100
            $cashWith100 = DB::table('payment_methods')
                ->where('account_code', '100')
                ->whereRaw("LOWER(name) IN ('cash', 'готовина', 'каса')")
                ->exists();

            if ($cashWith100) {
                // Old mapping detected — perform swap
                DB::table('payment_methods')
                    ->where('account_code', '100')
                    ->update(['account_code' => 'T100']);

                DB::table('payment_methods')
                    ->where('account_code', '102')
                    ->update(['account_code' => '100']);

                DB::table('payment_methods')
                    ->where('account_code', 'T100')
                    ->update(['account_code' => '102']);

                Log::info("[AccountMigration] Swapped payment_methods account_code 100↔102");
            } else {
                Log::info("[AccountMigration] payment_methods already correct or no Cash/100 detected, skipping swap");
            }
        }
    }

    private function migrateCompanyAccounts(int $companyId): int
    {
        $updated = 0;

        // ═══════════════════════════════════════════════════════════
        // Step 1: Swap 100 ↔ 102 (bank/cash semantic correction)
        // IDEMPOTENT: Read name in PHP to detect if already swapped
        // Old: 100 = Готовина (cash), 102 = Жиро-сметка (bank)
        // New: 100 = Трансакциски сметки (bank), 102 = Благајна (cash)
        // ═══════════════════════════════════════════════════════════

        $account100 = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('code', '100')
            ->first();

        $account102 = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('code', '102')
            ->first();

        // Only swap if account 100 still has the OLD name (contains cash-related terms)
        // PHP comparison avoids MySQL collation issues
        $needsSwap = $account100 && $account102
            && !str_contains($account100->name ?? '', 'трансакциски');

        if ($needsSwap) {
            DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('code', '100')
                ->update(['code' => 'T100']);

            DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('code', '102')
                ->update([
                    'code' => '100',
                    'name' => 'Парични средства на трансакциски сметки во денари',
                ]);

            DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('code', 'T100')
                ->update([
                    'code' => '102',
                    'name' => 'Парични средства во благајна',
                ]);

            $updated += 2;
        } elseif ($account100 && !$account102) {
            // Only 100 exists — just set official name
            DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('code', '100')
                ->update(['name' => 'Парични средства на трансакциски сметки во денари']);
            $updated++;
        } elseif (!$account100 && $account102) {
            // Only 102 exists — just set official name
            DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('code', '102')
                ->update(['name' => 'Парични средства во благајна']);
            $updated++;
        }
        // else: both exist but already swapped — names are already correct

        // ═══════════════════════════════════════════════════════════
        // Step 2: Code remaps (131→130, 231→230, 72x→74x)
        // IDEMPOTENT: checks if old code exists and new doesn't
        // ═══════════════════════════════════════════════════════════

        foreach ($this->codeRemaps as $oldCode => [$newCode, $newName]) {
            $hasOld = DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('code', $oldCode)
                ->exists();

            $hasNew = DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('code', $newCode)
                ->exists();

            if ($hasOld && !$hasNew) {
                DB::table('accounts')
                    ->where('company_id', $companyId)
                    ->where('code', $oldCode)
                    ->update([
                        'code' => $newCode,
                        'name' => $newName,
                    ]);
                $updated++;
            } elseif ($hasOld && $hasNew) {
                Log::warning("[AccountMigration] Company {$companyId}: Cannot remap {$oldCode}→{$newCode} (target exists).");
            }
            // else: already migrated (old doesn't exist), skip
        }

        // ═══════════════════════════════════════════════════════════
        // Step 3: Name-only updates (codes stay the same)
        // IDEMPOTENT: unconditional SET to official name
        // ═══════════════════════════════════════════════════════════

        foreach ($this->nameUpdates as $code => $newName) {
            $affected = DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('code', $code)
                ->where('system_defined', true)
                ->update(['name' => $newName]);

            $updated += $affected;
        }

        return $updated;
    }

    public function down(): void
    {
        Log::info('[AccountMigration] down() called — no-op. Re-seed manually if needed.');
    }
};
