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
        Schema::create('import_temp_payments', function (Blueprint $table) {
            $table->id();
            
            // Raw data fields (as imported)
            $table->json('raw_data'); // Original row data from import file
            $table->integer('row_number'); // Row number in source file
            
            // Mapped fields (matching payments table structure)
            $table->string('payment_number')->nullable();
            $table->date('payment_date')->nullable();
            $table->unsignedBigInteger('amount')->nullable(); // Amount in cents
            $table->text('notes')->nullable();
            $table->string('reference')->nullable(); // Bank reference, check number, etc.
            
            // Payment method information
            $table->string('payment_method')->nullable(); // cash, bank_transfer, check, etc.
            $table->string('payment_method_details')->nullable(); // Additional method details
            
            // Customer/Invoice identification
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_tax_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('invoice_reference')->nullable();
            
            // Currency handling
            $table->string('currency_code', 3)->nullable(); // EUR, MKD, USD, etc.
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->unsignedBigInteger('base_amount')->nullable(); // Amount in base currency
            
            // Bank/Financial details
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamp('bank_date')->nullable(); // Date from bank statement
            
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
            $table->unsignedBigInteger('temp_invoice_id')->nullable(); // Link to temp invoice
            $table->foreign('temp_invoice_id')->references('id')->on('import_temp_invoices')->onDelete('set null');
            $table->unsignedBigInteger('existing_payment_id')->nullable(); // Link to existing payment if duplicate
            $table->foreign('existing_payment_id')->references('id')->on('payments')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['import_job_id', 'status']);
            $table->index(['import_job_id', 'row_number']);
            $table->index(['payment_number', 'import_job_id']);
            $table->index(['customer_email', 'import_job_id']);
            $table->index(['invoice_number', 'import_job_id']);
            $table->index(['temp_customer_id']);
            $table->index(['temp_invoice_id']);
            $table->index('is_duplicate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_temp_payments');
    }
};