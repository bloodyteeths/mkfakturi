<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P0-03: Import Logging & Analytics
 *
 * Creates the import_logs table to track CSV bank import operations.
 * Stores per-import statistics: rows parsed/imported/duplicated/failed,
 * parse time, errors, and status for KPI dashboards.
 *
 * Idempotent: safe to re-run on every Railway deploy.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('import_logs')) {
            return;
        }

        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('restrict');
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->string('bank_code', 20);
            $table->string('file_name');
            $table->unsignedInteger('file_size_bytes')->default(0);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('parsed_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->json('errors')->nullable();
            $table->unsignedInteger('parse_time_ms')->default(0);
            $table->string('status', 20)->default('pending'); // pending, completed, failed, partial
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
            $table->index(['company_id', 'bank_code']);
        });

        // Ensure InnoDB + utf8mb4 (avoid errno 150 on foreign keys)
        if (config('database.default') === 'mysql') {
            \Illuminate\Support\Facades\DB::statement(
                'ALTER TABLE `import_logs` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};

// CLAUDE-CHECKPOINT
