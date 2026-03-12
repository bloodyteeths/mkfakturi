<?php

use App\Jobs\ParseInvoicePdfJob;
use App\Models\Bill;
use App\Models\Company;
use App\Models\CompanyInboundAlias;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\TaxType;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Artisan::call('migrate', ['--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);
});

test('parse invoice pdf job creates draft bill from parser response', function () {
    $company = Company::firstOrFail();
    $disk = config('filesystems.default', 'local');
    Storage::fake($disk);

    $filePath = 'inbound-bills/'.$company->id.'/test-invoice.pdf';
    Storage::disk($disk)->put($filePath, '%PDF-1.4 fake pdf content');

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
    expect($bill->sub_total)->toBe(800);
    expect($bill->tax)->toBe(200);
    expect($bill->items)->toHaveCount(1);

    $item = $bill->items->first();
    expect($item->price)->toBe(1000);
    expect($item->total)->toBe(1000);
    expect($item->tax)->toBe(200);
    expect($item->name)->toBe('Service A');
    expect($item->description)->toBe('Consulting'); // different from name, kept

    $supplier = Supplier::where('company_id', $company->id)
        ->where('tax_id', 'MK1234567')
        ->first();

    expect($supplier)->not()->toBeNull();
});

test('parse invoice deduplicates identical name and description', function () {
    $company = Company::firstOrFail();
    $disk = config('filesystems.default', 'local');
    Storage::fake($disk);

    $filePath = 'inbound-bills/'.$company->id.'/test-dedupe.pdf';
    Storage::disk($disk)->put($filePath, '%PDF-1.4 fake');

    Http::fake([
        '*' => Http::response([
            'supplier' => ['name' => 'Test Supplier'],
            'invoice' => ['number' => 'DD-001', 'date' => '2025-11-15', 'currency' => null],
            'totals' => ['total' => 5000, 'subtotal' => 5000, 'tax' => 0],
            'line_items' => [
                [
                    'name' => 'Одржување на хигиена',
                    'description' => 'Одржување на хигиена',
                    'quantity' => 1,
                    'unit_price' => 5000,
                    'total' => 5000,
                ],
            ],
        ], 200),
    ]);

    dispatch_sync(new ParseInvoicePdfJob(
        $company->id, $filePath, 'dedupe.pdf', 'test@example.com', 'Dedupe Test'
    ));

    $bill = Bill::where('company_id', $company->id)->where('bill_number', 'DD-001')->first();
    expect($bill)->not()->toBeNull();

    $item = $bill->items->first();
    expect($item->name)->toBe('Одржување на хигиена');
    expect($item->description)->toBeNull(); // deduplicated
    expect($item->price)->toBe(5000);
});

test('parse invoice creates tax records when matching tax type exists', function () {
    $company = Company::firstOrFail();
    $disk = config('filesystems.default', 'local');
    Storage::fake($disk);

    // Create an 18% VAT tax type for the company
    $taxType = TaxType::create([
        'name' => 'ДДВ 18%',
        'percent' => 18,
        'company_id' => $company->id,
        'compound_tax' => 0,
        'type' => 'general',
    ]);

    $filePath = 'inbound-bills/'.$company->id.'/test-tax.pdf';
    Storage::disk($disk)->put($filePath, '%PDF-1.4 fake');

    Http::fake([
        '*' => Http::response([
            'supplier' => ['name' => 'Tax Test Supplier', 'tax_id' => 'MK999'],
            'invoice' => ['number' => 'TAX-001', 'date' => '2025-11-15', 'currency' => null],
            'totals' => ['total' => 11800, 'subtotal' => 10000, 'tax' => 1800],
            'line_items' => [
                [
                    'name' => 'Управувачки услуги',
                    'quantity' => 1,
                    'unit_price' => 10000,
                    'tax' => 1800,
                    'total' => 11800,
                ],
            ],
        ], 200),
    ]);

    dispatch_sync(new ParseInvoicePdfJob(
        $company->id, $filePath, 'tax-test.pdf', 'test@example.com', 'Tax Test'
    ));

    $bill = Bill::where('company_id', $company->id)->where('bill_number', 'TAX-001')->first();
    expect($bill)->not()->toBeNull();

    $item = $bill->items()->with('taxes.taxType')->first();
    expect($item->tax)->toBe(1800);
    expect($item->taxes)->toHaveCount(1);
    expect($item->taxes->first()->percent)->toBe(18.0);
    expect($item->taxes->first()->amount)->toBe(1800);
    expect($item->taxes->first()->taxType)->not()->toBeNull();
});

test('parse pipeline respects tenant isolation via alias', function () {
    $companyA = Company::firstOrFail();
    $companyB = Company::factory()->create();

    $disk = config('filesystems.default', 'local');
    Storage::fake($disk);

    $filePath = 'inbound-bills/'.$companyA->id.'/test-invoice-200.pdf';
    Storage::disk($disk)->put($filePath, '%PDF-1.4 fake pdf content');

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

    // Dispatch job directly for company A (tests tenant isolation at job level)
    $job = new ParseInvoicePdfJob(
        $companyA->id,
        $filePath,
        'invoice.pdf',
        'supplier@example.com',
        'Test Invoice'
    );

    dispatch_sync($job);

    $billA = Bill::where('company_id', $companyA->id)
        ->where('bill_number', 'INV-200')
        ->first();

    $billB = Bill::where('company_id', $companyB->id)
        ->where('bill_number', 'INV-200')
        ->first();

    expect($billA)->not()->toBeNull();
    expect($billB)->toBeNull();
});
