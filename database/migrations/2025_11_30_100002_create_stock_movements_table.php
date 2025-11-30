<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stock movements table for tracking inventory changes (Phase 2: Stock Module)
     * Records every stock in/out with source document reference.
     * Used for weighted average cost calculation and stock history.
     */
    public function up(): void
    {
        if (Schema::hasTable('stock_movements')) {
            return;
        }

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedInteger('item_id');

            // Source document (polymorphic: invoice_item, bill_item, manual_adjustment, etc.)
            $table->string('source_type', 100); // 'invoice_item', 'bill_item', 'adjustment', 'initial', 'transfer'
            $table->unsignedBigInteger('source_id')->nullable();

            // Movement details
            $table->decimal('quantity', 15, 4); // Positive for IN, negative for OUT
            $table->unsignedBigInteger('unit_cost')->nullable(); // Cost per unit in cents (for IN movements)
            $table->unsignedBigInteger('total_cost')->nullable(); // Total cost of this movement
            $table->date('movement_date');
            $table->text('notes')->nullable();

            // Running balance after this movement (for quick lookups)
            $table->decimal('balance_quantity', 15, 4)->default(0);
            $table->unsignedBigInteger('balance_value')->default(0); // Total value at weighted avg cost

            // Metadata
            $table->json('meta')->nullable(); // Additional data (reference numbers, etc.)
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->onDelete('restrict');

            $table->foreign('item_id')
                ->references('id')
                ->on('items')
                ->onDelete('restrict');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes for common queries
            $table->index('company_id');
            $table->index('warehouse_id');
            $table->index('item_id');
            $table->index('movement_date');
            $table->index('source_type');
            $table->index(['item_id', 'warehouse_id', 'movement_date'], 'stock_item_warehouse_date');
            $table->index(['company_id', 'movement_date'], 'stock_company_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
// CLAUDE-CHECKPOINT
