<?php
// Emergency partner fix script
// Access via: https://app.facturino.mk/fix-partner-now.php
// DELETE THIS FILE AFTER USE!

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');

echo "=== Partner Login Fix ===\n\n";

try {
    // Check user 4
    echo "1. Checking user ID 4...\n";
    $user = App\Models\User::find(4);
    if (!$user) {
        die("ERROR: User 4 not found!\n");
    }
    echo "   User found: {$user->email}, Role: {$user->role}\n\n";

    // Check/create partner record
    echo "2. Checking partner record...\n";
    $partner = App\Models\Partner::where('user_id', 4)->first();

    if (!$partner) {
        echo "   Creating partner record...\n";
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
        echo "   Partner exists: ID={$partner->id}, Name={$partner->name}\n";
    }

    // Check company links
    echo "\n3. Checking partner-company links...\n";
    $links = App\Models\PartnerCompanyLink::where('partner_id', $partner->id)->get();
    echo "   Found {$links->count()} link(s)\n";

    // Link to company 2
    if (!$links->contains('company_id', 2)) {
        echo "\n4. Linking to company 2...\n";
        $company2 = App\Models\Company::find(2);
        if (!$company2) {
            echo "   ERROR: Company 2 not found!\n";
            echo "   Available companies:\n";
            foreach (App\Models\Company::all() as $c) {
                echo "   - ID {$c->id}: {$c->name}\n";
            }
        } else {
            App\Models\PartnerCompanyLink::create([
                'partner_id' => $partner->id,
                'company_id' => 2,
                'is_primary' => true,
                'is_active' => true,
            ]);
            echo "   Link created!\n";
        }
    } else {
        echo "\n4. Already linked to company 2\n";
    }

    echo "\n=== SUCCESS ===\n";
    echo "Partner {$partner->email} is ready to login.\n";
    echo "\nDELETE THIS FILE NOW: public/fix-partner-now.php\n";

} catch (Exception $e) {
    echo "\n=== ERROR ===\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
