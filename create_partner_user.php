<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Create partner user
    $userId = DB::table('users')->insertGetId([
        'name' => 'Partner Demo',
        'email' => 'partner@demo.mk',
        'password' => Hash::make('Partner2025!'),
        'role' => 'partner',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    echo "✓ Partner user created successfully!\n";
    echo "  User ID: {$userId}\n\n";

    // Get first company
    $company = DB::table('companies')->first();

    if ($company) {
        // Attach user to company
        DB::table('company_user')->insert([
            'user_id' => $userId,
            'company_id' => $company->id,
            'is_owner' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "✓ Partner attached to company: {$company->name}\n\n";
    }

    echo "===========================================\n";
    echo "PARTNER CREDENTIALS\n";
    echo "===========================================\n";
    echo "Email:    partner@demo.mk\n";
    echo "Password: Partner2025!\n";
    echo "===========================================\n\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
