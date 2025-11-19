<?php

use App\Models\User;
use App\Models\Company;
use Silber\Bouncer\Bouncer;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$partnerEmail = 'partner@demo.mk';
$user = User::where('email', $partnerEmail)->first();
$companyId = 1;

if (!$user) {
    echo "Partner user not found\n";
    exit(1);
}

echo "User: {$user->name} ({$user->email}, ID: {$user->id})\n";
echo "Role: {$user->role}\n";

// Check global roles
echo "Global Roles: " . implode(', ', $user->getRoles()->toArray()) . "\n";

// Scope to company
echo "\nScoping to Company ID: $companyId\n";
resolve(Silber\Bouncer\Bouncer::class)->scope()->to($companyId);

// Check scoped roles
echo "Scoped Roles: " . implode(', ', $user->getRoles()->toArray()) . "\n";

// Check specific ability
$ability = 'view-invoice';
$can = $user->can($ability, \App\Models\Invoice::class);
echo "Can $ability (Class): " . ($can ? 'YES' : 'NO') . "\n";

// Check if admin role has the ability
$adminRole = resolve(Silber\Bouncer\Bouncer::class)->role()->where('name', 'admin')->first();
if ($adminRole) {
    echo "Admin Role exists. Checking abilities...\n";
    $abilities = $adminRole->getAbilities();
    $hasAbility = $abilities->contains('name', $ability);
    echo "Admin Role has $ability: " . ($hasAbility ? 'YES' : 'NO') . "\n";
} else {
    echo "Admin Role not found!\n";
}

// Check if user is assigned admin in this scope
$isAssigned = $user->isAn('admin');
echo "User is assigned 'admin' in scope: " . ($isAssigned ? 'YES' : 'NO') . "\n";
