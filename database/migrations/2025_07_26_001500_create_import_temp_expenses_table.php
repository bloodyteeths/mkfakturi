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
        Schema::create('import_temp_expenses', function (Blueprint $table) {
            $table->id();
            
            // Raw data fields (as imported)
            $table->json('raw_data'); // Original row data from import file
            $table->integer('row_number'); // Row number in source file
            
            // Mapped fields (matching expenses table structure)
            $table->date('expense_date')->nullable();
            $table->unsignedBigInteger('amount')->nullable(); // Amount in cents
            $table->text('notes')->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('vendor_tax_id')->nullable();
            
            // Category information
            $table->string('category_name')->nullable();
            $table->string('subcategory')->nullable();
            
            // Tax information
            $table->decimal('tax_rate', 5, 2)->nullable(); // VAT rate percentage
            $table->unsignedBigInteger('tax_amount')->nullable(); // Tax amount in cents
            $table->unsignedBigInteger('net_amount')->nullable(); // Amount without tax
            $table->boolean('tax_deductible')->default(true);
            
            // Currency handling
            $table->string('currency_code', 3)->nullable(); // EUR, MKD, USD, etc.
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->unsignedBigInteger('base_amount')->nullable(); // Amount in base currency
            
            // Payment method information
            $table->string('payment_method')->nullable(); // cash, bank_transfer, check, credit_card, etc.
            $table->string('payment_reference')->nullable(); // Transaction ID, check number, etc.
            
            // Customer/Project assignment (optional)
            $table->string('customer_name')->nullable(); // If expense is billable to customer
            $table->string('project_name')->nullable(); // Project assignment
            $table->boolean('billable')->default(false);
            
            // Attachment information
            $table->string('attachment_path')->nullable(); // Path to receipt/invoice attachment
            $table->string('attachment_name')->nullable(); // Original filename
            $table->string('attachment_type')->nullable(); // mime type
            
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
            $table->unsignedBigInteger('temp_customer_id')->nullable(); // Link to temp customer if billable
            $table->foreign('temp_customer_id')->references('id')->on('import_temp_customers')->onDelete('set null');
            $table->unsignedInteger('existing_expense_id')->nullable(); // Link to existing expense if duplicate
            $table->foreign('existing_expense_id')->references('id')->on('expenses')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['import_job_id', 'status']);
            $table->index(['import_job_id', 'row_number']);
            $table->index(['vendor_name', 'import_job_id']);
            $table->index(['expense_date', 'import_job_id']);
            $table->index(['category_name', 'import_job_id']);
            $table->index(['temp_customer_id']);
            $table->index('is_duplicate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_temp_expenses');
    }
};