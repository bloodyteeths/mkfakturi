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
        if (Schema::hasTable('proforma_invoice_items')) {
            return;
        }

        Schema::create('proforma_invoice_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('discount_type', ['fixed', 'percentage']);
            $table->unsignedBigInteger('price');
            $table->decimal('quantity', 15, 2);
            $table->decimal('discount', 15, 2)->nullable();
            $table->unsignedBigInteger('discount_val');
            $table->unsignedBigInteger('tax');
            $table->unsignedBigInteger('total');
            $table->string('unit_name')->nullable();

            // Currency conversion fields
            $table->decimal('exchange_rate', 19, 6)->nullable();
            $table->unsignedBigInteger('base_price')->nullable();
            $table->unsignedBigInteger('base_discount_val')->nullable();
            $table->unsignedBigInteger('base_tax')->nullable();
            $table->unsignedBigInteger('base_total')->nullable();

            // Relationships
            $table->integer('proforma_invoice_id')->unsigned();
            $table->foreign('proforma_invoice_id')->references('id')->on('proforma_invoices')->onDelete('restrict');

            $table->integer('item_id')->unsigned()->nullable();
            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict');

            $table->integer('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');

            $table->timestamps();

            // Indexes
            $table->index('proforma_invoice_id');
            $table->index('item_id');
            $table->index('company_id');
        }) . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_invoice_items');
    }
};

// CLAUDE-CHECKPOINT
