<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create inventory_document_items table for line items within
 * inventory documents (приемница/издатница/преносница).
 *
 * Idempotent: uses Schema::hasTable() check for safe re-runs on Railway deployment.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_document_items')) {
            return;
        }

        Schema::create('inventory_document_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_document_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 15, 4);
            $table->unsignedBigInteger('unit_cost')->nullable();
            $table->unsignedBigInteger('total_cost')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('inventory_document_id')
                ->references('id')->on('inventory_documents')
                ->onDelete('cascade');

            $table->foreign('item_id')
                ->references('id')->on('items')
                ->onDelete('restrict');

            // Indexes
            $table->index('inventory_document_id');
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_document_items');
    }
};
