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
            // MySQL requires ALTER to modify enum
            DB::statement("ALTER TABLE `company_subscriptions` MODIFY COLUMN `provider` ENUM('paddle', 'cpay', 'stripe') NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('company_subscriptions')) {
            // Revert to original enum (only if no stripe records exist)
            DB::statement("ALTER TABLE `company_subscriptions` MODIFY COLUMN `provider` ENUM('paddle', 'cpay') NULL");
        }
    }
};
