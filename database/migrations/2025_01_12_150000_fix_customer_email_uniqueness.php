<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration fixes the customer email uniqueness constraint to be per-company
     * instead of global. This allows different companies to have customers with the
     * same email addresses, which is essential for multi-tenant functionality.
     *
     * CLAUDE-CHECKPOINT
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // First, check if the unique constraint exists before trying to drop it
            // The constraint was already dropped in migration 2021_12_09_065718_drop_unique_email_on_customers_table.php
            // So we skip dropping it again

            // Add composite unique constraint on (email, company_id)
            // This allows the same email to exist in different companies
            // but prevents duplicate emails within the same company
            // We use rawIndex to handle nullable emails properly
            $table->unique(['email', 'company_id'], 'customers_email_company_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique('customers_email_company_id_unique');
        });
    }
};
// CLAUDE-CHECKPOINT
