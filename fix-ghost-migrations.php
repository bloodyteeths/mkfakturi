<?php
/**
 * Fix ghost migration records and ensure critical tables exist.
 *
 * Ghost records: migration recorded as run but table doesn't exist.
 * This happens when a migration batch partially fails on deploy.
 *
 * Also creates missing tables directly as a failsafe.
 */

// Bootstrap Laravel
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ── Step 1: Fix ghost migration records ──
$ghosts = [
    '2026_02_15_000200_create_leave_management_tables' => 'leave_types',
    '2026_02_15_000400_create_client_documents_table' => 'client_documents',
    '2026_02_15_000500_create_deadlines_table' => 'deadlines',
    '2026_02_15_000600_create_fiscal_device_tables' => 'fiscal_devices',
    '2026_02_12_000001_create_woocommerce_tables' => 'woocommerce_settings',
];

$fixed = 0;
foreach ($ghosts as $migration => $table) {
    try {
        $recorded = DB::table('migrations')->where('migration', $migration)->exists();
        $tableExists = Schema::hasTable($table);

        if ($recorded && ! $tableExists) {
            DB::table('migrations')->where('migration', $migration)->delete();
            echo "Fixed ghost: {$migration} (table {$table} missing)\n";
            $fixed++;
        }
    } catch (\Exception $e) {
        echo "Error checking {$migration}: {$e->getMessage()}\n";
    }
}

echo $fixed > 0
    ? "Fixed {$fixed} ghost migration(s).\n"
    : "No ghost migrations found.\n";

// ── Step 2: Failsafe — create deadlines table directly if still missing ──
if (! Schema::hasTable('deadlines')) {
    echo "FAILSAFE: Creating deadlines table directly via SQL...\n";
    try {
        DB::statement('CREATE TABLE IF NOT EXISTS `deadlines` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `company_id` INT UNSIGNED NOT NULL,
            `partner_id` BIGINT UNSIGNED NULL,
            `title` VARCHAR(200) NOT NULL,
            `title_mk` VARCHAR(200) NULL,
            `description` TEXT NULL,
            `deadline_type` ENUM("vat_return","mpin","cit_advance","annual_fs","custom") DEFAULT "custom",
            `due_date` DATE NOT NULL,
            `status` ENUM("upcoming","due_today","overdue","completed") DEFAULT "upcoming",
            `completed_at` TIMESTAMP NULL,
            `completed_by` INT UNSIGNED NULL,
            `reminder_days_before` JSON DEFAULT NULL,
            `last_reminder_sent_at` TIMESTAMP NULL,
            `is_recurring` TINYINT(1) DEFAULT 0,
            `recurrence_rule` VARCHAR(50) NULL,
            `metadata` JSON NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            INDEX `idx_company_due_status` (`company_id`, `due_date`, `status`),
            INDEX `idx_partner_due_status` (`partner_id`, `due_date`, `status`),
            INDEX `idx_status_due` (`status`, `due_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        echo "Deadlines table created successfully.\n";
    } catch (\Exception $e) {
        echo "Failed to create deadlines table: {$e->getMessage()}\n";
    }
} else {
    echo "Deadlines table exists.\n";
}
