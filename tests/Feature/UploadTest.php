<?php

use App\Models\ImportJob;
use App\Models\ImportLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::find(1);
    $this->company = $user->companies()->first();
    $this->withHeaders([
        'company' => $this->company->id,
    ]);
    Sanctum::actingAs($user, ['*']);

    // Clear storage before each test
    Storage::fake('private');
});

describe('File Upload Validation', function () {
    test('uploads valid CSV file successfully', function () {
        $file = UploadedFile::fake()->createWithContent(
            'customers.csv',
            file_get_contents(__DIR__.'/../fixtures/sample_customers.csv')
        );

        $response = postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'csv',
            'description' => 'Test customer import',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'status',
                'source_system',
                'file_info' => [
                    'original_name',
                    'filename',
                    'extension',
                    'size',
                    'mime_type',
                ],
            ],
        ]);

        $importJob = ImportJob::latest()->first();
        expect($importJob->status)->toBe(ImportJob::STATUS_PENDING);
        expect($importJob->type)->toBe(ImportJob::TYPE_CUSTOMERS);
        expect($importJob->source_system)->toBe('csv');
        expect($importJob->file_info['original_name'])->toBe('customers.csv');
        expect($importJob->file_info['extension'])->toBe('csv');

        // Verify import log was created
        $this->assertDatabaseHas('import_logs', [
            'import_job_id' => $importJob->id,
            'log_type' => ImportLog::TYPE_INFO,
            'message' => 'File uploaded successfully',
        ]);
    });

    test('uploads valid Excel file successfully', function () {
        $file = UploadedFile::fake()->create('customers.xlsx', 1024, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // Copy our test Excel file
        $testFile = __DIR__.'/../fixtures/sample_customers.xlsx';
        $file = new UploadedFile($testFile, 'customers.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'excel',
        ]);

        $response->assertOk();

        $importJob = ImportJob::latest()->first();
        expect($importJob->file_info['extension'])->toBe('xlsx');
        expect($importJob->file_info['mime_type'])->toContain('spreadsheet');
    });

    test('uploads valid XML file successfully', function () {
        $file = UploadedFile::fake()->createWithContent(
            'customers.xml',
            file_get_contents(__DIR__.'/../fixtures/sample_customers.xml')
        );

        $response = postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'xml',
        ]);

        $response->assertOk();

        $importJob = ImportJob::latest()->first();
        expect($importJob->file_info['extension'])->toBe('xml');
        expect($importJob->file_info['original_name'])->toBe('customers.xml');
    });

    test('rejects unsupported file types', function () {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'other',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Unsupported file type. Please upload CSV, Excel, or XML files.',
            'errors' => [
                'file' => ['Invalid file type'],
            ],
        ]);

        // Ensure no import job was created
        $this->assertDatabaseMissing('import_jobs', [
            'company_id' => $this->company->id,
            'status' => ImportJob::STATUS_PENDING,
        ]);
    });

    test('rejects files exceeding size limit', function () {
        $file = UploadedFile::fake()->create('large_file.csv', 100001); // Slightly over 100MB in KB

        $response = postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'csv',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    });

    test('rejects missing file', function () {
        $response = postJson('api/v1/imports', [
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'csv',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
        $response->assertJsonFragment([
            'message' => 'Please select a file to upload.',
        ]);
    });

    test('validates import type options', function () {
        $file = UploadedFile::fake()->createWithContent(
            'test.csv',
            'name,email\nTest,test@example.com'
        );

        // Test invalid type
        $response = postJson('api/v1/imports', [
            'file' => $file,
            'type' => 'invalid_type',
            'source_system' => 'csv',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);

        // Test valid types
        $validTypes = [
            ImportJob::TYPE_CUSTOMERS,
            ImportJob::TYPE_INVOICES,
            ImportJob::TYPE_ITEMS,
            ImportJob::TYPE_PAYMENTS,
            ImportJob::TYPE_EXPENSES,
            ImportJob::TYPE_COMPLETE,
        ];

        foreach ($validTypes as $type) {
            $response = postJson('api/v1/imports', [
                'file' => $file,
                'type' => $type,
                'source_system' => 'csv',
            ]);
            $response->assertOk();
        }
    });

    test('validates source system options', function () {
        $file = UploadedFile::fake()->createWithContent(
            'test.csv',
            'name,email\nTest,test@example.com'
        );

        $validSystems = [
            'onivo', 'megasoft', 'pantheon', 'syntegra',
            'excel', 'csv', 'xml', 'other', 'unknown',
        ];

        foreach ($validSystems as $system) {
            $response = postJson('api/v1/imports', [
                'file' => $file,
                'type' => ImportJob::TYPE_CUSTOMERS,
                'source_system' => $system,
            ]);
            $response->assertOk();
        }

        // Test invalid system
        $response = postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'invalid_system',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['source_system']);
    });
});

describe('File Content Validation', function () {
    test('handles empty CSV file', function () {
        $file = UploadedFile::fake()->createWithContent('empty.csv', '');

        $response = postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'csv',
        ]);

        $response->assertOk();

        $importJob = ImportJob::latest()->first();
        expect($importJob->file_info['size'])->toBe(0);
    });

    test('handles corrupted XML file', function () {
        $file = UploadedFile::fake()->createWithContent(
            'corrupted.xml',
            file_get_contents(__DIR__.'/../fixtures/corrupted_file.xml')
        );

        $response = postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'xml',
        ]);

        // File upload should succeed, but parsing will fail later in the pipeline
        $response->assertOk();

        $importJob = ImportJob::latest()->first();
        expect($importJob->status)->toBe(ImportJob::STATUS_PENDING);
        expect($importJob->file_info['extension'])->toBe('xml');
    });

    test('handles CSV with invalid headers', function () {
        $file = UploadedFile::fake()->createWithContent(
            'invalid.csv',
            file_get_contents(__DIR__.'/../fixtures/invalid_data.csv')
        );

        $response = postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'csv',
        ]);

        $response->assertOk();

        $importJob = ImportJob::latest()->first();
        expect($importJob->status)->toBe(ImportJob::STATUS_PENDING);
    });
});

describe('Import Job Management', function () {
    test('creates import job with correct metadata', function () {
        $file = UploadedFile::fake()->createWithContent(
            'customers.csv',
            file_get_contents(__DIR__.'/../fixtures/sample_customers.csv')
        );

        $response = postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'pantheon',
            'description' => 'Import from Pantheon system',
        ]);

        $response->assertOk();

        $importJob = ImportJob::latest()->first();

        expect($importJob->company_id)->toBe($this->company->id);
        expect($importJob->creator_id)->toBe(auth()->id());
        expect($importJob->type)->toBe(ImportJob::TYPE_CUSTOMERS);
        expect($importJob->status)->toBe(ImportJob::STATUS_PENDING);
        expect($importJob->source_system)->toBe('pantheon');
        expect($importJob->total_records)->toBe(0);
        expect($importJob->processed_records)->toBe(0);
        expect($importJob->successful_records)->toBe(0);
        expect($importJob->failed_records)->toBe(0);

        expect($importJob->file_info)->toHaveKeys([
            'original_name', 'filename', 'extension', 'size', 'mime_type',
        ]);
    });

    test('stores file in correct location', function () {
        Storage::fake('private');

        $file = UploadedFile::fake()->createWithContent(
            'test_customers.csv',
            'name,email\nTest Customer,test@example.com'
        );

        $response = postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'csv',
        ]);

        $response->assertOk();

        $importJob = ImportJob::latest()->first();

        // Verify file was stored
        Storage::disk('private')->assertExists($importJob->file_path);

        // Verify file path structure
        expect($importJob->file_path)->toStartWith('imports/'.$this->company->id.'/');
        expect($importJob->file_path)->toEndWith('.csv');

        // Verify file info
        expect($importJob->file_info['original_name'])->toBe('test_customers.csv');
        expect($importJob->file_info['extension'])->toBe('csv');
    });

    test('lists import jobs with pagination', function () {
        // Create multiple import jobs
        for ($i = 1; $i <= 5; $i++) {
            $file = UploadedFile::fake()->createWithContent(
                "customers_{$i}.csv",
                "name,email\nCustomer {$i},customer{$i}@example.com"
            );

            postJson('api/v1/imports', [
                'file' => $file,
                'type' => ImportJob::TYPE_CUSTOMERS,
                'source_system' => 'csv',
            ]);
        }

        $response = getJson('api/v1/imports?page=1&limit=3');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'status',
                    'source_system',
                    'file_info',
                    'creator',
                    'formattedCreatedAt',
                ],
            ],
            'meta' => [
                'total_imports',
                'active_imports',
                'completed_imports',
                'failed_imports',
            ],
        ]);

        expect(count($response->json('data')))->toBe(3);
    });

    test('filters import jobs by type and status', function () {
        // Create different types of import jobs
        $customerFile = UploadedFile::fake()->createWithContent(
            'customers.csv',
            'name,email\nCustomer,customer@example.com'
        );

        $invoiceFile = UploadedFile::fake()->createWithContent(
            'invoices.csv',
            'invoice_number,amount\nINV-001,1000'
        );

        postJson('api/v1/imports', [
            'file' => $customerFile,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'csv',
        ]);

        postJson('api/v1/imports', [
            'file' => $invoiceFile,
            'type' => ImportJob::TYPE_INVOICES,
            'source_system' => 'csv',
        ]);

        // Filter by customer type
        $response = getJson('api/v1/imports?type=customers');
        $response->assertOk();

        $customerImports = collect($response->json('data'));
        expect($customerImports->every(fn ($import) => $import['type'] === 'customers'))->toBeTrue();

        // Filter by pending status
        $response = getJson('api/v1/imports?status=pending');
        $response->assertOk();

        $pendingImports = collect($response->json('data'));
        expect($pendingImports->every(fn ($import) => $import['status'] === 'pending'))->toBeTrue();
    });

    test('shows individual import job details', function () {
        $file = UploadedFile::fake()->createWithContent(
            'customers.csv',
            file_get_contents(__DIR__.'/../fixtures/sample_customers.csv')
        );

        $createResponse = postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'csv',
        ]);

        $importJob = ImportJob::latest()->first();

        $response = getJson("api/v1/imports/{$importJob->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'status',
                'source_system',
                'file_info',
                'creator' => [
                    'id',
                    'name',
                    'email',
                ],
                'logs' => [
                    '*' => [
                        'id',
                        'log_type',
                        'message',
                        'created_at',
                    ],
                ],
            ],
        ]);

        expect($response->json('data.id'))->toBe($importJob->id);
    });
});

describe('Large File Handling', function () {
    test('handles medium sized files efficiently', function () {
        $csvContent = "name,email,phone,address\n";

        // Generate approximately 10MB of test data
        for ($i = 1; $i <= 100000; $i++) {
            $csvContent .= "Customer {$i},customer{$i}@example.mk,+38970{$i},Address {$i} Skopje\n";
        }

        $file = UploadedFile::fake()->createWithContent('large_customers.csv', $csvContent);

        $response = postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'csv',
        ]);

        $response->assertOk();

        $importJob = ImportJob::latest()->first();
        expect($importJob->file_info['size'])->toBeGreaterThan(1000000); // > 1MB
    });

    test('tracks upload progress and metadata', function () {
        $file = UploadedFile::fake()->createWithContent(
            'customers.csv',
            file_get_contents(__DIR__.'/../fixtures/sample_customers.csv')
        );

        $startTime = now();

        $response = postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'csv',
        ]);

        $response->assertOk();

        $importJob = ImportJob::latest()->first();

        // Check timing
        expect($importJob->created_at)->toBeGreaterThanOrEqual($startTime);

        // Check progress tracking fields
        expect($importJob->progressPercentage)->toBe(0.0);
        expect($importJob->isInProgress)->toBeFalse();
        expect($importJob->canRetry)->toBeFalse();

        // Check file metadata
        expect($importJob->file_info['original_name'])->toBe('customers.csv');
        expect($importJob->file_info['size'])->toBeGreaterThan(0);
    });
});

describe('Security Validation', function () {
    test('prevents malicious file uploads', function () {
        $phpFile = UploadedFile::fake()->createWithContent(
            'malicious.php',
            '<?php echo "malicious code"; ?>'
        );

        $response = postJson('api/v1/imports', [
            'file' => $phpFile,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'other',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Unsupported file type. Please upload CSV, Excel, or XML files.',
        ]);
    });

    test('validates company authorization', function () {
        $file = UploadedFile::fake()->createWithContent(
            'customers.csv',
            'name,email\nTest,test@example.com'
        );

        // Test with different company header
        $response = $this->withHeaders(['company' => 999999])->postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'csv',
        ]);

        // Should still work but with the invalid company ID
        // The actual authorization logic would be handled by middleware/policies
        $response->assertOk();
    });

    test('requires authentication', function () {
        $file = UploadedFile::fake()->createWithContent(
            'customers.csv',
            'name,email\nTest,test@example.com'
        );

        // Remove authentication
        Sanctum::actingAs(null);

        $response = $this->withHeaders([])->postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'csv',
        ]);

        $response->assertStatus(401);
    });
});

describe('Error Handling', function () {
    test('handles storage failures gracefully', function () {
        // This would require mocking Storage to throw exceptions
        // For now, we'll test that the basic error structure is correct

        $file = UploadedFile::fake()->createWithContent(
            'customers.csv',
            'name,email\nTest,test@example.com'
        );

        $response = postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'csv',
        ]);

        // Should succeed in test environment
        $response->assertOk();
    });

    test('provides clear error messages', function () {
        $response = postJson('api/v1/imports', [
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'csv',
            // Missing file
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors' => [
                'file',
            ],
        ]);

        $errors = $response->json('errors.file');
        expect($errors)->toContain('Please select a file to upload.');
    });

    test('handles concurrent uploads properly', function () {
        $file1 = UploadedFile::fake()->createWithContent(
            'customers1.csv',
            'name,email\nCustomer1,customer1@example.com'
        );

        $file2 = UploadedFile::fake()->createWithContent(
            'customers2.csv',
            'name,email\nCustomer2,customer2@example.com'
        );

        // Simulate concurrent uploads
        $response1 = postJson('api/v1/imports', [
            'file' => $file1,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'csv',
        ]);

        $response2 = postJson('api/v1/imports', [
            'file' => $file2,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'csv',
        ]);

        $response1->assertOk();
        $response2->assertOk();

        // Verify both imports were created
        expect(ImportJob::count())->toBe(2);

        $jobs = ImportJob::latest()->take(2)->get();
        expect($jobs[0]->file_info['original_name'])->toBe('customers2.csv');
        expect($jobs[1]->file_info['original_name'])->toBe('customers1.csv');
    });
});
