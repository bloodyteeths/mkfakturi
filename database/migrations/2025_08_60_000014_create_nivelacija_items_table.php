<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix: nivelacija_items table was not created by migration 000013
 * because the hasTable('nivelacii') guard returned early before
 * creating the second table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nivelacija_items')) {
            return;
        }

        // Clean up orphaned nivelacii created before items table existed
        \Illuminate\Support\Facades\DB::table('nivelacii')->delete();

        Schema::create('nivelacija_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nivelacija_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->decimal('quantity_on_hand', 12, 4)->default(0);
            $table->bigInteger('old_retail_price')->default(0);
            $table->bigInteger('new_retail_price')->default(0);
            $table->bigInteger('old_wholesale_price')->nullable();
            $table->bigInteger('new_wholesale_price')->nullable();
            $table->decimal('old_markup_percent', 8, 2)->nullable();
            $table->decimal('new_markup_percent', 8, 2)->nullable();
            $table->bigInteger('price_difference')->default(0);
            $table->bigInteger('total_difference')->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->foreign('nivelacija_id')->references('id')->on('nivelacii')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');

            $table->index(['nivelacija_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nivelacija_items');
    }
};
