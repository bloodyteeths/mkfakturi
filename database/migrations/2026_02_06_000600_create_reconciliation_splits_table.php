<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P0-14: Create reconciliation_splits table for partial payments and multi-invoice settlement.
 *
 * Tracks how a single bank transaction (via reconciliation) is allocated across
 * multiple invoices (split payment) or how partial amounts are applied.
 *
 * Idempotent: uses Schema::hasTable() check for safe Railway re-deploys.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('reconciliation_splits')) {
            return;
        }

        Schema::create('reconciliation_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_id')
                ->constrained()
                ->onDelete('restrict');
            $table->foreignId('invoice_id')
                ->constrained()
                ->onDelete('restrict');
            $table->decimal('allocated_amount', 15, 2);
            $table->foreignId('payment_id')
                ->nullable()
                ->constrained()
                ->onDelete('restrict');
            $table->timestamps();

            $table->index(['reconciliation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliation_splits');
    }
};

// CLAUDE-CHECKPOINT
