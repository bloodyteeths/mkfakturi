<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Inventory tracking fields
            $table->string('sku', 255)->nullable()->after('name');
            $table->string('barcode', 255)->nullable()->after('sku');
            $table->integer('quantity')->default(0)->after('barcode');
            $table->integer('minimum_quantity')->nullable()->after('quantity');
            $table->boolean('track_quantity')->default(false)->after('minimum_quantity');
            $table->string('category', 255)->nullable()->after('track_quantity');

            // Add indexes
            $table->index('barcode', 'items_barcode_index');
            $table->index('category', 'items_category_index');
        });

        // Add unique index on company_id and sku where sku is not null
        Schema::table('items', function (Blueprint $table) {
            $table->unique(['company_id', 'sku'], 'items_company_sku_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('items_barcode_index');
            $table->dropIndex('items_category_index');
            $table->dropUnique('items_company_sku_unique');

            // Drop columns
            $table->dropColumn([
                'sku',
                'barcode',
                'quantity',
                'minimum_quantity',
                'track_quantity',
                'category',
            ]);
        });
    }
};
// CLAUDE-CHECKPOINT
