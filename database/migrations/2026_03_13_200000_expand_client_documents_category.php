<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expand client_documents.category from ENUM to VARCHAR.
     *
     * Needed for AI classification types: tax_form, product_list
     * which were not in the original ENUM list.
     */
    public function up(): void
    {
        if (! Schema::hasTable('client_documents')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE client_documents MODIFY COLUMN category VARCHAR(50) NOT NULL DEFAULT 'other'");
        } elseif ($driver === 'sqlite') {
            // SQLite: cannot ALTER CHECK constraints, must recreate table
            DB::statement('PRAGMA foreign_keys=OFF');

            $row = DB::selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name='client_documents'");
            if (! $row || ! $row->sql) {
                DB::statement('PRAGMA foreign_keys=ON');

                return;
            }

            $createSql = $row->sql;

            // Remove the CHECK constraint on category: check ("category" in (...))
            $createSql = preg_replace('/\s*check\s*\(\s*"category"\s+in\s*\([^)]+\)\s*\)/i', '', $createSql);

            DB::statement('ALTER TABLE client_documents RENAME TO client_documents_old');
            DB::statement($createSql);
            DB::statement('INSERT INTO client_documents SELECT * FROM client_documents_old');
            DB::statement('DROP TABLE client_documents_old');

            DB::statement('PRAGMA foreign_keys=ON');
        }
    }

    public function down(): void
    {
        // No rollback needed — VARCHAR is more permissive than ENUM
    }
}; // CLAUDE-CHECKPOINT
