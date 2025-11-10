<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reconciliations table for tracking invoice-bank transaction matches
 * Stores confidence scores and approval status for suggested matches
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reconciliations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('bank_transaction_id');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('confidence_score', 5, 4)->default(0.0000); // 0.0000 to 1.0000
            $table->enum('status', ['pending', 'approved', 'rejected', 'auto_matched'])->default('pending');
            $table->unsignedBigInteger('reconciled_by')->nullable(); // User who approved/rejected
            $table->timestamp('reconciled_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('restrict');

            $table->foreign('bank_transaction_id')
                ->references('id')
                ->on('bank_transactions')
                ->onDelete('restrict');

            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->onDelete('restrict');

            $table->foreign('reconciled_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'confidence_score']);
            $table->index('bank_transaction_id');
            $table->index('invoice_id');
        }) . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
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
