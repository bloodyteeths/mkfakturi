<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add 'stripe' to the provider enum in company_subscriptions table
     */
    public function up(): void
    {
        if (Schema::hasTable('company_subscriptions')) {
            // SQLite doesn't support MODIFY COLUMN or ENUM - skip for SQLite (used in tests)
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `company_subscriptions` MODIFY COLUMN `provider` ENUM('paddle', 'cpay', 'stripe') NULL");
            }
            // SQLite already treats text columns flexibly, no change needed
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('company_subscriptions')) {
            // SQLite doesn't support MODIFY COLUMN or ENUM
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `company_subscriptions` MODIFY COLUMN `provider` ENUM('paddle', 'cpay') NULL");
            }
        }
    }
};

// LLM-CHECKPOINT
