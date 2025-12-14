<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make migration idempotent - only create if table doesn't exist
        if (! Schema::hasTable('usage_tracking')) {
            Schema::create('usage_tracking', function (Blueprint $table) {
                $table->engine = 'InnoDB';

                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('feature', 100); // e.g., 'expenses_per_month', 'custom_fields'
                $table->unsignedInteger('count')->default(0); // Usage counter
                $table->string('period', 20); // 'YYYY-MM' for monthly features, 'total' for cumulative
                $table->timestamps();

                // Foreign key constraint
                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('cascade');

                // Unique constraint: one record per company/feature/period
                $table->unique(['company_id', 'feature', 'period'], 'usage_tracking_unique');

                // Index for faster lookups
                $table->index(['company_id', 'period'], 'usage_tracking_company_period');
            });

            // Ensure charset is utf8mb4 to match other tables (MySQL only)
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE `usage_tracking` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_tracking');
    }
};
// CLAUDE-CHECKPOINT
