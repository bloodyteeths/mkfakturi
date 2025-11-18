<?php

// Quick script to check and fix partner login issues
// Run with: php fix_partner_login.php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Partner Login Fix Script ===\n\n";

// Check user 4
echo "1. Checking user ID 4...\n";
$user = App\Models\User::find(4);
if (! $user) {
    echo "   ERROR: User 4 not found!\n";
    exit(1);
}
echo "   User found: {$user->email}, Role: {$user->role}\n";

// Check partner record
echo "\n2. Checking partner record...\n";
$partner = App\Models\Partner::where('user_id', 4)->first();

if (! $partner) {
    echo "   NO PARTNER RECORD FOUND! Creating...\n";
    $partner = App\Models\Partner::create([
        'user_id' => 4,
        'name' => $user->name ?? 'Demo Partner',
        'email' => $user->email,
        'company_name' => 'Demo Partner LLC',
        'is_active' => true,
        'kyc_status' => 'approved',
        'partner_tier' => 'free',
    ]);
    echo "   Partner created with ID: {$partner->id}\n";
} else {
    echo "   Partner found: ID={$partner->id}, Name={$partner->name}, Active=".($partner->is_active ? 'yes' : 'no')."\n";
}

// Check company links
echo "\n3. Checking partner-company links...\n";
$links = App\Models\PartnerCompanyLink::where('partner_id', $partner->id)->get();
echo "   Found {$links->count()} company links\n";

foreach ($links as $link) {
    $company = App\Models\Company::find($link->company_id);
    echo "   - Company {$link->company_id}: ".($company ? $company->name : 'DELETED').
         ', Primary='.($link->is_primary ? 'yes' : 'no').
         ', Active='.($link->is_active ? 'yes' : 'no')."\n";
}

// Link to company 2 if not already linked
if (! $links->contains('company_id', 2)) {
    echo "\n4. Linking partner to company 2...\n";
    $company2 = App\Models\Company::find(2);
    if (! $company2) {
        echo "   ERROR: Company 2 not found!\n";
        echo "   Available companies:\n";
        $companies = App\Models\Company::all();
        foreach ($companies as $c) {
            echo "   - ID {$c->id}: {$c->name}\n";
        }
        exit(1);
    }

    $link = App\Models\PartnerCompanyLink::create([
        'partner_id' => $partner->id,
        'company_id' => 2,
        'is_primary' => true,
        'is_active' => true,
    ]);
    echo "   Link created successfully!\n";
} else {
    echo "\n4. Partner already linked to company 2\n";
}

echo "\n=== Fix Complete ===\n";
echo "Partner {$partner->email} (ID: {$partner->id}) is ready to login.\n";
