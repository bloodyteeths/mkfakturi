<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * audit_logs.company_id was NOT NULL, but some legitimately company-less events
 * (user signup, login/last_login_at update, payment-gateway webhooks) produce an
 * audit entry with no resolvable company. That raised
 * "Column 'company_id' cannot be null" — caught by AuditObserver, so the audit
 * row was SILENTLY dropped. Make the column nullable so those system-level
 * events are recorded. The FK to companies stays intact (NULL bypasses the FK).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs') || ! Schema::hasColumn('audit_logs', 'company_id')) {
            return;
        }

        // Idempotent: only alter if currently NOT NULL.
        $col = DB::selectOne(
            "SELECT IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs' AND COLUMN_NAME = 'company_id'"
        );

        if ($col && strtoupper($col->IS_NULLABLE) === 'NO') {
            // Nullability change is allowed with the existing FK in place (MySQL).
            DB::statement('ALTER TABLE audit_logs MODIFY company_id INT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_logs') || ! Schema::hasColumn('audit_logs', 'company_id')) {
            return;
        }

        // Only safe to restore NOT NULL if no NULL rows exist.
        $nulls = DB::selectOne('SELECT COUNT(*) AS c FROM audit_logs WHERE company_id IS NULL');
        if (! $nulls || (int) $nulls->c === 0) {
            DB::statement('ALTER TABLE audit_logs MODIFY company_id INT UNSIGNED NOT NULL');
        }
    }
};

// CLAUDE-CHECKPOINT
