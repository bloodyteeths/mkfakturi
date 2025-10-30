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
        Schema::create('import_temp_invoices', function (Blueprint $table) {
            $table->id();
            
            // Raw data fields (as imported)
            $table->json('raw_data'); // Original row data from import file
            $table->integer('row_number'); // Row number in source file
            
            // Mapped fields (matching invoices table structure)
            $table->string('invoice_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('invoice_status')->nullable(); // draft, sent, viewed, overdue
            $table->string('paid_status')->nullable(); // unpaid, partial, paid
            $table->text('notes')->nullable();
            
            // Customer identification fields
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_tax_id')->nullable();
            $table->string('customer_identifier')->nullable(); // Flexible customer matching field
            
            // Financial fields
            $table->string('tax_per_item')->nullable(); // YES/NO
            $table->string('discount_per_item')->nullable(); // YES/NO
            $table->string('discount_type')->nullable(); // fixed, percentage
            $table->decimal('discount', 15, 2)->nullable();
            $table->unsignedBigInteger('discount_val')->nullable();
            $table->unsignedBigInteger('sub_total')->nullable();
            $table->unsignedBigInteger('total')->nullable();
            $table->unsignedBigInteger('tax')->nullable();
            $table->unsignedBigInteger('due_amount')->nullable();
            
            // Currency handling
            $table->string('currency_code', 3)->nullable(); // EUR, MKD, USD, etc.
            $table->decimal('exchange_rate', 10, 4)->nullable();
            
            // Invoice items (stored as JSON for complex imports)
            $table->json('line_items')->nullable(); // Array of invoice items
            
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
            $table->unsignedBigInteger('temp_customer_id')->nullable(); // Link to temp customer
            $table->foreign('temp_customer_id')->references('id')->on('import_temp_customers')->onDelete('set null');
            $table->unsignedInteger('existing_invoice_id')->nullable(); // Link to existing invoice if duplicate
            $table->foreign('existing_invoice_id')->references('id')->on('invoices')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['import_job_id', 'status']);
            $table->index(['import_job_id', 'row_number']);
            $table->index(['invoice_number', 'import_job_id']);
            $table->index(['customer_email', 'import_job_id']);
            $table->index(['temp_customer_id']);
            $table->index('is_duplicate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_temp_invoices');
    }
};