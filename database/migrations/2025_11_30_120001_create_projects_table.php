<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the projects table for project-based tracking of invoices, expenses, and payments.
 * Part of Phase 1.1 - Project Dimension feature for accountants.
 *
 * @see ACCOUNTANT_FEATURES_ROADMAP.md
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('projects')) {
            return;
        }

        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            // Company relationship (multi-tenant)
            $table->integer('company_id')->unsigned();
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('restrict');

            // Project identification
            $table->string('name', 255);
            $table->string('code', 50)->nullable(); // Short code for quick reference (e.g., "PROJ-001")
            $table->text('description')->nullable();

            // Customer relationship (optional - project can be for a specific customer)
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('restrict');

            // Status
            $table->enum('status', ['open', 'closed', 'on_hold'])->default('open');

            // Budget tracking (optional)
            $table->unsignedBigInteger('budget_amount')->nullable(); // In cents/smallest currency unit
            $table->unsignedInteger('currency_id')->nullable();
            $table->foreign('currency_id')
                ->references('id')
                ->on('currencies')
                ->onDelete('restrict');

            // Dates
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Creator
            $table->integer('creator_id')->unsigned()->nullable();
            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            // Notes
            $table->text('notes')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('company_id');
            $table->index('customer_id');
            $table->index('status');
            $table->index('code');
            $table->index(['company_id', 'status']);
            $table->unique(['company_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};

// CLAUDE-CHECKPOINT
