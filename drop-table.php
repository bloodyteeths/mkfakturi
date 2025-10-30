<?php
// Simple script to drop the problematic table
// Run this once, then delete this file

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    echo "Dropping import_temp_customers table...\n";
    Schema::dropIfExists('import_temp_customers');
    echo "Table dropped successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
