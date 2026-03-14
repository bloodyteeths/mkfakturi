<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use Database\Seeders\IfrsAuditSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Modules\Mk\Models\Budget;
use Modules\Mk\Models\BudgetLine;
use Modules\Mk\Models\CostCenter;
use Modules\Mk\Services\BudgetService;
use Tests\TestCase;

class BudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BudgetService $service;
    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IfrsAuditSeeder::class);

        $this->company = Company::find(IfrsAuditSeeder::$companyId);
        $this->user = User::where('email', 'ifrs-audit@facturino.mk')->first();
        Auth::login($this->user);

        config(['ifrs.enabled' => true]);
        CompanySetting::setSettings(['ifrs_enabled' => 'YES'], $this->company->id);

        $this->service = new BudgetService();
    }

    // ─── Helper ───────────────────────────────────────────────────────

    protected function createBudget(array $overrides = []): Budget
    {
        $defaults = [
            'company_id' => $this->company->id,
            'name' => 'Test Budget 2025',
            'period_type' => 'monthly',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'draft',
            'scenario' => 'expected',
            'created_by' => $this->user->id,
        ];

        return Budget::create(array_merge($defaults, $overrides));
    }

    protected function addBudgetLine(Budget $budget, array $overrides = []): BudgetLine
    {
        $defaults = [
            'budget_id' => $budget->id,
            'account_type' => 'OPERATING_REVENUE',
            'period_start' => '2025-01-01',
            'period_end' => '2025-01-31',
            'amount' => 10000.00,
        ];

        return BudgetLine::create(array_merge($defaults, $overrides));
    }

    // ─── CRUD Tests ───────────────────────────────────────────────────

    public function test_create_budget_with_lines(): void
    {
        $data = [
            'name' => 'Q1 Revenue Budget',
            'period_type' => 'quarterly',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'scenario' => 'expected',
            'created_by' => $this->user->id,
            'lines' => [
                [
                    'account_type' => 'OPERATING_REVENUE',
                    'period_start' => '2025-01-01',
                    'period_end' => '2025-03-31',
                    'amount' => 50000,
                ],
                [
                    'account_type' => 'OPERATING_EXPENSE',
                    'period_start' => '2025-01-01',
                    'period_end' => '2025-03-31',
                    'amount' => 30000,
                ],
            ],
        ];

        $budget = $this->service->create($this->company->id, $data);

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'company_id' => $this->company->id,
            'name' => 'Q1 Revenue Budget',
            'status' => 'draft',
            'period_type' => 'quarterly',
            'scenario' => 'expected',
        ]);

        $this->assertEquals(2, $budget->lines->count());
        $this->assertDatabaseCount('budget_lines', 2);
    }

    public function test_create_budget_without_lines(): void
    {
        $data = [
            'name' => 'Empty Budget',
            'period_type' => 'monthly',
            'start_date' => '2025-01-01',
            'end_date' => '2025-06-30',
            'scenario' => 'optimistic',
        ];

        $budget = $this->service->create($this->company->id, $data);

        $this->assertDatabaseHas('budgets', ['id' => $budget->id]);
        $this->assertEquals(0, $budget->lines->count());
    }

    public function test_create_budget_with_cost_center(): void
    {
        $costCenter = CostCenter::create([
            'company_id' => $this->company->id,
            'name' => 'Marketing',
            'code' => 'MKT',
        ]);

        $data = [
            'name' => 'Marketing Budget',
            'period_type' => 'monthly',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'cost_center_id' => $costCenter->id,
            'lines' => [],
        ];

        $budget = $this->service->create($this->company->id, $data);

        $this->assertEquals($costCenter->id, $budget->cost_center_id);
    }

    public function test_update_draft_budget(): void
    {
        $budget = $this->createBudget();
        $this->addBudgetLine($budget, ['amount' => 10000]);

        $updated = $this->service->update($budget, [
            'name' => 'Updated Budget Name',
            'scenario' => 'pessimistic',
            'lines' => [
                [
                    'account_type' => 'OPERATING_REVENUE',
                    'period_start' => '2025-01-01',
                    'period_end' => '2025-01-31',
                    'amount' => 20000,
                ],
                [
                    'account_type' => 'OPERATING_EXPENSE',
                    'period_start' => '2025-01-01',
                    'period_end' => '2025-01-31',
                    'amount' => 15000,
                ],
            ],
        ]);

        $this->assertEquals('Updated Budget Name', $updated->name);
        $this->assertEquals('pessimistic', $updated->scenario);
        $this->assertEquals(2, $updated->lines->count());
    }

    public function test_update_non_draft_budget_throws(): void
    {
        $budget = $this->createBudget(['status' => 'approved']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only draft budgets can be edited.');

        $this->service->update($budget, ['name' => 'Should Fail']);
    }

    public function test_list_budgets_with_filters(): void
    {
        $this->createBudget(['name' => 'Draft One', 'status' => 'draft']);
        $this->createBudget(['name' => 'Approved One', 'status' => 'approved']);
        $this->createBudget(['name' => 'Locked One', 'status' => 'locked']);

        // All
        $all = $this->service->list($this->company->id);
        $this->assertCount(3, $all);

        // Filter by status
        $drafts = $this->service->list($this->company->id, ['status' => 'draft']);
        $this->assertCount(1, $drafts);
        $this->assertEquals('Draft One', $drafts[0]['name']);

        // Filter by scenario
        $this->createBudget(['name' => 'Optimistic', 'scenario' => 'optimistic']);
        $optimistic = $this->service->list($this->company->id, ['scenario' => 'optimistic']);
        $this->assertCount(1, $optimistic);
    }

    public function test_list_budgets_scoped_to_company(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other Co',
            'owner_id' => $this->user->id,
            'slug' => 'other-co',
            'unique_hash' => \Illuminate\Support\Str::random(20),
        ]);

        $this->createBudget(['name' => 'My Budget']);
        Budget::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Budget',
            'period_type' => 'monthly',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'draft',
            'scenario' => 'expected',
        ]);

        $myBudgets = $this->service->list($this->company->id);
        $this->assertCount(1, $myBudgets);
        $this->assertEquals('My Budget', $myBudgets[0]['name']);
    }

    public function test_format_budget_returns_all_fields(): void
    {
        $budget = $this->createBudget();
        $this->addBudgetLine($budget);

        $list = $this->service->list($this->company->id);
        $formatted = $list[0];

        $this->assertArrayHasKey('id', $formatted);
        $this->assertArrayHasKey('company_id', $formatted);
        $this->assertArrayHasKey('name', $formatted);
        $this->assertArrayHasKey('period_type', $formatted);
        $this->assertArrayHasKey('start_date', $formatted);
        $this->assertArrayHasKey('end_date', $formatted);
        $this->assertArrayHasKey('status', $formatted);
        $this->assertArrayHasKey('scenario', $formatted);
        $this->assertArrayHasKey('cost_center', $formatted);
        $this->assertArrayHasKey('lines_count', $formatted);
        $this->assertArrayHasKey('created_at', $formatted);
        $this->assertEquals(1, $formatted['lines_count']);
    }

    // ─── Status Transition Tests ──────────────────────────────────────

    public function test_approve_draft_budget(): void
    {
        $budget = $this->createBudget();

        $approved = $this->service->approve($budget, $this->user->id);

        $this->assertEquals('approved', $approved->status);
        $this->assertEquals($this->user->id, $approved->approved_by);
        $this->assertNotNull($approved->approved_at);
    }

    public function test_approve_non_draft_throws(): void
    {
        $budget = $this->createBudget(['status' => 'approved']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only draft budgets can be approved.');

        $this->service->approve($budget, $this->user->id);
    }

    public function test_lock_approved_budget(): void
    {
        $budget = $this->createBudget(['status' => 'approved']);

        $locked = $this->service->lock($budget);

        $this->assertEquals('locked', $locked->status);
    }

    public function test_lock_non_approved_throws(): void
    {
        $budget = $this->createBudget(['status' => 'draft']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only approved budgets can be locked.');

        $this->service->lock($budget);
    }

    public function test_full_lifecycle_draft_to_locked(): void
    {
        $budget = $this->createBudget();
        $this->assertEquals('draft', $budget->status);

        $approved = $this->service->approve($budget, $this->user->id);
        $this->assertEquals('approved', $approved->status);

        $locked = $this->service->lock($approved);
        $this->assertEquals('locked', $locked->status);
    }

    // ─── Prefill From Actuals Tests ───────────────────────────────────

    public function test_prefill_from_actuals_returns_lines(): void
    {
        // IfrsAuditSeeder has 2025 data with revenue and expenses
        $result = $this->service->prefillFromActuals($this->company->id, '2025');

        $this->assertEquals('2025', $result['source_year']);
        $this->assertEquals('2026', $result['target_year']);
        $this->assertEquals(0, $result['growth_pct']);
        $this->assertNotEmpty($result['lines']);

        // Each line should have required fields
        $firstLine = $result['lines'][0];
        $this->assertArrayHasKey('account_type', $firstLine);
        $this->assertArrayHasKey('account_type_label', $firstLine);
        $this->assertArrayHasKey('period_start', $firstLine);
        $this->assertArrayHasKey('period_end', $firstLine);
        $this->assertArrayHasKey('month', $firstLine);
        $this->assertArrayHasKey('actual_amount', $firstLine);
        $this->assertArrayHasKey('amount', $firstLine);
    }

    public function test_prefill_with_growth_percentage(): void
    {
        $resultZero = $this->service->prefillFromActuals($this->company->id, '2025', 0);
        $resultGrowth = $this->service->prefillFromActuals($this->company->id, '2025', 10);

        // Same number of lines
        $this->assertCount(count($resultZero['lines']), $resultGrowth['lines']);

        // Growth should be applied: amount = actual * 1.10
        foreach ($resultGrowth['lines'] as $i => $line) {
            $zeroLine = $resultZero['lines'][$i];
            $expectedAmount = round($zeroLine['actual_amount'] * 1.10, 2);
            $this->assertEqualsWithDelta($expectedAmount, $line['amount'], 0.01,
                "Growth not applied to {$line['account_type']} month {$line['month']}");
        }
    }

    public function test_prefill_targets_next_year(): void
    {
        $result = $this->service->prefillFromActuals($this->company->id, '2025');

        foreach ($result['lines'] as $line) {
            $this->assertStringStartsWith('2026-', $line['period_start'],
                'Prefilled lines should target next year');
        }
    }

    public function test_prefill_with_cost_center_filters_actuals(): void
    {
        // Create a cost center
        $costCenter = CostCenter::create([
            'company_id' => $this->company->id,
            'name' => 'Engineering',
            'code' => 'ENG',
        ]);

        // Prefill without cost center — should return lines from all ledger entries
        $allResult = $this->service->prefillFromActuals($this->company->id, '2025');

        // Prefill WITH cost center — should return fewer/no lines since seeder
        // data doesn't have cost_center_id on ledger entries
        $filteredResult = $this->service->prefillFromActuals(
            $this->company->id, '2025', 0, $costCenter->id
        );

        // The seeder doesn't assign cost centers to ledger entries,
        // so filtered result should be empty or have fewer lines
        $this->assertLessThanOrEqual(
            count($allResult['lines']),
            count($filteredResult['lines']),
            'Cost center filter should not return MORE lines than unfiltered'
        );
    }

    public function test_prefill_empty_year_returns_no_lines(): void
    {
        // 2023 has no data in the seeder
        $result = $this->service->prefillFromActuals($this->company->id, '2023');

        $this->assertEmpty($result['lines']);
        $this->assertEquals('2023', $result['source_year']);
        $this->assertEquals('2024', $result['target_year']);
    }

    // ─── Budget vs Actual Tests ───────────────────────────────────────

    public function test_budget_vs_actual_comparison(): void
    {
        // IfrsAuditSeeder has 2025 revenue data
        $budget = $this->createBudget();

        // Add a revenue line for a month we know has actual data
        $this->addBudgetLine($budget, [
            'account_type' => 'OPERATING_REVENUE',
            'period_start' => '2025-01-01',
            'period_end' => '2025-06-30',
            'amount' => 50000,
        ]);

        $comparison = $this->service->getBudgetVsActual($budget);

        $this->assertNotEmpty($comparison);

        $row = $comparison[0];
        $this->assertArrayHasKey('period_start', $row);
        $this->assertArrayHasKey('period_end', $row);
        $this->assertArrayHasKey('account_type', $row);
        $this->assertArrayHasKey('account_type_label', $row);
        $this->assertArrayHasKey('budgeted', $row);
        $this->assertArrayHasKey('actual', $row);
        $this->assertArrayHasKey('variance', $row);
        $this->assertArrayHasKey('variance_pct', $row);

        $this->assertEquals(50000.0, $row['budgeted']);
        $this->assertEquals('OPERATING_REVENUE', $row['account_type']);
    }

    public function test_budget_vs_actual_variance_calculation(): void
    {
        $budget = $this->createBudget();
        $this->addBudgetLine($budget, [
            'account_type' => 'OPERATING_REVENUE',
            'period_start' => '2025-01-01',
            'period_end' => '2025-12-31',
            'amount' => 100000,
        ]);

        $comparison = $this->service->getBudgetVsActual($budget);
        $row = $comparison[0];

        // variance = actual - budgeted
        $this->assertEqualsWithDelta(
            $row['actual'] - $row['budgeted'],
            $row['variance'],
            0.01,
            'Variance should be actual - budgeted'
        );

        // variance_pct = (variance / abs(budgeted)) * 100
        if ($row['budgeted'] != 0) {
            $expectedPct = round(($row['variance'] / abs($row['budgeted'])) * 100, 2);
            $this->assertEqualsWithDelta($expectedPct, $row['variance_pct'], 0.01);
        }
    }

    public function test_budget_vs_actual_with_cost_center_filters(): void
    {
        $costCenter = CostCenter::create([
            'company_id' => $this->company->id,
            'name' => 'Sales',
            'code' => 'SLS',
        ]);

        // Budget WITHOUT cost center — gets all actuals
        $budgetAll = $this->createBudget(['name' => 'All Departments']);
        $this->addBudgetLine($budgetAll, [
            'account_type' => 'OPERATING_REVENUE',
            'period_start' => '2025-01-01',
            'period_end' => '2025-12-31',
            'amount' => 100000,
        ]);

        // Budget WITH cost center — only gets actuals for that cost center
        $budgetFiltered = $this->createBudget([
            'name' => 'Sales Only',
            'cost_center_id' => $costCenter->id,
        ]);
        $this->addBudgetLine($budgetFiltered, [
            'account_type' => 'OPERATING_REVENUE',
            'period_start' => '2025-01-01',
            'period_end' => '2025-12-31',
            'amount' => 100000,
        ]);

        $compAll = $this->service->getBudgetVsActual($budgetAll);
        $compFiltered = $this->service->getBudgetVsActual($budgetFiltered);

        // Both should have comparison rows
        $this->assertNotEmpty($compAll);
        $this->assertNotEmpty($compFiltered);

        // Seeder data has no cost_center_id, so filtered actuals should be 0
        $filteredActual = $compFiltered[0]['actual'];
        $allActual = $compAll[0]['actual'];

        $this->assertLessThanOrEqual(abs($allActual), abs($filteredActual),
            'Cost center filtered actuals should not exceed unfiltered');
    }

    public function test_budget_vs_actual_empty_budget(): void
    {
        $budget = $this->createBudget();
        // No lines added

        $comparison = $this->service->getBudgetVsActual($budget);

        $this->assertEmpty($comparison);
    }

    public function test_budget_vs_actual_revenue_sign_convention(): void
    {
        $budget = $this->createBudget();
        $this->addBudgetLine($budget, [
            'account_type' => 'OPERATING_REVENUE',
            'period_start' => '2025-01-01',
            'period_end' => '2025-12-31',
            'amount' => 100000,
        ]);

        $comparison = $this->service->getBudgetVsActual($budget);

        // Revenue actual should be positive (credit - debit)
        // IfrsAuditSeeder has revenue entries in 2025
        if (! empty($comparison) && $comparison[0]['actual'] != 0) {
            $this->assertGreaterThan(0, $comparison[0]['actual'],
                'Revenue actuals should be positive (credit - debit)');
        }
    }

    // ─── Variance Summary Tests ───────────────────────────────────────

    public function test_variance_summary_structure(): void
    {
        $budget = $this->createBudget();
        $this->addBudgetLine($budget, [
            'account_type' => 'OPERATING_REVENUE',
            'period_start' => '2025-01-01',
            'period_end' => '2025-06-30',
            'amount' => 100000,
        ]);
        $this->addBudgetLine($budget, [
            'account_type' => 'OPERATING_EXPENSE',
            'period_start' => '2025-01-01',
            'period_end' => '2025-06-30',
            'amount' => 60000,
        ]);

        $summary = $this->service->getVarianceSummary($budget);

        $this->assertArrayHasKey('total_budgeted', $summary);
        $this->assertArrayHasKey('total_actual', $summary);
        $this->assertArrayHasKey('total_variance', $summary);
        $this->assertArrayHasKey('total_variance_pct', $summary);
        $this->assertArrayHasKey('by_account_type', $summary);
        $this->assertArrayHasKey('top_over_budget', $summary);
        $this->assertArrayHasKey('top_under_budget', $summary);

        $this->assertEquals(160000.0, $summary['total_budgeted']);
    }

    public function test_variance_summary_totals_match_comparison(): void
    {
        $budget = $this->createBudget();
        $this->addBudgetLine($budget, [
            'account_type' => 'OPERATING_REVENUE',
            'period_start' => '2025-01-01',
            'period_end' => '2025-12-31',
            'amount' => 200000,
        ]);

        $comparison = $this->service->getBudgetVsActual($budget);
        $summary = $this->service->getVarianceSummary($budget);

        // Summary totals should match sum of comparison rows
        $compBudgeted = array_sum(array_column($comparison, 'budgeted'));
        $compActual = array_sum(array_column($comparison, 'actual'));

        $this->assertEqualsWithDelta($compBudgeted, $summary['total_budgeted'], 0.01);
        $this->assertEqualsWithDelta($compActual, $summary['total_actual'], 0.01);
    }

    public function test_variance_summary_over_under_budget_categorization(): void
    {
        $budget = $this->createBudget();
        $this->addBudgetLine($budget, [
            'account_type' => 'OPERATING_REVENUE',
            'period_start' => '2025-01-01',
            'period_end' => '2025-12-31',
            'amount' => 100000,
        ]);
        $this->addBudgetLine($budget, [
            'account_type' => 'OPERATING_EXPENSE',
            'period_start' => '2025-01-01',
            'period_end' => '2025-12-31',
            'amount' => 50000,
        ]);

        $summary = $this->service->getVarianceSummary($budget);

        // Over budget items should have positive variance
        foreach ($summary['top_over_budget'] as $item) {
            $this->assertGreaterThan(0, $item['variance'],
                "{$item['account_type']} should have positive variance in over_budget");
        }

        // Under budget items should have negative variance
        foreach ($summary['top_under_budget'] as $item) {
            $this->assertLessThan(0, $item['variance'],
                "{$item['account_type']} should have negative variance in under_budget");
        }
    }

    // ─── Soft Delete Test ─────────────────────────────────────────────

    public function test_budget_soft_delete(): void
    {
        $budget = $this->createBudget();
        $budgetId = $budget->id;
        $this->addBudgetLine($budget);

        $budget->delete();

        $this->assertSoftDeleted('budgets', ['id' => $budgetId]);
        // Lines should still exist (cascade is only for hard delete)
        $this->assertDatabaseHas('budget_lines', ['budget_id' => $budgetId]);
    }

    // ─── Edge Cases ───────────────────────────────────────────────────

    public function test_prefill_with_negative_growth(): void
    {
        $result = $this->service->prefillFromActuals($this->company->id, '2025', -10);

        foreach ($result['lines'] as $line) {
            $expectedAmount = round($line['actual_amount'] * 0.90, 2);
            $this->assertEqualsWithDelta($expectedAmount, $line['amount'], 0.01,
                "Negative growth should reduce amount for {$line['account_type']}");
        }
    }

    public function test_variance_pct_zero_budget(): void
    {
        $budget = $this->createBudget();
        // A line with zero budget amount
        $this->addBudgetLine($budget, [
            'account_type' => 'OPERATING_REVENUE',
            'period_start' => '2025-01-01',
            'period_end' => '2025-12-31',
            'amount' => 0,
        ]);

        $comparison = $this->service->getBudgetVsActual($budget);

        // Should not divide by zero
        $this->assertNotEmpty($comparison);
        $row = $comparison[0];
        // When budgeted is 0 and actual is non-zero, variance_pct should be 100
        if ($row['actual'] != 0) {
            $this->assertEquals(100, $row['variance_pct']);
        } else {
            $this->assertEquals(0, $row['variance_pct']);
        }
    }

    public function test_multiple_lines_same_account_type_same_period(): void
    {
        $budget = $this->createBudget();
        // Two lines with same account_type + period
        $this->addBudgetLine($budget, [
            'account_type' => 'OPERATING_REVENUE',
            'period_start' => '2025-01-01',
            'period_end' => '2025-06-30',
            'amount' => 30000,
        ]);
        $this->addBudgetLine($budget, [
            'account_type' => 'OPERATING_REVENUE',
            'period_start' => '2025-01-01',
            'period_end' => '2025-06-30',
            'amount' => 20000,
        ]);

        $comparison = $this->service->getBudgetVsActual($budget);

        // Should be grouped: budgeted = 30000 + 20000 = 50000
        $revenueRow = collect($comparison)->firstWhere('account_type', 'OPERATING_REVENUE');
        $this->assertNotNull($revenueRow);
        $this->assertEquals(50000.0, $revenueRow['budgeted']);
    }

    public function test_list_with_year_filter(): void
    {
        $this->createBudget([
            'name' => 'Budget 2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);
        $this->createBudget([
            'name' => 'Budget 2024',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);

        $result2025 = $this->service->list($this->company->id, ['year' => 2025]);
        $this->assertCount(1, $result2025);
        $this->assertEquals('Budget 2025', $result2025[0]['name']);

        $result2024 = $this->service->list($this->company->id, ['year' => 2024]);
        $this->assertCount(1, $result2024);
        $this->assertEquals('Budget 2024', $result2024[0]['name']);
    }

    public function test_list_with_cost_center_filter(): void
    {
        $costCenter = CostCenter::create([
            'company_id' => $this->company->id,
            'name' => 'R&D',
            'code' => 'RND',
        ]);

        $this->createBudget(['name' => 'With CC', 'cost_center_id' => $costCenter->id]);
        $this->createBudget(['name' => 'Without CC']);

        $filtered = $this->service->list($this->company->id, ['cost_center_id' => $costCenter->id]);
        $this->assertCount(1, $filtered);
        $this->assertEquals('With CC', $filtered[0]['name']);
    }
}

// CLAUDE-CHECKPOINT
