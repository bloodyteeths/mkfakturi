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

// Partner 26 record — dump all columns
$partner = DB::table('partners')->where('id', 26)->first();
echo "\nPartner 26 record:\n";
foreach ((array)$partner as $k => $v) {
    echo "  {$k}=" . ($v ?? 'NULL') . "\n";
}

// Total companies
$total = DB::table('companies')->count();
echo "\nTotal companies in DB: {$total}\n";

// User model companies
$userModel = App\Models\User::find(140);
echo "\nUser->companies(): " . $userModel->companies->count() . "\n";
foreach ($userModel->companies as $c) {
    echo "  id={$c->id} name={$c->name}\n";
}

// Partner model queries
$partnerModel = App\Models\Partner::find(26);
echo "\nPartner->portfolioCompanies: " . $partnerModel->portfolioCompanies->count() . "\n";
foreach ($partnerModel->portfolioCompanies as $pc) {
    echo "  id={$pc->id} name={$pc->name}\n";
}
echo "\nPartner->companies (all): " . $partnerModel->companies->count() . "\n";
foreach ($partnerModel->companies as $ac) {
    echo "  id={$ac->id} name={$ac->name}\n";
}

// Check what user 140 sees — is_partner flag, partner_id on user
echo "\nUser 140 all columns:\n";
$u = DB::table('users')->where('id', 140)->first();
foreach ((array)$u as $k => $v) {
    if (in_array($k, ['password', 'remember_token'])) continue;
    echo "  {$k}=" . ($v ?? 'NULL') . "\n";
}
