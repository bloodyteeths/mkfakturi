<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Missing Database Indexes (PERF-01-03)
 *
 * This migration adds indexes to frequently queried columns to improve
 * query performance, especially for:
 * - Invoice filtering by date and status
 * - Customer lookups
 * - Payment tracking
 * - Multi-tenant company scoping
 *
 * @package Database\Migrations
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Invoices: Composite index for date range queries with company scope
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['company_id', 'invoice_date'], 'idx_invoices_company_date');
            $table->index(['company_id', 'due_date', 'status'], 'idx_invoices_company_due_status');
            $table->index(['status'], 'idx_invoices_status');
        });

        // Some legacy databases may not have the user_id column on invoices.
        // Only create the index when the column exists to avoid SQL errors.
        if (Schema::hasColumn('invoices', 'user_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index(['user_id'], 'idx_invoices_user');
            });
        }

        // Customers: Index for company-scoped lookups and name searches
        Schema::table('customers', function (Blueprint $table) {
            $table->index(['company_id', 'name'], 'idx_customers_company_name');
            $table->index(['company_id', 'email'], 'idx_customers_company_email');
        });

        // Payments: Index for date-based queries and invoice lookups
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['company_id', 'payment_date'], 'idx_payments_company_date');
            $table->index(['invoice_id'], 'idx_payments_invoice');
            $table->index(['payment_method_id'], 'idx_payments_method');
        });

        // Estimates: Similar to invoices, date and status filtering
        Schema::table('estimates', function (Blueprint $table) {
            $table->index(['company_id', 'estimate_date'], 'idx_estimates_company_date');
            $table->index(['company_id', 'status'], 'idx_estimates_company_status');
        });

        // Expenses: Index for category and date-based reporting
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->index(['company_id', 'expense_date'], 'idx_expenses_company_date');
                $table->index(['expense_category_id'], 'idx_expenses_category');
            });
        }

        // Users: Index for company membership lookups
        Schema::table('users', function (Blueprint $table) {
            $table->index(['company_id'], 'idx_users_company');
            $table->index(['email'], 'idx_users_email');
        });

        // Items: Index for company-scoped product lookups
        Schema::table('items', function (Blueprint $table) {
            $table->index(['company_id', 'name'], 'idx_items_company_name');
        });

        // Partner-specific indexes (if tables exist)
        if (Schema::hasTable('partners')) {
            Schema::table('partners', function (Blueprint $table) {
                $table->index(['user_id'], 'idx_partners_user');
                $table->index(['kyc_status'], 'idx_partners_kyc_status');
            });
        }

        if (Schema::hasTable('partner_commissions')) {
            Schema::table('partner_commissions', function (Blueprint $table) {
                $table->index(['partner_id', 'status'], 'idx_commissions_partner_status');
                $table->index(['company_id'], 'idx_commissions_company');
                $table->index(['payout_date'], 'idx_commissions_payout_date');
            });
        }

        // Banking transactions indexes (if table exists)
        if (Schema::hasTable('bank_transactions')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->index(['company_id', 'transaction_date'], 'idx_bank_txn_company_date');
                $table->index(['bank_account_id'], 'idx_bank_txn_account');
                $table->index(['reconciliation_status'], 'idx_bank_txn_reconciliation');
            });
        }

        // IFRS ledger indexes (if tables exist from eloquent-ifrs)
        if (Schema::hasTable('ifrs_accounts')) {
            Schema::table('ifrs_accounts', function (Blueprint $table) {
                $table->index(['entity_id', 'account_type'], 'idx_ifrs_accounts_entity_type');
            });
        }

        if (Schema::hasTable('ifrs_transactions')) {
            Schema::table('ifrs_transactions', function (Blueprint $table) {
                $table->index(['entity_id', 'transaction_date'], 'idx_ifrs_txn_entity_date');
                $table->index(['account_id'], 'idx_ifrs_txn_account');
            });
        }

        // CLAUDE-CHECKPOINT
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order

        // Invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_company_date');
            $table->dropIndex('idx_invoices_company_due_status');
            $table->dropIndex('idx_invoices_status');
            $table->dropIndex('idx_invoices_user');
        });

        // Customers
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_company_name');
            $table->dropIndex('idx_customers_company_email');
        });

        // Payments
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_company_date');
            $table->dropIndex('idx_payments_invoice');
            $table->dropIndex('idx_payments_method');
        });

        // Estimates
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropIndex('idx_estimates_company_date');
            $table->dropIndex('idx_estimates_company_status');
        });

        // Expenses
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropIndex('idx_expenses_company_date');
                $table->dropIndex('idx_expenses_category');
            });
        }

        // Users
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_company');
            $table->dropIndex('idx_users_email');
        });

        // Items
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('idx_items_company_name');
        });

        // Partner tables
        if (Schema::hasTable('partners')) {
            Schema::table('partners', function (Blueprint $table) {
                $table->dropIndex('idx_partners_user');
                $table->dropIndex('idx_partners_kyc_status');
            });
        }

        if (Schema::hasTable('partner_commissions')) {
            Schema::table('partner_commissions', function (Blueprint $table) {
                $table->dropIndex('idx_commissions_partner_status');
                $table->dropIndex('idx_commissions_company');
                $table->dropIndex('idx_commissions_payout_date');
            });
        }

        // Banking
        if (Schema::hasTable('bank_transactions')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->dropIndex('idx_bank_txn_company_date');
                $table->dropIndex('idx_bank_txn_account');
                $table->dropIndex('idx_bank_txn_reconciliation');
            });
        }

        // IFRS
        if (Schema::hasTable('ifrs_accounts')) {
            Schema::table('ifrs_accounts', function (Blueprint $table) {
                $table->dropIndex('idx_ifrs_accounts_entity_type');
            });
        }

        if (Schema::hasTable('ifrs_transactions')) {
            Schema::table('ifrs_transactions', function (Blueprint $table) {
                $table->dropIndex('idx_ifrs_txn_entity_date');
                $table->dropIndex('idx_ifrs_txn_account');
            });
        }

        // CLAUDE-CHECKPOINT
    }
};
