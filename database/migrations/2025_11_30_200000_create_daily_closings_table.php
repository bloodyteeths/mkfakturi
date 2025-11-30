<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3: Daily Closing & Period Lock (P3-1)
 *
 * Creates the daily_closings table for tracking closed days.
 * Allows accountants to lock specific dates to prevent retroactive edits.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_closings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->date('date')->comment('The date being closed');
            $table->string('type', 50)->default('all')->comment('Type: all, cash, invoices, etc.');
            $table->unsignedBigInteger('closed_by')->nullable()->comment('User who closed the day');
            $table->timestamp('closed_at')->useCurrent()->comment('When the day was closed');
            $table->text('notes')->nullable()->comment('Optional notes about the closing');
            $table->timestamps();

            // Foreign keys
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('closed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Unique constraint: only one closing per date per type per company
            $table->unique(['company_id', 'date', 'type'], 'daily_closings_unique');

            // Indexes for common queries
            $table->index(['company_id', 'date'], 'daily_closings_company_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_closings');
    }
};
// CLAUDE-CHECKPOINT
