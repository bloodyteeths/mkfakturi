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
 * Journal entries use account_id (FK), so code changes are safe for existing data.
 */
return new class extends Migration
{
    /**
     * Code remappings: old_code => [new_code, new_name]
     * Order matters for swaps — use temp codes to avoid unique constraint violations.
     */
    private array $codeRemaps = [
        // 100 ↔ 102 swap (handled separately via temp code)
        '131' => ['130', 'Данок на додадена вредност'],
        '231' => ['230', 'Обврски за данокот на додадена вредност'],
        '720' => ['740', 'Приходи од продажба на добра (производи) и услуги во земјата'],
        '721' => ['741', 'Приходи од продажба на добра (стоки) во земјата'],
        '722' => ['742', 'Приходи од продажба на добра и услуги во странство'],
    ];

    /**
     * Name-only updates for accounts whose codes stay the same.
     * old_code => [old_name_pattern, new_name]
     */
    private array $nameUpdates = [
        // Class 0
        '001' => ['Гудвил', 'Гудвил (Goodwill)'],
        '002' => ['Концесии', 'Концесии, патенти, лиценци, трговски и услужни марки'],
        '003' => ['Софтвер', 'Софтвер и останати права'],
        '010' => ['Земјишта', 'Земјишта'],
        '011' => ['Градежни објекти', 'Градежни објекти'],
        '012' => ['Постројки и опрема', 'Постројки и опрема'],
        '013' => ['Транспортни средства', 'Алат, погонски и канцелариски инвентар, мебел и транспортни средства'],

        // Class 1
        '103' => ['Девизна сметка', 'Девизни сметки'],
        '105' => ['Други парични средства', 'Парични средства во благајна во странска валута'],
        '120' => ['Побарувања од купувачи', 'Побарувања од купувачи во земјата'],
        '121' => ['Побарувања од купувачи', 'Побарувања од купувачи во странство'],
        '140' => ['Побарувања од вработени', 'Побарувања од вработени и органи на управување'],

        // Class 2
        '200' => ['Долгорочни заеми', 'Долгорочни кредити и заеми од поврзани друштва во земјата'],
        '220' => ['Обврски кон добавувачи', 'Обврски кон добавувачите во земјата'],
        '221' => ['Обврски кон странски', 'Обврски кон добавувачите во странство'],
        '240' => ['Обврски за плати', 'Обврски за нето-плати и надоместоци на плати'],
        '241' => ['Обврски за придонеси', 'Обврски за придонеси од плати и на плати'],

        // Class 3
        '300' => ['Суровини', 'Суровини и материјали'],
        '303' => ['Набавна калкулација', 'Трговски стоки во магацин и продавници'],
        '330' => ['Ситен инвентар', 'Ситен инвентар, амбалажа и авто гуми во употреба'],

        // Class 4
        '400' => ['Трошоци за суровини', 'Трошоци за суровини и материјали'],
        '419' => ['Останати услуги', 'Останати нематеријални трошоци'],
        '420' => ['Трошоци за плати', 'Плати - бруто'],
        '421' => ['Придонеси', 'Придонеси на товар на работодавачот'],
        '430' => ['Амортизација', 'Амортизација на нематеријални средства'],
        '431' => ['Амортизација', 'Амортизација на материјални средства'],
        '441' => ['Патни трошоци', 'Дневници за службени патувања и теренски додаток'],
        '442' => ['Репрезентација', 'Трошоци за репрезентација'],
        '450' => ['Даноци', 'Даноци, придонеси и други давачки'],
        '460' => ['Финансиски расходи', 'Расходи врз основа на камати од работењето со поврзани друштва'],

        // Class 6
        '600' => ['Недовршено производство', 'Недовршени производи и услуги'],
        '630' => ['Залихи на стоки', 'Стоки во магацин'],

        // Class 7
        '700' => ['Расходи на продадени', 'Расходи врз основа на продадени добра (производи) и услуги'],
        '701' => ['Набавна вредност', 'Набавна вредност на продадени добра (стоки)'],
        '760' => ['Други приходи', 'Добивки од продажба на нематеријални и материјални средства'],
        '770' => ['Финансиски приходи', 'Приходи врз основа на камати од работењето со поврзани друштва'],

        // Class 8
        '800' => ['Добивка', 'Добивка пред оданочување'],
        '801' => ['Загуба', 'Загуба пред оданочување'],
        '810' => ['Данок на добивка', 'Данок на добивка'],
        '820' => ['Нето добивка', 'Нето добивка за периодот'],
        '821' => ['Нето загуба', 'Нето загуба за периодот'],

        // Class 9
        '900' => ['Основна главнина', 'Основна главнина (запишан капитал)'],
        '901' => ['Премии на акции', 'Премии на емисија и вложениот капитал'],
        '902' => ['Резерви', 'Резерви'],
        '910' => ['Почетна состојба', 'Почетна состојба на работењето'],
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
        // PaymentMethods reference account codes, not IDs.
        // Since account 100 swapped with 102, payment methods must follow.
        // ═══════════════════════════════════════════════════════════

        if (Schema::hasColumn('payment_methods', 'account_code')) {
            DB::table('payment_methods')
                ->where('account_code', '100')
                ->update(['account_code' => 'T100']);

            DB::table('payment_methods')
                ->where('account_code', '102')
                ->update(['account_code' => '100']);

            DB::table('payment_methods')
                ->where('account_code', 'T100')
                ->update(['account_code' => '102']);

            $swapped = DB::table('payment_methods')
                ->whereIn('account_code', ['100', '102'])
                ->count();

            Log::info("[AccountMigration] Swapped payment_methods account_code 100↔102 ({$swapped} records)");
        }
    }

    private function migrateCompanyAccounts(int $companyId): int
    {
        $updated = 0;

        // ═══════════════════════════════════════════════════════════
        // Step 1: Swap 100 ↔ 102 (bank/cash semantic correction)
        // Old: 100 = Готовина (cash), 102 = Жиро-сметка (bank)
        // Official: 100 = Трансакциски сметки (bank), 102 = Благајна (cash)
        // ═══════════════════════════════════════════════════════════

        $has100 = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('code', '100')
            ->exists();

        $has102 = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('code', '102')
            ->exists();

        if ($has100 && $has102) {
            // Both exist — swap via temp code
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
        } elseif ($has100) {
            // Only 100 exists — just rename (was cash, now bank with same code)
            DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('code', '100')
                ->update(['name' => 'Парични средства на трансакциски сметки во денари']);
            $updated++;
        } elseif ($has102) {
            // Only 102 exists — just rename
            DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('code', '102')
                ->update(['name' => 'Парични средства во благајна']);
            $updated++;
        }

        // ═══════════════════════════════════════════════════════════
        // Step 2: Code remaps (131→130, 231→230, 72x→74x)
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
                // Safe to remap
                DB::table('accounts')
                    ->where('company_id', $companyId)
                    ->where('code', $oldCode)
                    ->update([
                        'code' => $newCode,
                        'name' => $newName,
                    ]);
                $updated++;
            } elseif ($hasOld && $hasNew) {
                // Target code already exists (conflict) — just update old code's name
                Log::warning("[AccountMigration] Company {$companyId}: Cannot remap {$oldCode}→{$newCode} (target exists). Updating name only.");
                DB::table('accounts')
                    ->where('company_id', $companyId)
                    ->where('code', $oldCode)
                    ->update(['name' => $newName . ' (legacy ' . $oldCode . ')']);
                $updated++;
            }
        }

        // ═══════════════════════════════════════════════════════════
        // Step 3: Name-only updates (codes stay the same)
        // ═══════════════════════════════════════════════════════════

        foreach ($this->nameUpdates as $code => [$oldNamePattern, $newName]) {
            $affected = DB::table('accounts')
                ->where('company_id', $companyId)
                ->where('code', $code)
                ->where('system_defined', true)
                ->where('name', '!=', $newName)
                ->update(['name' => $newName]);

            $updated += $affected;
        }

        return $updated;
    }

    public function down(): void
    {
        // This migration updates ~50 account names per company.
        // Reverting would require the old seeder data which is no longer available.
        // The down() is intentionally a no-op — re-run the old seeder manually if needed.
        Log::info('[AccountMigration] down() called — no-op. Re-seed manually if needed.');
    }
};
