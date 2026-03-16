<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add proforma_invoice_number_format setting to all companies that lack it.
     */
    public function up(): void
    {
        $companyIds = DB::table('companies')->pluck('id');

        foreach ($companyIds as $companyId) {
            $exists = DB::table('company_settings')
                ->where('company_id', $companyId)
                ->where('option', 'proforma_invoice_number_format')
                ->exists();

            if (! $exists) {
                DB::table('company_settings')->insert([
                    'company_id' => $companyId,
                    'option' => 'proforma_invoice_number_format',
                    'value' => '{{SERIES:PRO}}{{DELIMITER:-}}{{SEQUENCE:6}}',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('company_settings')
            ->where('option', 'proforma_invoice_number_format')
            ->delete();
    }
};
