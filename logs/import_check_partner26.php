<?php
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

// Simulate partner user 141 accessing the PDF
$user = App\Models\User::find(141);
Auth::login($user);
echo "Logged in as: {$user->email} role={$user->role}\n";

// Check company access
$company = App\Models\Company::find(118);
echo "Company: {$company->name}\n";

// Check policy
echo "\nPolicy checks:\n";
echo "  owner_id match: " . ($user->id == $company->owner_id ? 'YES' : 'NO (owner_id=' . $company->owner_id . ')') . "\n";

$userCompanies = $user->companies;
echo "  user->companies count: " . count($userCompanies) . "\n";
echo "  contains(118): " . ($userCompanies->contains($company) ? 'YES' : 'NO') . "\n";
echo "  isOwner(): " . ($user->isOwner() ? 'YES' : 'NO') . "\n";

// Check partner
$partner = $user->partner;
echo "\nPartner: " . ($partner ? "id={$partner->id}" : 'NULL') . "\n";

// Check IFRS entity
$entity = DB::table('ifrs_entities')->where('id', 109)->first();
echo "Entity 109: " . ($entity ? $entity->name : 'NOT FOUND') . "\n";

// Check transaction exists
$tx = DB::table('ifrs_transactions')->where('entity_id', 109)->first();
echo "\nFirst transaction for entity 109: " . ($tx ? "id={$tx->id} ref={$tx->transaction_no}" : 'NONE') . "\n";

// Try getJournalEntry
$adapter = app(App\Domain\Accounting\IfrsAdapter::class);
$entry = $adapter->getJournalEntry($company, $tx->id);
echo "getJournalEntry result: " . ($entry ? "id={$entry['id']} ref={$entry['reference']}" : 'NULL') . "\n";
