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
        Schema::create('import_temp_items', function (Blueprint $table) {
            $table->id();

            // Raw data fields (as imported)
            $table->json('raw_data'); // Original row data from import file
            $table->integer('row_number'); // Row number in source file

            // Mapped fields (matching items table structure)
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('unit')->nullable(); // piece, kg, hour, etc.
            $table->unsignedBigInteger('price')->nullable(); // Price in cents
            $table->string('sku')->nullable(); // Stock Keeping Unit
            $table->string('barcode')->nullable();

            // Inventory fields
            $table->decimal('quantity', 15, 2)->nullable(); // Available quantity
            $table->decimal('minimum_quantity', 15, 2)->nullable(); // Minimum stock level
            $table->boolean('track_quantity')->default(false);

            // Tax configuration
            $table->boolean('tax_per_item')->default(false);
            $table->json('tax_rates')->nullable(); // Tax rates applied to this item

            // Currency handling
            $table->string('currency_code', 3)->nullable(); // EUR, MKD, USD, etc.
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->unsignedBigInteger('base_price')->nullable(); // Price in base currency

            // Category/Classification
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();

            // Invoice line item context (when imported as part of invoice)
            $table->string('invoice_number')->nullable(); // Associated invoice
            $table->decimal('line_quantity', 15, 2)->nullable(); // Quantity in invoice line
            $table->unsignedBigInteger('line_total')->nullable(); // Line total in cents
            $table->decimal('discount_percent', 5, 2)->nullable(); // Line discount percentage
            $table->unsignedBigInteger('discount_amount')->nullable(); // Line discount amount

            // Processing fields
            $table->enum('status', ['pending', 'validated', 'mapped', 'failed', 'committed'])->default('pending');
            $table->json('validation_errors')->nullable(); // Validation error details
            $table->json('mapping_confidence')->nullable(); // Field mapping confidence scores
            $table->boolean('is_duplicate')->default(false);
            $table->string('duplicate_match_field')->nullable(); // Field used for duplicate detection
            $table->json('transformation_log')->nullable(); // Record of transformations applied

            // References
            $table->unsignedBigInteger('import_job_id');
            $table->foreign('import_job_id')->references('id')->on('import_jobs')->onDelete('cascade');
            $table->unsignedBigInteger('temp_invoice_id')->nullable(); // Link to temp invoice if item is part of invoice
            $table->foreign('temp_invoice_id')->references('id')->on('import_temp_invoices')->onDelete('set null');
            $table->unsignedInteger('existing_item_id')->nullable(); // Link to existing item if duplicate
            $table->foreign('existing_item_id')->references('id')->on('items')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index(['import_job_id', 'status']);
            $table->index(['import_job_id', 'row_number']);
            $table->index(['name', 'import_job_id']);
            $table->index(['sku', 'import_job_id']);
            $table->index(['temp_invoice_id']);
            $table->index('is_duplicate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_temp_items');
    }
};
