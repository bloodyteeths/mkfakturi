<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === COMPOSITE INDEXES for performance ===
        // Use try/catch for idempotency — if index exists, skip silently

        $indexes = [
            ['invoices', ['company_id', 'status'], 'invoices_company_id_status_index'],
            ['invoices', ['company_id', 'invoice_date'], 'invoices_company_id_invoice_date_index'],
            ['bills', ['company_id', 'status'], 'bills_company_id_status_index'],
            ['bills', ['company_id', 'due_date'], 'bills_company_id_due_date_index'],
            ['payments', ['company_id', 'payment_date'], 'payments_company_id_payment_date_index'],
            ['bank_transactions', ['company_id', 'transaction_date'], 'bank_transactions_company_id_transaction_date_index'],
        ];

        foreach ($indexes as [$tableName, $columns, $indexName]) {
            if (Schema::hasTable($tableName)) {
                try {
                    Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                        $table->index($columns, $indexName);
                    });
                } catch (\Exception $e) {
                    // Index already exists — skip
                }
            }
        }

        // Composite unique for bill_number (data may have duplicates)
        if (Schema::hasTable('bills')) {
            try {
                Schema::table('bills', function (Blueprint $table) {
                    $table->unique(['bill_number', 'company_id'], 'bills_bill_number_company_id_unique');
                });
            } catch (\Exception $e) {
                \Log::warning('Could not add unique constraint on bills.bill_number: ' . $e->getMessage());
            }
        }

        // === FOREIGN KEYS for critical financial tables ===
        // SQLite does not enforce FKs added via ALTER TABLE — these only apply to MySQL

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $fks = [
                ['payment_batches', 'bank_account_id', 'bank_accounts', 'id'],
                ['interest_calculations', 'customer_id', 'customers', 'id'],
                ['interest_calculations', 'invoice_id', 'invoices', 'id'],
                ['client_documents', 'linked_bill_id', 'bills', 'id'],
                ['client_documents', 'linked_expense_id', 'expenses', 'id'],
                ['client_documents', 'linked_invoice_id', 'invoices', 'id'],
            ];

            foreach ($fks as [$tableName, $column, $refTable, $refColumn]) {
                if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, $column) && Schema::hasTable($refTable)) {
                    try {
                        Schema::table($tableName, function (Blueprint $table) use ($column, $refTable, $refColumn, $tableName) {
                            $table->foreign($column, "{$tableName}_{$column}_foreign")
                                ->references($refColumn)
                                ->on($refTable)
                                ->onDelete('restrict');
                        });
                    } catch (\Exception $e) {
                        \Log::warning("Could not add FK {$tableName}.{$column}: " . $e->getMessage());
                    }
                }
            }
        }
    }

    // CLAUDE-CHECKPOINT

    public function down(): void
    {
        // Remove indexes
        $indexes = [
            ['invoices', 'invoices_company_id_status_index'],
            ['invoices', 'invoices_company_id_invoice_date_index'],
            ['bills', 'bills_company_id_status_index'],
            ['bills', 'bills_company_id_due_date_index'],
            ['payments', 'payments_company_id_payment_date_index'],
            ['bank_transactions', 'bank_transactions_company_id_transaction_date_index'],
        ];

        foreach ($indexes as [$table, $indexName]) {
            if (Schema::hasTable($table)) {
                try {
                    Schema::table($table, fn(Blueprint $t) => $t->dropIndex($indexName));
                } catch (\Exception $e) {}
            }
        }

        // Remove unique constraint
        if (Schema::hasTable('bills')) {
            try {
                Schema::table('bills', fn(Blueprint $t) => $t->dropUnique('bills_bill_number_company_id_unique'));
            } catch (\Exception $e) {}
        }

        // Remove FKs (MySQL only)
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $fks = [
                ['payment_batches', 'payment_batches_bank_account_id_foreign'],
                ['interest_calculations', 'interest_calculations_customer_id_foreign'],
                ['interest_calculations', 'interest_calculations_invoice_id_foreign'],
                ['client_documents', 'client_documents_linked_bill_id_foreign'],
                ['client_documents', 'client_documents_linked_expense_id_foreign'],
                ['client_documents', 'client_documents_linked_invoice_id_foreign'],
            ];

            foreach ($fks as [$table, $fkName]) {
                if (Schema::hasTable($table)) {
                    try {
                        Schema::table($table, fn(Blueprint $t) => $t->dropForeign($fkName));
                    } catch (\Exception $e) {}
                }
            }
        }
    }
};
