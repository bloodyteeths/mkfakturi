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
        // Bank transactions table - for imported bank transactions from PSD2/API
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_account_id');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
            $table->unsignedInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            // Transaction identifiers
            $table->string('external_reference')->nullable(); // Bank's external transaction ID
            $table->string('transaction_reference')->nullable(); // Bank's internal transaction reference
            $table->string('transaction_id')->nullable(); // PSD2 transaction ID

            // Financial details
            $table->decimal('amount', 15, 2); // Transaction amount (positive for credit, negative for debit)
            $table->string('currency', 3)->default('MKD'); // ISO currency code
            $table->string('transaction_type')->nullable(); // credit, debit, transfer
            $table->string('booking_status')->default('booked'); // booked, pending, info

            // Transaction details
            $table->datetime('transaction_date'); // When transaction occurred
            $table->datetime('booking_date')->nullable(); // When transaction was booked
            $table->datetime('value_date')->nullable(); // Value date for interest calculation

            // Description and references
            $table->text('description')->nullable(); // Transaction description
            $table->text('remittance_info')->nullable(); // Remittance information/reference
            $table->string('payment_reference')->nullable(); // Payment reference number
            $table->string('end_to_end_id')->nullable(); // End-to-end identifier

            // Counterparty information
            $table->string('debtor_name')->nullable(); // Name of debtor
            $table->string('debtor_iban')->nullable(); // Debtor IBAN
            $table->string('debtor_account')->nullable(); // Debtor account number
            $table->string('creditor_name')->nullable(); // Name of creditor
            $table->string('creditor_iban')->nullable(); // Creditor IBAN
            $table->string('creditor_account')->nullable(); // Creditor account number

            // Bank codes
            $table->string('debtor_bic')->nullable(); // Debtor bank BIC
            $table->string('creditor_bic')->nullable(); // Creditor bank BIC

            // Matching information (for F-13 Matcher)
            $table->unsignedInteger('matched_invoice_id')->nullable();
            $table->foreign('matched_invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->unsignedBigInteger('matched_payment_id')->nullable();
            $table->foreign('matched_payment_id')->references('id')->on('payments')->onDelete('set null');
            $table->datetime('matched_at')->nullable();
            $table->decimal('match_confidence', 5, 2)->nullable(); // Match confidence percentage

            // Processing status
            $table->string('processing_status')->default('unprocessed'); // unprocessed, processed, failed, ignored
            $table->text('processing_notes')->nullable();
            $table->datetime('processed_at')->nullable();

            // Metadata
            $table->string('source')->default('psd2'); // psd2, csv_import, manual
            $table->json('raw_data')->nullable(); // Store original API response for debugging
            $table->boolean('is_duplicate')->default(false);
            $table->unsignedBigInteger('duplicate_of')->nullable(); // Reference to original transaction

            $table->timestamps();

            // Indexes for performance
            $table->index(['bank_account_id', 'transaction_date']);
            $table->index(['company_id', 'transaction_date']);
            $table->index(['external_reference', 'bank_account_id']);
            $table->index('transaction_reference');
            $table->index(['amount', 'transaction_date']);
            $table->index('processing_status');
            $table->index(['matched_invoice_id', 'matched_at']);
            $table->index('booking_status');
            $table->index('is_duplicate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
