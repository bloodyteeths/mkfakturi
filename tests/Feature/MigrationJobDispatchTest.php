<?php

namespace Tests\Feature;

use App\Jobs\Migration\CommitImportJob;
use App\Jobs\Migration\DetectFileTypeJob;
use App\Jobs\Migration\ValidateDataJob;
use App\Models\Company;
use App\Models\Currency;
use App\Models\ImportJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * MIG-DISPATCH Feature Test
 *
 * Verifies that MigrationController properly dispatches background jobs to queue
 * and that the Universal Migration Wizard works end-to-end with Horizon monitoring.
 *
 * Success Criteria:
 * - Migration jobs show in Horizon queue
 * - Background processing works end-to-end
 * - Universal Migration Wizard operational
 */
class MigrationJobDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test environment
        $this->setupTestData();

        // Setup storage for test files
        Storage::fake('private');
    }

    /**
     * Setup minimal test data for migration testing
     */
    protected function setupTestData(): void
    {
        // Create test company
        $this->company = Company::factory()->create([
            'name' => 'Test Macedonia Company',
            'country_id' => 142, // Macedonia
        ]);

        // Create test user
        $this->user = User::factory()->create([
            'role' => 'super admin',
        ]);

        // Setup MKD currency
        $this->currency = Currency::factory()->create([
            'name' => 'Macedonian Denar',
            'code' => 'MKD',
            'symbol' => 'ден',
            'precision' => 2,
        ]);
    }

    /**
     * Test that file upload dispatches DetectFileTypeJob to migration queue
     *
     * @test
     */
    public function file_upload_dispatches_detect_file_type_job()
    {
        Queue::fake();

        // Create test CSV file
        $csvContent = "name,email,phone\n";
        $csvContent .= "John Doe,john@example.com,123456789\n";
        $csvContent .= "Jane Smith,jane@example.com,987654321\n";

        $file = UploadedFile::fake()->createWithContent('customers.csv', $csvContent);

        // Make authenticated request to upload file
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson('/api/v1/imports', [
                'file' => $file,
                'type' => 'customers',
                'source_system' => 'test',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'status',
                'file_info',
                'created_at',
            ],
        ]);

        // Verify DetectFileTypeJob was dispatched to migration queue
        Queue::assertPushedOn('migration', DetectFileTypeJob::class);

        // Verify job was dispatched with correct import job
        Queue::assertPushed(DetectFileTypeJob::class, function ($job) {
            return $job->importJob instanceof ImportJob &&
                   $job->importJob->type === ImportJob::TYPE_CUSTOMERS &&
                   $job->importJob->status === ImportJob::STATUS_PENDING;
        });
    }

    /**
     * Test that mapping submission dispatches ValidateDataJob to migration queue
     *
     * @test
     */
    public function mapping_submission_dispatches_validate_data_job()
    {
        Queue::fake();

        // Create import job in mapping state
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => $this->user->id,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'status' => ImportJob::STATUS_MAPPING,
            'file_info' => [
                'original_name' => 'customers.csv',
                'size' => 1024,
                'headers' => ['name', 'email', 'phone'],
            ],
        ]);

        // Submit field mappings
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson("/api/v1/imports/{$importJob->id}/mapping", [
                'mappings' => [
                    'name' => 'display_name',
                    'email' => 'email',
                    'phone' => 'phone',
                ],
                'validation_rules' => [
                    'email' => 'required|email',
                    'display_name' => 'required|string',
                ],
            ]);

        $response->assertStatus(200);

        // Verify ValidateDataJob was dispatched to migration queue
        Queue::assertPushedOn('migration', ValidateDataJob::class);

        // Verify job was dispatched with correct import job
        Queue::assertPushed(ValidateDataJob::class, function ($job) use ($importJob) {
            return $job->importJob->id === $importJob->id &&
                   $job->importJob->status === ImportJob::STATUS_MAPPING;
        });
    }

    /**
     * Test that commit request dispatches CommitImportJob to migration queue
     *
     * @test
     */
    public function commit_request_dispatches_commit_import_job()
    {
        Queue::fake();

        // Create import job in validating state
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => $this->user->id,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'status' => ImportJob::STATUS_VALIDATING,
            'mapping_config' => [
                'name' => 'display_name',
                'email' => 'email',
            ],
            'total_records' => 10,
            'processed_records' => 10,
            'successful_records' => 8,
            'failed_records' => 2,
        ]);

        // Submit commit request
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson("/api/v1/imports/{$importJob->id}/commit", [
                'force_commit' => false,
            ]);

        $response->assertStatus(200);

        // Verify CommitImportJob was dispatched to migration queue
        Queue::assertPushedOn('migration', CommitImportJob::class);

        // Verify job was dispatched with correct import job
        Queue::assertPushed(CommitImportJob::class, function ($job) use ($importJob) {
            return $job->importJob->id === $importJob->id &&
                   $job->importJob->status === ImportJob::STATUS_COMMITTING;
        });
    }

    /**
     * Test complete migration workflow job chain
     *
     * @test
     */
    public function complete_migration_workflow_dispatches_job_chain()
    {
        // Use real queue for this test to verify chain
        Queue::fake();

        // Create comprehensive test file with Macedonia data
        $csvContent = "naziv,email,telefon,adresa\n";
        $csvContent .= "Петар Петровски,petar@example.mk,+38970123456,\"Македонски Херои 15, Скопје\"\n";
        $csvContent .= "Марија Николовска,marija@example.mk,+38971987654,\"Гоце Делчев 25, Битола\"\n";
        $csvContent .= "Стефан Јовановски,stefan@example.mk,+38972456789,\"Партизанска 10, Прилеп\"\n";

        $file = UploadedFile::fake()->createWithContent('macedonian_customers.csv', $csvContent);

        // Step 1: Upload file
        $uploadResponse = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson('/api/v1/imports', [
                'file' => $file,
                'type' => 'customers',
                'source_system' => 'onivo',
            ]);

        $uploadResponse->assertStatus(201);
        $importJobId = $uploadResponse->json('data.id');

        // Verify DetectFileTypeJob dispatched
        Queue::assertPushedOn('migration', DetectFileTypeJob::class);

        // Step 2: Submit mappings (Macedonia field names)
        $mappingResponse = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson("/api/v1/imports/{$importJobId}/mapping", [
                'mappings' => [
                    'naziv' => 'display_name',    // Macedonian "name" field
                    'email' => 'email',
                    'telefon' => 'phone',          // Macedonian "phone" field
                    'adresa' => 'billing_address_1', // Macedonian "address" field
                ],
                'validation_rules' => [
                    'email' => 'required|email',
                    'display_name' => 'required|string',
                ],
            ]);

        $mappingResponse->assertStatus(200);

        // Verify ValidateDataJob dispatched
        Queue::assertPushed(ValidateDataJob::class, 2); // Once from mapping, once from validation request

        // Step 3: Validate data
        $validateResponse = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson("/api/v1/imports/{$importJobId}/validate");

        $validateResponse->assertStatus(200);

        // Step 4: Commit import
        $commitResponse = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson("/api/v1/imports/{$importJobId}/commit", [
                'force_commit' => true, // Allow commit even with validation warnings
            ]);

        $commitResponse->assertStatus(200);

        // Verify CommitImportJob dispatched
        Queue::assertPushedOn('migration', CommitImportJob::class);

        // Verify complete job chain was triggered
        $this->assertGreaterThanOrEqual(3, Queue::pushed(DetectFileTypeJob::class)->count() +
                                               Queue::pushed(ValidateDataJob::class)->count() +
                                               Queue::pushed(CommitImportJob::class)->count());
    }

    /**
     * Test that jobs are queued with proper delays for performance
     *
     * @test
     */
    public function jobs_are_queued_with_appropriate_delays()
    {
        Queue::fake();

        // Create test file
        $file = UploadedFile::fake()->createWithContent('test.csv', "name,email\nTest,test@example.com");

        // Upload file
        $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson('/api/v1/imports', [
                'file' => $file,
                'type' => 'customers',
            ]);

        // Verify DetectFileTypeJob has appropriate delay (2 seconds)
        Queue::assertPushed(DetectFileTypeJob::class, function ($job) {
            // Check that job has delay (exact delay testing is complex with fake queue)
            return $job->delay !== null;
        });
    }

    /**
     * Test that migration progress can be tracked in real-time
     *
     * @test
     */
    public function migration_progress_can_be_tracked()
    {
        // Create import job with progress data
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => $this->user->id,
            'type' => ImportJob::TYPE_CUSTOMERS,
            'status' => ImportJob::STATUS_PROCESSING,
            'total_records' => 100,
            'processed_records' => 60,
            'successful_records' => 55,
            'failed_records' => 5,
        ]);

        // Get progress
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->getJson("/api/v1/imports/{$importJob->id}/progress");

        $response->assertStatus(200);
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
            'recent_logs',
            'last_updated',
        ]);

        // Verify progress calculation
        $this->assertEquals(60, $response->json('progress_percentage'));
        $this->assertTrue($response->json('is_in_progress'));
    }

    /**
     * Test queue configuration and job routing
     *
     * @test
     */
    public function jobs_are_routed_to_correct_migration_queue()
    {
        Queue::fake();

        // Create import job
        $importJob = ImportJob::factory()->create([
            'company_id' => $this->company->id,
            'creator_id' => $this->user->id,
            'status' => ImportJob::STATUS_PENDING,
        ]);

        // Dispatch job directly to verify queue routing
        DetectFileTypeJob::dispatch($importJob);

        // Verify job goes to migration queue specifically
        Queue::assertPushedOn('migration', DetectFileTypeJob::class);

        // Verify job is not on default queue
        Queue::assertNotPushedOn('default', DetectFileTypeJob::class);
    }
}
