<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3: Daily Closing & Period Lock (P3-1)
 *
 * Creates the period_locks table for locking entire date ranges.
 * Used by accountants to prevent edits to months/quarters after export.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('period_locks')) {
            return;
        }

        Schema::create('period_locks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id'); // companies.id is unsigned int
            $table->date('period_start')->comment('Start date of locked period');
            $table->date('period_end')->comment('End date of locked period (inclusive)');
            $table->unsignedInteger('locked_by')->nullable()->comment('User who locked the period');
            $table->timestamp('locked_at')->useCurrent()->comment('When the period was locked');
            $table->text('notes')->nullable()->comment('Optional notes (e.g., exported to Pantheon)');
            $table->timestamps();

            // Foreign keys
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('locked_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes for common queries
            $table->index(['company_id', 'period_start', 'period_end'], 'period_locks_company_range');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('period_locks');
    }
};
// CLAUDE-CHECKPOINT
