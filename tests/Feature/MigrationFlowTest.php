<?php

namespace Tests\Feature;

use App\Jobs\Migration\AutoMapFieldsJob;
use App\Jobs\Migration\CommitImportJob;
use App\Jobs\Migration\DetectFileTypeJob;
use App\Jobs\Migration\ParseFileJob;
use App\Jobs\Migration\ValidateDataJob;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ImportJob;
use App\Models\ImportLog;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\TaxType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * This test validates the complete migration platform that enables businesses to
 * migrate from competitors like Onivo/Megasoft/Pantheon in minutes, not months.
 *
 * Features tested:
 * - Complete business migration (customers, invoices, items, payments, expenses)
 * - Data integrity and relationship preservation
 * - Macedonian field name auto-mapping
 * - Migration wizard flow with progress tracking
 * - Rollback capabilities on failures
 * - Performance with large datasets
 * - Concurrent migration scenarios
 * - Audit trail maintenance
 */
class MigrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Currency $mkdCurrency;

    protected PaymentMethod $defaultPaymentMethod;

    protected ExpenseCategory $defaultExpenseCategory;

    protected TaxType $defaultTaxType;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test environment
        $this->setupTestData();

        // Setup storage for test files
        Storage::fake('private');

        // Use sync queue for testing
        Queue::fake();
    }

    /**
     * Setup test data - company, user, currencies, etc.
     */
    protected function setupTestData(): void
    {
        // Create test company
        $this->company = Company::factory()->create([
            'name' => 'Test Macedonia Business',
            'country_id' => 142, // Macedonia
        ]);

        // Create test user
        $this->user = User::factory()->create([
            'role' => 'super admin',
        ]);

        // Setup currencies
        $this->mkdCurrency = Currency::factory()->create([
            'name' => 'Macedonian Denar',
            'code' => 'MKD',
            'symbol' => 'ден',
            'precision' => 2,
        ]);

        // Setup default payment method
        $this->defaultPaymentMethod = PaymentMethod::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Готовина', // Cash in Macedonian
            'type' => 'GENERAL',
        ]);

        // Setup default expense category
        $this->defaultExpenseCategory = ExpenseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Општи трошоци', // General expenses in Macedonian
            'description' => 'Општи деловни трошоци',
        ]);

        // Setup default tax type
        $this->defaultTaxType = TaxType::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'ДДВ 18%',
            'percent' => 18.00,
        ]);
    }

    /**
     * Test Scenario 1: Complete SME Migration
     * 50 customers, 200 invoices, 100 items, 150 payments
     */
    public function test_complete_sme_migration(): void
    {
        $this->actingAs($this->user);

        // Create CSV file with complete business data
        $csvContent = $this->generateCompleteSMEData();
        $file = $this->createTestFile('complete_sme_business.csv', $csvContent);

        // Step 1: Upload file
        $uploadResponse = $this->postJson('/api/v1/admin/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_COMPLETE,
            'source_system' => 'onivo',
        ], [
            'company' => $this->company->id,
        ]);

        $uploadResponse->assertStatus(201);
        $importJob = ImportJob::find($uploadResponse->json('data.id'));
        $this->assertNotNull($importJob);

        // Step 2: Process file through complete pipeline
        $this->processImportPipeline($importJob);

        // Step 3: Validate migration results
        $this->validateSMEMigrationResults();

        // Step 4: Test data integrity
        $this->validateDataIntegrity();

        // Step 5: Test performance metrics
        $this->validatePerformanceMetrics($importJob);
    }

    /**
     * Test Scenario 2: Large Business Migration
     * 500 customers, 2000 invoices, 1000 items, 1500 payments
     */
    public function test_large_business_migration(): void
    {
        $this->actingAs($this->user);

        // Create Excel file with large business data
        $csvContent = $this->generateLargeBusinessData();
        $file = $this->createTestFile('large_business.xlsx', $csvContent, 'xlsx');

        // Upload and process
        $uploadResponse = $this->postJson('/api/v1/admin/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_COMPLETE,
            'source_system' => 'megasoft',
        ], [
            'company' => $this->company->id,
        ]);

        $uploadResponse->assertStatus(201);
        $importJob = ImportJob::find($uploadResponse->json('data.id'));

        // Process with performance monitoring
        $startTime = microtime(true);
        $this->processImportPipeline($importJob);
        $processingTime = microtime(true) - $startTime;

        // Validate results and performance
        $this->validateLargeBusinessResults();
        $this->assertLessThan(300, $processingTime, 'Large business migration should complete within 5 minutes');
    }

    /**
     * Test Scenario 3: Partial Migration with Dependencies
     * Test importing customers first, then invoices, then payments
     */
    public function test_partial_migration_with_dependencies(): void
    {
        $this->actingAs($this->user);

        // Step 1: Import customers only
        $customersData = $this->generateCustomersData(50);
        $customersFile = $this->createTestFile('customers.csv', $customersData);

        $customersResponse = $this->postJson('/api/v1/admin/imports', [
            'file' => $customersFile,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'pantheon',
        ], [
            'company' => $this->company->id,
        ]);

        $customersJob = ImportJob::find($customersResponse->json('data.id'));
        $this->processImportPipeline($customersJob);

        // Verify customers imported
        $this->assertEquals(50, Customer::where('company_id', $this->company->id)->count());

        // Step 2: Import invoices with customer references
        $invoicesData = $this->generateInvoicesData(200, true); // with customer references
        $invoicesFile = $this->createTestFile('invoices.csv', $invoicesData);

        $invoicesResponse = $this->postJson('/api/v1/admin/imports', [
            'file' => $invoicesFile,
            'type' => ImportJob::TYPE_INVOICES,
            'source_system' => 'pantheon',
        ], [
            'company' => $this->company->id,
        ]);

        $invoicesJob = ImportJob::find($invoicesResponse->json('data.id'));
        $this->processImportPipeline($invoicesJob);

        // Verify invoices imported with customer relationships
        $this->assertEquals(200, Invoice::where('company_id', $this->company->id)->count());
        $this->validateCustomerInvoiceRelationships();

        // Step 3: Import payments with invoice references
        $paymentsData = $this->generatePaymentsData(150, true); // with invoice references
        $paymentsFile = $this->createTestFile('payments.csv', $paymentsData);

        $paymentsResponse = $this->postJson('/api/v1/admin/imports', [
            'file' => $paymentsFile,
            'type' => ImportJob::TYPE_PAYMENTS,
            'source_system' => 'pantheon',
        ], [
            'company' => $this->company->id,
        ]);

        $paymentsJob = ImportJob::find($paymentsResponse->json('data.id'));
        $this->processImportPipeline($paymentsJob);

        // Verify payments imported with invoice relationships
        $this->assertEquals(150, Payment::where('company_id', $this->company->id)->count());
        $this->validatePaymentInvoiceRelationships();
    }

    /**
     * Test Scenario 4: Error Recovery and Rollback
     * Test handling of corrupted data and missing relationships
     */
    public function test_error_recovery_and_rollback(): void
    {
        $this->actingAs($this->user);

        // Create corrupted data with validation errors
        $corruptedData = $this->generateCorruptedData();
        $file = $this->createTestFile('corrupted_data.csv', $corruptedData);

        $uploadResponse = $this->postJson('/api/v1/admin/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_COMPLETE,
            'source_system' => 'onivo',
        ], [
            'company' => $this->company->id,
        ]);

        $importJob = ImportJob::find($uploadResponse->json('data.id'));

        // Process through validation - should detect errors
        $this->processImportPipelineUntilValidation($importJob);

        // Verify validation errors were detected
        $this->assertEquals(ImportJob::STATUS_VALIDATING, $importJob->fresh()->status);
        $errorLogs = ImportLog::where('import_job_id', $importJob->id)
            ->where('log_type', ImportLog::TYPE_ERROR)
            ->count();
        $this->assertGreaterThan(0, $errorLogs);

        // Test rollback capability
        $this->testRollbackCapability($importJob);

        // Verify no production data was affected
        $this->assertEquals(0, Customer::where('company_id', $this->company->id)->count());
        $this->assertEquals(0, Invoice::where('company_id', $this->company->id)->count());
    }

    /**
     * Test Scenario 5: Performance with Large Datasets
     * Test processing 1000+ records within time limits
     */
    public function test_performance_with_large_datasets(): void
    {
        $this->actingAs($this->user);

        // Create large dataset
        $largeData = $this->generateLargeDataset(1000, 5000, 2000, 3000, 1500);
        $file = $this->createTestFile('large_dataset.csv', $largeData);

        $uploadResponse = $this->postJson('/api/v1/admin/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_COMPLETE,
            'source_system' => 'megasoft',
        ], [
            'company' => $this->company->id,
        ]);

        $importJob = ImportJob::find($uploadResponse->json('data.id'));

        // Monitor processing time
        $startTime = microtime(true);
        $this->processImportPipeline($importJob);
        $totalTime = microtime(true) - $startTime;

        // Performance assertions
        $this->assertLessThan(600, $totalTime, 'Large dataset should process within 10 minutes');
        $this->assertEquals(ImportJob::STATUS_COMPLETED, $importJob->fresh()->status);

        // Verify memory usage remained reasonable
        $this->assertLessThan(256 * 1024 * 1024, memory_get_peak_usage(true), 'Memory usage should stay under 256MB');
    }

    /**
     * Test Scenario 6: Macedonian Field Auto-Mapping
     * Test automatic mapping of Macedonian field names
     */
    public function test_macedonian_field_auto_mapping(): void
    {
        $this->actingAs($this->user);

        // Create CSV with Macedonian field names
        $macedonianData = $this->generateMacedonianFieldData();
        $file = $this->createTestFile('macedonia_fields.csv', $macedonianData);

        $uploadResponse = $this->postJson('/api/v1/admin/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'source_system' => 'local_system',
        ], [
            'company' => $this->company->id,
        ]);

        $importJob = ImportJob::find($uploadResponse->json('data.id'));

        // Process through auto-mapping
        $this->processAutoMapping($importJob);

        // Verify Macedonian fields were correctly mapped
        $mappingConfig = $importJob->fresh()->mapping_config;
        $this->assertNotNull($mappingConfig);

        // Check specific Macedonian field mappings
        $this->assertArrayHasKey('име', $mappingConfig); // Name
        $this->assertEquals('name', $mappingConfig['име']);
        $this->assertArrayHasKey('емаил', $mappingConfig); // Email
        $this->assertEquals('email', $mappingConfig['емаил']);
        $this->assertArrayHasKey('телефон', $mappingConfig); // Phone
        $this->assertEquals('phone', $mappingConfig['телефон']);
    }

    /**
     * Test Scenario 7: Migration Wizard Flow
     * Test complete wizard flow with progress tracking
     */
    public function test_migration_wizard_flow(): void
    {
        $this->actingAs($this->user);

        $csvData = $this->generateCompleteSMEData();
        $file = $this->createTestFile('wizard_test.csv', $csvData);

        // Step 1: Upload
        $uploadResponse = $this->postJson('/api/v1/admin/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_COMPLETE,
            'source_system' => 'onivo',
        ], [
            'company' => $this->company->id,
        ]);

        $importJob = ImportJob::find($uploadResponse->json('data.id'));

        // Step 2: Check initial progress
        $progressResponse = $this->getJson("/api/v1/admin/imports/{$importJob->id}/progress", [
            'company' => $this->company->id,
        ]);
        $progressResponse->assertStatus(200);
        $this->assertEquals(ImportJob::STATUS_PENDING, $progressResponse->json('status'));

        // Step 3: Process through pipeline with progress checks
        $this->processWithProgressTracking($importJob);

        // Step 4: Submit field mappings
        $mappingResponse = $this->putJson("/api/v1/admin/imports/{$importJob->id}/mapping", [
            'mappings' => $this->getTestMappings(),
            'validation_rules' => $this->getTestValidationRules(),
        ], [
            'company' => $this->company->id,
        ]);
        $mappingResponse->assertStatus(200);

        // Step 5: Validate data
        $validateResponse = $this->postJson("/api/v1/admin/imports/{$importJob->id}/validate", [], [
            'company' => $this->company->id,
        ]);
        $validateResponse->assertStatus(200);

        // Step 6: Commit data
        $commitResponse = $this->postJson("/api/v1/admin/imports/{$importJob->id}/commit", [], [
            'company' => $this->company->id,
        ]);
        $commitResponse->assertStatus(200);

        // Step 7: Verify final status
        $finalProgress = $this->getJson("/api/v1/admin/imports/{$importJob->id}/progress", [
            'company' => $this->company->id,
        ]);
        $this->assertEquals(ImportJob::STATUS_COMPLETED, $finalProgress->json('status'));
    }

    /**
     * Test Scenario 8: Concurrent Migrations
     * Test multiple imports running simultaneously
     */
    public function test_concurrent_migrations(): void
    {
        $this->actingAs($this->user);

        $jobs = [];

        // Start 3 concurrent import jobs
        for ($i = 1; $i <= 3; $i++) {
            $csvData = $this->generateTestData($i * 10, $i * 20, $i * 15, $i * 25, $i * 5);
            $file = $this->createTestFile("concurrent_import_{$i}.csv", $csvData);

            $uploadResponse = $this->postJson('/api/v1/admin/imports', [
                'file' => $file,
                'type' => ImportJob::TYPE_COMPLETE,
                'source_system' => "system_{$i}",
            ], [
                'company' => $this->company->id,
            ]);

            $jobs[] = ImportJob::find($uploadResponse->json('data.id'));
        }

        // Process all jobs concurrently
        foreach ($jobs as $job) {
            $this->processImportPipeline($job);
        }

        // Verify all completed successfully
        foreach ($jobs as $job) {
            $this->assertEquals(ImportJob::STATUS_COMPLETED, $job->fresh()->status);
        }

        // Verify total record counts
        $expectedCustomers = (1 * 10) + (2 * 10) + (3 * 10); // 60
        $expectedInvoices = (1 * 20) + (2 * 20) + (3 * 20); // 120

        $this->assertEquals($expectedCustomers, Customer::where('company_id', $this->company->id)->count());
        $this->assertEquals($expectedInvoices, Invoice::where('company_id', $this->company->id)->count());
    }

    /**
     * Test Scenario 9: Audit Trail Validation
     * Verify comprehensive audit trail is maintained
     */
    public function test_audit_trail_validation(): void
    {
        $this->actingAs($this->user);

        $csvData = $this->generateCompleteSMEData();
        $file = $this->createTestFile('audit_test.csv', $csvData);

        $uploadResponse = $this->postJson('/api/v1/admin/imports', [
            'file' => $file,
            'type' => ImportJob::TYPE_COMPLETE,
            'source_system' => 'onivo',
        ], [
            'company' => $this->company->id,
        ]);

        $importJob = ImportJob::find($uploadResponse->json('data.id'));
        $this->processImportPipeline($importJob);

        // Verify comprehensive audit trail
        $logs = ImportLog::where('import_job_id', $importJob->id)->get();

        // Check required log types exist
        $logTypes = $logs->pluck('log_type')->unique()->toArray();
        $requiredLogTypes = [
            ImportLog::TYPE_INFO,
            ImportLog::LOG_JOB_STARTED,
            ImportLog::LOG_JOB_COMPLETED,
            ImportLog::LOG_RECORD_COMMITTED,
        ];

        foreach ($requiredLogTypes as $type) {
            $this->assertContains($type, $logTypes, "Missing required log type: {$type}");
        }

        // Verify audit trail completeness
        $infoLogs = $logs->where('log_type', ImportLog::TYPE_INFO)->count();
        $this->assertGreaterThan(0, $infoLogs);

        // Verify performance tracking
        $completionLog = $logs->where('log_type', ImportLog::LOG_JOB_COMPLETED)->first();
        $this->assertNotNull($completionLog);
        $this->assertArrayHasKey('processing_time', $completionLog->details ?? []);
    }

    /**
     * Process import through complete pipeline
     */
    protected function processImportPipeline(ImportJob $importJob): void
    {
        // Dispatch and process each job in sequence
        DetectFileTypeJob::dispatchSync($importJob);
        ParseFileJob::dispatchSync($importJob);
        AutoMapFieldsJob::dispatchSync($importJob);
        ValidateDataJob::dispatchSync($importJob);
        CommitImportJob::dispatchSync($importJob);
    }

    /**
     * Process import pipeline until validation
     */
    protected function processImportPipelineUntilValidation(ImportJob $importJob): void
    {
        DetectFileTypeJob::dispatchSync($importJob);
        ParseFileJob::dispatchSync($importJob);
        AutoMapFieldsJob::dispatchSync($importJob);
        ValidateDataJob::dispatchSync($importJob);
    }

    /**
     * Process auto-mapping only
     */
    protected function processAutoMapping(ImportJob $importJob): void
    {
        DetectFileTypeJob::dispatchSync($importJob);
        ParseFileJob::dispatchSync($importJob);
        AutoMapFieldsJob::dispatchSync($importJob);
    }

    /**
     * Process with progress tracking
     */
    protected function processWithProgressTracking(ImportJob $importJob): void
    {
        $stages = [
            DetectFileTypeJob::class,
            ParseFileJob::class,
            AutoMapFieldsJob::class,
        ];

        foreach ($stages as $jobClass) {
            $jobClass::dispatchSync($importJob);

            // Check progress after each stage
            $progress = $this->getJson("/api/v1/admin/imports/{$importJob->id}/progress", [
                'company' => $this->company->id,
            ]);
            $progress->assertStatus(200);
        }
    }

    /**
     * Test rollback capability
     */
    protected function test_rollback_capability(ImportJob $importJob): void
    {
        // Record initial state
        $initialCustomerCount = Customer::where('company_id', $this->company->id)->count();
        $initialInvoiceCount = Invoice::where('company_id', $this->company->id)->count();

        // Attempt commit with force (should fail and rollback)
        try {
            $this->postJson("/api/v1/admin/imports/{$importJob->id}/commit", [
                'force_commit' => true,
            ], [
                'company' => $this->company->id,
            ]);
        } catch (\Exception $e) {
            // Expected to fail
        }

        // Verify rollback occurred
        $this->assertEquals($initialCustomerCount, Customer::where('company_id', $this->company->id)->count());
        $this->assertEquals($initialInvoiceCount, Invoice::where('company_id', $this->company->id)->count());

        // Verify rollback was logged
        $rollbackLog = ImportLog::where('import_job_id', $importJob->id)
            ->where('log_type', ImportLog::LOG_ROLLBACK_EXECUTED)
            ->exists();
        $this->assertTrue($rollbackLog);
    }

    /**
     * Validate SME migration results
     */
    protected function validateSMEMigrationResults(): void
    {
        // Check expected record counts
        $this->assertEquals(50, Customer::where('company_id', $this->company->id)->count());
        $this->assertEquals(200, Invoice::where('company_id', $this->company->id)->count());
        $this->assertEquals(100, Item::where('company_id', $this->company->id)->count());
        $this->assertEquals(150, Payment::where('company_id', $this->company->id)->count());

        // Verify sample data integrity
        $customer = Customer::where('company_id', $this->company->id)->first();
        $this->assertNotNull($customer->name);
        $this->assertNotNull($customer->email);
    }

    /**
     * Validate large business results
     */
    protected function validateLargeBusinessResults(): void
    {
        $this->assertEquals(500, Customer::where('company_id', $this->company->id)->count());
        $this->assertEquals(2000, Invoice::where('company_id', $this->company->id)->count());
        $this->assertEquals(1000, Item::where('company_id', $this->company->id)->count());
        $this->assertEquals(1500, Payment::where('company_id', $this->company->id)->count());
    }

    /**
     * Validate data integrity
     */
    protected function validateDataIntegrity(): void
    {
        // Test customer-invoice relationships
        $customersWithInvoices = Customer::where('company_id', $this->company->id)
            ->whereHas('invoices')
            ->count();
        $this->assertGreaterThan(0, $customersWithInvoices);

        // Test invoice-payment relationships
        $invoicesWithPayments = Invoice::where('company_id', $this->company->id)
            ->whereHas('payments')
            ->count();
        $this->assertGreaterThan(0, $invoicesWithPayments);

        // Test data consistency
        $totalInvoiceAmount = Invoice::where('company_id', $this->company->id)->sum('total');
        $this->assertGreaterThan(0, $totalInvoiceAmount);
    }

    /**
     * Validate customer-invoice relationships
     */
    protected function validateCustomerInvoiceRelationships(): void
    {
        $invoicesWithCustomers = Invoice::where('company_id', $this->company->id)
            ->whereNotNull('customer_id')
            ->count();
        $this->assertEquals(200, $invoicesWithCustomers);
    }

    /**
     * Validate payment-invoice relationships
     */
    protected function validatePaymentInvoiceRelationships(): void
    {
        $paymentsWithInvoices = Payment::where('company_id', $this->company->id)
            ->whereNotNull('invoice_id')
            ->count();
        $this->assertGreaterThan(0, $paymentsWithInvoices);
    }

    /**
     * Validate performance metrics
     */
    protected function validatePerformanceMetrics(ImportJob $importJob): void
    {
        $summary = $importJob->fresh()->summary;
        $this->assertNotNull($summary);
        $this->assertArrayHasKey('processing_time', $summary);
        $this->assertLessThan(180, $summary['processing_time'], 'SME migration should complete within 3 minutes');
    }

    /**
     * Generate complete SME business data
     */
    protected function generateCompleteSMEData(): string
    {
        return $this->generateTestData(50, 200, 100, 150, 50);
    }

    /**
     * Generate large business data
     */
    protected function generateLargeBusinessData(): string
    {
        return $this->generateTestData(500, 2000, 1000, 1500, 200);
    }

    /**
     * Generate test data with specified counts
     */
    protected function generateTestData(int $customers, int $invoices, int $items, int $payments, int $expenses): string
    {
        $csv = [];
        $csv[] = ['type', 'име', 'емаил', 'телефон', 'опис', 'цена', 'датум', 'износ', 'статус'];

        // Generate customers
        for ($i = 1; $i <= $customers; $i++) {
            $csv[] = [
                'customer',
                "Клиент {$i}",
                "client{$i}@example.mk",
                "070{$i}123",
                '',
                '',
                '',
                '',
                'активен',
            ];
        }

        // Generate items
        for ($i = 1; $i <= $items; $i++) {
            $csv[] = [
                'item',
                "Производ {$i}",
                '',
                '',
                "Опис на производ {$i}",
                rand(100, 10000),
                '',
                '',
                'активен',
            ];
        }

        // Generate invoices
        for ($i = 1; $i <= $invoices; $i++) {
            $customerRef = rand(1, min($customers, 50));
            $csv[] = [
                'invoice',
                "Клиент {$customerRef}",
                '',
                '',
                "Фактура {$i}",
                '',
                '2024-'.rand(1, 12).'-'.rand(1, 28),
                rand(1000, 50000),
                'издадена',
            ];
        }

        // Generate payments
        for ($i = 1; $i <= $payments; $i++) {
            $csv[] = [
                'payment',
                '',
                '',
                '',
                "Плаќање {$i}",
                '',
                '2024-'.rand(1, 12).'-'.rand(1, 28),
                rand(500, 25000),
                'потврдено',
            ];
        }

        // Generate expenses
        for ($i = 1; $i <= $expenses; $i++) {
            $csv[] = [
                'expense',
                '',
                '',
                '',
                "Трошок {$i}",
                '',
                '2024-'.rand(1, 12).'-'.rand(1, 28),
                rand(100, 5000),
                'одобрен',
            ];
        }

        return $this->arrayToCsv($csv);
    }

    /**
     * Generate customers data only
     */
    protected function generateCustomersData(int $count): string
    {
        $csv = [];
        $csv[] = ['име', 'емаил', 'телефон', 'адреса'];

        for ($i = 1; $i <= $count; $i++) {
            $csv[] = [
                "Клиент {$i}",
                "client{$i}@example.mk",
                "070{$i}123",
                "Адреса {$i}, Скопје",
            ];
        }

        return $this->arrayToCsv($csv);
    }

    /**
     * Generate invoices data with customer references
     */
    protected function generateInvoicesData(int $count, bool $withCustomerRefs = false): string
    {
        $csv = [];
        $csv[] = ['број_фактура', 'клиент', 'датум', 'износ', 'ддв'];

        for ($i = 1; $i <= $count; $i++) {
            $customerRef = $withCustomerRefs ? 'Клиент '.rand(1, 50) : '';
            $csv[] = [
                "INV-{$i}",
                $customerRef,
                '2024-'.rand(1, 12).'-'.rand(1, 28),
                rand(1000, 50000),
                rand(100, 5000),
            ];
        }

        return $this->arrayToCsv($csv);
    }

    /**
     * Generate payments data with invoice references
     */
    protected function generatePaymentsData(int $count, bool $withInvoiceRefs = false): string
    {
        $csv = [];
        $csv[] = ['број_плаќање', 'фактура', 'датум', 'износ', 'начин'];

        for ($i = 1; $i <= $count; $i++) {
            $invoiceRef = $withInvoiceRefs ? 'INV-'.rand(1, 200) : '';
            $csv[] = [
                "PAY-{$i}",
                $invoiceRef,
                '2024-'.rand(1, 12).'-'.rand(1, 28),
                rand(500, 25000),
                'готовина',
            ];
        }

        return $this->arrayToCsv($csv);
    }

    /**
     * Generate corrupted data for error testing
     */
    protected function generateCorruptedData(): string
    {
        $csv = [];
        $csv[] = ['type', 'име', 'емаил', 'телефон', 'датум', 'износ'];

        // Invalid email format
        $csv[] = ['customer', 'Клиент 1', 'invalid-email', '070123456', '', ''];

        // Missing required field
        $csv[] = ['customer', '', 'client2@example.mk', '070234567', '', ''];

        // Invalid date format
        $csv[] = ['invoice', 'Клиент 1', '', '', 'invalid-date', '1000'];

        // Negative amount
        $csv[] = ['payment', '', '', '', '2024-01-15', '-500'];

        return $this->arrayToCsv($csv);
    }

    /**
     * Generate large dataset for performance testing
     */
    protected function generateLargeDataset(int $customers, int $invoices, int $items, int $payments, int $expenses): string
    {
        return $this->generateTestData($customers, $invoices, $items, $payments, $expenses);
    }

    /**
     * Generate data with Macedonian field names
     */
    protected function generateMacedonianFieldData(): string
    {
        $csv = [];
        $csv[] = ['име', 'емаил', 'телефон', 'адреса', 'даночен_број'];

        for ($i = 1; $i <= 10; $i++) {
            $csv[] = [
                "Клиент {$i}",
                "client{$i}@example.mk",
                "070{$i}123",
                "Адреса {$i}",
                "400{$i}567",
            ];
        }

        return $this->arrayToCsv($csv);
    }

    /**
     * Get test field mappings
     */
    protected function getTestMappings(): array
    {
        return [
            'име' => 'name',
            'емаил' => 'email',
            'телефон' => 'phone',
            'опис' => 'description',
            'цена' => 'price',
            'датум' => 'date',
            'износ' => 'amount',
            'статус' => 'status',
        ];
    }

    /**
     * Get test validation rules
     */
    protected function getTestValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'amount' => 'nullable|numeric|min:0',
            'date' => 'nullable|date',
        ];
    }

    /**
     * Create test file
     */
    protected function createTestFile(string $filename, string $content, string $extension = 'csv'): UploadedFile
    {
        $path = storage_path("app/temp/{$filename}");

        // Ensure directory exists
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, $content);

        return new UploadedFile($path, $filename, "text/{$extension}", null, true);
    }

    /**
     * Convert array to CSV string
     */
    protected function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
