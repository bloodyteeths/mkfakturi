<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wac_audit_discrepancies')) {
            return;
        }

        Schema::create('wac_audit_discrepancies', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('audit_run_id');
            $table->unsignedBigInteger('movement_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->integer('chain_position');

            $table->decimal('stored_balance_quantity', 15, 4);
            $table->bigInteger('stored_balance_value');
            $table->decimal('expected_balance_quantity', 15, 4);
            $table->bigInteger('expected_balance_value');

            $table->decimal('quantity_drift', 15, 4)->default(0);
            $table->bigInteger('value_drift')->default(0);

            $table->string('error_category', 50)->nullable();
            $table->text('ai_explanation')->nullable();
            $table->boolean('is_root_cause')->default(false);

            $table->timestamps();

            $table->foreign('audit_run_id')->references('id')->on('wac_audit_runs')->onDelete('cascade');
            $table->foreign('movement_id')->references('id')->on('stock_movements')->onDelete('restrict');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->index(['audit_run_id', 'item_id', 'warehouse_id'], 'idx_wac_disc_run_item_wh');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wac_audit_discrepancies');
    }
};
