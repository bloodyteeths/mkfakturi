<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. cost_centers table
        if (! Schema::hasTable('cost_centers')) {
            Schema::create('cost_centers', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedInteger('company_id');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('name', 150);
                $table->string('code', 20)->nullable();
                $table->string('color', 7)->default('#6366f1');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('restrict');

                $table->foreign('parent_id')
                    ->references('id')
                    ->on('cost_centers')
                    ->onDelete('set null');

                $table->index('company_id', 'idx_cc_company');
                $table->unique(['company_id', 'code'], 'uq_cc_company_code');
            });
        }

        // 2. cost_center_rules table
        if (! Schema::hasTable('cost_center_rules')) {
            Schema::create('cost_center_rules', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedInteger('company_id');
                $table->unsignedBigInteger('cost_center_id');
                $table->enum('match_type', ['vendor', 'account', 'description', 'item']);
                $table->string('match_value', 255);
                $table->integer('priority')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('restrict');

                $table->foreign('cost_center_id')
                    ->references('id')
                    ->on('cost_centers')
                    ->onDelete('cascade');

                $table->index(['company_id', 'is_active', 'priority'], 'idx_ccr_company_active_prio');
            });
        }

        // 3. Add cost_center_id to ifrs_ledgers
        if (Schema::hasTable('ifrs_ledgers') && ! Schema::hasColumn('ifrs_ledgers', 'cost_center_id')) {
            Schema::table('ifrs_ledgers', function (Blueprint $table) {
                $table->unsignedBigInteger('cost_center_id')->nullable();
                $table->index('cost_center_id', 'idx_ledger_cost_center');
            });
        }

        // 4. Add cost_center_id to invoices
        if (Schema::hasTable('invoices') && ! Schema::hasColumn('invoices', 'cost_center_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('cost_center_id')->nullable();
            });
        }

        // 5. Add cost_center_id to bills
        if (Schema::hasTable('bills') && ! Schema::hasColumn('bills', 'cost_center_id')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->unsignedBigInteger('cost_center_id')->nullable();
            });
        }

        // 6. Add cost_center_id to expenses
        if (Schema::hasTable('expenses') && ! Schema::hasColumn('expenses', 'cost_center_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->unsignedBigInteger('cost_center_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Remove cost_center_id from documents
        if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'cost_center_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropColumn('cost_center_id');
            });
        }

        if (Schema::hasTable('bills') && Schema::hasColumn('bills', 'cost_center_id')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->dropColumn('cost_center_id');
            });
        }

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'cost_center_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('cost_center_id');
            });
        }

        if (Schema::hasTable('ifrs_ledgers') && Schema::hasColumn('ifrs_ledgers', 'cost_center_id')) {
            Schema::table('ifrs_ledgers', function (Blueprint $table) {
                $table->dropIndex('idx_ledger_cost_center');
                $table->dropColumn('cost_center_id');
            });
        }

        Schema::dropIfExists('cost_center_rules');
        Schema::dropIfExists('cost_centers');
    }
};

// CLAUDE-CHECKPOINT
