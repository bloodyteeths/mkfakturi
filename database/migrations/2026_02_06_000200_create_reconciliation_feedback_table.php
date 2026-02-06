<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for reconciliation_feedback table
 *
 * P0-05: Reconciliation Database Schema
 * This table stores user feedback on reconciliation matches to improve
 * the matching algorithm over time through machine learning.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Idempotent check - skip if table already exists (Railway auto-deploys)
        if (Schema::hasTable('reconciliation_feedback')) {
            return;
        }

        Schema::create('reconciliation_feedback', function (Blueprint $table) {
            $table->id();

            // Reference to the reconciliation record
            $table->unsignedBigInteger('reconciliation_id');
            $table->foreign('reconciliation_id')
                ->references('id')
                ->on('reconciliations')
                ->onDelete('cascade');

            // User feedback on the match
            $table->enum('feedback', ['correct', 'wrong', 'partial']);

            // If the match was wrong, what was the correct invoice?
            $table->unsignedInteger('correct_invoice_id')->nullable();
            $table->foreign('correct_invoice_id')
                ->references('id')
                ->on('invoices')
                ->onDelete('restrict');

            // User who provided the feedback
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliation_feedback');
    }
};

// CLAUDE-CHECKPOINT
