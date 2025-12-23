<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Seeder;

/**
 * Macedonian Chart of Accounts Seeder
 *
 * Implements the OFFICIAL Macedonian 10-class accounting system
 * based on Правилник за сметковниот план (Regulation 174/2011)
 *
 * 10 Classes (0-9):
 * - Class 0: Non-current assets (НЕТЕКОВНИ СРЕДСТВА)
 * - Class 1: Cash and receivables (ПАРИЧНИ СРЕДСТВА, КРАТКОРОЧНИ ПОБАРУВАЊА)
 * - Class 2: Liabilities and provisions (ОБВРСКИ, РЕЗЕРВИРАЊА)
 * - Class 3: Raw materials inventory (ЗАЛИХИ НА СУРОВИНИ)
 * - Class 4: Costs and expenses (ТРОШОЦИ И РАСХОДИ)
 * - Class 5: Free/internal use (СЛОБОДНА)
 * - Class 6: Production inventory (ЗАЛИХИ НА ПРОИЗВОДСТВО)
 * - Class 7: Revenue coverage (ПОКРИВАЊЕ НА РАСХОДИ И ПРИХОДИ)
 * - Class 8: Operating results (РЕЗУЛТАТИ ОД РАБОТЕЊЕТО)
 * - Class 9: Capital and reserves (КАПИТАЛ, РЕЗЕРВИ)
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
            $this->command->info("Seeding chart of accounts for company: {$company->name}");

            // Seed all 10 classes
            $this->seedClass0NonCurrentAssets($company->id);
            $this->seedClass1CashAndReceivables($company->id);
            $this->seedClass2LiabilitiesAndProvisions($company->id);
            $this->seedClass3RawMaterialsInventory($company->id);
            $this->seedClass4CostsAndExpenses($company->id);
            $this->seedClass5Internal($company->id);
            $this->seedClass6ProductionInventory($company->id);
            $this->seedClass7Revenue($company->id);
            $this->seedClass8OperatingResults($company->id);
            $this->seedClass9CapitalAndReserves($company->id);

            $this->command->info("  ✓ Seeded chart of accounts for {$company->name}");
        }

        $this->command->info('Macedonian Chart of Accounts seeded successfully!');
    }

    /**
     * Class 0: НЕТЕКОВНИ СРЕДСТВА (Non-current Assets)
     */
    private function seedClass0NonCurrentAssets(int $companyId): void
    {
        $accounts = [
            // 00x - Intangible assets (Нематеријални средства)
            ['code' => '000', 'name' => 'Трошоци за развој', 'type' => Account::TYPE_ASSET],
            ['code' => '001', 'name' => 'Концесии, патенти, лиценци, трговски марки', 'type' => Account::TYPE_ASSET],
            ['code' => '002', 'name' => 'Софтвер', 'type' => Account::TYPE_ASSET],
            ['code' => '003', 'name' => 'Деловна репутација (Goodwill)', 'type' => Account::TYPE_ASSET],
            ['code' => '004', 'name' => 'Аванси за нематеријални средства', 'type' => Account::TYPE_ASSET],
            ['code' => '005', 'name' => 'Нематеријални средства во подготовка', 'type' => Account::TYPE_ASSET],
            ['code' => '009', 'name' => 'Исправка на вредност на нематеријални средства', 'type' => Account::TYPE_ASSET],

            // 01x - Tangible assets (Материјални средства)
            ['code' => '010', 'name' => 'Земјиште', 'type' => Account::TYPE_ASSET],
            ['code' => '011', 'name' => 'Градежни објекти', 'type' => Account::TYPE_ASSET],
            ['code' => '012', 'name' => 'Постројки и опрема', 'type' => Account::TYPE_ASSET],
            ['code' => '013', 'name' => 'Алат, погонски инвентар и транспортни средства', 'type' => Account::TYPE_ASSET],
            ['code' => '014', 'name' => 'Биолошки средства', 'type' => Account::TYPE_ASSET],
            ['code' => '015', 'name' => 'Аванси за материјални средства', 'type' => Account::TYPE_ASSET],
            ['code' => '016', 'name' => 'Материјални средства во подготовка', 'type' => Account::TYPE_ASSET],
            ['code' => '019', 'name' => 'Исправка на вредност на материјални средства', 'type' => Account::TYPE_ASSET],

            // 02x - Investment property (Инвестициски недвижности)
            ['code' => '020', 'name' => 'Инвестициски недвижности - земјиште', 'type' => Account::TYPE_ASSET],
            ['code' => '021', 'name' => 'Инвестициски недвижности - градежни објекти', 'type' => Account::TYPE_ASSET],
            ['code' => '029', 'name' => 'Исправка на вредност на инвестициски недвижности', 'type' => Account::TYPE_ASSET],

            // 03x - Long-term financial assets (Долгорочни финансиски средства)
            ['code' => '030', 'name' => 'Учества во капиталот на поврзани друштва', 'type' => Account::TYPE_ASSET],
            ['code' => '031', 'name' => 'Учества во капиталот на здружени друштва', 'type' => Account::TYPE_ASSET],
            ['code' => '032', 'name' => 'Учества во капиталот на заеднички контролирани друштва', 'type' => Account::TYPE_ASSET],
            ['code' => '033', 'name' => 'Учества во капиталот на други друштва', 'type' => Account::TYPE_ASSET],
            ['code' => '034', 'name' => 'Долгорочни кредити и заеми дадени на поврзани друштва', 'type' => Account::TYPE_ASSET],
            ['code' => '035', 'name' => 'Други долгорочни кредити и заеми', 'type' => Account::TYPE_ASSET],
            ['code' => '036', 'name' => 'Хартии од вредност', 'type' => Account::TYPE_ASSET],
            ['code' => '037', 'name' => 'Долгорочни финансиски пласмани', 'type' => Account::TYPE_ASSET],
            ['code' => '039', 'name' => 'Исправка на вредност на долгорочни финансиски средства', 'type' => Account::TYPE_ASSET],

            // 04x - Long-term receivables (Долгорочни побарувања)
            ['code' => '040', 'name' => 'Долгорочни побарувања од поврзани друштва', 'type' => Account::TYPE_ASSET],
            ['code' => '041', 'name' => 'Долгорочни побарувања од купувачи', 'type' => Account::TYPE_ASSET],
            ['code' => '042', 'name' => 'Долгорочни побарувања од државата', 'type' => Account::TYPE_ASSET],
            ['code' => '043', 'name' => 'Други долгорочни побарувања', 'type' => Account::TYPE_ASSET],
            ['code' => '049', 'name' => 'Исправка на вредност на долгорочни побарувања', 'type' => Account::TYPE_ASSET],

            // 05x - Deferred tax assets (Одложени даночни средства)
            ['code' => '050', 'name' => 'Одложени даночни средства', 'type' => Account::TYPE_ASSET],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * Class 1: ПАРИЧНИ СРЕДСТВА, КРАТКОРОЧНИ ПОБАРУВАЊА (Cash and Receivables)
     */
    private function seedClass1CashAndReceivables(int $companyId): void
    {
        $accounts = [
            // 10x - Cash and cash equivalents (Парични средства)
            ['code' => '100', 'name' => 'Готовина', 'type' => Account::TYPE_ASSET],
            ['code' => '101', 'name' => 'Готовина во странска валута', 'type' => Account::TYPE_ASSET],
            ['code' => '102', 'name' => 'Жиро-сметка', 'type' => Account::TYPE_ASSET],
            ['code' => '103', 'name' => 'Девизна сметка', 'type' => Account::TYPE_ASSET],
            ['code' => '104', 'name' => 'Благајна за авансни плаќања', 'type' => Account::TYPE_ASSET],
            ['code' => '105', 'name' => 'Други парични средства', 'type' => Account::TYPE_ASSET],

            // 11x - Receivables from related parties (Побарувања од поврзани друштва)
            ['code' => '110', 'name' => 'Побарувања од поврзани друштва - главница', 'type' => Account::TYPE_ASSET],
            ['code' => '111', 'name' => 'Побарувања од поврзани друштва - камата', 'type' => Account::TYPE_ASSET],
            ['code' => '119', 'name' => 'Исправка на вредност на побарувања од поврзани друштва', 'type' => Account::TYPE_ASSET],

            // 12x - Receivables from customers (Побарувања од купувачи)
            ['code' => '120', 'name' => 'Побарувања од купувачи во земјата', 'type' => Account::TYPE_ASSET],
            ['code' => '121', 'name' => 'Побарувања од купувачи во странство', 'type' => Account::TYPE_ASSET],
            ['code' => '122', 'name' => 'Побарувања по основ на издадени записи', 'type' => Account::TYPE_ASSET],
            ['code' => '123', 'name' => 'Побарувања од купувачи - спорни', 'type' => Account::TYPE_ASSET],
            ['code' => '129', 'name' => 'Исправка на вредност на побарувања од купувачи', 'type' => Account::TYPE_ASSET],

            // 13x - Receivables from government (Побарувања од државата)
            ['code' => '130', 'name' => 'Побарувања од даноци и придонеси', 'type' => Account::TYPE_ASSET],
            ['code' => '131', 'name' => 'Побарувања за ДДВ', 'type' => Account::TYPE_ASSET],
            ['code' => '132', 'name' => 'Побарувања за акцизи', 'type' => Account::TYPE_ASSET],
            ['code' => '133', 'name' => 'Побарувања за царини', 'type' => Account::TYPE_ASSET],
            ['code' => '134', 'name' => 'Побарувања за субвенции, дотации и донации', 'type' => Account::TYPE_ASSET],
            ['code' => '139', 'name' => 'Други побарувања од државата', 'type' => Account::TYPE_ASSET],

            // 14x - Receivables from employees (Побарувања од вработени)
            ['code' => '140', 'name' => 'Побарувања од вработени за дадени аванси', 'type' => Account::TYPE_ASSET],
            ['code' => '141', 'name' => 'Побарувања од вработени за кредити', 'type' => Account::TYPE_ASSET],
            ['code' => '142', 'name' => 'Побарувања од вработени за надоместоци', 'type' => Account::TYPE_ASSET],
            ['code' => '149', 'name' => 'Други побарувања од вработени', 'type' => Account::TYPE_ASSET],

            // 15x - Short-term financial assets (Краткорочни финансиски средства)
            ['code' => '150', 'name' => 'Краткорочни кредити и заеми - поврзани друштва', 'type' => Account::TYPE_ASSET],
            ['code' => '151', 'name' => 'Краткорочни кредити и заеми - други', 'type' => Account::TYPE_ASSET],
            ['code' => '152', 'name' => 'Хартии од вредност', 'type' => Account::TYPE_ASSET],
            ['code' => '153', 'name' => 'Краткорочни финансиски пласмани', 'type' => Account::TYPE_ASSET],
            ['code' => '159', 'name' => 'Исправка на вредност на краткорочни финансиски средства', 'type' => Account::TYPE_ASSET],

            // 16x - Other receivables (Други побарувања)
            ['code' => '160', 'name' => 'Побарувања за дивиденди и уделни во добивка', 'type' => Account::TYPE_ASSET],
            ['code' => '161', 'name' => 'Побарувања за камати', 'type' => Account::TYPE_ASSET],
            ['code' => '162', 'name' => 'Побарувања за штети од осигурување', 'type' => Account::TYPE_ASSET],
            ['code' => '163', 'name' => 'Побарувања за продажба на средства', 'type' => Account::TYPE_ASSET],
            ['code' => '164', 'name' => 'Побарувања од содружници', 'type' => Account::TYPE_ASSET],
            ['code' => '169', 'name' => 'Разни побарувања', 'type' => Account::TYPE_ASSET],

            // 19x - Prepaid expenses (Активни временски разграничувања)
            ['code' => '190', 'name' => 'Однапред плаќени трошоци', 'type' => Account::TYPE_ASSET],
            ['code' => '191', 'name' => 'Пресметани приходи', 'type' => Account::TYPE_ASSET],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * Class 2: ОБВРСКИ, РЕЗЕРВИРАЊА (Liabilities and Provisions)
     */
    private function seedClass2LiabilitiesAndProvisions(int $companyId): void
    {
        $accounts = [
            // 21x - Short-term liabilities to related parties (Краткорочни обврски кон поврзани друштва)
            ['code' => '210', 'name' => 'Обврски кон поврзани друштва - главница', 'type' => Account::TYPE_LIABILITY],
            ['code' => '211', 'name' => 'Обврски кон поврзани друштва - камата', 'type' => Account::TYPE_LIABILITY],

            // 22x - Liabilities to suppliers (Обврски кон добавувачи)
            ['code' => '220', 'name' => 'Обврски кон добавувачи во земјата', 'type' => Account::TYPE_LIABILITY],
            ['code' => '221', 'name' => 'Обврски кон добавувачи во странство', 'type' => Account::TYPE_LIABILITY],
            ['code' => '222', 'name' => 'Обврски по основ на дадени записи', 'type' => Account::TYPE_LIABILITY],

            // 23x - Tax liabilities (Обврски за даноци и придонеси)
            ['code' => '230', 'name' => 'Обврски за данок на добивка', 'type' => Account::TYPE_LIABILITY],
            ['code' => '231', 'name' => 'Обврски за ДДВ', 'type' => Account::TYPE_LIABILITY],
            ['code' => '232', 'name' => 'Обврски за акцизи', 'type' => Account::TYPE_LIABILITY],
            ['code' => '233', 'name' => 'Обврски за царини', 'type' => Account::TYPE_LIABILITY],
            ['code' => '234', 'name' => 'Обврски за данок на личен доход', 'type' => Account::TYPE_LIABILITY],
            ['code' => '235', 'name' => 'Обврски за придонеси од плати', 'type' => Account::TYPE_LIABILITY],
            ['code' => '236', 'name' => 'Обврски за данок на наследство и подароци', 'type' => Account::TYPE_LIABILITY],
            ['code' => '237', 'name' => 'Обврски за данок на имот', 'type' => Account::TYPE_LIABILITY],
            ['code' => '239', 'name' => 'Обврски за други даноци', 'type' => Account::TYPE_LIABILITY],

            // 24x - Employee liabilities (Обврски кон вработени)
            ['code' => '240', 'name' => 'Обврски за нето плати', 'type' => Account::TYPE_LIABILITY],
            ['code' => '241', 'name' => 'Обврски за придонеси на товар на вработени', 'type' => Account::TYPE_LIABILITY],
            ['code' => '242', 'name' => 'Обврски за надоместоци од плата', 'type' => Account::TYPE_LIABILITY],
            ['code' => '243', 'name' => 'Обврски за други примања на вработени', 'type' => Account::TYPE_LIABILITY],
            ['code' => '244', 'name' => 'Обврски за отпремнини', 'type' => Account::TYPE_LIABILITY],

            // 25x - Short-term financial liabilities (Краткорочни финансиски обврски)
            ['code' => '250', 'name' => 'Краткорочни кредити од банки', 'type' => Account::TYPE_LIABILITY],
            ['code' => '251', 'name' => 'Краткорочни заеми од поврзани друштва', 'type' => Account::TYPE_LIABILITY],
            ['code' => '252', 'name' => 'Краткорочни заеми од други', 'type' => Account::TYPE_LIABILITY],
            ['code' => '253', 'name' => 'Обврски по основ на хартии од вредност', 'type' => Account::TYPE_LIABILITY],
            ['code' => '254', 'name' => 'Обврски по основ на финансиски лизинг', 'type' => Account::TYPE_LIABILITY],

            // 26x - Other short-term liabilities (Други краткорочни обврски)
            ['code' => '260', 'name' => 'Обврски за дивиденди', 'type' => Account::TYPE_LIABILITY],
            ['code' => '261', 'name' => 'Обврски за камати', 'type' => Account::TYPE_LIABILITY],
            ['code' => '262', 'name' => 'Обврски за штети', 'type' => Account::TYPE_LIABILITY],
            ['code' => '263', 'name' => 'Обврски кон содружници', 'type' => Account::TYPE_LIABILITY],
            ['code' => '269', 'name' => 'Разни обврски', 'type' => Account::TYPE_LIABILITY],

            // 27x - Provisions (Резервирања)
            ['code' => '270', 'name' => 'Резервирања за пензии и слични обврски', 'type' => Account::TYPE_LIABILITY],
            ['code' => '271', 'name' => 'Резервирања за трошоци за реструктуирање', 'type' => Account::TYPE_LIABILITY],
            ['code' => '272', 'name' => 'Резервирања за обврски по основ на гаранции', 'type' => Account::TYPE_LIABILITY],
            ['code' => '273', 'name' => 'Резервирања за тековни судски спорови', 'type' => Account::TYPE_LIABILITY],
            ['code' => '279', 'name' => 'Други резервирања', 'type' => Account::TYPE_LIABILITY],

            // 28x - Long-term liabilities (Долгорочни обврски)
            ['code' => '280', 'name' => 'Долгорочни кредити од банки', 'type' => Account::TYPE_LIABILITY],
            ['code' => '281', 'name' => 'Долгорочни заеми од поврзани друштва', 'type' => Account::TYPE_LIABILITY],
            ['code' => '282', 'name' => 'Долгорочни заеми од други', 'type' => Account::TYPE_LIABILITY],
            ['code' => '283', 'name' => 'Долгорочни обврски по основ на хартии од вредност', 'type' => Account::TYPE_LIABILITY],
            ['code' => '284', 'name' => 'Долгорочни обврски по основ на финансиски лизинг', 'type' => Account::TYPE_LIABILITY],
            ['code' => '285', 'name' => 'Долгорочни обврски кон добавувачи', 'type' => Account::TYPE_LIABILITY],
            ['code' => '286', 'name' => 'Други долгорочни обврски', 'type' => Account::TYPE_LIABILITY],
            ['code' => '287', 'name' => 'Одложени даночни обврски', 'type' => Account::TYPE_LIABILITY],

            // 29x - Deferred income (Пасивни временски разграничувања)
            ['code' => '290', 'name' => 'Однапред наплатени приходи', 'type' => Account::TYPE_LIABILITY],
            ['code' => '291', 'name' => 'Пресметани трошоци', 'type' => Account::TYPE_LIABILITY],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * Class 3: ЗАЛИХИ НА СУРОВИНИ (Raw Materials Inventory)
     */
    private function seedClass3RawMaterialsInventory(int $companyId): void
    {
        $accounts = [
            // 30x - Purchase calculations (Пресметки на набавки)
            ['code' => '300', 'name' => 'Набавка на материјали', 'type' => Account::TYPE_ASSET],
            ['code' => '301', 'name' => 'Набавка на резервни делови', 'type' => Account::TYPE_ASSET],
            ['code' => '302', 'name' => 'Набавка на ситен инвентар', 'type' => Account::TYPE_ASSET],
            ['code' => '303', 'name' => 'Набавка на стока', 'type' => Account::TYPE_ASSET],

            // 31x - Raw materials (Суровини и материјали)
            ['code' => '310', 'name' => 'Суровини', 'type' => Account::TYPE_ASSET],
            ['code' => '311', 'name' => 'Материјали', 'type' => Account::TYPE_ASSET],
            ['code' => '312', 'name' => 'Помошни материјали', 'type' => Account::TYPE_ASSET],
            ['code' => '313', 'name' => 'Енергенти', 'type' => Account::TYPE_ASSET],
            ['code' => '314', 'name' => 'Амбалажа', 'type' => Account::TYPE_ASSET],

            // 32x - Spare parts (Резервни делови)
            ['code' => '320', 'name' => 'Резервни делови', 'type' => Account::TYPE_ASSET],

            // 33x - Small inventory (Ситен инвентар)
            ['code' => '330', 'name' => 'Ситен инвентар', 'type' => Account::TYPE_ASSET],
            ['code' => '331', 'name' => 'Погонска облека и обувки', 'type' => Account::TYPE_ASSET],

            // 34x - Materials in transit (Материјали во транзит)
            ['code' => '340', 'name' => 'Материјали во транзит', 'type' => Account::TYPE_ASSET],

            // 35x - Advances for materials (Аванси за материјали)
            ['code' => '350', 'name' => 'Аванси за материјали', 'type' => Account::TYPE_ASSET],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * Class 4: ТРОШОЦИ И РАСХОДИ (Costs and Expenses)
     */
    private function seedClass4CostsAndExpenses(int $companyId): void
    {
        $accounts = [
            // 40x - Material costs (Трошоци за материјали)
            ['code' => '400', 'name' => 'Трошоци за суровини и материјали', 'type' => Account::TYPE_EXPENSE],
            ['code' => '401', 'name' => 'Трошоци за енергија', 'type' => Account::TYPE_EXPENSE],
            ['code' => '402', 'name' => 'Трошоци за резервни делови', 'type' => Account::TYPE_EXPENSE],
            ['code' => '403', 'name' => 'Трошоци за ситен инвентар', 'type' => Account::TYPE_EXPENSE],
            ['code' => '404', 'name' => 'Трошоци за канцелариски материјал', 'type' => Account::TYPE_EXPENSE],

            // 41x - Service costs (Трошоци за услуги)
            ['code' => '410', 'name' => 'Трошоци за транспортни услуги', 'type' => Account::TYPE_EXPENSE],
            ['code' => '411', 'name' => 'Трошоци за одржување', 'type' => Account::TYPE_EXPENSE],
            ['code' => '412', 'name' => 'Трошоци за закупнини', 'type' => Account::TYPE_EXPENSE],
            ['code' => '413', 'name' => 'Трошоци за реклама и пропаганда', 'type' => Account::TYPE_EXPENSE],
            ['code' => '414', 'name' => 'Трошоци за истражување и развој', 'type' => Account::TYPE_EXPENSE],
            ['code' => '415', 'name' => 'Трошоци за комунални услуги', 'type' => Account::TYPE_EXPENSE],
            ['code' => '416', 'name' => 'Трошоци за сметководствени и консултантски услуги', 'type' => Account::TYPE_EXPENSE],
            ['code' => '417', 'name' => 'Трошоци за осигурување', 'type' => Account::TYPE_EXPENSE],
            ['code' => '418', 'name' => 'Трошоци за телефон, поштарина, интернет', 'type' => Account::TYPE_EXPENSE],
            ['code' => '419', 'name' => 'Други трошоци за услуги', 'type' => Account::TYPE_EXPENSE],

            // 42x - Salary costs (Трошоци за вработени)
            ['code' => '420', 'name' => 'Плати на вработени', 'type' => Account::TYPE_EXPENSE],
            ['code' => '421', 'name' => 'Придонеси на товар на работодавач', 'type' => Account::TYPE_EXPENSE],
            ['code' => '422', 'name' => 'Надоместоци на плата', 'type' => Account::TYPE_EXPENSE],
            ['code' => '423', 'name' => 'Надоместоци за превоз', 'type' => Account::TYPE_EXPENSE],
            ['code' => '424', 'name' => 'Надоместоци за топол оброк', 'type' => Account::TYPE_EXPENSE],
            ['code' => '425', 'name' => 'Отпремнини', 'type' => Account::TYPE_EXPENSE],
            ['code' => '426', 'name' => 'Јубилејни награди', 'type' => Account::TYPE_EXPENSE],
            ['code' => '429', 'name' => 'Други трошоци за вработени', 'type' => Account::TYPE_EXPENSE],

            // 43x - Depreciation and amortization (Амортизација)
            ['code' => '430', 'name' => 'Амортизација на нематеријални средства', 'type' => Account::TYPE_EXPENSE],
            ['code' => '431', 'name' => 'Амортизација на материјални средства', 'type' => Account::TYPE_EXPENSE],
            ['code' => '432', 'name' => 'Амортизација на инвестициски недвижности', 'type' => Account::TYPE_EXPENSE],

            // 44x - Other operating expenses (Други оперативни трошоци)
            ['code' => '440', 'name' => 'Трошоци за репрезентација', 'type' => Account::TYPE_EXPENSE],
            ['code' => '441', 'name' => 'Трошоци за службени патувања', 'type' => Account::TYPE_EXPENSE],
            ['code' => '442', 'name' => 'Трошоци за стручно усовршување', 'type' => Account::TYPE_EXPENSE],
            ['code' => '443', 'name' => 'Трошоци за членарини', 'type' => Account::TYPE_EXPENSE],
            ['code' => '444', 'name' => 'Трошоци за донации и спонзорства', 'type' => Account::TYPE_EXPENSE],
            ['code' => '445', 'name' => 'Трошоци за провизии и надоместоци', 'type' => Account::TYPE_EXPENSE],
            ['code' => '449', 'name' => 'Други оперативни трошоци', 'type' => Account::TYPE_EXPENSE],

            // 45x - Impairment (Оштетување)
            ['code' => '450', 'name' => 'Оштетување на побарувања', 'type' => Account::TYPE_EXPENSE],
            ['code' => '451', 'name' => 'Оштетување на залихи', 'type' => Account::TYPE_EXPENSE],
            ['code' => '452', 'name' => 'Оштетување на финансиски средства', 'type' => Account::TYPE_EXPENSE],

            // 46x - Other expenses (Други расходи)
            ['code' => '460', 'name' => 'Загуби од продажба на средства', 'type' => Account::TYPE_EXPENSE],
            ['code' => '461', 'name' => 'Отпис на побарувања', 'type' => Account::TYPE_EXPENSE],
            ['code' => '462', 'name' => 'Отпис на залихи', 'type' => Account::TYPE_EXPENSE],
            ['code' => '463', 'name' => 'Казни и пенали', 'type' => Account::TYPE_EXPENSE],
            ['code' => '464', 'name' => 'Неоперативни расходи', 'type' => Account::TYPE_EXPENSE],

            // 47x - Financial expenses (Финансиски расходи)
            ['code' => '470', 'name' => 'Камати', 'type' => Account::TYPE_EXPENSE],
            ['code' => '471', 'name' => 'Негативни курсни разлики', 'type' => Account::TYPE_EXPENSE],
            ['code' => '472', 'name' => 'Загуби од промени на фер вредност', 'type' => Account::TYPE_EXPENSE],
            ['code' => '479', 'name' => 'Други финансиски расходи', 'type' => Account::TYPE_EXPENSE],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * Class 5: СЛОБОДНА (Internal/Free Use)
     */
    private function seedClass5Internal(int $companyId): void
    {
        $accounts = [
            ['code' => '500', 'name' => 'Интерни пресметки', 'type' => Account::TYPE_EXPENSE],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * Class 6: ЗАЛИХИ НА ПРОИЗВОДСТВО (Production Inventory)
     */
    private function seedClass6ProductionInventory(int $companyId): void
    {
        $accounts = [
            // 60x - Work in progress (Недовршено производство)
            ['code' => '600', 'name' => 'Недовршено производство', 'type' => Account::TYPE_ASSET],

            // 61x - Semi-finished products (Полупроизводи)
            ['code' => '610', 'name' => 'Полупроизводи', 'type' => Account::TYPE_ASSET],

            // 62x - Finished products (Готови производи)
            ['code' => '620', 'name' => 'Готови производи', 'type' => Account::TYPE_ASSET],
            ['code' => '621', 'name' => 'Готови производи во магацин', 'type' => Account::TYPE_ASSET],
            ['code' => '622', 'name' => 'Готови производи во транзит', 'type' => Account::TYPE_ASSET],

            // 63x - Merchandise (Стока)
            ['code' => '630', 'name' => 'Стока во магацин', 'type' => Account::TYPE_ASSET],
            ['code' => '631', 'name' => 'Стока во транзит', 'type' => Account::TYPE_ASSET],
            ['code' => '632', 'name' => 'Стока на конзигнација', 'type' => Account::TYPE_ASSET],

            // 64x - Biological assets (Биолошки средства)
            ['code' => '640', 'name' => 'Биолошки средства', 'type' => Account::TYPE_ASSET],

            // 65x - Non-current assets held for sale (Нетековни средства за продажба)
            ['code' => '650', 'name' => 'Нетековни средства наменети за продажба', 'type' => Account::TYPE_ASSET],

            // 66x - Advances for goods (Аванси за стока)
            ['code' => '660', 'name' => 'Аванси за стока', 'type' => Account::TYPE_ASSET],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * Class 7: ПОКРИВАЊЕ НА РАСХОДИ И ПРИХОДИ (Revenue Coverage)
     */
    private function seedClass7Revenue(int $companyId): void
    {
        $accounts = [
            // 70x - Cost of goods sold (Вредност на продадени производи)
            ['code' => '700', 'name' => 'Вредност на продадени готови производи', 'type' => Account::TYPE_EXPENSE],
            ['code' => '701', 'name' => 'Вредност на продадени полупроизводи', 'type' => Account::TYPE_EXPENSE],
            ['code' => '702', 'name' => 'Вредност на продадена стока', 'type' => Account::TYPE_EXPENSE],

            // 71x - Sales revenue to related parties (Приходи од продажба до поврзани друштва)
            ['code' => '710', 'name' => 'Приходи од продажба на производи до поврзани друштва', 'type' => Account::TYPE_REVENUE],
            ['code' => '711', 'name' => 'Приходи од продажба на услуги до поврзани друштва', 'type' => Account::TYPE_REVENUE],
            ['code' => '712', 'name' => 'Приходи од продажба на стока до поврзани друштва', 'type' => Account::TYPE_REVENUE],

            // 72x - Sales revenue domestic (Приходи од продажба во земјата)
            ['code' => '720', 'name' => 'Приходи од продажба на производи во земјата', 'type' => Account::TYPE_REVENUE],
            ['code' => '721', 'name' => 'Приходи од продажба на услуги во земјата', 'type' => Account::TYPE_REVENUE],
            ['code' => '722', 'name' => 'Приходи од продажба на стока во земјата', 'type' => Account::TYPE_REVENUE],

            // 73x - Sales revenue export (Приходи од продажба во странство)
            ['code' => '730', 'name' => 'Приходи од продажба на производи во странство', 'type' => Account::TYPE_REVENUE],
            ['code' => '731', 'name' => 'Приходи од продажба на услуги во странство', 'type' => Account::TYPE_REVENUE],
            ['code' => '732', 'name' => 'Приходи од продажба на стока во странство', 'type' => Account::TYPE_REVENUE],

            // 74x - Changes in inventory (Промени на залихи)
            ['code' => '740', 'name' => 'Зголемување на залихи на готови производи', 'type' => Account::TYPE_REVENUE],
            ['code' => '741', 'name' => 'Намалување на залихи на готови производи', 'type' => Account::TYPE_EXPENSE],
            ['code' => '742', 'name' => 'Зголемување на залихи на недовршено производство', 'type' => Account::TYPE_REVENUE],
            ['code' => '743', 'name' => 'Намалување на залихи на недовршено производство', 'type' => Account::TYPE_EXPENSE],

            // 75x - Value adjustments income (Усогласувања на вредност - приходи)
            ['code' => '750', 'name' => 'Приходи од намалување на оштетување на побарувања', 'type' => Account::TYPE_REVENUE],
            ['code' => '751', 'name' => 'Приходи од намалување на оштетување на залихи', 'type' => Account::TYPE_REVENUE],
            ['code' => '752', 'name' => 'Приходи од намалување на резервирања', 'type' => Account::TYPE_REVENUE],

            // 76x - Other income (Други приходи)
            ['code' => '760', 'name' => 'Приходи од продажба на материјали', 'type' => Account::TYPE_REVENUE],
            ['code' => '761', 'name' => 'Приходи од закупнини', 'type' => Account::TYPE_REVENUE],
            ['code' => '762', 'name' => 'Приходи од добиени неоперативни средства', 'type' => Account::TYPE_REVENUE],
            ['code' => '763', 'name' => 'Приходи од продажба на средства', 'type' => Account::TYPE_REVENUE],
            ['code' => '764', 'name' => 'Приходи од отпис на обврски', 'type' => Account::TYPE_REVENUE],
            ['code' => '765', 'name' => 'Приходи од субвенции, дотации и донации', 'type' => Account::TYPE_REVENUE],
            ['code' => '769', 'name' => 'Други оперативни приходи', 'type' => Account::TYPE_REVENUE],

            // 77x - Financial income (Финансиски приходи)
            ['code' => '770', 'name' => 'Приходи од камати', 'type' => Account::TYPE_REVENUE],
            ['code' => '771', 'name' => 'Позитивни курсни разлики', 'type' => Account::TYPE_REVENUE],
            ['code' => '772', 'name' => 'Добивки од промени на фер вредност', 'type' => Account::TYPE_REVENUE],
            ['code' => '773', 'name' => 'Приходи од дивиденди', 'type' => Account::TYPE_REVENUE],
            ['code' => '779', 'name' => 'Други финансиски приходи', 'type' => Account::TYPE_REVENUE],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * Class 8: РЕЗУЛТАТИ ОД РАБОТЕЊЕТО (Operating Results)
     */
    private function seedClass8OperatingResults(int $companyId): void
    {
        $accounts = [
            // 80x - Profit/loss before tax (Добивка/загуба пред оданочување)
            ['code' => '800', 'name' => 'Добивка пред оданочување', 'type' => Account::TYPE_EQUITY],
            ['code' => '801', 'name' => 'Загуба пред оданочување', 'type' => Account::TYPE_EQUITY],

            // 81x - Income tax (Данок на добивка)
            ['code' => '810', 'name' => 'Данок на добивка', 'type' => Account::TYPE_EXPENSE],
            ['code' => '811', 'name' => 'Одложен данок на добивка', 'type' => Account::TYPE_EXPENSE],

            // 82x - Net profit/loss (Нето добивка/загуба)
            ['code' => '820', 'name' => 'Нето добивка', 'type' => Account::TYPE_EQUITY],
            ['code' => '821', 'name' => 'Нето загуба', 'type' => Account::TYPE_EQUITY],

            // 83x - Comprehensive income (Севкупен сеопфатен доход)
            ['code' => '830', 'name' => 'Севкупен сеопфатен доход', 'type' => Account::TYPE_EQUITY],

            // 84x - Profit distribution (Распределба на добивка)
            ['code' => '840', 'name' => 'Распределба на добивка', 'type' => Account::TYPE_EQUITY],
            ['code' => '841', 'name' => 'Покривање на загуби', 'type' => Account::TYPE_EQUITY],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * Class 9: КАПИТАЛ, РЕЗЕРВИ (Capital and Reserves)
     */
    private function seedClass9CapitalAndReserves(int $companyId): void
    {
        $accounts = [
            // 90x - Share capital (Акционерски капитал / Основен капитал)
            ['code' => '900', 'name' => 'Запишан капитал', 'type' => Account::TYPE_EQUITY],
            ['code' => '901', 'name' => 'Уплатен капитал', 'type' => Account::TYPE_EQUITY],
            ['code' => '902', 'name' => 'Сопствени акции', 'type' => Account::TYPE_EQUITY],

            // 91x - Share premiums (Премија од издавање акции)
            ['code' => '910', 'name' => 'Премија од емисија на акции', 'type' => Account::TYPE_EQUITY],

            // 92x - Revaluation reserves (Ревалоризациски резерви)
            ['code' => '920', 'name' => 'Ревалоризациска резерва', 'type' => Account::TYPE_EQUITY],
            ['code' => '921', 'name' => 'Резерви од добивки/загуби од финансиски средства', 'type' => Account::TYPE_EQUITY],

            // 93x - Reserves (Резерви)
            ['code' => '930', 'name' => 'Законски резерви', 'type' => Account::TYPE_EQUITY],
            ['code' => '931', 'name' => 'Статутарни резерви', 'type' => Account::TYPE_EQUITY],
            ['code' => '932', 'name' => 'Резерви за сопствени акции', 'type' => Account::TYPE_EQUITY],
            ['code' => '933', 'name' => 'Други резерви', 'type' => Account::TYPE_EQUITY],

            // 94x - Retained earnings (Задржана добивка)
            ['code' => '940', 'name' => 'Задржана добивка од претходни години', 'type' => Account::TYPE_EQUITY],
            ['code' => '941', 'name' => 'Нераспределена добивка', 'type' => Account::TYPE_EQUITY],

            // 95x - Losses (Загуби)
            ['code' => '950', 'name' => 'Загуба пренесена од претходни години', 'type' => Account::TYPE_EQUITY],
            ['code' => '951', 'name' => 'Загуба на тековната година', 'type' => Account::TYPE_EQUITY],

            // 96x - Non-controlling interest (Учество без контрола)
            ['code' => '960', 'name' => 'Учество без контрола', 'type' => Account::TYPE_EQUITY],

            // 99x - Off-balance sheet (Вонбилансна евиденција)
            ['code' => '990', 'name' => 'Примени гаранции', 'type' => Account::TYPE_EQUITY],
            ['code' => '991', 'name' => 'Издадени гаранции', 'type' => Account::TYPE_EQUITY],
            ['code' => '992', 'name' => 'Примени средства на чување', 'type' => Account::TYPE_EQUITY],
            ['code' => '993', 'name' => 'Дадени средства на чување', 'type' => Account::TYPE_EQUITY],
            ['code' => '999', 'name' => 'Друга вонбилансна евиденција', 'type' => Account::TYPE_EQUITY],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * Helper method to create accounts (idempotent)
     */
    private function createAccounts(int $companyId, array $accounts): void
    {
        foreach ($accounts as $accountData) {
            // Check if account already exists (idempotent)
            $existingAccount = Account::where('company_id', $companyId)
                ->where('code', $accountData['code'])
                ->first();

            if (!$existingAccount) {
                Account::create([
                    'company_id' => $companyId,
                    'code' => $accountData['code'],
                    'name' => $accountData['name'],
                    'description' => null,
                    'type' => $accountData['type'],
                    'is_active' => true,
                    'system_defined' => true,
                ]);
            }
        }
    }
}
// CLAUDE-CHECKPOINT
