<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds invoice_number and supplier_id to expenses table for duplicate detection.
 * Part of Phase 1.3 - Expense Duplicate Protection feature for accountants.
 *
 * Note: In the expense context:
 * - supplier_id refers to the supplier/vendor from the suppliers table
 * - invoice_number refers to the supplier's invoice reference number
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
        // Add supplier_id and invoice_number for duplicate detection
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                // Supplier reference for expenses (vendor/supplier who issued the invoice)
                if (! Schema::hasColumn('expenses', 'supplier_id')) {
                    $table->unsignedBigInteger('supplier_id')->nullable()->after('customer_id');

                    // Only add foreign key if suppliers table exists
                    if (Schema::hasTable('suppliers')) {
                        $table->foreign('supplier_id')
                            ->references('id')
                            ->on('suppliers')
                            ->onDelete('restrict');
                    }

                    $table->index('supplier_id');
                }

                // Supplier's invoice number for duplicate detection
                if (! Schema::hasColumn('expenses', 'invoice_number')) {
                    $table->string('invoice_number', 100)->nullable()->after('supplier_id');
                    $table->index('invoice_number');
                }

                // Composite index for duplicate detection queries
                if (! Schema::hasColumn('expenses', 'supplier_id') && ! Schema::hasColumn('expenses', 'invoice_number')) {
                    // Will be added after columns are created
                }
            });

            // Add composite index for efficient duplicate checking
            // Note: This runs after the column additions
            try {
                Schema::table('expenses', function (Blueprint $table) {
                    $table->index(['company_id', 'supplier_id', 'invoice_number'], 'expenses_duplicate_check_index');
                });
            } catch (\Exception $e) {
                // Index may already exist, ignore
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                // Drop index first
                try {
                    $table->dropIndex('expenses_duplicate_check_index');
                } catch (\Exception $e) {
                    // Index may not exist
                }

                if (Schema::hasColumn('expenses', 'supplier_id')) {
                    try {
                        $table->dropForeign(['supplier_id']);
                    } catch (\Exception $e) {
                        // FK may not exist
                    }
                    $table->dropIndex(['supplier_id']);
                    $table->dropColumn('supplier_id');
                }

                if (Schema::hasColumn('expenses', 'invoice_number')) {
                    $table->dropIndex(['invoice_number']);
                    $table->dropColumn('invoice_number');
                }
            });
        }
    }
};

// CLAUDE-CHECKPOINT
