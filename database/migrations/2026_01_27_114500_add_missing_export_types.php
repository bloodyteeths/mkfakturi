<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds missing export types to the enum column
     */
    public function up(): void
    {
        // MySQL requires ALTER to change ENUM values
        // Add items, estimates, proforma_invoices, recurring_invoices to the type enum
        DB::statement("ALTER TABLE export_jobs MODIFY COLUMN type ENUM('invoices', 'bills', 'customers', 'suppliers', 'transactions', 'expenses', 'payments', 'items', 'estimates', 'proforma_invoices', 'recurring_invoices') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE export_jobs MODIFY COLUMN type ENUM('invoices', 'bills', 'customers', 'suppliers', 'transactions', 'expenses', 'payments') NOT NULL");
    }
};
// CLAUDE-CHECKPOINT
