<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Partner;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "--- Starting Partner Flow Audit ---\n";

// 1. Create a fresh Company
$owner = User::factory()->create();
$company = Company::create([
    'name' => 'Audit Test Company ' . time(),
    'owner_id' => $owner->id,
    'slug' => 'audit-test-' . time(),
]);
echo "Created Company: {$company->name} (ID: {$company->id})\n";

// 2. Run setupRoles (simulating what happens on creation)
echo "Running setupRoles...\n";
$company->setupRoles();

// 3. Check created roles in this scope
Bouncer::scope()->to($company->id);
$roles = Bouncer::role()->where('scope', $company->id)->get();
echo "Roles in Company Scope:\n";
foreach ($roles as $role) {
    echo "- {$role->name}\n";
}

$adminRole = $roles->where('name', 'admin')->first();
if (!$adminRole) {
    echo "CRITICAL: 'admin' role MISSING in company scope!\n";
} else {
    echo "OK: 'admin' role exists.\n";
}

// 4. Create a Partner
$partnerUser = User::factory()->create(['role' => 'partner']);
$partner = Partner::create([
    'user_id' => $partnerUser->id,
    'name' => 'Audit Partner',
    'email' => $partnerUser->email,
    'is_active' => true,
]);
echo "Created Partner: {$partner->name} (User ID: {$partnerUser->id})\n";

// 5. Assign Partner to Company (simulating PartnerManagementController logic)
echo "Assigning Partner to Company...\n";
// Replicating logic from PartnerManagementController::assignCompany
$partner->companies()->attach($company->id, [
    'is_primary' => false,
    'override_commission_rate' => 10,
    'permissions' => json_encode(['full_access']),
    'is_active' => true,
]);

// 6. Check if Partner User has 'admin' role in this scope
Bouncer::scope()->to($company->id);
$isAssigned = $partnerUser->isAn('admin');
echo "Partner User has 'admin' role in scope? " . ($isAssigned ? 'YES' : 'NO') . "\n";

if (!$isAssigned) {
    echo "CRITICAL: Partner User was NOT assigned 'admin' role automatically!\n";
}

// 7. Cleanup
// $company->delete();
// $partner->delete();
// $partnerUser->delete();
// $owner->delete();
