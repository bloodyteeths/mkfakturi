<?php

namespace App\Observers;

use App\Models\Company;
use Database\Seeders\MacedonianChartOfAccountsSeeder;
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
     * Uses the official 3-digit codes from Regulation 174/2011.
     */
    protected function seedChartOfAccounts(Company $company): void
    {
        Log::info('CompanyObserver: Seeding chart of accounts for company', [
            'company_id' => $company->id,
            'company_name' => $company->name,
        ]);

        // Use the seeder to ensure consistent 3-digit Macedonian codes
        $seeder = new MacedonianChartOfAccountsSeeder();
        $seeder->seedForCompany($company->id);

        Log::info('CompanyObserver: Chart of accounts seeded for company', [
            'company_id' => $company->id,
        ]);
    }
}

// CLAUDE-CHECKPOINT
