<?php

use App\Http\Controllers\V1\Admin\AccountsPayable\ReceiptScannerController;
use App\Http\Requests\ReceiptScanRequest;
use App\Models\Bill;
use App\Models\Company;
use App\Models\User;
use App\Services\FiscalReceiptQrService;
use App\Services\InvoiceParsing\InvoiceParserClient;
use App\Services\InvoiceParsing\ParsedInvoiceMapper;
use Illuminate\Http\UploadedFile;
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

test('upload jpeg creates draft bill via parser', function () {
    $company = Company::firstOrFail();

    // Fake parser client returning a normalized invoice payload
    $fakeParserClient = new class implements InvoiceParserClient
    {
        public function parse(int $companyId, string $filePath, string $originalName, string $from, ?string $subject): array
        {
            return [
                'supplier' => [
                    'name' => 'OCR Supplier',
                    'tax_id' => 'MK9999999',
                    'email' => 'ocr@example.com',
                ],
                'invoice' => [
                    'number' => 'OCR-INV-1',
                    'date' => '2025-11-15',
                    'currency' => 13, // MKD id in seeded data
                ],
                'totals' => [
                    'total' => 1000,
                    'subtotal' => 1000,
                    'tax' => 0,
                    'discount' => 0,
                ],
                'line_items' => [
                    [
                        'name' => 'OCR Item',
                        'description' => 'Parsed from image',
                        'quantity' => 1,
                        'unit_price' => 1000,
                        'total' => 1000,
                        'tax' => 0,
                    ],
                ],
            ];
        }

        public function ocr(int $companyId, string $filePath, string $originalName): array
        {
            return [];
        }
    };
    $this->app->instance(InvoiceParserClient::class, $fakeParserClient);

    // Mapper stub that forwards parsed data into Bill components
    $fakeMapper = new class extends ParsedInvoiceMapper
    {
        public function mapToBillComponents(int $companyId, array $parsed): array
        {
            return [
                'supplier' => $parsed['supplier'],
                'bill' => [
                    'bill_date' => $parsed['invoice']['date'],
                    'due_date' => null,
                    'bill_number' => $parsed['invoice']['number'],
                    'status' => \App\Models\Bill::STATUS_DRAFT,
                    'paid_status' => \App\Models\Bill::PAID_STATUS_UNPAID,
                    'sub_total' => $parsed['totals']['subtotal'],
                    'discount' => 0,
                    'discount_val' => 0,
                    'total' => $parsed['totals']['total'],
                    'tax' => $parsed['totals']['tax'],
                    'due_amount' => $parsed['totals']['total'],
                    'currency_id' => $parsed['invoice']['currency'],
                    'exchange_rate' => 1,
                    'base_total' => $parsed['totals']['total'],
                    'base_discount_val' => 0,
                    'base_sub_total' => $parsed['totals']['subtotal'],
                    'base_tax' => $parsed['totals']['tax'],
                    'base_due_amount' => $parsed['totals']['total'],
                ],
                'items' => [
                    [
                        'name' => $parsed['line_items'][0]['name'],
                        'description' => $parsed['line_items'][0]['description'],
                        'quantity' => $parsed['line_items'][0]['quantity'],
                        'price' => $parsed['line_items'][0]['unit_price'],
                        'discount' => 0,
                        'discount_val' => 0,
                        'tax' => $parsed['line_items'][0]['tax'],
                        'total' => $parsed['line_items'][0]['total'],
                    ],
                ],
            ];
        }
    };
    $this->app->instance(ParsedInvoiceMapper::class, $fakeMapper);

    // Ensure QR path fails so we exercise parser fallback in controller
    $failingQrService = new class extends FiscalReceiptQrService
    {
        public function decodeAndNormalize(UploadedFile $file): array
        {
            throw new RuntimeException('QR not found in test image');
        }
    };
    $this->app->instance(FiscalReceiptQrService::class, $failingQrService);

    Storage::fake(config('filesystems.default', 'public'));

    $file = UploadedFile::fake()->image('ocr-receipt.jpg');

    $response = postJson('/api/v1/receipts/scan', [
        'receipt' => $file,
    ], [
        'company' => $company->id,
    ]);

    $response->assertCreated();

    $bill = Bill::where('company_id', $company->id)
        ->where('bill_number', 'OCR-INV-1')
        ->first();

    expect($bill)->not()->toBeNull();
});

test('receipt scanner respects tenant isolation', function () {
    $companyA = Company::firstOrFail();
    $companyB = Company::factory()->create();

    $fakeParserClient = new class implements InvoiceParserClient
    {
        public function parse(int $companyId, string $filePath, string $originalName, string $from, ?string $subject): array
        {
            return [
                'supplier' => [
                    'name' => 'Tenant Supplier',
                    'tax_id' => 'MK0000000',
                    'email' => 'tenant@example.com',
                ],
                'invoice' => [
                    'number' => 'TENANT-INV-1',
                    'date' => '2025-11-15',
                    'currency' => 13,
                ],
                'totals' => [
                    'total' => 500,
                    'subtotal' => 500,
                    'tax' => 0,
                    'discount' => 0,
                ],
                'line_items' => [],
            ];
        }

        public function ocr(int $companyId, string $filePath, string $originalName): array
        {
            return [];
        }
    };
    $this->app->instance(InvoiceParserClient::class, $fakeParserClient);

    $fakeMapper = new class extends ParsedInvoiceMapper
    {
        public function mapToBillComponents(int $companyId, array $parsed): array
        {
            return [
                'supplier' => $parsed['supplier'],
                'bill' => [
                    'bill_date' => $parsed['invoice']['date'],
                    'due_date' => null,
                    'bill_number' => $parsed['invoice']['number'],
                    'status' => \App\Models\Bill::STATUS_DRAFT,
                    'paid_status' => \App\Models\Bill::PAID_STATUS_UNPAID,
                    'sub_total' => $parsed['totals']['subtotal'],
                    'discount' => 0,
                    'discount_val' => 0,
                    'total' => $parsed['totals']['total'],
                    'tax' => $parsed['totals']['tax'],
                    'due_amount' => $parsed['totals']['total'],
                    'currency_id' => $parsed['invoice']['currency'],
                    'exchange_rate' => 1,
                    'base_total' => $parsed['totals']['total'],
                    'base_discount_val' => 0,
                    'base_sub_total' => $parsed['totals']['subtotal'],
                    'base_tax' => $parsed['totals']['tax'],
                    'base_due_amount' => $parsed['totals']['total'],
                ],
                'items' => [],
            ];
        }
    };
    $this->app->instance(ParsedInvoiceMapper::class, $fakeMapper);

    $failingQrService = new class extends FiscalReceiptQrService
    {
        public function decodeAndNormalize(UploadedFile $file): array
        {
            throw new RuntimeException('QR not found in test image');
        }
    };
    $this->app->instance(FiscalReceiptQrService::class, $failingQrService);

    Storage::fake(config('filesystems.default', 'public'));

    $file = UploadedFile::fake()->image('tenant-receipt.png');

    postJson('/api/v1/receipts/scan', [
        'receipt' => $file,
    ], [
        'company' => $companyA->id,
    ])->assertCreated();

    $billA = Bill::where('company_id', $companyA->id)
        ->where('bill_number', 'TENANT-INV-1')
        ->first();

    $billB = Bill::where('company_id', $companyB->id)
        ->where('bill_number', 'TENANT-INV-1')
        ->first();

    expect($billA)->not()->toBeNull();
    expect($billB)->toBeNull();
});

test('parser fallback generates bill_number when missing', function () {
    $company = Company::firstOrFail();

    $fakeParserClient = new class implements InvoiceParserClient
    {
        public function parse(int $companyId, string $filePath, string $originalName, string $from, ?string $subject): array
        {
            return [
                'supplier' => [
                    'name' => 'No Number Supplier',
                    'tax_id' => 'MK1111111',
                    'email' => 'nonumber@example.com',
                ],
                'invoice' => [
                    'number' => null,
                    'date' => '2025-11-15',
                    'currency' => 13,
                ],
                'totals' => [
                    'total' => 200,
                    'subtotal' => 200,
                    'tax' => 0,
                    'discount' => 0,
                ],
                'line_items' => [],
            ];
        }

        public function ocr(int $companyId, string $filePath, string $originalName): array
        {
            return [];
        }
    };
    $this->app->instance(InvoiceParserClient::class, $fakeParserClient);

    $fakeMapper = new class extends ParsedInvoiceMapper
    {
        public function mapToBillComponents(int $companyId, array $parsed): array
        {
            return [
                'supplier' => $parsed['supplier'],
                'bill' => [
                    'bill_date' => $parsed['invoice']['date'],
                    'due_date' => null,
                    'bill_number' => null,
                    'status' => \App\Models\Bill::STATUS_DRAFT,
                    'paid_status' => \App\Models\Bill::PAID_STATUS_UNPAID,
                    'sub_total' => $parsed['totals']['subtotal'],
                    'discount' => 0,
                    'discount_val' => 0,
                    'total' => $parsed['totals']['total'],
                    'tax' => $parsed['totals']['tax'],
                    'due_amount' => $parsed['totals']['total'],
                    'currency_id' => $parsed['invoice']['currency'],
                    'exchange_rate' => 1,
                    'base_total' => $parsed['totals']['total'],
                    'base_discount_val' => 0,
                    'base_sub_total' => $parsed['totals']['subtotal'],
                    'base_tax' => $parsed['totals']['tax'],
                    'base_due_amount' => $parsed['totals']['total'],
                ],
                'items' => [],
            ];
        }
    };
    $this->app->instance(ParsedInvoiceMapper::class, $fakeMapper);

    $failingQrService = new class extends FiscalReceiptQrService
    {
        public function decodeAndNormalize(UploadedFile $file): array
        {
            throw new RuntimeException('QR not found in test image');
        }
    };
    $this->app->instance(FiscalReceiptQrService::class, $failingQrService);

    Storage::fake(config('filesystems.default', 'public'));

    $file = UploadedFile::fake()->image('no-number-receipt.jpg');

    $response = postJson('/api/v1/receipts/scan', [
        'receipt' => $file,
    ], [
        'company' => $company->id,
    ]);

    $response->assertCreated();

    $bill = Bill::where('company_id', $company->id)->latest()->first();

    expect($bill)->not()->toBeNull();
    expect($bill->bill_number)->not()->toBeNull();
    expect($bill->bill_number)->toStartWith('SCAN-');
})
    ->group('receipt-scanner');
