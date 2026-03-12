<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\CompanyInboundAlias;
use Database\Seeders\MacedonianChartOfAccountsSeeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        }

        try {
            $this->createInboundAlias($company);
        } catch (\Exception $e) {
            Log::error('CompanyObserver: Failed to create inbound alias', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
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

    /**
     * Create a unique inbound email alias for supplier invoice forwarding.
     * Format: bills-{random8chars} → bills-a7f3x9kp@in.facturino.mk
     */
    protected function createInboundAlias(Company $company): void
    {
        if (CompanyInboundAlias::where('company_id', $company->id)->exists()) {
            return;
        }

        $alias = 'bills-'.Str::lower(Str::random(8));

        // Ensure uniqueness (collision extremely unlikely but guard anyway)
        while (CompanyInboundAlias::where('alias', $alias)->exists()) {
            $alias = 'bills-'.Str::lower(Str::random(8));
        }

        CompanyInboundAlias::create([
            'company_id' => $company->id,
            'alias' => $alias,
        ]);

        Log::info('CompanyObserver: Inbound alias created', [
            'company_id' => $company->id,
            'alias' => $alias,
        ]);
    }
}
// CLAUDE-CHECKPOINT

