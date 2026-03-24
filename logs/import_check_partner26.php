<?php
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// User 140
$user = DB::table('users')->where('id', 140)->first();
echo "User 140: email={$user->email} role=" . ($user->role ?? 'NULL') . "\n";

// user_company pivot (correct table name)
$userCompanies = DB::table('user_company')->where('user_id', 140)->get();
echo "\nUser 140 user_company links: " . count($userCompanies) . "\n";
foreach ($userCompanies as $uc) {
    $cname = DB::table('companies')->where('id', $uc->company_id)->value('name');
    echo "  company_id={$uc->company_id} name={$cname}\n";
}

// Partner 26 links
$links = DB::table('partner_company_links')->where('partner_id', 26)->get();
echo "\nPartner 26 company links: " . count($links) . "\n";
foreach ($links as $l) {
    $cname = DB::table('companies')->where('id', $l->company_id)->value('name');
    echo "  company_id={$l->company_id} name={$cname} active={$l->is_active} portfolio=" . ($l->is_portfolio_managed ?? 'NULL') . "\n";
}

// Partner 26 record
$partner = DB::table('partners')->where('id', 26)->first();
echo "\nPartner 26: user_id={$partner->user_id} status={$partner->status}\n";
echo "  onboarding=" . ($partner->onboarding_completed_at ?? 'NULL') . "\n";
echo "  portfolio_activated=" . ($partner->portfolio_activated_at ?? 'NULL') . "\n";

// All companies (to see what she might be seeing)
$total = DB::table('companies')->count();
echo "\nTotal companies in DB: {$total}\n";

// Via model
$userModel = App\Models\User::find(140);
$companies = $userModel->companies;
echo "\nUser->companies() relationship: " . count($companies) . "\n";
foreach ($companies as $c) {
    echo "  id={$c->id} name={$c->name}\n";
}

// Check if partner dashboard uses different query
$partnerModel = App\Models\Partner::find(26);
echo "\nPartner->portfolioCompanies: " . $partnerModel->portfolioCompanies->count() . "\n";
foreach ($partnerModel->portfolioCompanies as $pc) {
    echo "  id={$pc->id} name={$pc->name}\n";
}

echo "\nPartner->companies (all, no portfolio filter): " . $partnerModel->companies->count() . "\n";
foreach ($partnerModel->companies as $ac) {
    echo "  id={$ac->id} name={$ac->name}\n";
}
