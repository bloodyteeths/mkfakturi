<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Seeder;

/**
 * Macedonian Chart of Accounts Seeder
 *
 * Seeds a standard Macedonian chart of accounts following local accounting standards.
 * This seeder creates accounts in the Facturino `accounts` table (not IFRS).
 *
 * Structure:
 * - 1xxx - Assets (Средства)
 * - 2xxx - Liabilities (Обврски)
 * - 3xxx - Equity (Капитал)
 * - 4xxx - Revenue (Приходи)
 * - 5xxx - Expenses (Расходи)
 */
class MacedonianChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Seeding Macedonian Chart of Accounts...');

        // Get all companies to seed accounts for each
        $companies = Company::all();

        if ($companies->isEmpty()) {
            $this->command->warn('No companies found. Skipping chart of accounts seeding.');
            return;
        }

        foreach ($companies as $company) {
            // Skip if company already has accounts
            $existingCount = Account::where('company_id', $company->id)->count();
            if ($existingCount > 0) {
                $this->command->info("Company '{$company->name}' already has {$existingCount} accounts. Skipping.");
                continue;
            }

            $this->command->info("Seeding chart of accounts for company: {$company->name}");

            // Seed all account categories
            $this->seedAssetAccounts($company->id);
            $this->seedLiabilityAccounts($company->id);
            $this->seedEquityAccounts($company->id);
            $this->seedRevenueAccounts($company->id);
            $this->seedExpenseAccounts($company->id);

            $this->command->info("  ✓ Seeded chart of accounts for {$company->name}");
        }

        $this->command->info('Macedonian Chart of Accounts seeded successfully!');
    }

    /**
     * Seed asset accounts (1xxx)
     *
     * @param int $companyId
     * @return void
     */
    protected function seedAssetAccounts(int $companyId): void
    {
        $accounts = [
            // Cash and Bank (10xx)
            ['code' => '1000', 'name' => 'Средства', 'description' => 'Парични средства и банки'],
            ['code' => '1010', 'name' => 'Каса', 'description' => 'Готовина во каса'],
            ['code' => '1020', 'name' => 'Жиро сметка', 'description' => 'Тековна сметка во домашна валута'],
            ['code' => '1021', 'name' => 'Жиро сметка - Комерцијална банка', 'description' => 'Тековна сметка'],
            ['code' => '1030', 'name' => 'Девизна сметка', 'description' => 'Сметка во странска валута'],

            // Fixed Assets (12xx)
            ['code' => '1200', 'name' => 'Основни средства', 'description' => 'Недвижен и движен имот'],
            ['code' => '1210', 'name' => 'Згради и објекти', 'description' => 'Деловни простори и објекти'],
            ['code' => '1220', 'name' => 'Опрема', 'description' => 'Машини и алати'],
            ['code' => '1230', 'name' => 'Возила', 'description' => 'Службени возила'],
            ['code' => '1240', 'name' => 'Компјутерска опрема', 'description' => 'Компјутери и софтвер'],
            ['code' => '1250', 'name' => 'Мебел', 'description' => 'Канцелариски мебел'],
            ['code' => '1260', 'name' => 'Амортизација', 'description' => 'Акумулирана амортизација на основни средства'],

            // Inventory (14xx)
            ['code' => '1400', 'name' => 'Залихи', 'description' => 'Материјали и стока'],
            ['code' => '1410', 'name' => 'Суровини и материјали', 'description' => 'Основни материјали за производство'],
            ['code' => '1420', 'name' => 'Стока', 'description' => 'Стока за препродажба'],
            ['code' => '1430', 'name' => 'Готови производи', 'description' => 'Завршени производи за продажба'],

            // Receivables (16xx)
            ['code' => '1600', 'name' => 'Побарувања', 'description' => 'Побарувања од купувачи и други'],
            ['code' => '1610', 'name' => 'Побарувања од купувачи - домашни', 'description' => 'Фактури за наплата од домашни купувачи'],
            ['code' => '1620', 'name' => 'Побарувања од купувачи - странски', 'description' => 'Фактури за наплата од странски купувачи'],
            ['code' => '1630', 'name' => 'ДДВ за поврат', 'description' => 'Претходен данок за поврат'],
            ['code' => '1640', 'name' => 'Аванси дадени', 'description' => 'Предуплати на добавувачи'],
            ['code' => '1650', 'name' => 'Други побарувања', 'description' => 'Останати краткорочни побарувања'],
        ];

        foreach ($accounts as $account) {
            Account::create([
                'company_id' => $companyId,
                'code' => $account['code'],
                'name' => $account['name'],
                'description' => $account['description'] ?? null,
                'type' => Account::TYPE_ASSET,
                'is_active' => true,
                'system_defined' => true,
            ]);
        }
    }

    /**
     * Seed liability accounts (2xxx)
     *
     * @param int $companyId
     * @return void
     */
    protected function seedLiabilityAccounts(int $companyId): void
    {
        $accounts = [
            // Long-term Liabilities (20xx)
            ['code' => '2000', 'name' => 'Долгорочни обврски', 'description' => 'Кредити и заеми'],
            ['code' => '2010', 'name' => 'Долгорочни кредити', 'description' => 'Банкарски кредити со рок над 1 година'],
            ['code' => '2020', 'name' => 'Хипотеки', 'description' => 'Хипотекарни кредити'],

            // Trade Payables (22xx)
            ['code' => '2200', 'name' => 'Обврски кон добавувачи', 'description' => 'Обврски за плаќање'],
            ['code' => '2210', 'name' => 'Обврски - домашни добавувачи', 'description' => 'Неплатени фактури од домашни добавувачи'],
            ['code' => '2220', 'name' => 'Обврски - странски добавувачи', 'description' => 'Неплатени фактури од странски добавувачи'],
            ['code' => '2230', 'name' => 'Краткорочни кредити', 'description' => 'Кредити со рок до 1 година'],

            // Tax Liabilities (24xx)
            ['code' => '2400', 'name' => 'Даночни обврски', 'description' => 'Обврски за даноци'],
            ['code' => '2410', 'name' => 'Данок на добивка за уплата', 'description' => 'Пресметан данок на добивка'],
            ['code' => '2420', 'name' => 'Персонален данок на доход', 'description' => 'Персонален данок за исплата'],
            ['code' => '2430', 'name' => 'Придонеси за социјално', 'description' => 'Придонеси од и на плата'],

            // VAT (27xx)
            ['code' => '2700', 'name' => 'ДДВ обврски', 'description' => 'Данок на додадена вредност'],
            ['code' => '2710', 'name' => 'ДДВ за уплата', 'description' => 'Пресметан ДДВ за уплата'],
            ['code' => '2720', 'name' => 'ДДВ на излез', 'description' => 'ДДВ од продажба'],

            // Other Liabilities (29xx)
            ['code' => '2900', 'name' => 'Други краткорочни обврски', 'description' => 'Останати обврски'],
            ['code' => '2910', 'name' => 'Плати за исплата', 'description' => 'Пресметани плати'],
            ['code' => '2920', 'name' => 'Аванси примени', 'description' => 'Примени предуплати од купувачи'],
        ];

        foreach ($accounts as $account) {
            Account::create([
                'company_id' => $companyId,
                'code' => $account['code'],
                'name' => $account['name'],
                'description' => $account['description'] ?? null,
                'type' => Account::TYPE_LIABILITY,
                'is_active' => true,
                'system_defined' => true,
            ]);
        }
    }

    /**
     * Seed equity accounts (3xxx)
     *
     * @param int $companyId
     * @return void
     */
    protected function seedEquityAccounts(int $companyId): void
    {
        $accounts = [
            ['code' => '3000', 'name' => 'Капитал', 'description' => 'Основен капитал'],
            ['code' => '3100', 'name' => 'Задржана добивка', 'description' => 'Акумулирана добивка од претходни години'],
            ['code' => '3200', 'name' => 'Тековна добивка/загуба', 'description' => 'Резултат од тековната година'],
            ['code' => '3300', 'name' => 'Приватни повлекувања', 'description' => 'Повлекувања од сопственик'],
        ];

        foreach ($accounts as $account) {
            Account::create([
                'company_id' => $companyId,
                'code' => $account['code'],
                'name' => $account['name'],
                'description' => $account['description'] ?? null,
                'type' => Account::TYPE_EQUITY,
                'is_active' => true,
                'system_defined' => true,
            ]);
        }
    }

    /**
     * Seed revenue accounts (4xxx)
     *
     * @param int $companyId
     * @return void
     */
    protected function seedRevenueAccounts(int $companyId): void
    {
        $accounts = [
            // Sales Revenue (40xx)
            ['code' => '4000', 'name' => 'Приходи од продажба', 'description' => 'Приходи од основна дејност'],
            ['code' => '4010', 'name' => 'Приходи од продажба на стока', 'description' => 'Продажба на стока'],
            ['code' => '4020', 'name' => 'Приходи од услуги', 'description' => 'Приходи од услуги'],
            ['code' => '4030', 'name' => 'Приходи од консалтинг', 'description' => 'Консултантски услуги'],
            ['code' => '4040', 'name' => 'Приходи од производи', 'description' => 'Продажба на сопствени производи'],
            ['code' => '4050', 'name' => 'Попусти одобрени', 'description' => 'Попусти на продажба (негативен приход)'],

            // Other Revenue (46xx)
            ['code' => '4600', 'name' => 'Останати приходи', 'description' => 'Приходи вон основна дејност'],
            ['code' => '4610', 'name' => 'Приходи од камата', 'description' => 'Каматни приходи'],
            ['code' => '4620', 'name' => 'Приходи од течајни разлики', 'description' => 'Позитивни девизни курсни разлики'],
            ['code' => '4630', 'name' => 'Приходи од закупнина', 'description' => 'Приходи од изнајмување'],
            ['code' => '4690', 'name' => 'Разни останати приходи', 'description' => 'Други приходи'],
        ];

        foreach ($accounts as $account) {
            Account::create([
                'company_id' => $companyId,
                'code' => $account['code'],
                'name' => $account['name'],
                'description' => $account['description'] ?? null,
                'type' => Account::TYPE_REVENUE,
                'is_active' => true,
                'system_defined' => true,
            ]);
        }
    }

    /**
     * Seed expense accounts (5xxx)
     *
     * @param int $companyId
     * @return void
     */
    protected function seedExpenseAccounts(int $companyId): void
    {
        $accounts = [
            // Cost of Goods Sold (50xx)
            ['code' => '5000', 'name' => 'Набавна вредност на продадена стока', 'description' => 'Трошоци за стока'],
            ['code' => '5010', 'name' => 'Набавка на стока', 'description' => 'Набавка на стока за препродажба'],
            ['code' => '5020', 'name' => 'Набавка на материјали', 'description' => 'Суровини и материјали'],

            // Material Costs (52xx)
            ['code' => '5200', 'name' => 'Материјални трошоци', 'description' => 'Трошоци за материјали'],
            ['code' => '5210', 'name' => 'Канцелариски материјал', 'description' => 'Канцелариски потрепштини'],
            ['code' => '5220', 'name' => 'Ситен инвентар', 'description' => 'Алат иситен инвентар'],
            ['code' => '5230', 'name' => 'Амбалажа', 'description' => 'Материјали за пакување'],

            // Service Costs (54xx)
            ['code' => '5400', 'name' => 'Трошоци за услуги', 'description' => 'Надворешни услуги'],
            ['code' => '5410', 'name' => 'Кирија', 'description' => 'Закуп на простор'],
            ['code' => '5420', 'name' => 'Комунални услуги', 'description' => 'Струја, вода, греење'],
            ['code' => '5430', 'name' => 'Телефон и интернет', 'description' => 'Телекомуникациски услуги'],
            ['code' => '5440', 'name' => 'Транспорт и гориво', 'description' => 'Трошоци за транспорт'],
            ['code' => '5450', 'name' => 'Маркетинг и реклама', 'description' => 'Рекламни услуги'],
            ['code' => '5460', 'name' => 'Сметководствени услуги', 'description' => 'Надворешна сметководствена поддршка'],
            ['code' => '5470', 'name' => 'Правни и консултантски услуги', 'description' => 'Адвокатски и советодавни услуги'],
            ['code' => '5480', 'name' => 'Банкарски провизии', 'description' => 'Провизии и такси на банка'],
            ['code' => '5481', 'name' => 'Провизии за плаќање', 'description' => 'Провизии за картички и дигитални плаќања'],

            // Personnel Costs (56xx)
            ['code' => '5600', 'name' => 'Трошоци за вработени', 'description' => 'Плати и надоместоци'],
            ['code' => '5610', 'name' => 'Бруто плати', 'description' => 'Бруто плати на вработени'],
            ['code' => '5620', 'name' => 'Придонеси на плата', 'description' => 'Придонеси од работодавач'],
            ['code' => '5630', 'name' => 'Дневници и патни трошоци', 'description' => 'Службени патувања'],
            ['code' => '5640', 'name' => 'Трошоци за обука', 'description' => 'Едукација на вработени'],

            // Depreciation (58xx)
            ['code' => '5800', 'name' => 'Амортизација', 'description' => 'Амортизација на основни средства'],
            ['code' => '5810', 'name' => 'Амортизација на згради', 'description' => 'Амортизација на објекти'],
            ['code' => '5820', 'name' => 'Амортизација на опрема', 'description' => 'Амортизација на опрема и возила'],

            // Other Expenses (59xx)
            ['code' => '5900', 'name' => 'Останати трошоци', 'description' => 'Разни трошоци'],
            ['code' => '5910', 'name' => 'Камати', 'description' => 'Каматни трошоци'],
            ['code' => '5920', 'name' => 'Загуби од течајни разлики', 'description' => 'Негативни девизни курсни разлики'],
            ['code' => '5930', 'name' => 'Казни и пенали', 'description' => 'Законски казни'],
            ['code' => '5940', 'name' => 'Застрахување', 'description' => 'Премии за осигурување'],
            ['code' => '5950', 'name' => 'Репрезентација', 'description' => 'Репрезентативни трошоци'],
        ];

        foreach ($accounts as $account) {
            Account::create([
                'company_id' => $companyId,
                'code' => $account['code'],
                'name' => $account['name'],
                'description' => $account['description'] ?? null,
                'type' => Account::TYPE_EXPENSE,
                'is_active' => true,
                'system_defined' => true,
            ]);
        }
    }
}
// CLAUDE-CHECKPOINT
