<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Mk\Jobs\BatchDailyCloseJob;
use Modules\Mk\Jobs\BatchExportJob;
use Modules\Mk\Jobs\BatchFinancialStatementExportJob;
use Modules\Mk\Jobs\BatchPeriodLockJob;
use Modules\Mk\Jobs\BatchVatReturnJob;
use Modules\Mk\Models\BatchJob;
use Tests\TestCase;

class BatchOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $partnerUser;
    protected Partner $partner;
    protected Company $companyA;
    protected Company $companyB;
    protected Company $unlinkedCompany;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partnerUser = User::factory()->create([
            'email' => 'batch-partner@test.com',
            'role' => 'partner',
        ]);

        $this->partner = Partner::factory()->create([
            'user_id' => $this->partnerUser->id,
            'is_active' => true,
            'commission_rate' => 20.0,
        ]);

        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);
        $this->unlinkedCompany = Company::factory()->create(['name' => 'Unlinked Co']);

        $this->partner->companies()->attach($this->companyA->id, ['is_active' => true]);
        $this->partner->companies()->attach($this->companyB->id, ['is_active' => true]);

        $this->superAdmin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => 'super admin',
        ]);
    }

    // ── API Endpoint Tests ──

    public function test_list_operations_returns_all_types(): void
    {
        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->getJson('/api/v1/partner/batch-operations/operations');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $keys = collect($response->json('data'))->pluck('key')->toArray();
        $this->assertContains('daily_close', $keys);
        $this->assertContains('vat_return', $keys);
        $this->assertContains('trial_balance_export', $keys);
        $this->assertContains('journal_export', $keys);
        $this->assertContains('period_lock', $keys);
        $this->assertContains('balance_sheet_export', $keys);
        $this->assertContains('income_statement_export', $keys);
    }

    public function test_create_daily_close_job(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/api/v1/partner/batch-operations', [
                'operation_type' => 'daily_close',
                'company_ids' => [$this->companyA->id, $this->companyB->id],
                'parameters' => [
                    'date' => '2026-03-01',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('batch_jobs', [
            'partner_id' => $this->partner->id,
            'operation_type' => 'daily_close',
            'status' => 'queued',
            'total_items' => 2,
        ]);

        Queue::assertPushedOn('background', BatchDailyCloseJob::class);
    }

    public function test_create_vat_return_job(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/api/v1/partner/batch-operations', [
                'operation_type' => 'vat_return',
                'company_ids' => [$this->companyA->id],
                'parameters' => [
                    'year' => 2026,
                    'month' => 2,
                ],
            ]);

        $response->assertStatus(201);
        Queue::assertPushedOn('background', BatchVatReturnJob::class);
    }

    public function test_create_trial_balance_export_job(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/api/v1/partner/batch-operations', [
                'operation_type' => 'trial_balance_export',
                'company_ids' => [$this->companyA->id],
                'parameters' => [
                    'date_from' => '2026-01-01',
                    'date_to' => '2026-03-01',
                    'format' => 'csv',
                ],
            ]);

        $response->assertStatus(201);
        Queue::assertPushedOn('background', BatchExportJob::class);
    }

    public function test_create_balance_sheet_export_job(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/api/v1/partner/batch-operations', [
                'operation_type' => 'balance_sheet_export',
                'company_ids' => [$this->companyA->id],
                'parameters' => [
                    'as_of_date' => '2026-03-01',
                    'format' => 'csv',
                ],
            ]);

        $response->assertStatus(201);
        Queue::assertPushedOn('background', BatchFinancialStatementExportJob::class);
    }

    public function test_create_income_statement_export_job(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/api/v1/partner/batch-operations', [
                'operation_type' => 'income_statement_export',
                'company_ids' => [$this->companyA->id],
                'parameters' => [
                    'as_of_date' => '2026-03-01',
                    'format' => 'json',
                ],
            ]);

        $response->assertStatus(201);
        Queue::assertPushedOn('background', BatchFinancialStatementExportJob::class);
    }

    public function test_create_period_lock_job(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/api/v1/partner/batch-operations', [
                'operation_type' => 'period_lock',
                'company_ids' => [$this->companyA->id],
                'parameters' => [
                    'period_start' => '2026-01-01',
                    'period_end' => '2026-01-31',
                ],
            ]);

        $response->assertStatus(201);
        Queue::assertPushedOn('background', BatchPeriodLockJob::class);
    }

    // ── Validation Tests ──

    public function test_daily_close_requires_date_parameter(): void
    {
        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/api/v1/partner/batch-operations', [
                'operation_type' => 'daily_close',
                'company_ids' => [$this->companyA->id],
                'parameters' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parameters.date']);
    }

    public function test_vat_return_requires_year_and_month(): void
    {
        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/api/v1/partner/batch-operations', [
                'operation_type' => 'vat_return',
                'company_ids' => [$this->companyA->id],
                'parameters' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parameters.year', 'parameters.month']);
    }

    public function test_export_requires_dates_and_format(): void
    {
        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/api/v1/partner/batch-operations', [
                'operation_type' => 'trial_balance_export',
                'company_ids' => [$this->companyA->id],
                'parameters' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parameters.date_from', 'parameters.date_to', 'parameters.format']);
    }

    public function test_financial_statement_export_requires_as_of_date(): void
    {
        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/api/v1/partner/batch-operations', [
                'operation_type' => 'balance_sheet_export',
                'company_ids' => [$this->companyA->id],
                'parameters' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parameters.as_of_date', 'parameters.format']);
    }

    public function test_rejects_empty_company_ids(): void
    {
        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/api/v1/partner/batch-operations', [
                'operation_type' => 'daily_close',
                'company_ids' => [],
                'parameters' => ['date' => '2026-03-01'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_ids']);
    }

    public function test_rejects_invalid_operation_type(): void
    {
        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/api/v1/partner/batch-operations', [
                'operation_type' => 'delete_everything',
                'company_ids' => [$this->companyA->id],
                'parameters' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['operation_type']);
    }

    // ── Access Control Tests ──

    public function test_rejects_unlinked_company(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/api/v1/partner/batch-operations', [
                'operation_type' => 'daily_close',
                'company_ids' => [$this->unlinkedCompany->id],
                'parameters' => ['date' => '2026-03-01'],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        Queue::assertNothingPushed();
    }

    public function test_super_admin_can_create_batch_job(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v1/partner/batch-operations', [
                'operation_type' => 'daily_close',
                'company_ids' => [$this->companyA->id],
                'parameters' => ['date' => '2026-03-01'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        // Super admin jobs have null partner_id
        $this->assertDatabaseHas('batch_jobs', [
            'operation_type' => 'daily_close',
            'partner_id' => null,
        ]);

        Queue::assertPushedOn('background', BatchDailyCloseJob::class);
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $response = $this->getJson('/api/v1/partner/batch-operations');
        $response->assertStatus(401);
    }

    // ── Job Listing & Progress Tests ──

    public function test_list_jobs_returns_partner_jobs_only(): void
    {
        // Create jobs for this partner
        BatchJob::create([
            'partner_id' => $this->partner->id,
            'operation_type' => 'daily_close',
            'company_ids' => [$this->companyA->id],
            'parameters' => ['date' => '2026-03-01'],
            'status' => 'completed',
            'total_items' => 1,
            'completed_items' => 1,
            'failed_items' => 0,
            'results' => [['company_id' => $this->companyA->id, 'status' => 'success', 'message' => 'Done']],
        ]);

        // Create a job for another partner
        $otherPartner = Partner::factory()->create(['user_id' => User::factory()->create()->id]);
        BatchJob::create([
            'partner_id' => $otherPartner->id,
            'operation_type' => 'daily_close',
            'company_ids' => [$this->companyA->id],
            'parameters' => ['date' => '2026-03-01'],
            'status' => 'completed',
            'total_items' => 1,
            'completed_items' => 1,
            'failed_items' => 0,
            'results' => [],
        ]);

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->getJson('/api/v1/partner/batch-operations');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_show_job_returns_progress(): void
    {
        $batchJob = BatchJob::create([
            'partner_id' => $this->partner->id,
            'operation_type' => 'trial_balance_export',
            'company_ids' => [$this->companyA->id, $this->companyB->id],
            'parameters' => ['date_from' => '2026-01-01', 'date_to' => '2026-03-01', 'format' => 'csv'],
            'status' => 'running',
            'total_items' => 2,
            'completed_items' => 1,
            'failed_items' => 0,
            'results' => [['company_id' => $this->companyA->id, 'status' => 'success', 'message' => 'Exported']],
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->getJson("/api/v1/partner/batch-operations/{$batchJob->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'running')
            ->assertJsonPath('data.progress_percentage', 50);
    }

    public function test_progress_endpoint_returns_lightweight_data(): void
    {
        $batchJob = BatchJob::create([
            'partner_id' => $this->partner->id,
            'operation_type' => 'daily_close',
            'company_ids' => [$this->companyA->id],
            'parameters' => ['date' => '2026-03-01'],
            'status' => 'queued',
            'total_items' => 1,
            'completed_items' => 0,
            'failed_items' => 0,
            'results' => [],
        ]);

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->getJson("/api/v1/partner/batch-operations/{$batchJob->id}/progress");

        $response->assertOk()
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.progress_percentage', 0)
            ->assertJsonStructure(['success', 'data' => ['id', 'status', 'total_items', 'completed_items', 'failed_items', 'progress_percentage']]);
    }

    // ── Cancel Tests ──

    public function test_cancel_queued_job(): void
    {
        $batchJob = BatchJob::create([
            'partner_id' => $this->partner->id,
            'operation_type' => 'daily_close',
            'company_ids' => [$this->companyA->id],
            'parameters' => ['date' => '2026-03-01'],
            'status' => 'queued',
            'total_items' => 1,
            'completed_items' => 0,
            'failed_items' => 0,
            'results' => [],
        ]);

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson("/api/v1/partner/batch-operations/{$batchJob->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('batch_jobs', [
            'id' => $batchJob->id,
            'status' => 'failed',
        ]);
    }

    public function test_cannot_cancel_running_job(): void
    {
        $batchJob = BatchJob::create([
            'partner_id' => $this->partner->id,
            'operation_type' => 'daily_close',
            'company_ids' => [$this->companyA->id],
            'parameters' => ['date' => '2026-03-01'],
            'status' => 'running',
            'total_items' => 1,
            'completed_items' => 0,
            'failed_items' => 0,
            'results' => [],
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson("/api/v1/partner/batch-operations/{$batchJob->id}/cancel");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // ── BatchJob Model Tests ──

    public function test_batch_job_progress_percentage(): void
    {
        $job = new BatchJob([
            'total_items' => 10,
            'completed_items' => 7,
            'failed_items' => 2,
        ]);

        $this->assertEquals(90.0, $job->progress_percentage);
    }

    public function test_batch_job_progress_zero_total(): void
    {
        $job = new BatchJob([
            'total_items' => 0,
            'completed_items' => 0,
            'failed_items' => 0,
        ]);

        $this->assertEquals(0, $job->progress_percentage);
    }

    // ── Super Admin Sees All Jobs ──

    public function test_super_admin_sees_all_jobs(): void
    {
        BatchJob::create([
            'partner_id' => $this->partner->id,
            'operation_type' => 'daily_close',
            'company_ids' => [$this->companyA->id],
            'parameters' => ['date' => '2026-03-01'],
            'status' => 'completed',
            'total_items' => 1,
            'completed_items' => 1,
            'failed_items' => 0,
            'results' => [],
        ]);

        BatchJob::create([
            'partner_id' => null,
            'operation_type' => 'daily_close',
            'company_ids' => [$this->companyB->id],
            'parameters' => ['date' => '2026-03-02'],
            'status' => 'completed',
            'total_items' => 1,
            'completed_items' => 1,
            'failed_items' => 0,
            'results' => [],
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v1/partner/batch-operations');

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Super admin should see both jobs
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }
}
