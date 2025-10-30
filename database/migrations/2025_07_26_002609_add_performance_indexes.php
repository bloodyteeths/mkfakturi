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
        // Add indexes for frequently queried columns
        
        // Company settings - frequently queried by company_id and option
        Schema::table('company_settings', function (Blueprint $table) {
            $table->index(['company_id', 'option']);
        });

        // Users - frequently queried by email and company relationships
        Schema::table('users', function (Blueprint $table) {
            $table->index('email');
            $table->index('created_at');
        });

        // User company relationships
        if (Schema::hasTable('user_company')) {
            Schema::table('user_company', function (Blueprint $table) {
                $table->index(['user_id', 'company_id']);
            });
        }

        // Customers - frequently queried by company_id and email
        Schema::table('customers', function (Blueprint $table) {
            $table->index(['company_id', 'email']);
            $table->index('created_at');
        });

        // Invoices - frequently queried by company_id, customer_id, and status
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['company_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('invoice_date');
            $table->index('due_date');
        });

        // Payments - frequently queried by company_id and customer_id
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['company_id', 'customer_id']);
            $table->index('payment_date');
        });

        // Items - frequently queried by company_id
        Schema::table('items', function (Blueprint $table) {
            $table->index('company_id');
            $table->index('name');
        });

        // Expenses - frequently queried by company_id and expense_date
        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['company_id', 'expense_date']);
            $table->index('expense_category_id');
        });

        // Estimates - frequently queried by company_id and status
        Schema::table('estimates', function (Blueprint $table) {
            $table->index(['company_id', 'status']);
            $table->index('customer_id');
        });

        // Custom field values - frequently joined with other tables
        Schema::table('custom_field_values', function (Blueprint $table) {
            $table->index(['custom_field_id', 'custom_field_valuable_type', 'custom_field_valuable_id'], 'cfv_field_valuable_idx');
        });

        // Notes - frequently queried by company_id and type
        Schema::table('notes', function (Blueprint $table) {
            if (Schema::hasColumn('notes', 'company_id')) {
                $table->index('company_id');
            }
            if (Schema::hasColumn('notes', 'type')) {
                $table->index('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        Schema::table('notes', function (Blueprint $table) {
            if (Schema::hasColumn('notes', 'company_id')) {
                $table->dropIndex(['company_id']);
            }
            if (Schema::hasColumn('notes', 'type')) {
                $table->dropIndex(['type']);
            }
        });

        Schema::table('custom_field_values', function (Blueprint $table) {
            $table->dropIndex('cfv_field_valuable_idx');
        });

        Schema::table('estimates', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'status']);
            $table->dropIndex(['customer_id']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'expense_date']);
            $table->dropIndex(['expense_category_id']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['name']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'customer_id']);
            $table->dropIndex(['payment_date']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'status']);
            $table->dropIndex(['customer_id', 'status']);
            $table->dropIndex(['invoice_date']);
            $table->dropIndex(['due_date']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'email']);
            $table->dropIndex(['created_at']);
        });

        if (Schema::hasTable('user_company')) {
            Schema::table('user_company', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'company_id']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'option']);
        });
    }
};
