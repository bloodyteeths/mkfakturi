<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Backfill vat_number from vat_id for existing companies.
     * This ensures compatibility between the legacy vat_id field (from InvoiceShelf)
     * and the new vat_number field (for Macedonian VAT returns).
     */
    public function up(): void
    {
        // Copy vat_id to vat_number for all companies where vat_number is NULL but vat_id is not
        DB::table('companies')
            ->whereNull('vat_number')
            ->whereNotNull('vat_id')
            ->update(['vat_number' => DB::raw('vat_id')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional: Clear vat_number if you need to roll back
        // DB::table('companies')->update(['vat_number' => null]);
    }
};
