<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\PayrollEmployee;
use App\Models\PayrollRun;
use App\Models\SalaryStructure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payroll Run Lifecycle Test
 *
 * Tests the complete payroll run lifecycle from draft to paid:
 * 1. Draft: Create payroll run
 * 2. Calculated: Calculate salaries for all active employees
 * 3. Approved: Approve the calculated payroll
 * 4. Posted: Post to IFRS general ledger
 * 5. Paid: Mark as paid and generate bank file
 */
class PayrollRunLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected Currency $currency;

    protected PayrollEmployee $employee1;

    protected PayrollEmployee $employee2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test currency (MKD)
        $this->currency = Currency::create([
            'name' => 'Macedonian Denar',
            'code' => 'MKD',
            'symbol' => 'ден',
            'precision' => 2,
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'swap_currency_symbol' => 0,
        ]);

        // Create test user and company
        $this->user = User::factory()->create();
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'currency_id' => $this->currency->id,
        ]);

        // Associate user with company
        $this->user->companies()->attach($this->company->id, [
            'role' => 'owner',
        ]);

        // Create test employees
        $this->employee1 = PayrollEmployee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP001',
            'first_name' => 'Marko',
            'last_name' => 'Markovski',
            'email' => 'marko@test.com',
            'embg' => '0101990450006',
            'bank_account_iban' => 'MK07250120000058984',
            'bank_name' => 'Stopanska Banka',
            'employment_date' => now()->subYear(),
            'employment_type' => 'full_time',
            'department' => 'Engineering',
            'position' => 'Software Developer',
            'base_salary_amount' => 10000000, // 100,000 MKD in cents
            'currency_id' => $this->currency->id,
            'is_active' => true,
            'creator_id' => $this->user->id,
        ]);

        $this->employee2 = PayrollEmployee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP002',
            'first_name' => 'Jana',
            'last_name' => 'Janeska',
            'email' => 'jana@test.com',
            'embg' => '1512985410003',
            'bank_account_iban' => 'MK07250120000058985',
            'bank_name' => 'Stopanska Banka',
            'employment_date' => now()->subYear(),
            'employment_type' => 'full_time',
            'department' => 'Sales',
            'position' => 'Sales Manager',
            'base_salary_amount' => 15000000, // 150,000 MKD in cents
            'currency_id' => $this->currency->id,
            'is_active' => true,
            'creator_id' => $this->user->id,
        ]);

        // Create salary structures for both employees
        SalaryStructure::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee1->id,
            'effective_from' => now()->startOfMonth(),
            'effective_to' => null,
            'gross_salary' => 10000000, // 100,000 MKD
            'transport_allowance' => 0,
            'meal_allowance' => 0,
            'other_allowances' => [],
            'is_current' => true,
        ]);

        SalaryStructure::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee2->id,
            'effective_from' => now()->startOfMonth(),
            'effective_to' => null,
            'gross_salary' => 15000000, // 150,000 MKD
            'transport_allowance' => 0,
            'meal_allowance' => 0,
            'other_allowances' => [],
            'is_current' => true,
        ]);
    }

    /** @test */
    public function it_creates_payroll_run_in_draft_status()
    {
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson('/api/v1/admin/payroll/runs', [
                'period_year' => now()->year,
                'period_month' => now()->month,
                'period_start' => now()->startOfMonth()->toDateString(),
                'period_end' => now()->endOfMonth()->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'company_id',
                    'period_year',
                    'period_month',
                    'status',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('payroll_runs', [
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'status' => PayrollRun::STATUS_DRAFT,
        ]);

        $data = $response->json('data');
        $this->assertEquals(PayrollRun::STATUS_DRAFT, $data['status']);
        $this->assertEquals($this->company->id, $data['company_id']);
    }

    /** @test */
    public function it_prevents_duplicate_payroll_runs_for_same_period()
    {
        // Create first payroll run
        PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_DRAFT,
            'creator_id' => $this->user->id,
        ]);

        // Attempt to create duplicate
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson('/api/v1/admin/payroll/runs', [
                'period_year' => now()->year,
                'period_month' => now()->month,
                'period_start' => now()->startOfMonth()->toDateString(),
                'period_end' => now()->endOfMonth()->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'duplicate_period',
            ]);
    }

    /** @test */
    public function it_calculates_payroll_run_and_moves_to_calculated_status()
    {
        // Create draft payroll run
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_DRAFT,
            'creator_id' => $this->user->id,
        ]);

        // Calculate the payroll
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson("/api/v1/admin/payroll/runs/{$run->id}/calculate");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Payroll run calculated successfully.',
            ]);

        // Verify status changed
        $run->refresh();
        $this->assertEquals(PayrollRun::STATUS_CALCULATED, $run->status);
        $this->assertNotNull($run->calculated_at);

        // Verify payroll lines were created
        $this->assertEquals(2, $run->lines()->count());

        // Verify totals were calculated
        $this->assertGreaterThan(0, $run->total_gross);
        $this->assertGreaterThan(0, $run->total_net);
        $this->assertGreaterThan(0, $run->total_employer_tax);
        $this->assertGreaterThan(0, $run->total_employee_tax);

        // Verify total gross = sum of both employees
        // Employee 1: 100,000 MKD + Employee 2: 150,000 MKD = 250,000 MKD
        $this->assertEquals(25000000, $run->total_gross);
    }

    /** @test */
    public function it_cannot_calculate_already_calculated_run()
    {
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_CALCULATED,
            'creator_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson("/api/v1/admin/payroll/runs/{$run->id}/calculate");

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'cannot_calculate',
            ]);
    }

    /** @test */
    public function it_approves_calculated_payroll_run()
    {
        // Create calculated payroll run
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_CALCULATED,
            'calculated_at' => now(),
            'creator_id' => $this->user->id,
        ]);

        // Approve the payroll
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson("/api/v1/admin/payroll/runs/{$run->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Payroll run approved successfully.',
            ]);

        // Verify status changed
        $run->refresh();
        $this->assertEquals(PayrollRun::STATUS_APPROVED, $run->status);
        $this->assertNotNull($run->approved_at);
        $this->assertEquals($this->user->id, $run->approved_by);
    }

    /** @test */
    public function it_cannot_approve_draft_payroll_run()
    {
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_DRAFT,
            'creator_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson("/api/v1/admin/payroll/runs/{$run->id}/approve");

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'cannot_approve',
            ]);
    }

    /** @test */
    public function it_posts_approved_payroll_to_general_ledger()
    {
        // Skip if IFRS not enabled
        if (! config('ifrs.enabled', false)) {
            $this->markTestSkipped('IFRS accounting is not enabled');
        }

        // Create approved payroll run with totals
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_APPROVED,
            'total_gross' => 25000000, // 250,000 MKD
            'total_net' => 19248750, // Calculated net
            'total_employer_tax' => 3187500, // 12.75%
            'total_employee_tax' => 5751250, // Employee deductions + tax
            'calculated_at' => now(),
            'approved_at' => now(),
            'approved_by' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        // Post to GL
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson("/api/v1/admin/payroll/runs/{$run->id}/post");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Payroll run posted to general ledger successfully.',
            ]);

        // Verify status changed
        $run->refresh();
        $this->assertEquals(PayrollRun::STATUS_POSTED, $run->status);
        $this->assertNotNull($run->posted_at);
        $this->assertNotNull($run->ifrs_transaction_id);
    }

    /** @test */
    public function it_cannot_post_unapproved_payroll_run()
    {
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_CALCULATED,
            'creator_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson("/api/v1/admin/payroll/runs/{$run->id}/post");

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'cannot_post',
            ]);
    }

    /** @test */
    public function it_marks_posted_payroll_as_paid()
    {
        // Create posted payroll run
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_POSTED,
            'total_gross' => 25000000,
            'total_net' => 19248750,
            'calculated_at' => now(),
            'approved_at' => now(),
            'posted_at' => now(),
            'approved_by' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        // Mark as paid
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson("/api/v1/admin/payroll/runs/{$run->id}/mark-paid");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Payroll run marked as paid successfully.',
            ]);

        // Verify status changed
        $run->refresh();
        $this->assertEquals(PayrollRun::STATUS_PAID, $run->status);
        $this->assertNotNull($run->paid_at);
    }

    /** @test */
    public function it_cannot_mark_unposted_payroll_as_paid()
    {
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_APPROVED,
            'creator_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson("/api/v1/admin/payroll/runs/{$run->id}/mark-paid");

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'cannot_mark_paid',
            ]);
    }

    /** @test */
    public function it_completes_full_lifecycle_from_draft_to_paid()
    {
        // Step 1: Create draft payroll run
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson('/api/v1/admin/payroll/runs', [
                'period_year' => now()->year,
                'period_month' => now()->month,
                'period_start' => now()->startOfMonth()->toDateString(),
                'period_end' => now()->endOfMonth()->toDateString(),
            ]);

        $response->assertStatus(201);
        $runId = $response->json('data.id');
        $run = PayrollRun::find($runId);

        // Verify draft status
        $this->assertEquals(PayrollRun::STATUS_DRAFT, $run->status);

        // Step 2: Calculate
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson("/api/v1/admin/payroll/runs/{$runId}/calculate");

        $response->assertStatus(200);
        $run->refresh();
        $this->assertEquals(PayrollRun::STATUS_CALCULATED, $run->status);
        $this->assertEquals(2, $run->lines()->count());

        // Step 3: Approve
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson("/api/v1/admin/payroll/runs/{$runId}/approve");

        $response->assertStatus(200);
        $run->refresh();
        $this->assertEquals(PayrollRun::STATUS_APPROVED, $run->status);

        // Step 4: Post to GL (skip if IFRS disabled)
        if (config('ifrs.enabled', false)) {
            $response = $this->actingAs($this->user)
                ->withHeaders(['company' => $this->company->id])
                ->postJson("/api/v1/admin/payroll/runs/{$runId}/post");

            $response->assertStatus(200);
            $run->refresh();
            $this->assertEquals(PayrollRun::STATUS_POSTED, $run->status);

            // Step 5: Mark as paid
            $response = $this->actingAs($this->user)
                ->withHeaders(['company' => $this->company->id])
                ->postJson("/api/v1/admin/payroll/runs/{$runId}/mark-paid");

            $response->assertStatus(200);
            $run->refresh();
            $this->assertEquals(PayrollRun::STATUS_PAID, $run->status);
        }
    }

    /** @test */
    public function it_can_list_all_payroll_runs()
    {
        // Create multiple payroll runs
        PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_DRAFT,
            'creator_id' => $this->user->id,
        ]);

        PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->subMonth()->year,
            'period_month' => now()->subMonth()->month,
            'period_start' => now()->subMonth()->startOfMonth(),
            'period_end' => now()->subMonth()->endOfMonth(),
            'status' => PayrollRun::STATUS_PAID,
            'creator_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->getJson('/api/v1/admin/payroll/runs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'company_id',
                        'period_year',
                        'period_month',
                        'status',
                    ],
                ],
                'meta',
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_can_filter_payroll_runs_by_status()
    {
        PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_DRAFT,
            'creator_id' => $this->user->id,
        ]);

        PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->subMonth()->year,
            'period_month' => now()->subMonth()->month,
            'period_start' => now()->subMonth()->startOfMonth(),
            'period_end' => now()->subMonth()->endOfMonth(),
            'status' => PayrollRun::STATUS_PAID,
            'creator_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->getJson('/api/v1/admin/payroll/runs?status=draft');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(PayrollRun::STATUS_DRAFT, $response->json('data.0.status'));
    }

    /** @test */
    public function it_can_show_individual_payroll_run_with_lines()
    {
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_DRAFT,
            'creator_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->getJson("/api/v1/admin/payroll/runs/{$run->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'company_id',
                    'status',
                    'creator',
                    'lines',
                ],
            ]);
    }
}

// LLM-CHECKPOINT
