<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Seeder;

/**
 * Macedonian Chart of Accounts Seeder
 *
 * Implements the OFFICIAL Macedonian chart of accounts per:
 * Правилник за сметковниот план и содржината на одделните сметки
 * во сметковниот план (Службен весник на РМ број 174/2011)
 *
 * 10 Classes (0-9):
 * - Class 0: НЕТЕКОВНИ СРЕДСТВА
 * - Class 1: ПАРИЧНИ СРЕДСТВА, ХАРТИИ ОД ВРЕДНОСТ, КРАТКОРОЧНИ ПОБАРУВАЊА И ПЛАТЕНИ ТРОШОЦИ ЗА ИДНИ ПЕРИОДИ И ПРЕСМЕТАНИ ПРИХОДИ
 * - Class 2: ОБВРСКИ, РЕЗЕРВИРАЊА ЗА ТРОШОЦИ И РИЗИЦИ, ОДЛОЖЕНИ ПЛАЌАЊА И ПРИХОДИ ЗА ИДНИ ПЕРИОДИ
 * - Class 3: ЗАЛИХИ НА СУРОВИНИ, МАТЕРИЈАЛИ, РЕЗЕРВНИ ДЕЛОВИ И СИТЕН ИНВЕНТАР
 * - Class 4: ТРОШОЦИ И РАСХОДИ ОД РАБОТЕЊЕТО
 * - Class 5: СЛОБОДНА (ЗА ИНТЕРНА УПОТРЕБА)
 * - Class 6: ЗАЛИХИ НА ПРОИЗВОДСТВО, ГОТОВИ ПРОИЗВОДИ И СТОКИ
 * - Class 7: ПОКРИВАЊЕ НА РАСХОДИ И ПРИХОДИ
 * - Class 8: РЕЗУЛТАТИ ОД РАБОТЕЊЕТО
 * - Class 9: КАПИТАЛ, РЕЗЕРВИ И ВОНБИЛАНСНА ЕВИДЕНЦИЈА
 */
class MacedonianChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $this->log('Seeding Macedonian Chart of Accounts (Правилник 174/2011)...');

        $companies = Company::all();

        if ($companies->isEmpty()) {
            $this->log('No companies found. Skipping chart of accounts seeding.', 'warn');
            return;
        }

        $seeded = 0;

        foreach ($companies as $company) {
            $this->log("Seeding chart of accounts for company: {$company->name}");

            // createAccounts() skips codes that already exist, so this is safe
            // for both new companies and existing ones (fills in missing accounts)
            $this->seedClass0($company->id);
            $this->seedClass1($company->id);
            $this->seedClass2($company->id);
            $this->seedClass3($company->id);
            $this->seedClass4($company->id);
            $this->seedClass6($company->id);
            $this->seedClass7($company->id);
            $this->seedClass8($company->id);
            $this->seedClass9($company->id);
            $this->seedVatSubAccounts($company->id);
            $this->seedAnalyticalAccounts($company->id);

            $this->log("  ✓ Seeded chart of accounts for {$company->name}");
            $seeded++;
        }

        $this->log('Macedonian Chart of Accounts seeded successfully!');
    }

    /**
     * Seed chart of accounts for a specific company.
     * Called by CompanyObserver when a new company is created.
     */
    public function seedForCompany(int $companyId): void
    {
        // createAccounts() skips codes that already exist, so safe to call always
        $this->seedClass0($companyId);
        $this->seedClass1($companyId);
        $this->seedClass2($companyId);
        $this->seedClass3($companyId);
        $this->seedClass4($companyId);
        $this->seedClass6($companyId);
        $this->seedClass7($companyId);
        $this->seedClass8($companyId);
        $this->seedClass9($companyId);
        $this->seedVatSubAccounts($companyId);
        $this->seedAnalyticalAccounts($companyId);

        $this->log("Seeded chart of accounts for company {$companyId}");
    }

    private function log(string $message, string $level = 'info'): void
    {
        if ($this->command) {
            $this->command->{$level}($message);
        } else {
            \Log::{$level}("[MacedonianChartOfAccountsSeeder] {$message}");
        }
    }

    /**
     * КЛАСА 0: НЕТЕКОВНИ СРЕДСТВА
     */
    private function seedClass0(int $companyId): void
    {
        $accounts = [
            // 00 - НЕМАТЕРИЈАЛНИ СРЕДСТВА
            ['code' => '000', 'name' => 'Издатоци за развој', 'type' => Account::TYPE_ASSET],
            ['code' => '001', 'name' => 'Гудвил (Goodwill)', 'type' => Account::TYPE_ASSET],
            ['code' => '002', 'name' => 'Концесии, патенти, лиценци, трговски и услужни марки', 'type' => Account::TYPE_ASSET],
            ['code' => '003', 'name' => 'Софтвер и останати права', 'type' => Account::TYPE_ASSET],
            ['code' => '005', 'name' => 'Аванси за набавка на нематеријални средства', 'type' => Account::TYPE_ASSET],
            ['code' => '006', 'name' => 'Нематеријални средства во подготовка', 'type' => Account::TYPE_ASSET],
            ['code' => '007', 'name' => 'Останати нематеријални средства', 'type' => Account::TYPE_ASSET],
            ['code' => '008', 'name' => 'Вредносно усогласување на нематеријални средства', 'type' => Account::TYPE_ASSET],
            ['code' => '009', 'name' => 'Акумулирана амортизација на нематеријални средства', 'type' => Account::TYPE_ASSET],

            // 01 - МАТЕРИЈАЛНИ СРЕДСТВА
            ['code' => '010', 'name' => 'Земјишта', 'type' => Account::TYPE_ASSET],
            ['code' => '011', 'name' => 'Градежни објекти', 'type' => Account::TYPE_ASSET],
            ['code' => '012', 'name' => 'Постројки и опрема', 'type' => Account::TYPE_ASSET],
            ['code' => '013', 'name' => 'Алат, погонски и канцелариски инвентар, мебел и транспортни средства', 'type' => Account::TYPE_ASSET],
            ['code' => '014', 'name' => 'Биолошки средства', 'type' => Account::TYPE_ASSET],
            ['code' => '015', 'name' => 'Останати материјални средства', 'type' => Account::TYPE_ASSET],
            ['code' => '016', 'name' => 'Материјални средства во подготовка', 'type' => Account::TYPE_ASSET],
            ['code' => '017', 'name' => 'Аванси за набавка на материјални средства', 'type' => Account::TYPE_ASSET],
            ['code' => '018', 'name' => 'Вредносно усогласување на материјални средства', 'type' => Account::TYPE_ASSET],
            ['code' => '019', 'name' => 'Акумулирана амортизација на материјални средства', 'type' => Account::TYPE_ASSET],

            // 02 - ВЛОЖУВАЊА ВО НЕДВИЖНОСТИ
            ['code' => '020', 'name' => 'Вложувања во недвижности за наем', 'type' => Account::TYPE_ASSET],
            ['code' => '021', 'name' => 'Вложувања во недвижности заради зголемување на капиталот', 'type' => Account::TYPE_ASSET],
            ['code' => '026', 'name' => 'Вложувања во недвижности во подготовка', 'type' => Account::TYPE_ASSET],
            ['code' => '027', 'name' => 'Аванси за вложувања во недвижности', 'type' => Account::TYPE_ASSET],
            ['code' => '028', 'name' => 'Вредносно усогласување на вложувања во недвижности', 'type' => Account::TYPE_ASSET],
            ['code' => '029', 'name' => 'Акумулирана амортизација на вложувања во недвижности', 'type' => Account::TYPE_ASSET],

            // 03 - ДОЛГОРОЧНИ ФИНАНСИСКИ СРЕДСТВА
            ['code' => '030', 'name' => 'Вложувања во подружница', 'type' => Account::TYPE_ASSET],
            ['code' => '031', 'name' => 'Вложувања во придружни друштва и заеднички контролирани друштва', 'type' => Account::TYPE_ASSET],
            ['code' => '032', 'name' => 'Долгорочни заеми и кредити на поврзани друштва во земјата и странство', 'type' => Account::TYPE_ASSET],
            ['code' => '033', 'name' => 'Дадени заеми и кредити во земјата и во странство', 'type' => Account::TYPE_ASSET],
            ['code' => '034', 'name' => 'Финансиски средства кои се чуваат до доспевање', 'type' => Account::TYPE_ASSET],
            ['code' => '035', 'name' => 'Финансиски средства расположиви за продажба', 'type' => Account::TYPE_ASSET],
            ['code' => '036', 'name' => 'Финансиски средства според објективна вредност преку добивката или загубата', 'type' => Account::TYPE_ASSET],
            ['code' => '037', 'name' => 'Дадени депозити и кауции во земјата и странство', 'type' => Account::TYPE_ASSET],
            ['code' => '038', 'name' => 'Останати долгорочни финансиски средства', 'type' => Account::TYPE_ASSET],
            ['code' => '039', 'name' => 'Вредносно усогласување на долгорочните финансиски средства', 'type' => Account::TYPE_ASSET],

            // 04 - ДОЛГОРОЧНИ ПОБАРУВАЊА
            ['code' => '040', 'name' => 'Побарувања од поврзани друштва врз основа на продажба', 'type' => Account::TYPE_ASSET],
            ['code' => '041', 'name' => 'Побарувања од неповрзани друштва врз основа на продажба на кредит', 'type' => Account::TYPE_ASSET],
            ['code' => '042', 'name' => 'Побарувања врз основа на наем - финансиски лизинг', 'type' => Account::TYPE_ASSET],
            ['code' => '043', 'name' => 'Побарувања врз основа на форфетинг', 'type' => Account::TYPE_ASSET],
            ['code' => '044', 'name' => 'Побарувања врз основа на дадени гаранции', 'type' => Account::TYPE_ASSET],
            ['code' => '045', 'name' => 'Спорни и ризични побарувања', 'type' => Account::TYPE_ASSET],
            ['code' => '046', 'name' => 'Побарувања за дадени аванси', 'type' => Account::TYPE_ASSET],
            ['code' => '047', 'name' => 'Останати долгорочни побарувања', 'type' => Account::TYPE_ASSET],
            ['code' => '049', 'name' => 'Вредносно усогласување на долгорочни побарувања', 'type' => Account::TYPE_ASSET],

            // 05 - ОДЛОЖЕНИ ДАНОЧНИ СРЕДСТВА
            ['code' => '050', 'name' => 'Одложени даночни средства', 'type' => Account::TYPE_ASSET],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * КЛАСА 1: ПАРИЧНИ СРЕДСТВА, ХАРТИИ ОД ВРЕДНОСТ, КРАТКОРОЧНИ ПОБАРУВАЊА
     */
    private function seedClass1(int $companyId): void
    {
        $accounts = [
            // 10 - ПАРИЧНИ СРЕДСТВА И ПАРИЧНИ ЕКВИВАЛЕНТИ
            ['code' => '100', 'name' => 'Парични средства на трансакциски сметки во денари', 'type' => Account::TYPE_ASSET],
            ['code' => '101', 'name' => 'Издвоени парични средства и акредитиви', 'type' => Account::TYPE_ASSET],
            ['code' => '102', 'name' => 'Парични средства во благајна', 'type' => Account::TYPE_ASSET],
            ['code' => '103', 'name' => 'Девизни сметки', 'type' => Account::TYPE_ASSET],
            ['code' => '104', 'name' => 'Девизни акредитиви', 'type' => Account::TYPE_ASSET],
            ['code' => '105', 'name' => 'Парични средства во благајна во странска валута', 'type' => Account::TYPE_ASSET],
            ['code' => '106', 'name' => 'Депозити - парични еквиваленти', 'type' => Account::TYPE_ASSET],
            ['code' => '107', 'name' => 'Хартии од вредност - готовински еквиваленти', 'type' => Account::TYPE_ASSET],
            ['code' => '108', 'name' => 'Останати парични средства', 'type' => Account::TYPE_ASSET],
            ['code' => '109', 'name' => 'Вредносно усогласување на парични еквиваленти', 'type' => Account::TYPE_ASSET],

            // 11 - ПОБАРУВАЊА ОД ПОВРЗАНИ ДРУШТВА
            ['code' => '110', 'name' => 'Побарувања од поврзани друштва врз основа на продажба на добра и услуги во земјата', 'type' => Account::TYPE_ASSET],
            ['code' => '111', 'name' => 'Побарувања од поврзани друштва врз основа на продажба на добра и услуги во странство', 'type' => Account::TYPE_ASSET],
            ['code' => '112', 'name' => 'Побарувања од поврзани друштва за аванси, депозити и кауции во земјата', 'type' => Account::TYPE_ASSET],
            ['code' => '113', 'name' => 'Побарувања од поврзани друштва за аванси, депозити и кауции во странство', 'type' => Account::TYPE_ASSET],
            ['code' => '114', 'name' => 'Побарувања од поврзани друштва за камати во земјата', 'type' => Account::TYPE_ASSET],
            ['code' => '115', 'name' => 'Побарувања од поврзани друштва за камати во странство', 'type' => Account::TYPE_ASSET],
            ['code' => '116', 'name' => 'Побарувања од специфично работење од поврзани друштва', 'type' => Account::TYPE_ASSET],
            ['code' => '118', 'name' => 'Останати побарувања од поврзани друштва', 'type' => Account::TYPE_ASSET],
            ['code' => '119', 'name' => 'Вредносно усогласување на побарувањата од поврзани друштва', 'type' => Account::TYPE_ASSET],

            // 12 - ПОБАРУВАЊА ОД КУПУВАЧИ
            ['code' => '120', 'name' => 'Побарувања од купувачи во земјата', 'type' => Account::TYPE_ASSET],
            ['code' => '121', 'name' => 'Побарувања од купувачи во странство', 'type' => Account::TYPE_ASSET],
            ['code' => '122', 'name' => 'Побарувања за дадени аванси, депозити и кауции во земјата', 'type' => Account::TYPE_ASSET],
            ['code' => '123', 'name' => 'Побарувања за дадени аванси, депозити и кауции во странство', 'type' => Account::TYPE_ASSET],
            ['code' => '124', 'name' => 'Побарувања од специфично работење на неповрзани друштва', 'type' => Account::TYPE_ASSET],
            ['code' => '125', 'name' => 'Побарувања од камати (договорни и казнени)', 'type' => Account::TYPE_ASSET],
            ['code' => '126', 'name' => 'Спорни и сомнителни побарувања', 'type' => Account::TYPE_ASSET],
            ['code' => '127', 'name' => 'Останати побарувања', 'type' => Account::TYPE_ASSET],
            ['code' => '129', 'name' => 'Вредносно усогласување на побарувањата од купувачи', 'type' => Account::TYPE_ASSET],

            // 13 - ПОБАРУВАЊА ОД ДРЖАВНИ ОРГАНИ И ИНСТИТУЦИИ
            ['code' => '130', 'name' => 'Данок на додадена вредност', 'type' => Account::TYPE_ASSET],
            ['code' => '131', 'name' => 'Побарувања за повеќе платени акцизи', 'type' => Account::TYPE_ASSET],
            ['code' => '132', 'name' => 'Побарувања за повеќе платени царини и царински давачки', 'type' => Account::TYPE_ASSET],
            ['code' => '133', 'name' => 'Побарувања за повеќе платен данок на добивка', 'type' => Account::TYPE_ASSET],
            ['code' => '134', 'name' => 'Побарувања за повеќе платен персонален данок на доход', 'type' => Account::TYPE_ASSET],
            ['code' => '135', 'name' => 'Побарувања за повеќе платени придонеси и други давачки', 'type' => Account::TYPE_ASSET],
            ['code' => '136', 'name' => 'Побарувања за повеќе платен данок на имот', 'type' => Account::TYPE_ASSET],
            ['code' => '137', 'name' => 'Побарувања за регрес, субвенции, премии и други државни поддршки', 'type' => Account::TYPE_ASSET],
            ['code' => '138', 'name' => 'Останати побарувања од државни органи и институции', 'type' => Account::TYPE_ASSET],
            ['code' => '139', 'name' => 'Вредносно усогласување на побарувањата од државни органи и институции', 'type' => Account::TYPE_ASSET],

            // 14 - ПОБАРУВАЊА ОД ВРАБОТЕНИТЕ
            ['code' => '140', 'name' => 'Побарувања од вработените за повеќе исплатена плата и надоместоци', 'type' => Account::TYPE_ASSET],
            ['code' => '143', 'name' => 'Побарувања од вработените за аконтации за службени патувања', 'type' => Account::TYPE_ASSET],
            ['code' => '145', 'name' => 'Останати побарувања од вработените', 'type' => Account::TYPE_ASSET],
            ['code' => '149', 'name' => 'Вредносно усогласување на побарувањата од вработените', 'type' => Account::TYPE_ASSET],

            // 15 - ОСТАНАТИ ПОБАРУВАЊА
            ['code' => '150', 'name' => 'Побарувања од осигурителни друштва', 'type' => Account::TYPE_ASSET],
            ['code' => '151', 'name' => 'Побарувања за тантиеми', 'type' => Account::TYPE_ASSET],
            ['code' => '152', 'name' => 'Побарувања врз основа на цесија, асигнација и преземање на долг', 'type' => Account::TYPE_ASSET],
            ['code' => '153', 'name' => 'Побарувања за членарини', 'type' => Account::TYPE_ASSET],
            ['code' => '154', 'name' => 'Побарувања за дивиденда или удел во добивката', 'type' => Account::TYPE_ASSET],
            ['code' => '157', 'name' => 'Побарувања врз основа на продажба на удел', 'type' => Account::TYPE_ASSET],
            ['code' => '158', 'name' => 'Останати побарувања', 'type' => Account::TYPE_ASSET],
            ['code' => '159', 'name' => 'Вредносно усогласување на останати побарувања', 'type' => Account::TYPE_ASSET],

            // 16 - КРАТКОРОЧНИ ФИНАНСИСКИ СРЕДСТВА
            ['code' => '160', 'name' => 'Краткорочни кредити и заеми од поврзани друштва во земјата', 'type' => Account::TYPE_ASSET],
            ['code' => '161', 'name' => 'Краткорочни кредити и заеми од поврзани друштва во странство', 'type' => Account::TYPE_ASSET],
            ['code' => '162', 'name' => 'Краткорочни кредити и заеми во земјата', 'type' => Account::TYPE_ASSET],
            ['code' => '163', 'name' => 'Краткорочни кредити и заеми од странство', 'type' => Account::TYPE_ASSET],
            ['code' => '164', 'name' => 'Хартии од вредност кои се чуваат до доспевање', 'type' => Account::TYPE_ASSET],
            ['code' => '165', 'name' => 'Хартии од вредност според објективна вредност преку добивката или загубата', 'type' => Account::TYPE_ASSET],
            ['code' => '166', 'name' => 'Краткорочно орочени денарски средства', 'type' => Account::TYPE_ASSET],
            ['code' => '167', 'name' => 'Краткорочно орочени странски средства за плаќање', 'type' => Account::TYPE_ASSET],
            ['code' => '168', 'name' => 'Останати краткорочни финансиски средства', 'type' => Account::TYPE_ASSET],
            ['code' => '169', 'name' => 'Вредносно усогласување на финансиски средства', 'type' => Account::TYPE_ASSET],

            // 19 - ПЛАТЕНИ ТРОШОЦИ ЗА ИДНИ ПЕРИОДИ И ПРЕСМЕТАНИ ПРИХОДИ (АВР)
            ['code' => '190', 'name' => 'Однапред платени трошоци', 'type' => Account::TYPE_ASSET],
            ['code' => '191', 'name' => 'Однапред платени зависни трошоци за набавка', 'type' => Account::TYPE_ASSET],
            ['code' => '195', 'name' => 'Пресметани приходи што не можеле да бидат фактурирани', 'type' => Account::TYPE_ASSET],
            ['code' => '198', 'name' => 'Останати однапред платени трошоци и пресметани приходи', 'type' => Account::TYPE_ASSET],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * КЛАСА 2: ОБВРСКИ, РЕЗЕРВИРАЊА ЗА ТРОШОЦИ И РИЗИЦИ
     */
    private function seedClass2(int $companyId): void
    {
        $accounts = [
            // 21 - КРАТКОРОЧНИ ОБВРСКИ СПРЕМА ПОВРЗАНИ ДРУШТВА
            ['code' => '210', 'name' => 'Обврски од поврзани друштва врз основа на набавка на добра и услуги во земјата', 'type' => Account::TYPE_LIABILITY],
            ['code' => '211', 'name' => 'Обврски од поврзани друштва врз основа на набавка на добра и услуги од странство', 'type' => Account::TYPE_LIABILITY],
            ['code' => '212', 'name' => 'Обврски од поврзани друштва за аванси, депозити и кауции во земјата', 'type' => Account::TYPE_LIABILITY],
            ['code' => '213', 'name' => 'Обврски од поврзани друштва за аванси, депозити и кауции од странство', 'type' => Account::TYPE_LIABILITY],
            ['code' => '214', 'name' => 'Обврски од поврзани друштва за камати во земјата', 'type' => Account::TYPE_LIABILITY],
            ['code' => '215', 'name' => 'Обврски од поврзани друштва за камати од странство', 'type' => Account::TYPE_LIABILITY],
            ['code' => '216', 'name' => 'Обврски од специфично работење од поврзани друштва', 'type' => Account::TYPE_LIABILITY],
            ['code' => '218', 'name' => 'Останати обврски од поврзани друштва', 'type' => Account::TYPE_LIABILITY],

            // 22 - КРАТКОРОЧНИ ОБВРСКИ СПРЕМА ДОБАВУВАЧИ
            ['code' => '220', 'name' => 'Обврски спрема добавувачи во земјата', 'type' => Account::TYPE_LIABILITY],
            ['code' => '221', 'name' => 'Обврски спрема добавувачи од странство', 'type' => Account::TYPE_LIABILITY],
            ['code' => '222', 'name' => 'Обврски за примени аванси, депозити и кауции во земјата', 'type' => Account::TYPE_LIABILITY],
            ['code' => '223', 'name' => 'Обврски за примени аванси, депозити и кауции од странство', 'type' => Account::TYPE_LIABILITY],
            ['code' => '224', 'name' => 'Обврски од специфично работење од неповрзани друштва', 'type' => Account::TYPE_LIABILITY],
            ['code' => '225', 'name' => 'Обврски за камати (договорни и казнени)', 'type' => Account::TYPE_LIABILITY],
            ['code' => '229', 'name' => 'Останати обврски од добавувачи', 'type' => Account::TYPE_LIABILITY],

            // 23 - КРАТКОРОЧНИ ОБВРСКИ ЗА ДАНОЦИ, ПРИДОНЕСИ И ДРУГИ ДАВАЧКИ
            ['code' => '230', 'name' => 'Обврски за данокот на додадена вредност', 'type' => Account::TYPE_LIABILITY],
            ['code' => '231', 'name' => 'Обврски за акцизи', 'type' => Account::TYPE_LIABILITY],
            ['code' => '232', 'name' => 'Обврски за царини и царински давачки', 'type' => Account::TYPE_LIABILITY],
            ['code' => '233', 'name' => 'Обврски за данок на добивка', 'type' => Account::TYPE_LIABILITY],
            ['code' => '234', 'name' => 'Обврски за даноци и придонеси на плата и надоместоци на плата', 'type' => Account::TYPE_LIABILITY],
            ['code' => '235', 'name' => 'Обврски за персонален данок на доход', 'type' => Account::TYPE_LIABILITY],
            ['code' => '236', 'name' => 'Обврски за данок на имот', 'type' => Account::TYPE_LIABILITY],
            ['code' => '237', 'name' => 'Обврски за данок на непризнати расходи и помалку искажани приходи', 'type' => Account::TYPE_LIABILITY],
            ['code' => '239', 'name' => 'Обврски за останати даноци, придонеси и други давачки', 'type' => Account::TYPE_LIABILITY],

            // 24 - ОБВРСКИ СПРЕМА ВРАБОТЕНИТЕ
            ['code' => '240', 'name' => 'Обврски за плата и надоместоци на плата', 'type' => Account::TYPE_LIABILITY],
            ['code' => '241', 'name' => 'Обврски за надоместоци на трошоците на вработените', 'type' => Account::TYPE_LIABILITY],
            ['code' => '242', 'name' => 'Останати обврски спрема вработените врз основа на колективни договори', 'type' => Account::TYPE_LIABILITY],
            ['code' => '249', 'name' => 'Останати обврски спрема вработените', 'type' => Account::TYPE_LIABILITY],

            // 25 - ОСТАНАТИ КРАТКОРОЧНИ ОБВРСКИ И КРАТКОРОЧНИ РЕЗЕРВИРАЊА
            ['code' => '250', 'name' => 'Обврски спрема осигурителни друштва', 'type' => Account::TYPE_LIABILITY],
            ['code' => '251', 'name' => 'Обврски за надомест на членови на управен и надзорен одбор', 'type' => Account::TYPE_LIABILITY],
            ['code' => '252', 'name' => 'Обврски спрема вршители на дејност и други физички лица', 'type' => Account::TYPE_LIABILITY],
            ['code' => '253', 'name' => 'Обврски врз основа на наем', 'type' => Account::TYPE_LIABILITY],
            ['code' => '254', 'name' => 'Обврски врз основа на учество во добивката', 'type' => Account::TYPE_LIABILITY],
            ['code' => '255', 'name' => 'Обврски за членарини', 'type' => Account::TYPE_LIABILITY],
            ['code' => '256', 'name' => 'Обврски за краткорочни резервирања на трошоци во гарантен рок', 'type' => Account::TYPE_LIABILITY],
            ['code' => '259', 'name' => 'Останати краткорочни обврски', 'type' => Account::TYPE_LIABILITY],

            // 26 - КРАТКОРОЧНИ ФИНАНСИСКИ ОБВРСКИ
            ['code' => '260', 'name' => 'Краткорочни кредити и заеми од поврзани друштва во земјата', 'type' => Account::TYPE_LIABILITY],
            ['code' => '261', 'name' => 'Краткорочни кредити и заеми од поврзани друштва од странство', 'type' => Account::TYPE_LIABILITY],
            ['code' => '262', 'name' => 'Краткорочни кредити и заеми во земјата', 'type' => Account::TYPE_LIABILITY],
            ['code' => '263', 'name' => 'Краткорочни кредити и заеми од странство', 'type' => Account::TYPE_LIABILITY],
            ['code' => '264', 'name' => 'Обврски врз основа на издадени хартии од вредност', 'type' => Account::TYPE_LIABILITY],
            ['code' => '265', 'name' => 'Обврски врз основа на есконтни работи', 'type' => Account::TYPE_LIABILITY],
            ['code' => '266', 'name' => 'Обврски врз основа на откуп на побарувања', 'type' => Account::TYPE_LIABILITY],
            ['code' => '269', 'name' => 'Обврски врз основа на останати краткорочни финансиски средства', 'type' => Account::TYPE_LIABILITY],

            // 27 - ДОЛГОРОЧНИ РЕЗЕРВИРАЊА
            ['code' => '270', 'name' => 'Резервирања за трошоци во гарантен рок', 'type' => Account::TYPE_LIABILITY],
            ['code' => '271', 'name' => 'Резервирања за трошоци за обновување на природни богатства', 'type' => Account::TYPE_LIABILITY],
            ['code' => '272', 'name' => 'Резервирања за трошоци за преструктуирање', 'type' => Account::TYPE_LIABILITY],
            ['code' => '273', 'name' => 'Резервирања за користи на вработените', 'type' => Account::TYPE_LIABILITY],
            ['code' => '279', 'name' => 'Останати долгорочни резервирања', 'type' => Account::TYPE_LIABILITY],

            // 28 - ДОЛГОРОЧНИ ОБВРСКИ
            ['code' => '280', 'name' => 'Долгорочни обврски од поврзани друштва врз основа на набавка во земјата и странство', 'type' => Account::TYPE_LIABILITY],
            ['code' => '281', 'name' => 'Долгорочни обврски од поврзани друштва за аванси, депозити и кауции', 'type' => Account::TYPE_LIABILITY],
            ['code' => '282', 'name' => 'Долгорочни обврски спрема добавувачи во земјата', 'type' => Account::TYPE_LIABILITY],
            ['code' => '283', 'name' => 'Долгорочни обврски спрема добавувачи од странство', 'type' => Account::TYPE_LIABILITY],
            ['code' => '284', 'name' => 'Долгорочни обврски за примени аванси, депозити и кауции', 'type' => Account::TYPE_LIABILITY],
            ['code' => '285', 'name' => 'Долгорочни обврски врз основа на заеми и кредити од поврзани друштва', 'type' => Account::TYPE_LIABILITY],
            ['code' => '286', 'name' => 'Долгорочни обврски врз основа на заеми и кредити во земјата и странство', 'type' => Account::TYPE_LIABILITY],
            ['code' => '287', 'name' => 'Долгорочни обврски врз основа на издадени хартии од вредност', 'type' => Account::TYPE_LIABILITY],
            ['code' => '288', 'name' => 'Останати долгорочни обврски и останати финансиски долгорочни обврски', 'type' => Account::TYPE_LIABILITY],
            ['code' => '289', 'name' => 'Одложени даночни обврски', 'type' => Account::TYPE_LIABILITY],

            // 29 - ОДЛОЖЕНИ ПЛАЌАЊА НА ТРОШОЦИ И ПРИХОДИ НА ИДНИ ПЕРИОДИ (ПВР)
            ['code' => '290', 'name' => 'Однапред пресметани трошоци', 'type' => Account::TYPE_LIABILITY],
            ['code' => '291', 'name' => 'Пресметани трошоци за набавка на добра', 'type' => Account::TYPE_LIABILITY],
            ['code' => '293', 'name' => 'Пресметани приходи за идни периоди', 'type' => Account::TYPE_LIABILITY],
            ['code' => '294', 'name' => 'Одложено признавање на приходи врз основа на државни поддршки', 'type' => Account::TYPE_LIABILITY],
            ['code' => '295', 'name' => 'Одложено признавање на приходи', 'type' => Account::TYPE_LIABILITY],
            ['code' => '299', 'name' => 'Останати пасивни временски разграничувања', 'type' => Account::TYPE_LIABILITY],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * КЛАСА 3: ЗАЛИХИ НА СУРОВИНИ, МАТЕРИЈАЛИ, РЕЗЕРВНИ ДЕЛОВИ И СИТЕН ИНВЕНТАР
     */
    private function seedClass3(int $companyId): void
    {
        $accounts = [
            // 30 - ПРЕСМЕТКА НА НАБАВКАТА НА ЗАЛИХИ
            ['code' => '300', 'name' => 'Вредност по пресметка од добавувачите', 'type' => Account::TYPE_ASSET],
            ['code' => '301', 'name' => 'Зависни трошоци на набавката', 'type' => Account::TYPE_ASSET],
            ['code' => '302', 'name' => 'Царини и други увозни давачки', 'type' => Account::TYPE_ASSET],
            ['code' => '303', 'name' => 'Данок на додадена вредност и останати давачки (без право на одбивка)', 'type' => Account::TYPE_ASSET],
            ['code' => '304', 'name' => 'Останати давачки поврзани со набавката на залихите', 'type' => Account::TYPE_ASSET],
            ['code' => '309', 'name' => 'Пресметка на набавката', 'type' => Account::TYPE_ASSET],

            // 31 - ЗАЛИХА НА СУРОВИНИ И МАТЕРИЈАЛИ
            ['code' => '310', 'name' => 'Суровини и материјали на залиха', 'type' => Account::TYPE_ASSET],
            ['code' => '311', 'name' => 'Суровини и материјали на пат', 'type' => Account::TYPE_ASSET],
            ['code' => '316', 'name' => 'Суровини и материјали во доработка, обработка и манипулација', 'type' => Account::TYPE_ASSET],
            ['code' => '318', 'name' => 'Вредносно усогласување на залихите на суровини и материјали', 'type' => Account::TYPE_ASSET],
            ['code' => '319', 'name' => 'Отстапување од стандардните (плански) цени на суровини и материјали', 'type' => Account::TYPE_ASSET],

            // 32 - ЗАЛИХА НА РЕЗЕРВНИ ДЕЛОВИ
            ['code' => '320', 'name' => 'Залиха на резервни делови', 'type' => Account::TYPE_ASSET],
            ['code' => '328', 'name' => 'Вредносно усогласување на залиха на резервни делови', 'type' => Account::TYPE_ASSET],

            // 35 - ЗАЛИХА НА СИТЕН ИНВЕНТАР, АМБАЛАЖА И АВТОГУМИ
            ['code' => '350', 'name' => 'Ситен инвентар на залиха', 'type' => Account::TYPE_ASSET],
            ['code' => '351', 'name' => 'Ситен инвентар во употреба', 'type' => Account::TYPE_ASSET],
            ['code' => '352', 'name' => 'Залиха на амбалажа', 'type' => Account::TYPE_ASSET],
            ['code' => '353', 'name' => 'Амбалажа во употреба', 'type' => Account::TYPE_ASSET],
            ['code' => '354', 'name' => 'Залиха на автогуми', 'type' => Account::TYPE_ASSET],
            ['code' => '355', 'name' => 'Автогуми во употреба', 'type' => Account::TYPE_ASSET],
            ['code' => '358', 'name' => 'Вредносно усогласување на залихи на ситен инвентар, амбалажа и автогуми', 'type' => Account::TYPE_ASSET],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * КЛАСА 4: ТРОШОЦИ И РАСХОДИ ОД РАБОТЕЊЕТО
     */
    private function seedClass4(int $companyId): void
    {
        $accounts = [
            // 40 - ТРОШОЦИ ЗА СУРОВИНИ, МАТЕРИЈАЛИ, ЕНЕРГИЈА, РЕЗЕРВНИ ДЕЛОВИ И СИТЕН ИНВЕНТАР
            ['code' => '400', 'name' => 'Трошоци за суровини и материјали (за производство)', 'type' => Account::TYPE_EXPENSE],
            ['code' => '401', 'name' => 'Трошоци за материјали (за администрација, управа и продажба)', 'type' => Account::TYPE_EXPENSE],
            ['code' => '402', 'name' => 'Трошоци за енергија (за производство)', 'type' => Account::TYPE_EXPENSE],
            ['code' => '403', 'name' => 'Трошоци за енергија (за администрација, управа и продажба)', 'type' => Account::TYPE_EXPENSE],
            ['code' => '404', 'name' => 'Трошоци за резервни делови и материјали за одржување (за производство)', 'type' => Account::TYPE_EXPENSE],
            ['code' => '405', 'name' => 'Трошоци за резервни делови и материјали за одржување (за администрација)', 'type' => Account::TYPE_EXPENSE],
            ['code' => '406', 'name' => 'Трошоци за амбалажа', 'type' => Account::TYPE_EXPENSE],
            ['code' => '407', 'name' => 'Трошоци за ситен инвентар, амбалажа и автогуми (за производство)', 'type' => Account::TYPE_EXPENSE],
            ['code' => '408', 'name' => 'Трошоци за ситен инвентар, амбалажа и автогуми (за администрација)', 'type' => Account::TYPE_EXPENSE],
            ['code' => '409', 'name' => 'Отстапувања од стандардните (плански) цени', 'type' => Account::TYPE_EXPENSE],

            // 41 - ТРОШОЦИ ЗА УСЛУГИ
            ['code' => '410', 'name' => 'Транспортни услуги', 'type' => Account::TYPE_EXPENSE],
            ['code' => '411', 'name' => 'Поштенски услуги, телефонски услуги и интернет', 'type' => Account::TYPE_EXPENSE],
            ['code' => '412', 'name' => 'Надворешни услуги за изработка на добра и извршување на услуги', 'type' => Account::TYPE_EXPENSE],
            ['code' => '413', 'name' => 'Услуги за одржување и заштита', 'type' => Account::TYPE_EXPENSE],
            ['code' => '414', 'name' => 'Наем - лизинг', 'type' => Account::TYPE_EXPENSE],
            ['code' => '415', 'name' => 'Комунални услуги', 'type' => Account::TYPE_EXPENSE],
            ['code' => '416', 'name' => 'Трошоци за истражување и развој', 'type' => Account::TYPE_EXPENSE],
            ['code' => '417', 'name' => 'Трошоци за реклама, пропаганда, промоција и саеми', 'type' => Account::TYPE_EXPENSE],
            ['code' => '419', 'name' => 'Останати услуги', 'type' => Account::TYPE_EXPENSE],

            // 42 - ПЛАТА, НАДОМЕСТОЦИ НА ПЛАТА И ОСТАНАТИ ТРОШОЦИ ЗА ВРАБОТЕНИТЕ
            ['code' => '420', 'name' => 'Плата и надоместоци на плата - бруто (за производство)', 'type' => Account::TYPE_EXPENSE],
            ['code' => '421', 'name' => 'Плата и надоместоци на плата - бруто (за администрација, управа и продажба)', 'type' => Account::TYPE_EXPENSE],
            ['code' => '422', 'name' => 'Останати трошоци за вработените', 'type' => Account::TYPE_EXPENSE],

            // 43 - ТРОШОЦИ ЗА АМОРТИЗАЦИЈА И РЕЗЕРВИРАЊА
            ['code' => '430', 'name' => 'Трошоци за амортизација (за производство)', 'type' => Account::TYPE_EXPENSE],
            ['code' => '431', 'name' => 'Трошоци за амортизација на биолошки средства', 'type' => Account::TYPE_EXPENSE],
            ['code' => '432', 'name' => 'Трошоци за амортизација (за администрација, управа и продажба)', 'type' => Account::TYPE_EXPENSE],
            ['code' => '433', 'name' => 'Долгорочни резервирања за трошоци за обновување на природни богатства', 'type' => Account::TYPE_EXPENSE],
            ['code' => '435', 'name' => 'Долгорочни резервирања за користи на вработените', 'type' => Account::TYPE_EXPENSE],
            ['code' => '436', 'name' => 'Долгорочни резервирања на трошоци за преструктуирање', 'type' => Account::TYPE_EXPENSE],
            ['code' => '437', 'name' => 'Долгорочни резервирања за гарантен рок', 'type' => Account::TYPE_EXPENSE],
            ['code' => '438', 'name' => 'Долгорочни резервирања за кауции и депозити', 'type' => Account::TYPE_EXPENSE],
            ['code' => '439', 'name' => 'Останати долгорочни резервирања за трошоци и ризици', 'type' => Account::TYPE_EXPENSE],

            // 44 - ОСТАНАТИ ТРОШОЦИ ОД РАБОТЕЊЕТО
            ['code' => '440', 'name' => 'Дневници за службени патувања, ноќевања и патни трошоци', 'type' => Account::TYPE_EXPENSE],
            ['code' => '441', 'name' => 'Надоместоци на трошоци на вработените и подароци', 'type' => Account::TYPE_EXPENSE],
            ['code' => '442', 'name' => 'Трошоци за надомест на членови на управен и надзорен одбор', 'type' => Account::TYPE_EXPENSE],
            ['code' => '443', 'name' => 'Трошоци за спонзорства и донации', 'type' => Account::TYPE_EXPENSE],
            ['code' => '444', 'name' => 'Трошоци за репрезентација', 'type' => Account::TYPE_EXPENSE],
            ['code' => '445', 'name' => 'Трошоци за осигурување', 'type' => Account::TYPE_EXPENSE],
            ['code' => '446', 'name' => 'Банкарски услуги и трошоци за платен промет', 'type' => Account::TYPE_EXPENSE],
            ['code' => '447', 'name' => 'Даноци кои не зависат од резултатот, членарини и други давачки', 'type' => Account::TYPE_EXPENSE],
            ['code' => '448', 'name' => 'Трошоци за користење на права (освен наем)', 'type' => Account::TYPE_EXPENSE],
            ['code' => '449', 'name' => 'Останати трошоци на работењето', 'type' => Account::TYPE_EXPENSE],

            // 45 - ВРЕДНОСНО УСОГЛАСУВАЊЕ (ОБЕЗВРЕДНУВАЊЕ)
            ['code' => '450', 'name' => 'Вредносно усогласување (обезвреднување) на нематеријални средства', 'type' => Account::TYPE_EXPENSE],
            ['code' => '451', 'name' => 'Вредносно усогласување (обезвреднување) на материјални средства', 'type' => Account::TYPE_EXPENSE],
            ['code' => '452', 'name' => 'Вредносно усогласување (обезвреднување) на вложувања во недвижности', 'type' => Account::TYPE_EXPENSE],
            ['code' => '455', 'name' => 'Вредносно усогласување (обезвреднување) на краткорочни побарувања', 'type' => Account::TYPE_EXPENSE],
            ['code' => '456', 'name' => 'Вредносно усогласување (обезвреднување) на залихи', 'type' => Account::TYPE_EXPENSE],
            ['code' => '459', 'name' => 'Вредносно усогласување (обезвреднување) на останати средства', 'type' => Account::TYPE_EXPENSE],

            // 46 - ОСТАНАТИ РАСХОДИ
            ['code' => '460', 'name' => 'Загуби врз основа на расходување и загуби од продажба на средства', 'type' => Account::TYPE_EXPENSE],
            ['code' => '462', 'name' => 'Загуби врз основа на продажба на учество во капитал и хартии од вредност', 'type' => Account::TYPE_EXPENSE],
            ['code' => '463', 'name' => 'Загуба од продажба на материјали', 'type' => Account::TYPE_EXPENSE],
            ['code' => '464', 'name' => 'Кусоци, кало, растур, расипување и кршење', 'type' => Account::TYPE_EXPENSE],
            ['code' => '466', 'name' => 'Расходи врз основа на директен отпис на побарувања', 'type' => Account::TYPE_EXPENSE],
            ['code' => '467', 'name' => 'Расходи за дополнително одобрени попусти, рабат, рекламации', 'type' => Account::TYPE_EXPENSE],
            ['code' => '468', 'name' => 'Казни, пенали, надоместоци за штети и друго', 'type' => Account::TYPE_EXPENSE],
            ['code' => '469', 'name' => 'Останати расходи од работењето', 'type' => Account::TYPE_EXPENSE],

            // 47 - ФИНАНСИСКИ РАСХОДИ
            ['code' => '470', 'name' => 'Расходи врз основа на камати од работењето со поврзани друштва', 'type' => Account::TYPE_EXPENSE],
            ['code' => '471', 'name' => 'Расходи врз основа на курсни разлики од работењето со поврзани друштва', 'type' => Account::TYPE_EXPENSE],
            ['code' => '472', 'name' => 'Останати финансиски расходи од поврзани друштва', 'type' => Account::TYPE_EXPENSE],
            ['code' => '474', 'name' => 'Расходи врз основа на камати од работењето со неповрзани друштва', 'type' => Account::TYPE_EXPENSE],
            ['code' => '475', 'name' => 'Расходи врз основа на негативни курсни разлики', 'type' => Account::TYPE_EXPENSE],
            ['code' => '476', 'name' => 'Вредносно усогласување на долгорочни финансиски средства', 'type' => Account::TYPE_EXPENSE],
            ['code' => '477', 'name' => 'Нереализирани загуби (расходи) од финансиски средства', 'type' => Account::TYPE_EXPENSE],
            ['code' => '479', 'name' => 'Останати финансиски расходи', 'type' => Account::TYPE_EXPENSE],

            // 48 - УДЕЛ ВО ЗАГУБА НА ПРИДРУЖНО ДРУШТВО
            ['code' => '480', 'name' => 'Удел во загуба на придружно друштво', 'type' => Account::TYPE_EXPENSE],
            ['code' => '481', 'name' => 'Нето загуба од прекинато работење', 'type' => Account::TYPE_EXPENSE],

            // 49 - ПРЕНОС НА РАСХОДИ
            ['code' => '490', 'name' => 'Распоред на директни трошоци', 'type' => Account::TYPE_EXPENSE],
            ['code' => '491', 'name' => 'Распоред на општи трошоци', 'type' => Account::TYPE_EXPENSE],
            ['code' => '492', 'name' => 'Распоред на трошоците непосредно врз товар на вкупниот приход', 'type' => Account::TYPE_EXPENSE],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * КЛАСА 6: ЗАЛИХИ НА ПРОИЗВОДСТВО, ГОТОВИ ПРОИЗВОДИ И СТОКИ
     */
    private function seedClass6(int $companyId): void
    {
        $accounts = [
            // 60 - ПРОИЗВОДСТВО
            ['code' => '600', 'name' => 'Производство (изградба) во тек', 'type' => Account::TYPE_ASSET],
            ['code' => '601', 'name' => 'Залиха на полупроизводи', 'type' => Account::TYPE_ASSET],
            ['code' => '602', 'name' => 'Залиха на застарени недовршени производи, полупроизводи и делови', 'type' => Account::TYPE_ASSET],
            ['code' => '608', 'name' => 'Вредносно усогласување на недовршени производи', 'type' => Account::TYPE_ASSET],

            // 61 - БИОЛОШКИ СРЕДСТВА
            ['code' => '610', 'name' => 'Недовршено производство на биолошки средства', 'type' => Account::TYPE_ASSET],
            ['code' => '611', 'name' => 'Залиха на биолошки средства за продажба', 'type' => Account::TYPE_ASSET],
            ['code' => '618', 'name' => 'Вредносно усогласување на биолошки средства', 'type' => Account::TYPE_ASSET],

            // 63 - ГОТОВИ ПРОИЗВОДИ
            ['code' => '630', 'name' => 'Производи на залиха', 'type' => Account::TYPE_ASSET],
            ['code' => '631', 'name' => 'Производи во туѓ склад', 'type' => Account::TYPE_ASSET],
            ['code' => '633', 'name' => 'Производи во продавница', 'type' => Account::TYPE_ASSET],
            ['code' => '634', 'name' => 'Вкалкулиран данок на додадена вредност', 'type' => Account::TYPE_ASSET],
            ['code' => '637', 'name' => 'Залихи на некурентни производи и отпадоци', 'type' => Account::TYPE_ASSET],
            ['code' => '638', 'name' => 'Вредносно усогласување на залихите на готовите производи', 'type' => Account::TYPE_ASSET],

            // 65 - ПРЕСМЕТКА НА НАБАВКАТА НА СТОКИ
            ['code' => '650', 'name' => 'Вредност на стоките по пресметка на добавувачот', 'type' => Account::TYPE_ASSET],
            ['code' => '651', 'name' => 'Зависни трошоци за набавка на стоки', 'type' => Account::TYPE_ASSET],
            ['code' => '652', 'name' => 'Царини и други увозни давачки за стоките', 'type' => Account::TYPE_ASSET],
            ['code' => '659', 'name' => 'Пресметка на набавката', 'type' => Account::TYPE_ASSET],

            // 66 - СТОКИ
            ['code' => '660', 'name' => 'Стоки на залиха', 'type' => Account::TYPE_ASSET],
            ['code' => '661', 'name' => 'Стоки во туѓ склад', 'type' => Account::TYPE_ASSET],
            ['code' => '662', 'name' => 'Стоки на пат', 'type' => Account::TYPE_ASSET],
            ['code' => '663', 'name' => 'Стоки во продавница', 'type' => Account::TYPE_ASSET],
            ['code' => '664', 'name' => 'Вкалкулиран данок на додадена вредност', 'type' => Account::TYPE_ASSET],
            ['code' => '668', 'name' => 'Вредносно усогласување на залихата на стоките', 'type' => Account::TYPE_ASSET],
            ['code' => '669', 'name' => 'Разлика во цени на стоките', 'type' => Account::TYPE_ASSET],

            // 67 - НЕТЕКОВНИ СРЕДСТВА КОИ СЕ ЧУВААТ ЗА ПРОДАЖБА
            ['code' => '670', 'name' => 'Нематеријални средства кои се чуваат за продажба', 'type' => Account::TYPE_ASSET],
            ['code' => '674', 'name' => 'Останати нетековни средства кои се чуваат за продажба', 'type' => Account::TYPE_ASSET],
            ['code' => '678', 'name' => 'Вредносно усогласување на нетековни средства за продажба', 'type' => Account::TYPE_ASSET],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * КЛАСА 7: ПОКРИВАЊЕ НА РАСХОДИ И ПРИХОДИ
     */
    private function seedClass7(int $companyId): void
    {
        $accounts = [
            // 70 - РАСХОДИ НА ПРОДАДЕНИ ДОБРА И УСЛУГИ
            ['code' => '700', 'name' => 'Расходи врз основа на продадени добра (производи) и услуги', 'type' => Account::TYPE_EXPENSE],
            ['code' => '701', 'name' => 'Набавна вредност на продадени добра (стоки)', 'type' => Account::TYPE_EXPENSE],
            ['code' => '702', 'name' => 'Набавна вредност на продадени нетековни средства', 'type' => Account::TYPE_EXPENSE],
            ['code' => '703', 'name' => 'Набавна вредност на продадени материјали, резервни делови и отпадоци', 'type' => Account::TYPE_EXPENSE],

            // 73 - ПРИХОДИ ОД ПРОДАЖБА НА ДОБРА И УСЛУГИ НА ПОВРЗАНИ ДРУШТВА
            ['code' => '730', 'name' => 'Приходи од продажба на добра и услуги на поврзани друштва во земјата', 'type' => Account::TYPE_REVENUE],
            ['code' => '731', 'name' => 'Приходи од продажба на добра и услуги на поврзани друштва од странство', 'type' => Account::TYPE_REVENUE],
            ['code' => '732', 'name' => 'Приходи од специфично работење на поврзани друштва', 'type' => Account::TYPE_REVENUE],

            // 74 - ПРИХОДИ ОД ПРОДАЖБА НА НЕПОВРЗАНИ ДРУШТВА
            ['code' => '740', 'name' => 'Приходи од продажба на добра (производи) и услуги во земјата', 'type' => Account::TYPE_REVENUE],
            ['code' => '741', 'name' => 'Приходи од продажба на добра (стоки) во земјата', 'type' => Account::TYPE_REVENUE],
            ['code' => '742', 'name' => 'Приходи од продажба на добра и услуги во странство', 'type' => Account::TYPE_REVENUE],
            ['code' => '743', 'name' => 'Приходи од продажба на материјали, резервни делови и отпадоци', 'type' => Account::TYPE_REVENUE],
            ['code' => '744', 'name' => 'Приходи од продажба на нетековни средства за продажба', 'type' => Account::TYPE_REVENUE],
            ['code' => '745', 'name' => 'Приходи врз основа на употреба на сопствени добра и услуги', 'type' => Account::TYPE_REVENUE],
            ['code' => '747', 'name' => 'Приходи од наемнини', 'type' => Account::TYPE_REVENUE],
            ['code' => '749', 'name' => 'Останати приходи од продажба на неповрзани друштва', 'type' => Account::TYPE_REVENUE],

            // 75 - ПРИХОДИ ОД ВРЕДНОСНО УСОГЛАСУВАЊЕ
            ['code' => '750', 'name' => 'Приходи од вредносно усогласување на нематеријални средства', 'type' => Account::TYPE_REVENUE],
            ['code' => '751', 'name' => 'Приходи од вредносно усогласување на материјални средства', 'type' => Account::TYPE_REVENUE],
            ['code' => '755', 'name' => 'Приходи од вредносно усогласување на краткорочни побарувања', 'type' => Account::TYPE_REVENUE],
            ['code' => '756', 'name' => 'Приходи од вредносно усогласување на залихи', 'type' => Account::TYPE_REVENUE],
            ['code' => '759', 'name' => 'Приходи од вредносно усогласување на останати средства', 'type' => Account::TYPE_REVENUE],

            // 76 - ОСТАНАТИ ПРИХОДИ
            ['code' => '760', 'name' => 'Добивки од продажба на нематеријални и материјални средства', 'type' => Account::TYPE_REVENUE],
            ['code' => '762', 'name' => 'Добивки од продажба на учество во капитал и хартии од вредност', 'type' => Account::TYPE_REVENUE],
            ['code' => '764', 'name' => 'Вишоци', 'type' => Account::TYPE_REVENUE],
            ['code' => '765', 'name' => 'Приходи од наплатени отпишани побарувања и приходи од отпис на обврските', 'type' => Account::TYPE_REVENUE],
            ['code' => '767', 'name' => 'Приходи од премии, субвенции, дотации и донации', 'type' => Account::TYPE_REVENUE],
            ['code' => '768', 'name' => 'Приходи од укинување на долгорочни резервирања', 'type' => Account::TYPE_REVENUE],
            ['code' => '769', 'name' => 'Останати приходи од работењето', 'type' => Account::TYPE_REVENUE],

            // 77 - ФИНАНСИСКИ ПРИХОДИ
            ['code' => '770', 'name' => 'Приходи врз основа на камати од работењето со поврзани друштва', 'type' => Account::TYPE_REVENUE],
            ['code' => '771', 'name' => 'Приходи врз основа на позитивни курсни разлики со поврзани друштва', 'type' => Account::TYPE_REVENUE],
            ['code' => '773', 'name' => 'Приходи од вложувања во поврзани друштва', 'type' => Account::TYPE_REVENUE],
            ['code' => '774', 'name' => 'Приходи врз основа на камати од работењето со неповрзани друштва', 'type' => Account::TYPE_REVENUE],
            ['code' => '775', 'name' => 'Приходи врз основа на позитивни курсни разлики со неповрзани друштва', 'type' => Account::TYPE_REVENUE],
            ['code' => '776', 'name' => 'Приходи од вложувања во неповрзани друштва', 'type' => Account::TYPE_REVENUE],
            ['code' => '777', 'name' => 'Нереализирани добивки (приходи) од финансиски средства', 'type' => Account::TYPE_REVENUE],
            ['code' => '779', 'name' => 'Останати финансиски приходи', 'type' => Account::TYPE_REVENUE],

            // 78 - УДЕЛ ВО ДОБИВКАТА НА ПРИДРУЖНО ДРУШТВО
            ['code' => '780', 'name' => 'Удел во добивката на придружно друштво', 'type' => Account::TYPE_REVENUE],
            ['code' => '781', 'name' => 'Нето добивка од прекинато работење', 'type' => Account::TYPE_REVENUE],

            // 79 - РАЗЛИКА НА ПРИХОДИ И РАСХОДИ
            ['code' => '790', 'name' => 'Разлика на приходи и расходи од вкупното работење', 'type' => Account::TYPE_REVENUE],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * КЛАСА 8: РЕЗУЛТАТИ ОД РАБОТЕЊЕТО
     */
    private function seedClass8(int $companyId): void
    {
        $accounts = [
            // 80 - ДОБИВКА/ЗАГУБА ПРЕД ОДАНОЧУВАЊЕ
            ['code' => '800', 'name' => 'Добивка пред оданочување', 'type' => Account::TYPE_EQUITY],
            ['code' => '801', 'name' => 'Загуба пред оданочување', 'type' => Account::TYPE_EQUITY],

            // 81 - ДАНОК НА ДОБИВКА И ДРУГИ ДАВАЧКИ
            ['code' => '810', 'name' => 'Данок на добивка', 'type' => Account::TYPE_EXPENSE],
            ['code' => '811', 'name' => 'Одложени даночни приходи', 'type' => Account::TYPE_EXPENSE],
            ['code' => '812', 'name' => 'Одложени даночни расходи', 'type' => Account::TYPE_EXPENSE],
            ['code' => '813', 'name' => 'Други давачки', 'type' => Account::TYPE_EXPENSE],

            // 82 - НЕТО ДОБИВКА/ЗАГУБА ЗА ПЕРИОДОТ
            ['code' => '820', 'name' => 'Нето добивка за периодот', 'type' => Account::TYPE_EQUITY],
            ['code' => '821', 'name' => 'Нето загуба за периодот', 'type' => Account::TYPE_EQUITY],

            // 83 - ДОБИВКА/ЗАГУБА КОЈА ПРИПАЃА НА ДРУГИ
            ['code' => '830', 'name' => 'Добивка која припаѓа на сопствениците на матичното друштво', 'type' => Account::TYPE_EQUITY],
            ['code' => '831', 'name' => 'Добивка која припаѓа на учество кое нема контрола', 'type' => Account::TYPE_EQUITY],
            ['code' => '832', 'name' => 'Загуба која припаѓа на сопствениците на матичното друштво', 'type' => Account::TYPE_EQUITY],
            ['code' => '833', 'name' => 'Загуба која припаѓа на учество кое нема контрола', 'type' => Account::TYPE_EQUITY],

            // 89 - РАСПОРЕДУВАЊЕ НА ДОБИВКАТА
            ['code' => '890', 'name' => 'Покривање на загубата од претходни години', 'type' => Account::TYPE_EQUITY],
            ['code' => '891', 'name' => 'Зголемување на капиталот (капитал на сопствениците)', 'type' => Account::TYPE_EQUITY],
            ['code' => '892', 'name' => 'Дивиденди или удел во добивката и друго', 'type' => Account::TYPE_EQUITY],
            ['code' => '893', 'name' => 'Резерви', 'type' => Account::TYPE_EQUITY],
            ['code' => '899', 'name' => 'Нераспоредена добивка', 'type' => Account::TYPE_EQUITY],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * КЛАСА 9: КАПИТАЛ, РЕЗЕРВИ И ВОНБИЛАНСНА ЕВИДЕНЦИЈА
     */
    private function seedClass9(int $companyId): void
    {
        $accounts = [
            // 90 - ОСНОВНА ГЛАВНИНА - ЗАПИШАН КАПИТАЛ
            ['code' => '900', 'name' => 'Основна главнина - запишан и уплатен капитал', 'type' => Account::TYPE_EQUITY],
            ['code' => '901', 'name' => 'Запишана, а неуплатена основна главнина', 'type' => Account::TYPE_EQUITY],
            ['code' => '902', 'name' => 'Сопствени акции и удели', 'type' => Account::TYPE_EQUITY],

            // 91 - ПРЕМИИ НА ЕМИТИРАНИ АКЦИИ
            ['code' => '910', 'name' => 'Премии врз основа на продажба на обични акции', 'type' => Account::TYPE_EQUITY],
            ['code' => '911', 'name' => 'Премии врз основа на продажба на приоритетни акции', 'type' => Account::TYPE_EQUITY],

            // 93 - РЕВАЛОРИЗАЦИОНИ РЕЗЕРВИ
            ['code' => '930', 'name' => 'Ревалоризациони резерви на нематеријални и материјални средства', 'type' => Account::TYPE_EQUITY],
            ['code' => '931', 'name' => 'Ревалоризациони резерви врз основа на преведување на странско работење', 'type' => Account::TYPE_EQUITY],
            ['code' => '932', 'name' => 'Ревалоризациони резерви врз основа на финансиски средства расположиви за продажба', 'type' => Account::TYPE_EQUITY],
            ['code' => '935', 'name' => 'Останати ревалоризациони резерви', 'type' => Account::TYPE_EQUITY],

            // 94 - РЕЗЕРВИ
            ['code' => '940', 'name' => 'Законски резерви', 'type' => Account::TYPE_EQUITY],
            ['code' => '941', 'name' => 'Статутарни резерви', 'type' => Account::TYPE_EQUITY],
            ['code' => '942', 'name' => 'Останати резерви', 'type' => Account::TYPE_EQUITY],

            // 95 - ЗАДРЖАНА (АКУМУЛИРАНА) ДОБИВКА
            ['code' => '950', 'name' => 'Задржана (акумулирана) добивка од претходни години', 'type' => Account::TYPE_EQUITY],
            ['code' => '951', 'name' => 'Добивка од тековната година', 'type' => Account::TYPE_EQUITY],

            // 96 - ПРЕНЕСЕНА ЗАГУБА
            ['code' => '960', 'name' => 'Пренесена загуба од претходни години', 'type' => Account::TYPE_EQUITY],
            ['code' => '961', 'name' => 'Загуба за тековната година', 'type' => Account::TYPE_EQUITY],

            // 99 - ВОНБИЛАНСНА ЕВИДЕНЦИЈА
            ['code' => '990', 'name' => 'Примени туѓи недвижности, постројки и опрема', 'type' => Account::TYPE_EQUITY],
            ['code' => '991', 'name' => 'Примени туѓи материјали, полупроизводи, производи и стоки', 'type' => Account::TYPE_EQUITY],
            ['code' => '992', 'name' => 'Хартии од вредност и други вредносници', 'type' => Account::TYPE_EQUITY],
            ['code' => '993', 'name' => 'Права', 'type' => Account::TYPE_EQUITY],
            ['code' => '994', 'name' => 'Останата активна вонбилансна евиденција', 'type' => Account::TYPE_EQUITY],
            ['code' => '995', 'name' => 'Обврски за примени туѓи недвижности, постројки и опрема', 'type' => Account::TYPE_EQUITY],
            ['code' => '996', 'name' => 'Обврски за примени туѓи материјали, полупроизводи, производи и стоки', 'type' => Account::TYPE_EQUITY],
            ['code' => '997', 'name' => 'Обврски за хартии од вредност и други вредносници', 'type' => Account::TYPE_EQUITY],
            ['code' => '998', 'name' => 'Обврски за права', 'type' => Account::TYPE_EQUITY],
            ['code' => '999', 'name' => 'Останата пасивна вонбилансна евиденција', 'type' => Account::TYPE_EQUITY],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * 4-digit analytical VAT sub-accounts (UKLO/Proagens standard, remapped to 2011).
     * Input VAT under 130, Output VAT under 230.
     */
    /**
     * Seed all 4-digit analytical accounts from the UKLO/Proagens data file.
     * Loads database/data/analytical_accounts.php (711 accounts across all classes).
     * VAT accounts (130/230 children) are handled separately by seedVatSubAccounts().
     */
    private function seedAnalyticalAccounts(int $companyId): void
    {
        $dataFile = database_path('data/analytical_accounts.php');
        if (! file_exists($dataFile)) {
            $this->log('  analytical_accounts.php not found, skipping 4-digit accounts', 'warn');

            return;
        }

        $rows = require $dataFile;
        $accounts = [];
        foreach ($rows as [$code, $parentCode, $type, $name]) {
            $accounts[] = [
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'parent_code' => $parentCode,
            ];
        }

        $this->createAccounts($companyId, $accounts);
    }

    private function seedVatSubAccounts(int $companyId): void
    {
        $accounts = [
            // Input VAT (under 130 — Данок на додадена вредност)
            ['code' => '1300', 'name' => 'Претходен данок по влезни фактури по стапка од 18%', 'type' => Account::TYPE_ASSET, 'parent_code' => '130'],
            ['code' => '1301', 'name' => 'Претходен данок со право на одбивка по стапка од 5%', 'type' => Account::TYPE_ASSET, 'parent_code' => '130'],
            ['code' => '1302', 'name' => 'Претходен данок за промет извршен од странски субјект', 'type' => Account::TYPE_ASSET, 'parent_code' => '130'],
            ['code' => '1303', 'name' => 'Исправка на претходен данок поради пренамена на добро', 'type' => Account::TYPE_ASSET, 'parent_code' => '130'],
            ['code' => '1304', 'name' => 'Побарување на претходен данок за пресметковниот период', 'type' => Account::TYPE_ASSET, 'parent_code' => '130'],
            ['code' => '1305', 'name' => 'Побарување на претходен данок за даночниот период', 'type' => Account::TYPE_ASSET, 'parent_code' => '130'],
            ['code' => '1306', 'name' => 'Претходен данок по стапка од 10% (угостителство)', 'type' => Account::TYPE_ASSET, 'parent_code' => '130'],
            ['code' => '1309', 'name' => 'Друг претходен данок', 'type' => Account::TYPE_ASSET, 'parent_code' => '130'],

            // Output VAT (under 230 — Обврски за данокот на додадена вредност)
            ['code' => '2300', 'name' => 'Обврски за пресметан даночен долг по стапка од 18%', 'type' => Account::TYPE_LIABILITY, 'parent_code' => '230'],
            ['code' => '2301', 'name' => 'Обврски за пресметан даночен долг по стапка од 5%', 'type' => Account::TYPE_LIABILITY, 'parent_code' => '230'],
            ['code' => '2302', 'name' => 'Даночен долг за промет извршен од странски субјект', 'type' => Account::TYPE_LIABILITY, 'parent_code' => '230'],
            ['code' => '2306', 'name' => 'Обврски за пресметан даночен долг по стапка од 10%', 'type' => Account::TYPE_LIABILITY, 'parent_code' => '230'],
        ];

        $this->createAccounts($companyId, $accounts);
    }

    /**
     * Helper method to create accounts (idempotent)
     */
    private function createAccounts(int $companyId, array $accounts): void
    {
        foreach ($accounts as $accountData) {
            $existingAccount = Account::where('company_id', $companyId)
                ->where('code', $accountData['code'])
                ->first();

            if ($existingAccount) {
                // Fix name + parent on existing system_defined accounts (old placeholders)
                if ($existingAccount->system_defined) {
                    $updates = [];
                    if ($existingAccount->name !== $accountData['name']) {
                        $updates['name'] = $accountData['name'];
                    }
                    if (! empty($accountData['parent_code']) && ! $existingAccount->parent_id) {
                        $parent = Account::where('company_id', $companyId)
                            ->where('code', $accountData['parent_code'])
                            ->first();
                        if ($parent) {
                            $updates['parent_id'] = $parent->id;
                        }
                    }
                    if (! empty($updates)) {
                        $existingAccount->update($updates);
                    }
                }
            } else {
                $parentId = null;
                if (! empty($accountData['parent_code'])) {
                    $parent = Account::where('company_id', $companyId)
                        ->where('code', $accountData['parent_code'])
                        ->first();
                    $parentId = $parent?->id;
                }

                Account::create([
                    'company_id' => $companyId,
                    'code' => $accountData['code'],
                    'name' => $accountData['name'],
                    'description' => null,
                    'type' => $accountData['type'],
                    'parent_id' => $parentId,
                    'is_active' => true,
                    'system_defined' => true,
                ]);
            }
        }
    }

    // cleanupOldPlaceholderAccounts() removed — all 4-digit codes are now
    // legitimate analytical accounts per UKLO/Proagens standard
}

// CLAUDE-CHECKPOINT
