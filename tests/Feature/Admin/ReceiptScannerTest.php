<?php

use App\Http\Controllers\V1\Admin\AccountsPayable\ReceiptScannerController;
use App\Http\Requests\ReceiptScanRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\InvoiceParsing\InvoiceParserClient;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::find(1);
    $this->withHeaders([
        'company' => $user->companies()->first()->id,
    ]);
    Sanctum::actingAs(
        $user,
        ['*']
    );
});

test('store validates using a form request for receipt scanner', function () {
    $this->assertActionUsesFormRequest(
        ReceiptScannerController::class,
        'scan',
        ReceiptScanRequest::class
    );
});

test('scan returns structured invoice data from parser', function () {
    $company = Company::firstOrFail();

    $fakeParserClient = new class implements InvoiceParserClient
    {
        public function parse(int $companyId, string $filePath, string $originalName, string $from, ?string $subject): array
        {
            return [];
        }

        public function ocr(int $companyId, string $filePath, string $originalName): array
        {
            return [];
        }

        public function parseReceipt(int $companyId, string $filePath, string $originalName): array
        {
            return [
                'supplier' => [
                    'name' => 'OCR Supplier',
                    'tax_id' => 'MK9999999',
                ],
                'invoice' => [
                    'number' => 'OCR-INV-1',
                    'date' => '2025-11-15',
                    'due_date' => '2025-12-15',
                    'currency' => 'MKD',
                ],
                'totals' => [
                    'total' => 100000,
                    'subtotal' => 84746,
                    'tax' => 15254,
                ],
                'line_items' => [
                    [
                        'name' => 'OCR Item',
                        'description' => 'Parsed from image',
                        'quantity' => 1,
                        'unit_price' => 100000,
                        'total' => 100000,
                        'tax' => 15254,
                    ],
                ],
                'extraction_method' => 'gemini',
            ];
        }
    };
    $this->app->instance(InvoiceParserClient::class, $fakeParserClient);

    Storage::fake(config('filesystems.default', 'local'));

    $file = \Illuminate\Http\UploadedFile::fake()->image('ocr-receipt.jpg');

    $response = postJson('/api/v1/receipts/scan', [
        'receipt' => $file,
    ], [
        'company' => $company->id,
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'image_url',
        'stored_path',
        'ocr_file_path',
        'data' => [
            'vendor_name',
            'tax_id',
            'bill_number',
            'bill_date',
            'due_date',
            'total',
            'subtotal',
            'tax',
            'currency',
            'line_items',
        ],
        'extraction_method',
    ]);

    $data = $response->json('data');
    expect($data['vendor_name'])->toBe('OCR Supplier');
    expect($data['tax_id'])->toBe('MK9999999');
    expect($data['bill_number'])->toBe('OCR-INV-1');
    expect($data['bill_date'])->toBe('2025-11-15');
    expect($data['total'])->toBe(1000.0); // 100000 cents / 100
    expect($data['tax'])->toBe(152.54); // 15254 cents / 100
    expect($response->json('extraction_method'))->toBe('gemini');
});

test('receipt scanner respects tenant isolation', function () {
    $companyA = Company::firstOrFail();
    $companyB = Company::factory()->create();

    $fakeParserClient = new class implements InvoiceParserClient
    {
        public function parse(int $companyId, string $filePath, string $originalName, string $from, ?string $subject): array
        {
            return [];
        }

        public function ocr(int $companyId, string $filePath, string $originalName): array
        {
            return [];
        }

        public function parseReceipt(int $companyId, string $filePath, string $originalName): array
        {
            return [
                'supplier' => ['name' => 'Tenant Supplier', 'tax_id' => 'MK0000000'],
                'invoice' => ['number' => 'TENANT-INV-1', 'date' => '2025-11-15', 'due_date' => null, 'currency' => 'MKD'],
                'totals' => ['total' => 50000, 'subtotal' => 50000, 'tax' => 0],
                'line_items' => [],
                'extraction_method' => 'gemini',
            ];
        }
    };
    $this->app->instance(InvoiceParserClient::class, $fakeParserClient);

    Storage::fake(config('filesystems.default', 'local'));

    $file = \Illuminate\Http\UploadedFile::fake()->image('tenant-receipt.png');

    // Scan for company A should work
    $responseA = postJson('/api/v1/receipts/scan', [
        'receipt' => $file,
    ], [
        'company' => $companyA->id,
    ]);

    $responseA->assertOk();
    expect($responseA->json('data.vendor_name'))->toBe('Tenant Supplier');

    // The stored path should be scoped to company A
    expect($responseA->json('stored_path'))->toContain('scanned-receipts/'.$companyA->id);
});

test('scan handles missing invoice number gracefully', function () {
    $company = Company::firstOrFail();

    $fakeParserClient = new class implements InvoiceParserClient
    {
        public function parse(int $companyId, string $filePath, string $originalName, string $from, ?string $subject): array
        {
            return [];
        }

        public function ocr(int $companyId, string $filePath, string $originalName): array
        {
            return [];
        }

        public function parseReceipt(int $companyId, string $filePath, string $originalName): array
        {
            return [
                'supplier' => ['name' => 'No Number Supplier', 'tax_id' => 'MK1111111'],
                'invoice' => ['number' => null, 'date' => '2025-11-15', 'due_date' => null, 'currency' => 'MKD'],
                'totals' => ['total' => 20000, 'subtotal' => 20000, 'tax' => 0],
                'line_items' => [],
                'extraction_method' => 'gemini',
            ];
        }
    };
    $this->app->instance(InvoiceParserClient::class, $fakeParserClient);

    Storage::fake(config('filesystems.default', 'local'));

    $file = \Illuminate\Http\UploadedFile::fake()->image('no-number-receipt.jpg');

    $response = postJson('/api/v1/receipts/scan', [
        'receipt' => $file,
    ], [
        'company' => $company->id,
    ]);

    $response->assertOk();
    expect($response->json('data.bill_number'))->toBeNull();
    expect($response->json('data.vendor_name'))->toBe('No Number Supplier');
    expect($response->json('data.total'))->toBe(200.0);
})
    ->group('receipt-scanner');
