<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for reconciliations table
 *
 * P0-05: Reconciliation Database Schema
 * This table stores the reconciliation records that link bank transactions
 * to invoices and payments, with matching status and confidence scores.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Idempotent check - skip if table already exists (Railway auto-deploys)
        if (Schema::hasTable('reconciliations')) {
            return;
        }

        Schema::create('reconciliations', function (Blueprint $table) {
            $table->id();

            // Company reference (unsigned int to match companies.id)
            $table->unsignedInteger('company_id');
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('restrict');

            // Bank transaction reference (unsigned bigint to match bank_transactions.id)
            $table->unsignedBigInteger('bank_transaction_id');
            $table->foreign('bank_transaction_id')
                ->references('id')
                ->on('bank_transactions')
                ->onDelete('restrict');

            // Invoice reference (unsigned int to match invoices.id, nullable)
            $table->unsignedInteger('invoice_id')->nullable();
            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->onDelete('restrict');

            // Payment reference (unsigned bigint to match payments.id, nullable)
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->onDelete('restrict');

            // Reconciliation status
            $table->enum('status', ['pending', 'matched', 'partial', 'manual', 'ignored']);

            // How the match was made
            $table->enum('match_type', ['auto', 'manual', 'rule']);

            // Confidence score for automatic matches (0.00 to 100.00)
            $table->decimal('confidence', 5, 2)->nullable();

            // JSON details about the match (reasons, scores, etc.)
            $table->json('match_details')->nullable();

            // User who performed manual match
            $table->unsignedInteger('matched_by')->nullable();
            $table->foreign('matched_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            // When the match was made
            $table->timestamp('matched_at')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['bank_transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliations');
    }
};

// CLAUDE-CHECKPOINT
