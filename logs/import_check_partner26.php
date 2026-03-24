<?php
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check and fix onboarding_completed_at for partner 26
$partner = DB::table('partners')->where('id', 26)->first();
echo "Partner 26 onboarding_completed_at: " . ($partner->onboarding_completed_at ?? 'NULL') . "\n";
echo "Partner 26 portfolio_activated_at: " . ($partner->portfolio_activated_at ?? 'NULL') . "\n";

if (empty($partner->onboarding_completed_at)) {
    DB::table('partners')->where('id', 26)->update([
        'onboarding_completed_at' => now(),
        'portfolio_activated_at' => now(),
    ]);
    echo "FIXED: set onboarding_completed_at and portfolio_activated_at to now()\n";
} else {
    echo "Already set, no fix needed\n";
}

// Verify
$partner = DB::table('partners')->where('id', 26)->first();
echo "\nAfter fix:\n";
echo "  onboarding_completed_at: " . ($partner->onboarding_completed_at ?? 'NULL') . "\n";
echo "  portfolio_activated_at: " . ($partner->portfolio_activated_at ?? 'NULL') . "\n";
