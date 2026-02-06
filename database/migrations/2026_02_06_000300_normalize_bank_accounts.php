<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P0-15: Bank Accounts Normalization
 *
 * Additive migration to enhance the existing bank_accounts table with
 * PSD2-related columns, consent linkage, and proper indexing for
 * multi-account handling and deduplication.
 *
 * The bank_accounts table was originally created in 2025_07_24_core.php.
 * This migration ONLY adds missing columns and indexes.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('bank_accounts')) {
            return;
        }

        Schema::table('bank_accounts', function (Blueprint $table) {
            // PSD2 currency code (3-char ISO) alongside existing currency_id FK
            if (! Schema::hasColumn('bank_accounts', 'currency')) {
                $table->string('currency', 3)->default('MKD')
                    ->after('account_type')
                    ->comment('ISO 4217 currency code for PSD2 compatibility');
            }

            // User-friendly nickname for the account
            if (! Schema::hasColumn('bank_accounts', 'nickname')) {
                $table->string('nickname', 100)->nullable()
                    ->after('currency')
                    ->comment('User-friendly display name');
            }

            // Status enum (active, inactive, disconnected) for PSD2 lifecycle
            if (! Schema::hasColumn('bank_accounts', 'status')) {
                $table->enum('status', ['active', 'inactive', 'disconnected'])
                    ->default('active')
                    ->after('is_primary')
                    ->comment('Account connection status');
            }

            // PSD2 account resource ID from bank API
            if (! Schema::hasColumn('bank_accounts', 'external_id')) {
                $table->string('external_id', 100)->nullable()
                    ->after('status')
                    ->comment('PSD2 account resourceId from bank API');
            }

            // Last sync timestamp for PSD2 account data
            if (! Schema::hasColumn('bank_accounts', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()
                    ->after('external_id')
                    ->comment('Last successful PSD2 sync timestamp');
            }

            // Foreign key to bank_consents for PSD2 consent linkage
            if (! Schema::hasColumn('bank_accounts', 'bank_consent_id')) {
                $table->unsignedBigInteger('bank_consent_id')->nullable()
                    ->after('company_id')
                    ->comment('PSD2 consent that discovered this account');

                if (Schema::hasTable('bank_consents')) {
                    $table->foreign('bank_consent_id')
                        ->references('id')
                        ->on('bank_consents')
                        ->onDelete('restrict');
                }
            }
        });

        // Add unique and composite indexes (separate Schema::table call
        // to avoid issues with column addition + index in same closure)
        Schema::table('bank_accounts', function (Blueprint $table) {
            // Unique constraint: one IBAN per company
            // Use raw DB check to avoid duplicate index errors on re-deploy
            if (! $this->indexExists('bank_accounts', 'bank_accounts_company_iban_unique')) {
                $table->unique(['company_id', 'iban'], 'bank_accounts_company_iban_unique');
            }

            // Unique constraint: one external_id per company (PSD2 dedup)
            if (Schema::hasColumn('bank_accounts', 'external_id')
                && ! $this->indexExists('bank_accounts', 'bank_accounts_company_external_unique')) {
                $table->unique(['company_id', 'external_id'], 'bank_accounts_company_external_unique');
            }

            // Composite index for status-based queries
            if (Schema::hasColumn('bank_accounts', 'status')
                && ! $this->indexExists('bank_accounts', 'bank_accounts_company_status_index')) {
                $table->index(['company_id', 'status'], 'bank_accounts_company_status_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('bank_accounts')) {
            return;
        }

        Schema::table('bank_accounts', function (Blueprint $table) {
            // Drop indexes first
            if ($this->indexExists('bank_accounts', 'bank_accounts_company_status_index')) {
                $table->dropIndex('bank_accounts_company_status_index');
            }
            if ($this->indexExists('bank_accounts', 'bank_accounts_company_external_unique')) {
                $table->dropUnique('bank_accounts_company_external_unique');
            }
            if ($this->indexExists('bank_accounts', 'bank_accounts_company_iban_unique')) {
                $table->dropUnique('bank_accounts_company_iban_unique');
            }

            // Drop foreign key before column
            if (Schema::hasColumn('bank_accounts', 'bank_consent_id')) {
                if ($this->foreignKeyExists('bank_accounts', 'bank_accounts_bank_consent_id_foreign')) {
                    $table->dropForeign('bank_accounts_bank_consent_id_foreign');
                }
                $table->dropColumn('bank_consent_id');
            }

            // Drop added columns
            $columnsToDrop = ['currency', 'nickname', 'status', 'external_id', 'last_synced_at'];
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('bank_accounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $schemaManager = $connection->getDoctrineSchemaManager();

        try {
            $indexes = $schemaManager->listTableIndexes($table);

            return isset($indexes[strtolower($indexName)]);
        } catch (\Exception $e) {
            // Fallback: try raw SQL for MySQL
            try {
                $results = $connection->select(
                    "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
                    [$indexName]
                );

                return count($results) > 0;
            } catch (\Exception $e2) {
                return false;
            }
        }
    }

    /**
     * Check if a foreign key exists on a table.
     */
    private function foreignKeyExists(string $table, string $foreignKeyName): bool
    {
        $connection = Schema::getConnection();

        try {
            $schemaManager = $connection->getDoctrineSchemaManager();
            $foreignKeys = $schemaManager->listTableForeignKeys($table);

            foreach ($foreignKeys as $fk) {
                if ($fk->getName() === $foreignKeyName) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
};

// CLAUDE-CHECKPOINT
