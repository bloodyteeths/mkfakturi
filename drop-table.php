<?php
// Script to drop problematic tables in correct order (respecting foreign keys)
// Run this once, then delete this file

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Tables to drop in order (child tables first, then parent tables)
$tablesToDrop = [
    'import_temp_invoices',
    'import_temp_customers',
    'partner_company_links',
    'minimax_tokens',
];

foreach ($tablesToDrop as $table) {
    try {
        echo "Dropping $table table...\n";
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists($table);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        echo "Table $table dropped successfully!\n";
    } catch (Exception $e) {
        echo "Error dropping $table: " . $e->getMessage() . "\n";
    }
}
