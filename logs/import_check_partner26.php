<?php
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$l = DB::table('partner_company_links')->where('partner_id', 26)->first();
echo "invitation_status=" . ($l->invitation_status ?? 'NULL') . "\n";

// Also check activeCompanies query used by switchCompany
$partner = App\Models\Partner::find(26);
$active = $partner->activeCompanies()->get();
echo "activeCompanies count: " . count($active) . "\n";
foreach ($active as $c) {
    echo "  id={$c->id} name={$c->name}\n";
}

// Check what the fixed console query would return
$fixed = DB::table('partner_company_links')
    ->join('companies', 'companies.id', '=', 'partner_company_links.company_id')
    ->leftJoin('addresses', function ($join) {
        $join->on('addresses.company_id', '=', 'companies.id')
            ->where('addresses.type', '=', 'billing')
            ->whereNull('addresses.customer_id');
    })
    ->where('partner_company_links.partner_id', 26)
    ->where('partner_company_links.is_active', true)
    ->where('partner_company_links.invitation_status', 'accepted')
    ->select(['companies.id', 'companies.name'])
    ->get();
echo "\nFixed console query: " . count($fixed) . " rows\n";
foreach ($fixed as $r) {
    echo "  company_id={$r->id} name={$r->name}\n";
}
