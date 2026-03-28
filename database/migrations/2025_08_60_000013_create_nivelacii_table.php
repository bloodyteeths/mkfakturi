<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nivelacii')) {
            Schema::create('nivelacii', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('document_number', 30);
                $table->date('document_date');
                $table->string('type', 20)->default('price_change');
                $table->string('status', 20)->default('draft');
                $table->string('reason')->nullable();
                $table->unsignedBigInteger('source_bill_id')->nullable();
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->bigInteger('total_difference')->default(0);
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
                $table->foreign('source_bill_id')->references('id')->on('bills')->onDelete('set null');
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

                $table->index(['company_id', 'document_date']);
                $table->unique(['company_id', 'document_number']);
            });
        }

        if (! Schema::hasTable('nivelacija_items')) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('nivelacija_items');
        Schema::dropIfExists('nivelacii');
    }
};

// CLAUDE-CHECKPOINT
