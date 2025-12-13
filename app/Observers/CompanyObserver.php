<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

/**
 * Company Observer
 *
 * Seeds the Macedonian Chart of Accounts when a new company is created.
 * This ensures all companies have standard accounting codes for partner accounting features.
 */
class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     *
     * Seeds standard Macedonian chart of accounts for the new company.
     */
    public function created(Company $company): void
    {
        try {
            $this->seedChartOfAccounts($company);
        } catch (\Exception $e) {
            Log::error('CompanyObserver: Failed to seed chart of accounts', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - we don't want to block company creation
        }
    }

    /**
     * Seed standard Macedonian chart of accounts for a company.
     */
    protected function seedChartOfAccounts(Company $company): void
    {
        // Skip if company already has accounts
        if (Account::where('company_id', $company->id)->exists()) {
            return;
        }

        Log::info('CompanyObserver: Seeding chart of accounts for company', [
            'company_id' => $company->id,
            'company_name' => $company->name,
        ]);

        $accounts = $this->getStandardAccounts();

        foreach ($accounts as $accountData) {
            Account::create([
                'company_id' => $company->id,
                'code' => $accountData['code'],
                'name' => $accountData['name'],
                'type' => $accountData['type'],
                'description' => $accountData['description'] ?? null,
                'is_active' => true,
                'system_defined' => true,
            ]);
        }

        Log::info('CompanyObserver: Chart of accounts seeded', [
            'company_id' => $company->id,
            'accounts_created' => count($accounts),
        ]);
    }

    /**
     * Get standard Macedonian chart of accounts.
     */
    protected function getStandardAccounts(): array
    {
        return [
            // Assets (1xxx)
            ['code' => '1000', 'name' => 'Средства', 'type' => 'asset', 'description' => 'Парични средства и банки'],
            ['code' => '1010', 'name' => 'Каса', 'type' => 'asset', 'description' => 'Готовина во каса'],
            ['code' => '1020', 'name' => 'Жиро сметка', 'type' => 'asset', 'description' => 'Тековна сметка'],
            ['code' => '1030', 'name' => 'Девизна сметка', 'type' => 'asset', 'description' => 'Сметка во странска валута'],
            ['code' => '1200', 'name' => 'Основни средства', 'type' => 'asset', 'description' => 'Долгорочни средства'],
            ['code' => '1300', 'name' => 'Залихи', 'type' => 'asset', 'description' => 'Залихи на материјали и стоки'],

            // Receivables (22xx)
            ['code' => '2200', 'name' => 'Побарувања', 'type' => 'asset', 'description' => 'Побарувања од купувачи'],
            ['code' => '2201', 'name' => 'Побарувања од купувачи - домашни', 'type' => 'asset', 'description' => 'Домашни купувачи'],
            ['code' => '2202', 'name' => 'Побарувања од купувачи - странски', 'type' => 'asset', 'description' => 'Странски купувачи'],

            // Liabilities (4xxx)
            ['code' => '4000', 'name' => 'Обврски', 'type' => 'liability', 'description' => 'Краткорочни обврски'],
            ['code' => '4200', 'name' => 'Обврски кон добавувачи', 'type' => 'liability', 'description' => 'Обврски кон добавувачи'],
            ['code' => '4201', 'name' => 'Обврски кон добавувачи - домашни', 'type' => 'liability', 'description' => 'Домашни добавувачи'],
            ['code' => '4202', 'name' => 'Обврски кон добавувачи - странски', 'type' => 'liability', 'description' => 'Странски добавувачи'],
            ['code' => '4700', 'name' => 'ДДВ обврска', 'type' => 'liability', 'description' => 'Обврска за ДДВ'],

            // Equity (5xxx)
            ['code' => '5000', 'name' => 'Капитал', 'type' => 'equity', 'description' => 'Основен капитал'],
            ['code' => '5100', 'name' => 'Основен капитал', 'type' => 'equity', 'description' => 'Уплатен капитал'],
            ['code' => '5200', 'name' => 'Резерви', 'type' => 'equity', 'description' => 'Законски резерви'],
            ['code' => '5300', 'name' => 'Акумулирана добивка', 'type' => 'equity', 'description' => 'Задржана добивка'],

            // Revenue (6xxx)
            ['code' => '6000', 'name' => 'Приходи', 'type' => 'revenue', 'description' => 'Приходи од продажба'],
            ['code' => '6010', 'name' => 'Приходи од продажба на производи', 'type' => 'revenue', 'description' => 'Продажба на производи'],
            ['code' => '6020', 'name' => 'Приходи од продажба на услуги', 'type' => 'revenue', 'description' => 'Продажба на услуги'],
            ['code' => '6030', 'name' => 'Приходи од продажба на стоки', 'type' => 'revenue', 'description' => 'Продажба на стоки'],
            ['code' => '6800', 'name' => 'Финансиски приходи', 'type' => 'revenue', 'description' => 'Камати и курсни разлики'],

            // Expenses (7xxx)
            ['code' => '7000', 'name' => 'Расходи', 'type' => 'expense', 'description' => 'Оперативни расходи'],
            ['code' => '7010', 'name' => 'Трошоци за материјали', 'type' => 'expense', 'description' => 'Набавка на материјали'],
            ['code' => '7020', 'name' => 'Трошоци за услуги', 'type' => 'expense', 'description' => 'Надворешни услуги'],
            ['code' => '7030', 'name' => 'Плати и надоместоци', 'type' => 'expense', 'description' => 'Плати на вработени'],
            ['code' => '7040', 'name' => 'Амортизација', 'type' => 'expense', 'description' => 'Амортизација на основни средства'],
            ['code' => '7050', 'name' => 'Канцелариски материјал', 'type' => 'expense', 'description' => 'Канцелариски трошоци'],
            ['code' => '7060', 'name' => 'Телекомуникации', 'type' => 'expense', 'description' => 'Телефон и интернет'],
            ['code' => '7070', 'name' => 'Транспортни трошоци', 'type' => 'expense', 'description' => 'Транспорт и горива'],
            ['code' => '7080', 'name' => 'Наем', 'type' => 'expense', 'description' => 'Наем на простор'],
            ['code' => '7090', 'name' => 'Комунални трошоци', 'type' => 'expense', 'description' => 'Струја, вода, греење'],
            ['code' => '7100', 'name' => 'Осигурување', 'type' => 'expense', 'description' => 'Премии за осигурување'],
            ['code' => '7800', 'name' => 'Финансиски расходи', 'type' => 'expense', 'description' => 'Камати и провизии'],
        ];
    }
}

// CLAUDE-CHECKPOINT
