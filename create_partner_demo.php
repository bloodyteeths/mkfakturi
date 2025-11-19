<?php

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'partner@demo.mk')->first();

if (!$user) {
    echo "User not found\n";
    exit(1);
}

$existing = App\Models\Partner::where('user_id', $user->id)->first();

if ($existing) {
    echo "Partner already exists: ID " . $existing->id . "\n";
    exit(0);
}

$partner = App\Models\Partner::create([
    'user_id' => $user->id,
    'name' => 'Demo Partner',
    'email' => $user->email,
    'company_name' => 'Demo Partner Company',
    'commission_rate' => 15.00,
    'is_active' => true,
    'kyc_status' => 'verified'
]);

echo "Created Partner ID: " . $partner->id . "\n";
echo "Partner Email: " . $partner->email . "\n";
