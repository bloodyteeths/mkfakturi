<?php
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Partner 26 links
$links = DB::table('partner_company_links')->where('partner_id', 26)->get();
echo "Partner 26 company links: " . count($links) . "\n";
foreach ($links as $l) {
    $cname = DB::table('companies')->where('id', $l->company_id)->value('name');
    echo "  company_id={$l->company_id} name={$cname} active={$l->is_active} portfolio=" . ($l->is_portfolio_managed ?? 'NULL') . "\n";
}

// Companies where managing_partner_id = 26
$companies = DB::table('companies')->where('managing_partner_id', 26)->get();
echo "\nCompanies with managing_partner_id=26: " . count($companies) . "\n";
foreach ($companies as $c) {
    echo "  id={$c->id} name={$c->name} portfolio=" . ($c->is_portfolio_managed ?? 'NULL') . "\n";
}

// User 140 company_user pivot
$userCompanies = DB::table('company_user')->where('user_id', 140)->get();
echo "\nUser 140 (Ivana) company_user links: " . count($userCompanies) . "\n";
foreach ($userCompanies as $uc) {
    $cname = DB::table('companies')->where('id', $uc->company_id)->value('name');
    echo "  company_id={$uc->company_id} name={$cname} role=" . ($uc->role ?? 'NULL') . "\n";
}

// Check partner record
$partner = DB::table('partners')->where('id', 26)->first();
echo "\nPartner 26: user_id={$partner->user_id} status={$partner->status} onboarding=" . ($partner->onboarding_completed_at ?? 'NULL') . "\n";

// Check user 140 details
$user = DB::table('users')->where('id', 140)->first();
echo "User 140: email={$user->email} role=" . ($user->role ?? 'NULL') . " is_partner=" . ($user->is_partner ?? 'NULL') . "\n";

// How many total companies exist?
$total = DB::table('companies')->count();
echo "\nTotal companies in system: {$total}\n";
