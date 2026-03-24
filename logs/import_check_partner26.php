<?php
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Find ALL Ivana users
$users = DB::table('users')->where('email', 'LIKE', '%ivana%')->get();
echo "Users matching 'ivana':\n";
foreach ($users as $u) {
    echo "  id={$u->id} email={$u->email} role=" . ($u->role ?? 'NULL') . "\n";
}

// Partner records
$partners = DB::table('partners')->get();
echo "\nAll partners:\n";
foreach ($partners as $p) {
    $uemail = DB::table('users')->where('id', $p->user_id)->value('email');
    echo "  partner_id={$p->id} user_id={$p->user_id} email={$uemail}\n";
}

// All partner_company_links
$links = DB::table('partner_company_links')->get();
echo "\nAll partner_company_links:\n";
foreach ($links as $l) {
    $cname = DB::table('companies')->where('id', $l->company_id)->value('name');
    echo "  partner_id={$l->partner_id} company_id={$l->company_id} name={$cname} active={$l->is_active} portfolio=" . ($l->is_portfolio_managed ?? 'NULL') . "\n";
}

// User 140 details
$u140 = DB::table('users')->where('id', 140)->first();
echo "\nUser 140: email={$u140->email} role=" . ($u140->role ?? 'NULL') . "\n";

// User 140 companies
$ucs = DB::table('user_company')->where('user_id', 140)->get();
echo "User 140 user_company: " . count($ucs) . "\n";
foreach ($ucs as $uc) {
    $cname = DB::table('companies')->where('id', $uc->company_id)->value('name');
    echo "  company_id={$uc->company_id} name={$cname}\n";
}

// Check partner user's companies too
$partnerUser = DB::table('users')->where('email', 'LIKE', '%yahoo%')->where('email', 'LIKE', '%ivana%')->first();
if ($partnerUser) {
    echo "\nPartner user: id={$partnerUser->id} email={$partnerUser->email} role=" . ($partnerUser->role ?? 'NULL') . "\n";
    $pucs = DB::table('user_company')->where('user_id', $partnerUser->id)->get();
    echo "Partner user user_company: " . count($pucs) . "\n";
    foreach ($pucs as $puc) {
        $cname = DB::table('companies')->where('id', $puc->company_id)->value('name');
        echo "  company_id={$puc->company_id} name={$cname}\n";
    }
}
