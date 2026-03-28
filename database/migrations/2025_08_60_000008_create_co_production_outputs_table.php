<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('co_production_outputs')) {
            return;
        }

        Schema::create('co_production_outputs', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->unsignedInteger('item_id');
            $table->boolean('is_primary')->default(false);
            $table->decimal('quantity', 15, 4);
            $table->unsignedBigInteger('warehouse_id')->nullable();

            // Cost allocation
            $table->enum('allocation_method', ['weight', 'market_value', 'fixed_ratio', 'manual'])->default('weight');
            $table->decimal('allocation_percent', 8, 4)->default(0);
            $table->unsignedBigInteger('allocated_cost')->default(0)->comment('Cents');
            $table->unsignedBigInteger('cost_per_unit')->default(0)->comment('Cents');

            $table->unsignedBigInteger('stock_movement_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');

            $table->index('production_order_id', 'idx_cpo_production_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('co_production_outputs');
    }
};

// CLAUDE-CHECKPOINT
