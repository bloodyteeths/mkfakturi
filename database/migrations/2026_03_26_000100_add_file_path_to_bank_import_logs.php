<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add file_path column to bank_import_logs for R2 storage of original statements.
 * Idempotent: safe to re-run on every Railway deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_import_logs') && !Schema::hasColumn('bank_import_logs', 'file_path')) {
            Schema::table('bank_import_logs', function (Blueprint $table) {
                $table->string('file_path')->nullable()->after('file_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bank_import_logs', 'file_path')) {
            Schema::table('bank_import_logs', function (Blueprint $table) {
                $table->dropColumn('file_path');
            });
        }
    }
};
