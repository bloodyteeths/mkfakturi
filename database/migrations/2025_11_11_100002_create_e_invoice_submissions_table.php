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
        if (Schema::hasTable('e_invoice_submissions')) {
            return;
        }

        Schema::create('e_invoice_submissions', function (Blueprint $table) {
            $table->id();

            // Foreign key to e_invoices
            $table->unsignedBigInteger('e_invoice_id');
            $table->foreign('e_invoice_id')
                  ->references('id')
                  ->on('e_invoices')
                  ->onDelete('restrict');

            // Company scope (multi-tenant)
            $table->unsignedInteger('company_id');
            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->onDelete('restrict');

            // Submission tracking
            $table->timestamp('submitted_at')->nullable(); // When submission was initiated

            $table->unsignedInteger('submitted_by')->nullable(); // User who submitted
            $table->foreign('submitted_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            // Portal response information
            $table->string('portal_url')->nullable(); // Government portal URL
            $table->string('upload_id')->nullable(); // Portal upload/transaction ID
            $table->string('receipt_number')->nullable(); // Portal receipt/confirmation number

            // Submission status
            $table->enum('status', [
                'pending',   // Submission in progress
                'accepted',  // Portal accepted the submission
                'rejected',  // Portal rejected the submission
                'error'      // Submission error occurred
            ])->default('pending');

            // Response and error handling
            $table->json('response_data')->nullable(); // Full portal response (for debugging)
            $table->text('error_message')->nullable(); // Human-readable error message

            // Retry mechanism
            $table->integer('retry_count')->default(0); // Number of retry attempts
            $table->timestamp('next_retry_at')->nullable(); // When to retry next

            // Idempotency
            $table->string('idempotency_key', 255)->unique()->nullable(); // Prevent duplicate submissions

            $table->timestamps();

            // Indexes for performance
            $table->index('company_id');
            $table->index('e_invoice_id');
            $table->index('status');
            $table->index('idempotency_key');
            $table->index(['e_invoice_id', 'status']);
            $table->index(['company_id', 'status']);
            $table->index(['status', 'next_retry_at']); // For retry queue processing
            $table->index(['submitted_at', 'status']);

        }) . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_invoice_submissions');
    }
};
// CLAUDE-CHECKPOINT
