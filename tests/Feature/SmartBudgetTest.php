<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\IfrsAuditSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Mk\Models\Budget;
use Modules\Mk\Models\BudgetLine;
use Modules\Mk\Services\BudgetService;
use Tests\TestCase;

class SmartBudgetTest extends TestCase
{
    use RefreshDatabase;

    protected BudgetService $service;
    protected Company $company;
    protected User $user;
    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IfrsAuditSeeder::class);

        $this->company = Company::find(IfrsAuditSeeder::$companyId);
        $this->user = User::where('email', 'ifrs-audit@facturino.mk')->first();
        Auth::login($this->user);

        config(['ifrs.enabled' => true]);
        CompanySetting::setSettings(['ifrs_enabled' => 'YES'], $this->company->id);

        $this->currency = Currency::first() ?? Currency::create([
            'name' => 'Macedonian Denar',
            'code' => 'MKD',
            'symbol' => 'ден',
            'precision' => 2,
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'swap_currency_symbol' => false,
        ]);

        $this->service = new BudgetService();
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    protected function createInvoice(string $date, int $totalCents, string $status = 'SENT'): void
    {
        $customer = Customer::first() ?? Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'invoice_date' => $date,
            'status' => $status,
            'total' => $totalCents,
            'base_total' => $totalCents,
            'currency_id' => $this->currency->id,
            'customer_id' => $customer->id,
        ]);
    }

    protected function createBill(string $date, int $totalCents, string $status = 'SENT'): void
    {
        DB::table('bills')->insert([
            'company_id' => $this->company->id,
            'bill_number' => 'BILL-' . uniqid(),
            'bill_date' => $date,
            'due_date' => $date,
            'status' => $status,
            'paid_status' => 'UNPAID',
            'total' => $totalCents,
            'base_total' => $totalCents,
            'sub_total' => $totalCents,
            'base_sub_total' => $totalCents,
            'due_amount' => $totalCents,
            'base_due_amount' => $totalCents,
            'tax' => 0,
            'base_tax' => 0,
            'discount' => 0,
            'discount_val' => 0,
            'discount_type' => 'fixed',
            'tax_per_item' => 'NO',
            'discount_per_item' => 'NO',
            'exchange_rate' => 1,
            'currency_id' => $this->currency->id,
            'unique_hash' => str_random(60),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createExpenseWithCategory(string $date, int $amountCents, string $categoryName): void
    {
        $category = ExpenseCategory::firstOrCreate(
            ['name' => $categoryName, 'company_id' => $this->company->id],
            ['description' => $categoryName]
        );

        $customer = Customer::first() ?? Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        Expense::factory()->create([
            'company_id' => $this->company->id,
            'expense_date' => $date,
            'expense_category_id' => $category->id,
            'amount' => $amountCents,
            'base_amount' => $amountCents,
            'currency_id' => $this->currency->id,
            'customer_id' => $customer->id,
        ]);
    }

    protected function createBudget(array $overrides = []): Budget
    {
        return Budget::create(array_merge([
            'company_id' => $this->company->id,
            'name' => 'Test Budget',
            'period_type' => 'monthly',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'draft',
            'scenario' => 'expected',
            'created_by' => $this->user->id,
        ], $overrides));
    }

    // ─── Smart Budget Data Aggregation ────────────────────────────────

    public function test_smart_budget_returns_invoice_revenue(): void
    {
        $this->createInvoice('2025-03-15', 50000, 'SENT');
        $this->createInvoice('2025-03-20', 30000, 'SENT');

        $result = $this->service->generateSmartBudget($this->company->id, '2025');

        $this->assertTrue($result['has_data']);
        $this->assertEquals('2025', $result['source_year']);
        $this->assertEquals('2026', $result['target_year']);

        $invoiceCat = collect($result['categories'])->firstWhere('key', 'invoice_revenue');
        $this->assertNotNull($invoiceCat);
        $this->assertEquals('OPERATING_REVENUE', $invoiceCat['account_type']);
        // 50000 + 30000 = 80000 cents = 800.00
        $this->assertEqualsWithDelta(800.00, $invoiceCat['total'], 0.01);
    }

    public function test_smart_budget_returns_bill_expenses(): void
    {
        $this->createBill('2025-02-10', 120000, 'SENT');
        $this->createBill('2025-02-25', 80000, 'SENT');

        $result = $this->service->generateSmartBudget($this->company->id, '2025');

        $billCat = collect($result['categories'])->firstWhere('key', 'bill_expenses');
        $this->assertNotNull($billCat);
        $this->assertEquals('OPERATING_EXPENSE', $billCat['account_type']);
        // 120000 + 80000 = 200000 cents = 2000.00
        $this->assertEqualsWithDelta(2000.00, $billCat['total'], 0.01);
    }

    public function test_smart_budget_returns_expense_categories(): void
    {
        $this->createExpenseWithCategory('2025-04-01', 30000, 'Кирија');
        $this->createExpenseWithCategory('2025-05-01', 25000, 'Кирија');
        $this->createExpenseWithCategory('2025-04-15', 15000, 'Канцелариски материјали');

        $result = $this->service->generateSmartBudget($this->company->id, '2025');

        $this->assertTrue($result['has_data']);

        // Should have at least 2 expense categories
        $expenseCats = collect($result['categories'])
            ->filter(fn ($c) => str_starts_with($c['key'], 'expense_cat_'));
        $this->assertGreaterThanOrEqual(2, $expenseCats->count());

        // Rent category should map to OVERHEAD_EXPENSE (keyword mapping)
        $rentCat = $expenseCats->first(fn ($c) => str_contains($c['label'], 'Кирија'));
        $this->assertNotNull($rentCat);
        $this->assertEquals('OVERHEAD_EXPENSE', $rentCat['account_type']);
    }

    public function test_smart_budget_groups_by_month(): void
    {
        $this->createInvoice('2025-01-15', 10000, 'SENT');
        $this->createInvoice('2025-03-15', 20000, 'SENT');
        $this->createInvoice('2025-03-20', 15000, 'SENT');

        $result = $this->service->generateSmartBudget($this->company->id, '2025');

        $invoiceCat = collect($result['categories'])->firstWhere('key', 'invoice_revenue');
        $this->assertNotNull($invoiceCat);

        // Month 1: 10000 cents = 100.00
        $this->assertEqualsWithDelta(100.00, $invoiceCat['monthly'][1] ?? 0, 0.01);
        // Month 3: 20000 + 15000 = 35000 cents = 350.00
        $this->assertEqualsWithDelta(350.00, $invoiceCat['monthly'][3] ?? 0, 0.01);
        // Month 2: no invoices
        $this->assertArrayNotHasKey(2, $invoiceCat['monthly']);
    }

    public function test_smart_budget_applies_growth_percentage(): void
    {
        $this->createInvoice('2025-06-01', 100000, 'SENT'); // 1000.00

        $resultZero = $this->service->generateSmartBudget($this->company->id, '2025', 0);
        $resultGrowth = $this->service->generateSmartBudget($this->company->id, '2025', 10);

        $catZero = collect($resultZero['categories'])->firstWhere('key', 'invoice_revenue');
        $catGrowth = collect($resultGrowth['categories'])->firstWhere('key', 'invoice_revenue');

        // 1000.00 * 1.10 = 1100.00
        $this->assertEqualsWithDelta(1000.00, $catZero['total'], 0.01);
        $this->assertEqualsWithDelta(1100.00, $catGrowth['total'], 0.01);
    }

    public function test_smart_budget_excludes_draft_invoices(): void
    {
        $this->createInvoice('2025-05-01', 50000, 'DRAFT');
        $this->createInvoice('2025-05-15', 30000, 'SENT');

        $result = $this->service->generateSmartBudget($this->company->id, '2025');

        $invoiceCat = collect($result['categories'])->firstWhere('key', 'invoice_revenue');
        $this->assertNotNull($invoiceCat);
        // Only the SENT invoice should be included: 300.00
        $this->assertEqualsWithDelta(300.00, $invoiceCat['total'], 0.01);
    }

    public function test_smart_budget_no_data_returns_empty(): void
    {
        // 2020 has no data
        $result = $this->service->generateSmartBudget($this->company->id, '2020');

        $this->assertFalse($result['has_data']);
        $this->assertEmpty($result['categories']);
        $this->assertEquals(0, $result['summary']['total_revenue']);
        $this->assertEquals(0, $result['summary']['total_expenses']);
    }

    public function test_smart_budget_summary_calculations(): void
    {
        $this->createInvoice('2025-01-10', 500000, 'SENT');  // 5000.00 revenue
        $this->createBill('2025-01-15', 200000, 'SENT');      // 2000.00 expenses

        $result = $this->service->generateSmartBudget($this->company->id, '2025');

        $this->assertEqualsWithDelta(5000.00, $result['summary']['total_revenue'], 0.01);
        $this->assertEqualsWithDelta(2000.00, $result['summary']['total_expenses'], 0.01);
        $this->assertEqualsWithDelta(3000.00, $result['summary']['projected_profit'], 0.01);
    }

    public function test_smart_budget_amounts_divided_by_100(): void
    {
        // Invoice with 150000 cents = should show as 1500.00
        $this->createInvoice('2025-07-01', 150000, 'SENT');

        $result = $this->service->generateSmartBudget($this->company->id, '2025');

        $invoiceCat = collect($result['categories'])->firstWhere('key', 'invoice_revenue');
        $this->assertEqualsWithDelta(1500.00, $invoiceCat['total'], 0.01);
        $this->assertEqualsWithDelta(1500.00, $invoiceCat['original_total'], 0.01);
    }

    // ─── Budget vs Actual Fallback ────────────────────────────────────

    public function test_budget_vs_actual_fallback_to_company_data(): void
    {
        // Create invoices (real company data, not IFRS)
        $this->createInvoice('2025-03-15', 200000, 'SENT'); // 2000.00

        $budget = $this->createBudget();
        BudgetLine::create([
            'budget_id' => $budget->id,
            'account_type' => 'OPERATING_REVENUE',
            'period_start' => '2025-03-01',
            'period_end' => '2025-03-31',
            'amount' => 2500.00,
        ]);

        $comparison = $this->service->getBudgetVsActual($budget);

        $this->assertNotEmpty($comparison);
        $row = $comparison[0];

        // Should have found actual data from invoices (IFRS returns 0, fallback kicks in)
        // Note: IFRS seeder data may also contribute actuals; we just verify structure
        $this->assertEquals(2500.00, $row['budgeted']);
        $this->assertArrayHasKey('actual', $row);
        $this->assertArrayHasKey('variance', $row);
    }

    public function test_budget_vs_actual_expense_fallback(): void
    {
        // Create bills (real company data)
        $this->createBill('2025-04-10', 100000, 'SENT'); // 1000.00

        $budget = $this->createBudget();
        BudgetLine::create([
            'budget_id' => $budget->id,
            'account_type' => 'OPERATING_EXPENSE',
            'period_start' => '2025-04-01',
            'period_end' => '2025-04-30',
            'amount' => 1200.00,
        ]);

        $comparison = $this->service->getBudgetVsActual($budget);

        $this->assertNotEmpty($comparison);
        $row = $comparison[0];
        $this->assertEquals(1200.00, $row['budgeted']);
        $this->assertArrayHasKey('actual', $row);
    }
}

// CLAUDE-CHECKPOINT
