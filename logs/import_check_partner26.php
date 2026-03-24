<?php
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// How many addresses exist for company_id=118?
$addresses = DB::table('addresses')->where('company_id', 118)->get();
echo "Addresses with company_id=118: " . count($addresses) . "\n";
foreach ($addresses as $a) {
    echo "  id={$a->id} type={$a->type} customer_id=" . ($a->customer_id ?? 'NULL') . " name=" . ($a->name ?? 'NULL') . "\n";
}

// Simulate the exact console query for partner 26
$results = DB::table('partner_company_links')
    ->join('companies', 'companies.id', '=', 'partner_company_links.company_id')
    ->leftJoin('addresses', function ($join) {
        $join->on('addresses.company_id', '=', 'companies.id')
            ->where('addresses.type', '=', 'billing');
    })
    ->where('partner_company_links.partner_id', 26)
    ->where('partner_company_links.is_active', true)
    ->where('partner_company_links.invitation_status', 'accepted')
    ->select([
        'companies.id',
        'companies.name',
        'addresses.id as address_id',
        'addresses.customer_id',
    ])
    ->get();

echo "\nConsole query results (raw): " . count($results) . " rows\n";
foreach ($results as $r) {
    echo "  company_id={$r->id} name={$r->name} address_id=" . ($r->address_id ?? 'NULL') . " customer_id=" . ($r->customer_id ?? 'NULL') . "\n";
}
