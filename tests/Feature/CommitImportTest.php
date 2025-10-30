<?php

use App\Http\Controllers\V1\Admin\MigrationController;
use App\Models\ImportJob;
use App\Models\ImportLog;
use App\Models\ImportTempCustomer;
use App\Models\ImportTempInvoice;
use App\Models\ImportTempItem;
use App\Models\ImportTempPayment;
use App\Models\ImportTempExpense;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\deleteJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::find(1);
    $this->company = $user->companies()->first();
    $this->withHeaders([
        'company' => $this->company->id,
    ]);
    Sanctum::actingAs($user, ['*']);

    Storage::fake('private');
});

describe('Import Mapping Process', function () {
    test('accepts valid field mappings', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_PENDING,
            'type' => ImportJob::TYPE_CUSTOMERS
        ]);

        $mappings = [
            [
                'source_field' => 'name',
                'target_field' => 'name',
                'transformation_type' => 'none',
                'is_required' => true
            ],
            [
                'source_field' => 'email',
                'target_field' => 'email',
                'transformation_type' => 'email',
                'is_required' => true
            ],
            [
                'source_field' => 'phone',
                'target_field' => 'phone',
                'transformation_type' => 'phone',
                'is_required' => false
            ],
            [
                'source_field' => 'vat_number',
                'target_field' => 'vat_number',
                'transformation_type' => 'uppercase',
                'is_required' => false
            ]
        ];

        $response = postJson("api/v1/imports/{$importJob->id}/mapping", [
            'mappings' => $mappings,
            'validation_rules' => [
                [
                    'field' => 'email',
                    'rules' => ['email', 'unique:customers,email']
                ]
            ],
            'skip_duplicates' => true,
            'duplicate_handling' => 'skip'
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'status',
                'mapping_config',
                'validation_rules'
            ]
        ]);

        $importJob->refresh();
        expect($importJob->status)->toBe(ImportJob::STATUS_MAPPING);
        expect($importJob->mapping_config)->toHaveCount(4);
        expect($importJob->validation_rules)->toHaveCount(1);

        // Verify mapping log was created
        $this->assertDatabaseHas('import_logs', [
            'import_job_id' => $importJob->id,
            'log_type' => ImportLog::TYPE_INFO,
            'message' => 'Field mappings updated'
        ]);
    });

    test('validates mapping configuration', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_PENDING,
            'type' => ImportJob::TYPE_CUSTOMERS
        ]);

        // Test duplicate source fields
        $response = postJson("api/v1/imports/{$importJob->id}/mapping", [
            'mappings' => [
                [
                    'source_field' => 'name',
                    'target_field' => 'name',
                    'transformation_type' => 'none'
                ],
                [
                    'source_field' => 'name', // Duplicate source field
                    'target_field' => 'display_name',
                    'transformation_type' => 'none'
                ]
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['mappings']);
    });

    test('validates transformation configurations', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_PENDING,
            'type' => ImportJob::TYPE_INVOICES
        ]);

        // Test date transformation without format
        $response = postJson("api/v1/imports/{$importJob->id}/mapping", [
            'mappings' => [
                [
                    'source_field' => 'invoice_date',
                    'target_field' => 'invoice_date',
                    'transformation_type' => 'date',
                    'transformation_config' => [] // Missing format
                ]
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['mappings.0.transformation_config.format']);

        // Test valid date transformation
        $response = postJson("api/v1/imports/{$importJob->id}/mapping", [
            'mappings' => [
                [
                    'source_field' => 'invoice_date',
                    'target_field' => 'invoice_date',
                    'transformation_type' => 'date',
                    'transformation_config' => [
                        'format' => 'Y-m-d'
                    ]
                ]
            ]
        ]);

        $response->assertOk();
    });

    test('handles currency transformation for Macedonia denars', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_PENDING,
            'type' => ImportJob::TYPE_INVOICES
        ]);

        $response = postJson("api/v1/imports/{$importJob->id}/mapping", [
            'mappings' => [
                [
                    'source_field' => 'amount_mkd',
                    'target_field' => 'total',
                    'transformation_type' => 'currency',
                    'transformation_config' => [
                        'from_currency' => 'MKD',
                        'to_currency' => 'EUR'
                    ]
                ]
            ]
        ]);

        $response->assertOk();
        
        $importJob->refresh();
        expect($importJob->mapping_config[0]['transformation_config']['from_currency'])->toBe('MKD');
    });

    test('prevents mapping on invalid status', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_COMPLETED, // Invalid status for mapping
            'type' => ImportJob::TYPE_CUSTOMERS
        ]);

        $response = postJson("api/v1/imports/{$importJob->id}/mapping", [
            'mappings' => [
                [
                    'source_field' => 'name',
                    'target_field' => 'name',
                    'transformation_type' => 'none'
                ]
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Import job is not in a state that allows mapping changes.'
        ]);
    });
});

describe('Import Validation Process', function () {
    test('starts validation process', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_MAPPING,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'mapping_config' => [
                [
                    'source_field' => 'name',
                    'target_field' => 'name',
                    'transformation_type' => 'none'
                ]
            ]
        ]);

        $response = postJson("api/v1/imports/{$importJob->id}/validate");

        $response->assertOk();
        $response->assertJson([
            'message' => 'Validation started. Check progress for updates.'
        ]);

        $importJob->refresh();
        expect($importJob->status)->toBe(ImportJob::STATUS_VALIDATING);

        // Verify validation log was created
        $this->assertDatabaseHas('import_logs', [
            'import_job_id' => $importJob->id,
            'log_type' => ImportLog::TYPE_INFO,
            'message' => 'Data validation started'
        ]);
    });

    test('prevents validation on invalid status', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_PENDING,
            'type' => ImportJob::TYPE_CUSTOMERS
        ]);

        $response = postJson("api/v1/imports/{$importJob->id}/validate");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Import job is not ready for validation.'
        ]);
    });

    test('tracks validation progress', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_VALIDATING,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'total_records' => 100,
            'processed_records' => 50,
            'successful_records' => 45,
            'failed_records' => 5
        ]);

        $response = getJson("api/v1/imports/{$importJob->id}/progress");

        $response->assertOk();
        $response->assertJsonStructure([
            'import_job_id',
            'status',
            'progress_percentage',
            'total_records',
            'processed_records',
            'successful_records',
            'failed_records',
            'duration',
            'is_in_progress',
            'can_retry',
            'recent_logs'
        ]);

        expect($response->json('progress_percentage'))->toBe(50.0);
        expect($response->json('is_in_progress'))->toBeTrue();
    });
});

describe('Import Commit Process', function () {
    test('commits validated import successfully', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_VALIDATING,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'total_records' => 5,
            'processed_records' => 5,
            'successful_records' => 5,
            'failed_records' => 0
        ]);

        $response = postJson("api/v1/imports/{$importJob->id}/commit");

        $response->assertOk();
        $response->assertJson([
            'message' => 'Commit started. This may take several minutes for large imports.'
        ]);

        $importJob->refresh();
        expect($importJob->status)->toBe(ImportJob::STATUS_COMMITTING);

        // Verify commit log was created
        $this->assertDatabaseHas('import_logs', [
            'import_job_id' => $importJob->id,
            'log_type' => ImportLog::TYPE_INFO,
            'message' => 'Data commit started'
        ]);
    });

    test('prevents commit on invalid status', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_MAPPING,
            'type' => ImportJob::TYPE_CUSTOMERS
        ]);

        $response = postJson("api/v1/imports/{$importJob->id}/commit");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Import job must be validated before committing.'
        ]);
    });

    test('prevents commit with validation errors', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_VALIDATING,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'total_records' => 10,
            'processed_records' => 10,
            'successful_records' => 8,
            'failed_records' => 2
        ]);

        // Create error logs
        ImportLog::factory()->create([
            'import_job_id' => $importJob->id,
            'log_type' => ImportLog::TYPE_ERROR,
            'message' => 'Invalid email format'
        ]);

        $response = postJson("api/v1/imports/{$importJob->id}/commit");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Import has validation errors. Use force_commit=true to proceed anyway.',
            'errors_count' => 1
        ]);
    });

    test('allows force commit with errors', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_VALIDATING,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'total_records' => 10,
            'processed_records' => 10,
            'successful_records' => 8,
            'failed_records' => 2
        ]);

        // Create error logs
        ImportLog::factory()->create([
            'import_job_id' => $importJob->id,
            'log_type' => ImportLog::TYPE_ERROR,
            'message' => 'Invalid email format'
        ]);

        $response = postJson("api/v1/imports/{$importJob->id}/commit", [
            'force_commit' => true
        ]);

        $response->assertOk();
        
        $importJob->refresh();
        expect($importJob->status)->toBe(ImportJob::STATUS_COMMITTING);
    });

    test('handles large import commits', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_VALIDATING,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'total_records' => 50000,
            'processed_records' => 50000,
            'successful_records' => 49500,
            'failed_records' => 500
        ]);

        $response = postJson("api/v1/imports/{$importJob->id}/commit", [
            'force_commit' => true
        ]);

        $response->assertOk();
        
        $importJob->refresh();
        expect($importJob->status)->toBe(ImportJob::STATUS_COMMITTING);
        expect($importJob->total_records)->toBe(50000);
    });
});

describe('Import Progress and Monitoring', function () {
    test('provides real-time progress updates', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_COMMITTING,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'total_records' => 1000,
            'processed_records' => 750,
            'successful_records' => 700,
            'failed_records' => 50,
            'started_at' => now()->subMinutes(10),
        ]);

        // Add some recent logs
        ImportLog::factory()->create([
            'import_job_id' => $importJob->id,
            'log_type' => ImportLog::TYPE_INFO,
            'message' => 'Processing batch 1-100',
            'created_at' => now()->subMinutes(2)
        ]);

        ImportLog::factory()->create([
            'import_job_id' => $importJob->id,
            'log_type' => ImportLog::TYPE_WARNING,
            'message' => 'Skipped invalid record at line 250',
            'created_at' => now()->subMinute()
        ]);

        $response = getJson("api/v1/imports/{$importJob->id}/progress");

        $response->assertOk();
        
        $data = $response->json();
        expect($data['progress_percentage'])->toBe(75.0);
        expect($data['is_in_progress'])->toBeTrue();
        expect($data['recent_logs'])->toHaveCount(2);
        expect($data['duration'])->not->toBeNull();
    });

    test('filters import logs by type', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_COMPLETED,
            'type' => ImportJob::TYPE_CUSTOMERS
        ]);

        // Create logs of different types
        ImportLog::factory()->create([
            'import_job_id' => $importJob->id,
            'log_type' => ImportLog::TYPE_INFO,
            'message' => 'Import started'
        ]);

        ImportLog::factory()->create([
            'import_job_id' => $importJob->id,
            'log_type' => ImportLog::TYPE_ERROR,
            'message' => 'Invalid email format'
        ]);

        ImportLog::factory()->create([
            'import_job_id' => $importJob->id,
            'log_type' => ImportLog::TYPE_WARNING,
            'message' => 'Duplicate customer found'
        ]);

        // Filter by error logs
        $response = getJson("api/v1/imports/{$importJob->id}/logs?log_type=error");
        $response->assertOk();
        
        $logs = $response->json('data');
        expect($logs)->toHaveCount(1);
        expect($logs[0]['log_type'])->toBe(ImportLog::TYPE_ERROR);

        // Filter by info logs
        $response = getJson("api/v1/imports/{$importJob->id}/logs?log_type=info");
        $response->assertOk();
        
        $logs = $response->json('data');
        expect($logs)->toHaveCount(1);
        expect($logs[0]['log_type'])->toBe(ImportLog::TYPE_INFO);

        // Get all logs
        $response = getJson("api/v1/imports/{$importJob->id}/logs");
        $response->assertOk();
        
        $logs = $response->json('data');
        expect($logs)->toHaveCount(3);
    });

    test('paginates import logs', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_COMPLETED,
            'type' => ImportJob::TYPE_CUSTOMERS
        ]);

        // Create 25 logs
        for ($i = 1; $i <= 25; $i++) {
            ImportLog::factory()->create([
                'import_job_id' => $importJob->id,
                'log_type' => ImportLog::TYPE_INFO,
                'message' => "Log message {$i}"
            ]);
        }

        $response = getJson("api/v1/imports/{$importJob->id}/logs?limit=10&page=1");
        $response->assertOk();
        
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total'
            ],
            'links'
        ]);

        expect($response->json('meta.total'))->toBe(25);
        expect($response->json('meta.per_page'))->toBe(10);
        expect(count($response->json('data')))->toBe(10);
    });
});

describe('Import Cleanup and Deletion', function () {
    test('deletes import job and associated data', function () {
        Storage::fake('private');
        
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_COMPLETED,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'file_path' => 'imports/test/sample.csv'
        ]);

        // Create a fake file
        Storage::disk('private')->put($importJob->file_path, 'test content');

        // Create associated temp data
        ImportTempCustomer::factory()->create(['import_job_id' => $importJob->id]);
        ImportLog::factory()->create(['import_job_id' => $importJob->id]);

        $response = deleteJson("api/v1/imports/{$importJob->id}");

        $response->assertOk();
        $response->assertJson([
            'message' => 'Import job deleted successfully.'
        ]);

        // Verify job was deleted
        $this->assertDatabaseMissing('import_jobs', ['id' => $importJob->id]);
        
        // Verify associated data was deleted
        $this->assertDatabaseMissing('import_temp_customers', ['import_job_id' => $importJob->id]);
        $this->assertDatabaseMissing('import_logs', ['import_job_id' => $importJob->id]);
        
        // Verify file was deleted
        Storage::disk('private')->assertMissing($importJob->file_path);
    });

    test('prevents deletion of in-progress imports', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_COMMITTING, // In progress
            'type' => ImportJob::TYPE_CUSTOMERS
        ]);

        $response = deleteJson("api/v1/imports/{$importJob->id}");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Cannot delete import job while it is in progress.'
        ]);

        // Verify job still exists
        $this->assertDatabaseHas('import_jobs', ['id' => $importJob->id]);
    });

    test('handles missing files during deletion', function () {
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_FAILED,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'file_path' => 'imports/test/nonexistent.csv'
        ]);

        $response = deleteJson("api/v1/imports/{$importJob->id}");

        // Should still succeed even if file doesn't exist
        $response->assertOk();
        
        // Verify job was deleted
        $this->assertDatabaseMissing('import_jobs', ['id' => $importJob->id]);
    });
});

describe('End-to-End Import Flow', function () {
    test('completes full customer import workflow', function () {
        
        // 1. Upload file
        $file = UploadedFile::fake()->createWithContent(
            'customers.csv',
            file_get_contents(__DIR__ . '/../fixtures/sample_customers.csv')
        );

        $uploadResponse = postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'csv'
        ]);

        $uploadResponse->assertOk();
        $importJob = ImportJob::latest()->first();

        // 2. Configure mappings
        $mappingResponse = postJson("api/v1/imports/{$importJob->id}/mapping", [
            'mappings' => [
                [
                    'source_field' => 'name',
                    'target_field' => 'name',
                    'transformation_type' => 'trim',
                    'is_required' => true
                ],
                [
                    'source_field' => 'email',
                    'target_field' => 'email',
                    'transformation_type' => 'lowercase',
                    'is_required' => true
                ],
                [
                    'source_field' => 'vat_number',
                    'target_field' => 'vat_number',
                    'transformation_type' => 'uppercase',
                    'is_required' => false
                ]
            ],
            'validation_rules' => [
                [
                    'field' => 'email',
                    'rules' => ['email', 'unique:customers,email']
                ]
            ]
        ]);

        $mappingResponse->assertOk();

        // 3. Start validation
        $validationResponse = postJson("api/v1/imports/{$importJob->id}/validate");
        $validationResponse->assertOk();

        // 4. Check progress
        $progressResponse = getJson("api/v1/imports/{$importJob->id}/progress");
        $progressResponse->assertOk();

        // 5. Simulate successful validation by updating status
        $importJob->update([
            'status' => ImportJob::STATUS_VALIDATING,
            'total_records' => 5,
            'processed_records' => 5,
            'successful_records' => 5,
            'failed_records' => 0
        ]);

        // 6. Commit import
        $commitResponse = postJson("api/v1/imports/{$importJob->id}/commit");
        $commitResponse->assertOk();

        // Verify final state
        $importJob->refresh();
        expect($importJob->status)->toBe(ImportJob::STATUS_COMMITTING);
        expect($importJob->mapping_config)->toHaveCount(3);
        expect($importJob->validation_rules)->toHaveCount(1);

        // Verify logs were created for each step
        $logs = $importJob->logs()->orderBy('created_at')->get();
        expect($logs->where('message', 'File uploaded successfully'))->toHaveCount(1);
        expect($logs->where('message', 'Field mappings updated'))->toHaveCount(1);
        expect($logs->where('message', 'Data validation started'))->toHaveCount(1);
        expect($logs->where('message', 'Data commit started'))->toHaveCount(1);
    });

    test('handles complex invoice import with multiple currencies', function () {
        
        // Upload invoice file
        $file = UploadedFile::fake()->createWithContent(
            'invoices.csv',
            file_get_contents(__DIR__ . '/../fixtures/sample_invoices.csv')
        );

        $uploadResponse = postJson('api/v1/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_INVOICES,
            'source_system' => 'pantheon'
        ]);

        $uploadResponse->assertOk();
        $importJob = ImportJob::latest()->first();

        // Configure complex mappings with currency conversion
        $mappingResponse = postJson("api/v1/imports/{$importJob->id}/mapping", [
            'mappings' => [
                [
                    'source_field' => 'invoice_number',
                    'target_field' => 'invoice_number',
                    'transformation_type' => 'uppercase',
                    'is_required' => true
                ],
                [
                    'source_field' => 'total',
                    'target_field' => 'total',
                    'transformation_type' => 'decimal',
                    'transformation_config' => [
                        'decimal_places' => 2
                    ],
                    'is_required' => true
                ],
                [
                    'source_field' => 'invoice_date',
                    'target_field' => 'invoice_date',
                    'transformation_type' => 'date',
                    'transformation_config' => [
                        'format' => 'Y-m-d'
                    ],
                    'is_required' => true
                ],
                [
                    'source_field' => 'currency',
                    'target_field' => 'currency_code',
                    'transformation_type' => 'uppercase',
                    'is_required' => true
                ]
            ],
            'validation_rules' => [
                [
                    'field' => 'invoice_number',
                    'rules' => ['unique:invoices,invoice_number']
                ],
                [
                    'field' => 'currency_code',
                    'rules' => ['in:MKD,EUR,USD,GBP']
                ]
            ],
            'duplicate_handling' => 'skip'
        ]);

        $mappingResponse->assertOk();

        // Verify mapping configuration
        $importJob->refresh();
        expect($importJob->mapping_config)->toHaveCount(4);
        expect($importJob->mapping_config[1]['transformation_config']['decimal_places'])->toBe(2);
        expect($importJob->mapping_config[2]['transformation_config']['format'])->toBe('Y-m-d');
    });

    test('handles failed import recovery', function () {
        
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => auth()->id(),
            'status' => ImportJob::STATUS_FAILED,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'error_message' => 'Database connection timeout',
            'total_records' => 100,
            'processed_records' => 50,
            'successful_records' => 40,
            'failed_records' => 10
        ]);

        // Create error logs
        ImportLog::factory()->create([
            'import_job_id' => $importJob->id,
            'log_type' => ImportLog::TYPE_ERROR,
            'message' => 'Database connection timeout',
            'details' => ['error_code' => 'DB_TIMEOUT']
        ]);

        // Check that job can be retried
        expect($importJob->canRetry)->toBeTrue();
        expect($importJob->canBeRetried())->toBeTrue();

        // Verify error information is available
        $response = getJson("api/v1/imports/{$importJob->id}");
        $response->assertOk();
        
        $data = $response->json('data');
        expect($data['status'])->toBe(ImportJob::STATUS_FAILED);
        expect($data['error_message'])->toBe('Database connection timeout');

        // Verify logs contain error details
        $errorLogs = $importJob->logs()->where('log_type', ImportLog::TYPE_ERROR)->get();
        expect($errorLogs)->toHaveCount(1);
        expect($errorLogs->first()->details['error_code'])->toBe('DB_TIMEOUT');
    });
});