<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('work_centers')) {
            return;
        }

        Schema::create('work_centers', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedInteger('company_id');
            $table->string('name', 100);
            $table->string('code', 30)->nullable();
            $table->text('description')->nullable();

            // Capacity & costing
            $table->decimal('capacity_hours_per_day', 6, 2)->default(8.00);
            $table->unsignedBigInteger('hourly_rate')->default(0); // cents
            $table->unsignedBigInteger('overhead_rate')->default(0); // cents per hour

            // OEE baseline targets (percentage, stored as decimal 0-100)
            $table->decimal('target_availability', 5, 2)->default(90.00);
            $table->decimal('target_performance', 5, 2)->default(85.00);
            $table->decimal('target_quality', 5, 2)->default(95.00);

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'is_active'], 'idx_wc_company_active');
            $table->unique(['company_id', 'code'], 'uq_wc_company_code');
        });

        // Add work_center_id to production_orders
        if (! Schema::hasColumn('production_orders', 'work_center_id')) {
            Schema::table('production_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('work_center_id')->nullable()->after('output_warehouse_id');
                $table->foreign('work_center_id')->references('id')->on('work_centers')->onDelete('set null');
            });
        }

        // Add work_center_id to production_order_labor for per-center tracking
        if (! Schema::hasColumn('production_order_labor', 'work_center_id')) {
            Schema::table('production_order_labor', function (Blueprint $table) {
                $table->unsignedBigInteger('work_center_id')->nullable()->after('production_order_id');
                $table->foreign('work_center_id')->references('id')->on('work_centers')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('production_order_labor', 'work_center_id')) {
            Schema::table('production_order_labor', function (Blueprint $table) {
                $table->dropForeign(['work_center_id']);
                $table->dropColumn('work_center_id');
            });
        }

        if (Schema::hasColumn('production_orders', 'work_center_id')) {
            Schema::table('production_orders', function (Blueprint $table) {
                $table->dropForeign(['work_center_id']);
                $table->dropColumn('work_center_id');
            });
        }

        Schema::dropIfExists('work_centers');
    }
};

// CLAUDE-CHECKPOINT
