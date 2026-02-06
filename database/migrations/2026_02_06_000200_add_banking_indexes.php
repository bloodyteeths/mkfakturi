<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * P0-00: Migration Patch + Index Verification
 *
 * This migration adds missing columns and indexes to banking tables
 * for Phase 0 of the Facturino roadmap. It is designed to be idempotent
 * and can run multiple times safely on Railway deployments.
 *
 * Columns added:
 * - bank_transactions.fingerprint: SHA256 hash for deduplication
 * - bank_transactions.external_transaction_id: Bank's external ID
 *
 * Indexes added:
 * - Unique constraint on (company_id, fingerprint)
 * - Unique constraint on (company_id, external_transaction_id)
 * - Performance indexes for common queries
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only proceed if bank_transactions table exists
        if (! Schema::hasTable('bank_transactions')) {
            Log::info('P0-00: bank_transactions table does not exist, skipping migration');

            return;
        }

        // Add fingerprint column if missing
        if (! Schema::hasColumn('bank_transactions', 'fingerprint')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->string('fingerprint', 64)
                    ->nullable()
                    ->after('raw_data')
                    ->comment('SHA256 hash for transaction deduplication');
            });
            Log::info('P0-00: Added fingerprint column to bank_transactions');
        }

        // Add external_transaction_id column if missing
        if (! Schema::hasColumn('bank_transactions', 'external_transaction_id')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->string('external_transaction_id', 100)
                    ->nullable()
                    ->after('fingerprint')
                    ->comment('Bank-provided external transaction ID');
            });
            Log::info('P0-00: Added external_transaction_id column to bank_transactions');
        }

        // Add unique constraint on (company_id, fingerprint)
        $this->safeAddUnique('bank_transactions', ['company_id', 'fingerprint'], 'bank_tx_fingerprint_unique');

        // Add unique constraint on (company_id, external_transaction_id)
        $this->safeAddUnique('bank_transactions', ['company_id', 'external_transaction_id'], 'bank_tx_external_id_unique');

        // Add performance index on (company_id, type, status) if 'type' column exists
        if (Schema::hasColumn('bank_transactions', 'transaction_type') &&
            Schema::hasColumn('bank_transactions', 'processing_status')) {
            $this->safeAddIndex('bank_transactions', ['company_id', 'transaction_type', 'processing_status'], 'bank_tx_company_type_status_idx');
        }

        // Verify bank_accounts has company_id index (existing migration has composite with is_active)
        // Add standalone company_id index for cases where we query without is_active filter
        if (Schema::hasTable('bank_accounts')) {
            $this->safeAddIndex('bank_accounts', ['company_id'], 'bank_accounts_company_id_idx');
        }

        Log::info('P0-00: Banking indexes migration completed successfully');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bank_transactions')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                // Drop unique constraints
                $this->safeDropIndex($table, 'bank_tx_fingerprint_unique');
                $this->safeDropIndex($table, 'bank_tx_external_id_unique');
                $this->safeDropIndex($table, 'bank_tx_company_type_status_idx');
            });

            // Drop columns
            if (Schema::hasColumn('bank_transactions', 'fingerprint')) {
                Schema::table('bank_transactions', function (Blueprint $table) {
                    $table->dropColumn('fingerprint');
                });
            }

            if (Schema::hasColumn('bank_transactions', 'external_transaction_id')) {
                Schema::table('bank_transactions', function (Blueprint $table) {
                    $table->dropColumn('external_transaction_id');
                });
            }
        }

        if (Schema::hasTable('bank_accounts')) {
            Schema::table('bank_accounts', function (Blueprint $table) {
                $this->safeDropIndex($table, 'bank_accounts_company_id_idx');
            });
        }
    }

    /**
     * Safely add index - catches duplicate index errors across MySQL/PostgreSQL.
     *
     * NOTE: We intentionally avoid Doctrine SchemaManager to prevent requiring
     * doctrine/dbal as a dependency. This try/catch approach is simpler and
     * works reliably on both databases.
     */
    private function safeAddIndex(string $tableName, array $columns, string $indexName): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            Log::info("P0-00: Index {$indexName} already exists, skipping");

            return;
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
            Log::info("P0-00: Added index {$indexName}");
        } catch (\Illuminate\Database\QueryException $e) {
            // MySQL: 1061 "Duplicate key name"
            // PostgreSQL: 42P07 "relation already exists"
            if (! str_contains($e->getMessage(), 'Duplicate') &&
                ! str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
            Log::info("P0-00: Index {$indexName} already exists (caught), skipping");
        }
    }

    /**
     * Safely add unique constraint - catches duplicate constraint errors.
     */
    private function safeAddUnique(string $tableName, array $columns, string $indexName): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            Log::info("P0-00: Unique constraint {$indexName} already exists, skipping");

            return;
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                $table->unique($columns, $indexName);
            });
            Log::info("P0-00: Added unique constraint {$indexName}");
        } catch (\Illuminate\Database\QueryException $e) {
            if (! str_contains($e->getMessage(), 'Duplicate') &&
                ! str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
            Log::info("P0-00: Unique constraint {$indexName} already exists (caught), skipping");
        }
    }

    /**
     * Safely drop index - catches non-existent index errors.
     */
    private function safeDropIndex(Blueprint $table, string $indexName): void
    {
        try {
            $table->dropIndex($indexName);
        } catch (\Exception $e) {
            // Index doesn't exist, that's fine
            Log::info("P0-00: Index {$indexName} doesn't exist, skipping drop");
        }
    }

    /**
     * Check if a given index already exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        // SQLite doesn't have information_schema
        if ($driver === 'sqlite') {
            return false;
        }

        $database = $connection->getDatabaseName();
        $tableName = $connection->getTablePrefix().$table;

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $tableName)
            ->where('index_name', $indexName)
            ->exists();
    }
};

// CLAUDE-CHECKPOINT
