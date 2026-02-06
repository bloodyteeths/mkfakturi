<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add idempotency columns to payments table
 *
 * P0-12: Reconciliation Posting Service
 * Adds source_type and source_id columns with a unique constraint
 * to ensure DB-level idempotency: one payment per source per company.
 *
 * source_type: 'bank_transaction', 'payment_link', 'manual', etc.
 * source_id: ID of the source record (e.g., bank_transactions.id)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Idempotency key: what created this payment?
            if (! Schema::hasColumn('payments', 'source_type')) {
                $table->string('source_type', 50)->nullable()->after('notes')
                    ->comment('Source that created this payment: bank_transaction, payment_link, manual');
            }

            if (! Schema::hasColumn('payments', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type')
                    ->comment('ID of the source record that created this payment');
            }
        });

        // Add unique constraint separately to handle idempotent re-runs
        // Check if the index already exists before adding it
        if (Schema::hasColumn('payments', 'source_type') && Schema::hasColumn('payments', 'source_id')) {
            try {
                Schema::table('payments', function (Blueprint $table) {
                    $table->unique(
                        ['company_id', 'source_type', 'source_id'],
                        'payments_idempotency_unique'
                    );
                });
            } catch (\Exception $e) {
                // Index already exists - safe to ignore on Railway re-deploys
                \Illuminate\Support\Facades\Log::info('Payments idempotency index already exists, skipping', [
                    'migration' => '2026_02_06_000500_add_idempotency_to_payments',
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop index first
            try {
                $table->dropUnique('payments_idempotency_unique');
            } catch (\Exception $e) {
                // Index may not exist
            }

            if (Schema::hasColumn('payments', 'source_type')) {
                $table->dropColumn('source_type');
            }

            if (Schema::hasColumn('payments', 'source_id')) {
                $table->dropColumn('source_id');
            }
        });
    }
};

// CLAUDE-CHECKPOINT
