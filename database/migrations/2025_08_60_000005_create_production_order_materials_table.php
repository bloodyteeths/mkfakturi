<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('production_order_materials')) {
            return;
        }

        Schema::create('production_order_materials', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->unsignedInteger('item_id');
            $table->unsignedBigInteger('warehouse_id')->nullable();

            // Planned (from BOM)
            $table->decimal('planned_quantity', 15, 4)->default(0);
            $table->unsignedBigInteger('planned_unit_cost')->default(0)->comment('WAC at order creation, cents');

            // Actual (entered during/after production)
            $table->decimal('actual_quantity', 15, 4)->default(0);
            $table->unsignedBigInteger('actual_unit_cost')->default(0)->comment('WAC at consumption, cents');
            $table->unsignedBigInteger('actual_total_cost')->default(0)->comment('Cents');

            // Wastage
            $table->decimal('wastage_quantity', 15, 4)->default(0);
            $table->unsignedBigInteger('wastage_cost')->default(0)->comment('Cents');

            // Variance
            $table->decimal('quantity_variance', 15, 4)->default(0);
            $table->bigInteger('cost_variance')->default(0);

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('stock_movement_id')->nullable();
            $table->timestamps();

            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');

            $table->index('production_order_id', 'idx_pom_production_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_materials');
    }
};

// CLAUDE-CHECKPOINT
