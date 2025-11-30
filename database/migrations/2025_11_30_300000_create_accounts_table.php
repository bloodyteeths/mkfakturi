<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4: Accountant Chart of Accounts & Export (P4-1)
 *
 * Creates the accounts table for the Chart of Accounts.
 * Used by accountants to define and manage their account structure.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('code', 20)->comment('Account code (e.g., 1100, 2100)');
            $table->string('name', 255)->comment('Account name');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('Parent account for hierarchy');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense'])
                ->comment('Account type classification');
            $table->boolean('is_active')->default(true)->comment('Whether account is active');
            $table->boolean('system_defined')->default(false)->comment('System accounts cannot be deleted');
            $table->json('meta')->nullable()->comment('Additional metadata (e.g., for export mappings)');
            $table->text('description')->nullable()->comment('Optional account description');
            $table->timestamps();

            // Foreign keys
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('parent_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('set null');

            // Unique constraint: one code per company
            $table->unique(['company_id', 'code'], 'accounts_company_code_unique');

            // Indexes for common queries
            $table->index(['company_id', 'type'], 'accounts_company_type');
            $table->index(['company_id', 'is_active'], 'accounts_company_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
// CLAUDE-CHECKPOINT
