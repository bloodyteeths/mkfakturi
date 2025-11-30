<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds project_id foreign key to invoices, expenses, and payments tables.
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
        // Add project_id to invoices
        if (Schema::hasTable('invoices') && ! Schema::hasColumn('invoices', 'project_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('project_id')->nullable()->after('customer_id');
                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->onDelete('restrict');
                $table->index('project_id');
            });
        }

        // Add project_id to expenses
        if (Schema::hasTable('expenses') && ! Schema::hasColumn('expenses', 'project_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->unsignedBigInteger('project_id')->nullable()->after('customer_id');
                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->onDelete('restrict');
                $table->index('project_id');
            });
        }

        // Add project_id to payments
        if (Schema::hasTable('payments') && ! Schema::hasColumn('payments', 'project_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unsignedBigInteger('project_id')->nullable()->after('customer_id');
                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->onDelete('restrict');
                $table->index('project_id');
            });
        }

        // Add project_id to bills (if table exists - part of AP automation)
        if (Schema::hasTable('bills') && ! Schema::hasColumn('bills', 'project_id')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->unsignedBigInteger('project_id')->nullable()->after('supplier_id');
                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->onDelete('restrict');
                $table->index('project_id');
            });
        }

        // Add project_id to estimates
        if (Schema::hasTable('estimates') && ! Schema::hasColumn('estimates', 'project_id')) {
            Schema::table('estimates', function (Blueprint $table) {
                $table->unsignedBigInteger('project_id')->nullable()->after('customer_id');
                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->onDelete('restrict');
                $table->index('project_id');
            });
        }

        // Add project_id to proforma_invoices
        if (Schema::hasTable('proforma_invoices') && ! Schema::hasColumn('proforma_invoices', 'project_id')) {
            Schema::table('proforma_invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('project_id')->nullable()->after('customer_id');
                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->onDelete('restrict');
                $table->index('project_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove from invoices
        if (Schema::hasColumn('invoices', 'project_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropForeign(['project_id']);
                $table->dropIndex(['project_id']);
                $table->dropColumn('project_id');
            });
        }

        // Remove from expenses
        if (Schema::hasColumn('expenses', 'project_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropForeign(['project_id']);
                $table->dropIndex(['project_id']);
                $table->dropColumn('project_id');
            });
        }

        // Remove from payments
        if (Schema::hasColumn('payments', 'project_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['project_id']);
                $table->dropIndex(['project_id']);
                $table->dropColumn('project_id');
            });
        }

        // Remove from bills
        if (Schema::hasColumn('bills', 'project_id')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->dropForeign(['project_id']);
                $table->dropIndex(['project_id']);
                $table->dropColumn('project_id');
            });
        }

        // Remove from estimates
        if (Schema::hasColumn('estimates', 'project_id')) {
            Schema::table('estimates', function (Blueprint $table) {
                $table->dropForeign(['project_id']);
                $table->dropIndex(['project_id']);
                $table->dropColumn('project_id');
            });
        }

        // Remove from proforma_invoices
        if (Schema::hasColumn('proforma_invoices', 'project_id')) {
            Schema::table('proforma_invoices', function (Blueprint $table) {
                $table->dropForeign(['project_id']);
                $table->dropIndex(['project_id']);
                $table->dropColumn('project_id');
            });
        }
    }
};

// CLAUDE-CHECKPOINT
