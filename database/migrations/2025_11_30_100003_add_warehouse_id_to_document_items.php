<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add warehouse_id to invoice_items and bill_items for multi-warehouse support.
     * When stock tracking is enabled, each line item can specify which warehouse
     * the stock comes from (sales) or goes to (purchases).
     */
    public function up(): void
    {
        // Add warehouse_id to invoice_items (if not already exists)
        if (! Schema::hasColumn('invoice_items', 'warehouse_id')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('item_id');

                $table->foreign('warehouse_id')
                    ->references('id')
                    ->on('warehouses')
                    ->onDelete('set null');

                $table->index('warehouse_id');
            });
        }

        // Add warehouse_id to bill_items (if not already exists)
        if (! Schema::hasColumn('bill_items', 'warehouse_id')) {
            Schema::table('bill_items', function (Blueprint $table) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('item_id');

                $table->foreign('warehouse_id')
                    ->references('id')
                    ->on('warehouses')
                    ->onDelete('set null');

                $table->index('warehouse_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropIndex(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });

        Schema::table('bill_items', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropIndex(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
};
// CLAUDE-CHECKPOINT
