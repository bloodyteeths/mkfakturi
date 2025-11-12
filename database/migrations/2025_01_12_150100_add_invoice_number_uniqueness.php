<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds a composite unique constraint on (invoice_number, company_id)
     * to ensure invoice numbers are unique within each company, but allows different
     * companies to use the same invoice numbering schemes.
     *
     * CLAUDE-CHECKPOINT
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Add composite unique constraint on (invoice_number, company_id)
            // This ensures invoice numbers are unique per company
            // but allows different companies to have the same invoice numbers
            $table->unique(['invoice_number', 'company_id'], 'invoices_invoice_number_company_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique('invoices_invoice_number_company_id_unique');
        });
    }
};
// CLAUDE-CHECKPOINT
