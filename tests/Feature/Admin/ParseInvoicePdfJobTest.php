<?php

use App\Jobs\ParseInvoicePdfJob;
use App\Models\Bill;
use App\Models\Company;
use App\Models\CompanyInboundAlias;
use App\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\post;

beforeEach(function () {
    Artisan::call('migrate', ['--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);
});

test('parse invoice pdf job creates draft bill from parser response', function () {
    $company = Company::firstOrFail();
    Storage::fake('local');

    $disk = config('filesystems.default', 'local');
    $filePath = Storage::disk($disk)->put('inbound-bills/'.$company->id, 'PDFDATA');

    Http::fake([
        '*' => Http::response([
            'supplier' => [
                'name' => 'ACME Ltd',
                'tax_id' => 'MK1234567',
                'email' => 'billing@acme.test',
            ],
            'invoice' => [
                'number' => 'INV-100',
                'date' => '2025-11-15',
                'currency' => null,
            ],
            'totals' => [
                'total' => 1000,
                'subtotal' => 800,
                'tax' => 200,
            ],
            'line_items' => [
                [
                    'name' => 'Service A',
                    'description' => 'Consulting',
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'tax' => 200,
                    'total' => 1000,
                ],
            ],
        ], 200),
    ]);

    $job = new ParseInvoicePdfJob(
        $company->id,
        $filePath,
        'invoice.pdf',
        'supplier@example.com',
        'Test Invoice'
    );

    dispatch_sync($job);

    $bill = Bill::where('company_id', $company->id)
        ->where('bill_number', 'INV-100')
        ->first();

    expect($bill)->not()->toBeNull();
    expect($bill->total)->toBe(1000);
    expect($bill->items)->toHaveCount(1);

    $supplier = Supplier::where('company_id', $company->id)
        ->where('tax_id', 'MK1234567')
        ->first();

    expect($supplier)->not()->toBeNull();
});

test('parse pipeline respects tenant isolation via alias', function () {
    $companyA = Company::firstOrFail();
    $companyB = Company::factory()->create();

    CompanyInboundAlias::create([
        'company_id' => $companyA->id,
        'alias' => 'bills-'.$companyA->id,
    ]);

    Storage::fake('local');

    Http::fake([
        '*' => Http::response([
            'supplier' => [
                'name' => 'ACME Ltd',
            ],
            'invoice' => [
                'number' => 'INV-200',
                'date' => '2025-11-15',
                'currency' => null,
            ],
            'totals' => [
                'total' => 500,
                'subtotal' => 500,
                'tax' => 0,
            ],
            'line_items' => [
                [
                    'name' => 'Service B',
                    'quantity' => 1,
                    'unit_price' => 500,
                    'total' => 500,
                ],
            ],
        ], 200),
    ]);

    $file = UploadedFile::fake()->create('invoice.pdf', 10, 'application/pdf');

    post('/webhooks/email-inbound', [
        'to' => 'bills-'.$companyA->id.'@example.test',
        'from' => 'supplier@example.com',
        'subject' => 'Test Invoice',
        'attachments' => [$file],
    ])->assertOk();

    $billA = Bill::where('company_id', $companyA->id)
        ->where('bill_number', 'INV-200')
        ->first();

    $billB = Bill::where('company_id', $companyB->id)
        ->where('bill_number', 'INV-200')
        ->first();

    expect($billA)->not()->toBeNull();
    expect($billB)->toBeNull();
});

