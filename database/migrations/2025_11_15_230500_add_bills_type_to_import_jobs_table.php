<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('import_jobs')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        // Only adjust enum definition on MySQL/MariaDB – other drivers
        // (like SQLite in tests) will use the updated base migration.
        if (in_array($driver, ['mysql', 'mysqli', 'mariadb'], true)) {
            DB::statement("
                ALTER TABLE import_jobs
                MODIFY COLUMN name VARCHAR(255) NULL,
                MODIFY COLUMN type ENUM(
                    'customers',
                    'invoices',
                    'items',
                    'payments',
                    'expenses',
                    'bills',
                    'complete'
                ) NOT NULL
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('import_jobs')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mysqli', 'mariadb'], true)) {
            DB::statement("
                ALTER TABLE import_jobs
                MODIFY COLUMN name VARCHAR(255) NOT NULL,
                MODIFY COLUMN type ENUM(
                    'customers',
                    'invoices',
                    'items',
                    'payments',
                    'expenses',
                    'complete'
                ) NOT NULL
            ");
        }
    }
};
