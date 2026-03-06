<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create IFRS Entity records for ALL companies that don't have one.
     * IFRS entity ID must match company ID for the accounting system.
     * Also ensures ifrs_enabled=YES setting exists for every company.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ifrs_entities') || ! Schema::hasTable('companies')) {
            return;
        }

        $allCompanyIds = DB::table('companies')->pluck('id');
        $existingEntityIds = DB::table('ifrs_entities')->pluck('id');
        $missing = $allCompanyIds->diff($existingEntityIds);

        foreach ($missing as $companyId) {
            $company = DB::table('companies')->find($companyId);
            if (! $company) {
                continue;
            }

            DB::table('ifrs_entities')->insert([
                'id' => $companyId,
                'name' => $company->name . ' (System)',
                'currency_id' => null,
                'parent_id' => null,
                'multi_currency' => 0,
                'mid_year_balances' => 0,
                'year_start' => 1,
                'locale' => 'en_GB',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Ensure ifrs_enabled=YES for all companies
        if (Schema::hasTable('company_settings')) {
            foreach ($allCompanyIds as $companyId) {
                $exists = DB::table('company_settings')
                    ->where('company_id', $companyId)
                    ->where('option', 'ifrs_enabled')
                    ->exists();

                if (! $exists) {
                    DB::table('company_settings')->insert([
                        'option' => 'ifrs_enabled',
                        'value' => 'YES',
                        'company_id' => $companyId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Data migration - don't remove entities
    }
};
