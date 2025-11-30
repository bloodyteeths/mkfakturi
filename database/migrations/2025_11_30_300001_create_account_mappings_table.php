<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4: Accountant Chart of Accounts & Export (P4-1)
 *
 * Creates the account_mappings table for linking entities to accounts.
 * Used to determine which accounts to use for journal entries when processing
 * invoices, payments, expenses, etc.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('account_mappings')) {
            return;
        }

        Schema::create('account_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id'); // companies.id is unsigned int
            $table->string('entity_type', 50)->comment('customer|supplier|expense_category|tax_type|payment_method');
            $table->unsignedBigInteger('entity_id')->nullable()->comment('ID of the mapped entity, null for default');
            $table->unsignedBigInteger('debit_account_id')->nullable()->comment('Account to debit');
            $table->unsignedBigInteger('credit_account_id')->nullable()->comment('Account to credit');
            $table->string('transaction_type', 50)->nullable()->comment('Optional: invoice|payment|expense|adjustment');
            $table->json('meta')->nullable()->comment('Additional mapping metadata');
            $table->timestamps();

            // Foreign keys
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('debit_account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('set null');

            $table->foreign('credit_account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('set null');

            // Unique constraint: one mapping per entity type + entity ID per company
            $table->unique(
                ['company_id', 'entity_type', 'entity_id', 'transaction_type'],
                'account_mappings_unique'
            );

            // Indexes for common queries
            $table->index(['company_id', 'entity_type'], 'account_mappings_company_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_mappings');
    }
};
// CLAUDE-CHECKPOINT
