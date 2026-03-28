<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columnsToAdd = [];

        if (! Schema::hasColumn('expenses', 'expense_number')) {
            $columnsToAdd[] = 'expense_number';
        }
        if (! Schema::hasColumn('expenses', 'vat_rate')) {
            $columnsToAdd[] = 'vat_rate';
        }
        if (! Schema::hasColumn('expenses', 'vat_amount')) {
            $columnsToAdd[] = 'vat_amount';
        }
        if (! Schema::hasColumn('expenses', 'tax_base')) {
            $columnsToAdd[] = 'tax_base';
        }
        if (! Schema::hasColumn('expenses', 'status')) {
            $columnsToAdd[] = 'status';
        }

        if (! empty($columnsToAdd)) {
            Schema::table('expenses', function (Blueprint $table) use ($columnsToAdd) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                if (in_array('expense_number', $columnsToAdd)) {
                    $table->string('expense_number', 50)->nullable()->after('id');
                }
                if (in_array('vat_rate', $columnsToAdd)) {
                    $table->decimal('vat_rate', 5, 2)->nullable()->default(18.00)->after('amount');
                }
                if (in_array('vat_amount', $columnsToAdd)) {
                    $table->bigInteger('vat_amount')->nullable()->default(0)->after('vat_rate');
                }
                if (in_array('tax_base', $columnsToAdd)) {
                    $table->bigInteger('tax_base')->nullable()->default(0)->after('vat_amount');
                }
                if (in_array('status', $columnsToAdd)) {
                    $table->string('status', 20)->default('draft')->after('tax_base');
                }
            });
        }

        // Add indexes only if columns exist and indexes don't
        try {
            if (Schema::hasColumn('expenses', 'status')) {
                Schema::table('expenses', function (Blueprint $table) {
                    $table->index('status', 'expenses_status_index');
                });
            }
        } catch (\Exception $e) {
            // Index already exists
        }

        try {
            if (Schema::hasColumn('expenses', 'expense_number')) {
                Schema::table('expenses', function (Blueprint $table) {
                    $table->index(['expense_number', 'company_id'], 'expenses_expense_number_company_id_index');
                });
            }
        } catch (\Exception $e) {
            // Index already exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_status_index');
            $table->dropIndex('expenses_expense_number_company_id_index');

            $table->dropColumn([
                'expense_number',
                'vat_rate',
                'vat_amount',
                'tax_base',
                'status',
            ]);
        });
    }
};
// CLAUDE-CHECKPOINT
