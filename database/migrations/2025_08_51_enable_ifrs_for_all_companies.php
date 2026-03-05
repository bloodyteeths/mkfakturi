<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enable IFRS accounting backbone for all existing companies.
     * New companies already get this via Company::setupDefaultSettings().
     */
    public function up(): void
    {
        if (! Schema::hasTable('company_settings')) {
            return;
        }

        // Get all company IDs that don't already have ifrs_enabled set
        $companyIds = DB::table('companies')->pluck('id');

        foreach ($companyIds as $companyId) {
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

    public function down(): void
    {
        // Don't remove - this is a data migration that enables a core feature
    }
};
