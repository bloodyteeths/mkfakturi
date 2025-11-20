<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "--- Checking Companies ---\n";
$companies = \App\Models\Company::all();
foreach ($companies as $company) {
    echo "Company ID: {$company->id}, Name: {$company->name}\n";
}

$targetCompanyId = 1; // Try Company 1 first as per logs
$company = \App\Models\Company::find($targetCompanyId);

if (!$company) {
    echo "Company $targetCompanyId not found. Trying Company 2.\n";
    $targetCompanyId = 2;
    $company = \App\Models\Company::find($targetCompanyId);
}

if ($company) {
    echo "Creating test data for Company {$company->id}...\n";

    // 1. Create/Find Customer
    $customer = \App\Models\Customer::firstOrCreate(
        ['email' => 'test@example.com', 'company_id' => $company->id],
        ['name' => 'Test Customer', 'currency_id' => 1]
    );
    echo "Customer ID: {$customer->id}\n";

    // 2. Create Invoice
    $invoice = \App\Models\Invoice::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'invoice_number' => 'TEST-001',
        'status' => 'DRAFT',
        'paid_status' => 'UNPAID',
        'currency_id' => 1,
        'total' => 10000,
        'sub_total' => 10000,
        'tax_total' => 0,
        'discount_val' => 0,
        'exchange_rate' => 1,
    ]);
    echo "Created Invoice ID: {$invoice->id}\n";

    // 3. Verify Count
    $count = \App\Models\Invoice::where('company_id', $company->id)->count();
    echo "Total Invoices for Company {$company->id}: $count\n";
} else {
    echo "No companies found to create data for.\n";
}
