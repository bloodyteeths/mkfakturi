<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add critical performance indexes for invoice list page.
 *
 * These indexes address the slow loading of /admin/invoices:
 * - customer_id: Used in every invoice list query for filtering
 * - sequence_number: Used for default sorting (ORDER BY sequence_number DESC)
 * - Composite index for common query patterns
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add missing indexes to invoices table
        Schema::table('invoices', function (Blueprint $table) {
            // Index on customer_id - critical for filtering by customer
            if (!$this->indexExists('invoices', 'idx_invoices_customer')) {
                $table->index(['customer_id'], 'idx_invoices_customer');
            }

            // Index on sequence_number - used for default sorting
            if (!$this->indexExists('invoices', 'idx_invoices_sequence')) {
                $table->index(['sequence_number'], 'idx_invoices_sequence');
            }

            // Composite index for common list query pattern
            if (!$this->indexExists('invoices', 'idx_invoices_company_customer')) {
                $table->index(['company_id', 'customer_id'], 'idx_invoices_company_customer');
            }

            // Composite index for sorted pagination
            if (!$this->indexExists('invoices', 'idx_invoices_company_sequence')) {
                $table->index(['company_id', 'sequence_number'], 'idx_invoices_company_sequence');
            }
        });

        // Add index for customer name search (used in whereSearch scope)
        Schema::table('customers', function (Blueprint $table) {
            if (!$this->indexExists('customers', 'idx_customers_name')) {
                $table->index(['name'], 'idx_customers_name');
            }

            if (!$this->indexExists('customers', 'idx_customers_contact_name')) {
                if (Schema::hasColumn('customers', 'contact_name')) {
                    $table->index(['contact_name'], 'idx_customers_contact_name');
                }
            }

            if (!$this->indexExists('customers', 'idx_customers_company_name_col')) {
                if (Schema::hasColumn('customers', 'company_name')) {
                    $table->index(['company_name'], 'idx_customers_company_name_col');
                }
            }
        });

        // Add index for invoice items lookup
        Schema::table('invoice_items', function (Blueprint $table) {
            if (!$this->indexExists('invoice_items', 'idx_invoice_items_invoice')) {
                $table->index(['invoice_id'], 'idx_invoice_items_invoice');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_customer');
            $table->dropIndex('idx_invoices_sequence');
            $table->dropIndex('idx_invoices_company_customer');
            $table->dropIndex('idx_invoices_company_sequence');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_name');
            if (Schema::hasColumn('customers', 'contact_name')) {
                $table->dropIndex('idx_customers_contact_name');
            }
            if (Schema::hasColumn('customers', 'company_name')) {
                $table->dropIndex('idx_customers_company_name_col');
            }
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex('idx_invoice_items_invoice');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            return false; // SQLite will handle duplicates
        }

        $database = $connection->getDatabaseName();
        $tableName = $connection->getTablePrefix() . $table;

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $tableName)
            ->where('index_name', $indexName)
            ->exists();
    }
};
