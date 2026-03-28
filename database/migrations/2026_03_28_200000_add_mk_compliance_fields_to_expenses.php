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
        Schema::table('expenses', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            if (! Schema::hasColumn('expenses', 'expense_number')) {
                $table->string('expense_number', 50)->nullable()->after('id');
            }

            if (! Schema::hasColumn('expenses', 'vat_rate')) {
                $table->decimal('vat_rate', 5, 2)->nullable()->default(18.00)->after('amount');
            }

            if (! Schema::hasColumn('expenses', 'vat_amount')) {
                $table->bigInteger('vat_amount')->nullable()->default(0)->after('vat_rate');
            }

            if (! Schema::hasColumn('expenses', 'tax_base')) {
                $table->bigInteger('tax_base')->nullable()->default(0)->after('vat_amount');
            }

            if (! Schema::hasColumn('expenses', 'status')) {
                $table->string('status', 20)->default('draft')->after('tax_base');
            }
        });

        // Add indexes separately to avoid issues with hasColumn checks
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'status')) {
                $table->index('status', 'expenses_status_index');
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'expense_number')) {
                $table->index(['expense_number', 'company_id'], 'expenses_expense_number_company_id_index');
            }
        });
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
