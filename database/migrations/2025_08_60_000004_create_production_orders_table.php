<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('production_orders')) {
            return;
        }

        Schema::create('production_orders', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('currency_id')->nullable();
            $table->unsignedBigInteger('bom_id')->nullable();
            $table->string('order_number', 50);
            $table->date('order_date');
            $table->date('expected_completion_date')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Status workflow: draft → in_progress → completed | cancelled
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])->default('draft');

            // Output
            $table->unsignedInteger('output_item_id');
            $table->decimal('planned_quantity', 15, 4);
            $table->decimal('actual_quantity', 15, 4)->default(0);
            $table->unsignedBigInteger('output_warehouse_id')->nullable();

            // Cost summary (in cents)
            $table->unsignedBigInteger('total_material_cost')->default(0);
            $table->unsignedBigInteger('total_labor_cost')->default(0);
            $table->unsignedBigInteger('total_overhead_cost')->default(0);
            $table->unsignedBigInteger('total_wastage_cost')->default(0);
            $table->unsignedBigInteger('total_production_cost')->default(0);
            $table->unsignedBigInteger('cost_per_unit')->default(0);

            // Variance (actual - normative, can be negative = favorable)
            $table->bigInteger('material_variance')->default(0);
            $table->bigInteger('labor_variance')->default(0);
            $table->bigInteger('total_variance')->default(0);

            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('ifrs_transaction_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
            $table->foreign('bom_id')->references('id')->on('boms')->onDelete('set null');
            $table->foreign('output_item_id')->references('id')->on('items')->onDelete('restrict');
            $table->foreign('output_warehouse_id')->references('id')->on('warehouses')->onDelete('set null');

            $table->index(['company_id', 'status'], 'idx_po_company_status');
            $table->index('order_date', 'idx_po_order_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};

// CLAUDE-CHECKPOINT
