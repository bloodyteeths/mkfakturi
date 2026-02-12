<?php

namespace Tests\Feature;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Company;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Feature tests for P8-03: Bulk Reporting Across Clients
 *
 * Tests multi-company report generation, consolidated reports,
 * access control, validation, and CSV export for partners.
 */
class BulkReportingTest extends TestCase
{
    use RefreshDatabase;

    protected User $partnerUser;

    protected Partner $partner;

    protected Company $companyA;

    protected Company $companyB;

    protected Company $unlinkedCompany;

    protected function setUp(): void
    {
        parent::setUp();

        // Create partner user
        $this->partnerUser = User::factory()->create([
            'email' => 'bulk-partner@test.com',
            'role' => 'partner',
        ]);

        // Create partner record
        $this->partner = Partner::factory()->create([
            'user_id' => $this->partnerUser->id,
            'is_active' => true,
            'commission_rate' => 20.0,
        ]);

        // Create two linked companies
        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);

        // Attach both to partner
        $this->partner->companies()->attach($this->companyA->id, [
            'is_active' => true,
        ]);
        $this->partner->companies()->attach($this->companyB->id, [
            'is_active' => true,
        ]);

        // Create an unlinked company
        $this->unlinkedCompany = Company::factory()->create(['name' => 'Unlinked Co']);
    }

    /**
     * Helper: Mock the IfrsAdapter to return predictable report data.
     */
    protected function mockIfrsAdapter(): void
    {
        $mock = Mockery::mock(IfrsAdapter::class);

        $mock->shouldReceive('getTrialBalance')->andReturnUsing(function (Company $company, ?string $asOfDate = null) {
            return [
                'date' => $asOfDate ?? now()->toDateString(),
                'trial_balance' => [
                    'accounts' => [],
                    'total_debits' => 1000.00,
                    'total_credits' => 1000.00,
                    'is_balanced' => true,
                ],
                'total_debits' => 1000.00,
                'total_credits' => 1000.00,
                'is_balanced' => true,
            ];
        });

        $mock->shouldReceive('getBalanceSheet')->andReturnUsing(function (Company $company, ?string $asOfDate = null) {
            return [
                'date' => $asOfDate ?? now()->toDateString(),
                'balance_sheet' => [
                    'assets' => [],
                    'liabilities' => [],
                    'equity' => [],
                    'totals' => [
                        'assets' => 5000.00,
                        'liabilities' => 2000.00,
                        'equity' => 3000.00,
                    ],
                ],
            ];
        });

        $mock->shouldReceive('getIncomeStatement')->andReturnUsing(function (Company $company, string $startDate, string $endDate) {
            return [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'income_statement' => [
                    'revenues' => [],
                    'expenses' => [],
                    'totals' => [
                        'revenue' => 8000.00,
                        'expenses' => 3000.00,
                    ],
                ],
            ];
        });

        $this->app->instance(IfrsAdapter::class, $mock);
    }

    /**
     * Test: Multi-company endpoint returns separate reports for each company.
     */
    public function test_multi_company_returns_separate_reports(): void
    {
        $this->mockIfrsAdapter();

        $response = $this->actingAs($this->partnerUser)
            ->postJson('/api/v1/partner/reports/multi-company', [
                'company_ids' => [$this->companyA->id, $this->companyB->id],
                'from_date' => '2025-01-01',
                'to_date' => '2025-12-31',
                'report_type' => 'trial_balance',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'companies' => [
                        '*' => [
                            'id',
                            'name',
                            'report_data',
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data.companies');
        $this->assertCount(2, $data);
        $this->assertEquals('Company A', $data[0]['name']);
        $this->assertEquals('Company B', $data[1]['name']);

        // Verify report data structure for trial balance
        $this->assertArrayHasKey('total_debits', $data[0]['report_data']);
        $this->assertArrayHasKey('total_credits', $data[0]['report_data']);
    }

    /**
     * Test: Consolidated report sums match individual company totals.
     */
    public function test_consolidated_sums_match_individual_totals(): void
    {
        $this->mockIfrsAdapter();

        $response = $this->actingAs($this->partnerUser)
            ->postJson('/api/v1/partner/reports/consolidated', [
                'company_ids' => [$this->companyA->id, $this->companyB->id],
                'from_date' => '2025-01-01',
                'to_date' => '2025-12-31',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');

        // Verify structure
        $this->assertArrayHasKey('consolidated', $data);
        $this->assertArrayHasKey('company_count', $data);
        $this->assertArrayHasKey('companies', $data);

        // Two companies
        $this->assertEquals(2, $data['company_count']);

        // Each mock company returns: assets=5000, liabilities=2000, equity=3000, revenue=8000, expenses=3000
        // Consolidated should be double (2 companies)
        $this->assertEquals(10000.00, $data['consolidated']['total_assets']);
        $this->assertEquals(4000.00, $data['consolidated']['total_liabilities']);
        $this->assertEquals(6000.00, $data['consolidated']['total_equity']);
        $this->assertEquals(16000.00, $data['consolidated']['total_revenue']);
        $this->assertEquals(6000.00, $data['consolidated']['total_expenses']);
        $this->assertEquals(10000.00, $data['consolidated']['net_income']);

        // Verify sum of individual company breakdown matches consolidated totals
        $companyAssets = array_sum(array_column($data['companies'], 'assets'));
        $this->assertEquals($data['consolidated']['total_assets'], $companyAssets);
    }

    /**
     * Test: Partner cannot access companies they don't manage.
     */
    public function test_partner_cannot_access_unmanaged_company(): void
    {
        $this->mockIfrsAdapter();

        $response = $this->actingAs($this->partnerUser)
            ->postJson('/api/v1/partner/reports/multi-company', [
                'company_ids' => [$this->companyA->id, $this->unlinkedCompany->id],
                'from_date' => '2025-01-01',
                'to_date' => '2025-12-31',
                'report_type' => 'balance_sheet',
            ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    /**
     * Test: Empty company list returns 422 validation error.
     */
    public function test_empty_company_list_returns_422(): void
    {
        $response = $this->actingAs($this->partnerUser)
            ->postJson('/api/v1/partner/reports/multi-company', [
                'company_ids' => [],
                'from_date' => '2025-01-01',
                'to_date' => '2025-12-31',
                'report_type' => 'trial_balance',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_ids']);
    }

    /**
     * Test: CSV export returns a valid CSV response.
     */
    public function test_csv_export_returns_valid_csv(): void
    {
        $this->mockIfrsAdapter();

        $response = $this->actingAs($this->partnerUser)
            ->postJson('/api/v1/partner/reports/export', [
                'company_ids' => [$this->companyA->id, $this->companyB->id],
                'from_date' => '2025-01-01',
                'to_date' => '2025-12-31',
                'report_type' => 'trial_balance',
                'format' => 'csv',
            ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition');

        // Verify CSV content has header row + 2 data rows
        $content = $response->streamedContent();
        $lines = array_filter(explode("\n", trim($content)));
        $this->assertGreaterThanOrEqual(3, count($lines), 'CSV should have header + 2 company rows');

        // Verify header contains expected columns
        $this->assertStringContainsString('Company ID', $lines[0]);
        $this->assertStringContainsString('Company Name', $lines[0]);
    }

    /**
     * Test: Invalid report type is rejected with 422.
     */
    public function test_report_type_validation(): void
    {
        $response = $this->actingAs($this->partnerUser)
            ->postJson('/api/v1/partner/reports/multi-company', [
                'company_ids' => [$this->companyA->id],
                'from_date' => '2025-01-01',
                'to_date' => '2025-12-31',
                'report_type' => 'invalid_type',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['report_type']);
    }

    /**
     * Test: Missing from_date is rejected with 422.
     */
    public function test_missing_dates_returns_422(): void
    {
        $response = $this->actingAs($this->partnerUser)
            ->postJson('/api/v1/partner/reports/multi-company', [
                'company_ids' => [$this->companyA->id],
                'report_type' => 'trial_balance',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_date', 'to_date']);
    }

    /**
     * Test: JSON export returns structured data.
     */
    public function test_json_export_returns_structured_data(): void
    {
        $this->mockIfrsAdapter();

        $response = $this->actingAs($this->partnerUser)
            ->postJson('/api/v1/partner/reports/export', [
                'company_ids' => [$this->companyA->id],
                'from_date' => '2025-01-01',
                'to_date' => '2025-12-31',
                'report_type' => 'balance_sheet',
                'format' => 'json',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'report_type',
                    'from_date',
                    'to_date',
                    'generated_at',
                    'data' => [
                        'companies',
                    ],
                ],
            ]);

        $this->assertEquals('balance_sheet', $response->json('data.report_type'));
    }

    /**
     * Test: Consolidated endpoint also validates access.
     */
    public function test_consolidated_denies_unmanaged_company(): void
    {
        $this->mockIfrsAdapter();

        $response = $this->actingAs($this->partnerUser)
            ->postJson('/api/v1/partner/reports/consolidated', [
                'company_ids' => [$this->unlinkedCompany->id],
                'from_date' => '2025-01-01',
                'to_date' => '2025-12-31',
            ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }
}

// CLAUDE-CHECKPOINT
