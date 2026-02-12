<?php
/**
 * Fix ghost migration records: migration recorded as run but table doesn't exist.
 * This happens when a migration batch partially fails on deploy.
 *
 * Run via: php artisan tinker < fix-ghost-migrations.php
 * Or via: php fix-ghost-migrations.php (requires bootstrap)
 */

// Bootstrap Laravel if running standalone
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$ghosts = [
    '2026_02_15_000200_create_leave_management_tables' => 'leave_types',
    '2026_02_15_000400_create_client_documents_table' => 'client_documents',
    '2026_02_15_000500_create_deadlines_table' => 'deadlines',
    '2026_02_15_000600_create_fiscal_device_tables' => 'fiscal_devices',
    '2026_02_12_000001_create_woocommerce_tables' => 'woocommerce_settings',
];

$fixed = 0;
foreach ($ghosts as $migration => $table) {
    $recorded = DB::table('migrations')->where('migration', $migration)->exists();
    $tableExists = Schema::hasTable($table);

    if ($recorded && ! $tableExists) {
        DB::table('migrations')->where('migration', $migration)->delete();
        echo "Fixed ghost: {$migration} (table {$table} missing)\n";
        $fixed++;
    }
}

echo $fixed > 0
    ? "Fixed {$fixed} ghost migration(s). Re-run php artisan migrate.\n"
    : "No ghost migrations found.\n";
