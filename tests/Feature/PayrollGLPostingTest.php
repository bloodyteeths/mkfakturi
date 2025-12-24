<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PayrollRun;
use App\Models\User;
use IFRS\Models\Account;
use IFRS\Models\Currency;
use IFRS\Models\Entity;
use IFRS\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Mk\Payroll\Services\PayrollGLService;
use Tests\TestCase;

/**
 * Payroll GL Posting Test
 *
 * Tests the IFRS General Ledger integration for payroll:
 * - Posts payroll runs to correct GL accounts
 * - Verifies debit/credit entries balance
 * - Tests account mappings (420, 421, 240, 241)
 * - Tests reversal functionality
 */
class PayrollGLPostingTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected Currency $currency;

    protected Entity $entity;

    protected PayrollGLService $glService;

    protected function setUp(): void
    {
        parent::setUp();

        // Only run these tests if IFRS is enabled
        if (! $this->isIfrsAvailable()) {
            $this->markTestSkipped('IFRS package is not available');
        }

        // Enable IFRS
        config(['ifrs.enabled' => true]);

        $this->glService = new PayrollGLService();

        // Create test currency
        $this->currency = Currency::create([
            'name' => 'Macedonian Denar',
            'currency_code' => 'MKD',
        ]);

        // Create test entity
        $this->entity = Entity::create([
            'name' => 'Test Company',
            'currency_id' => $this->currency->id,
        ]);

        // Create test user and company
        $this->user = User::factory()->create();
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'currency_id' => $this->currency->id,
        ]);

        // Enable IFRS for company
        \App\Models\CompanySetting::setSetting('ifrs_enabled', 'YES', $this->company->id);

        // Associate user with company
        $this->user->companies()->attach($this->company->id, [
            'role' => 'owner',
        ]);
    }

    /** @test */
    public function it_posts_payroll_run_to_general_ledger()
    {
        // Create approved payroll run
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_APPROVED,
            'total_gross' => 10000000, // 100,000 MKD
            'total_net' => 7699500, // 76,995 MKD (from roadmap example)
            'total_employer_tax' => 1275000, // 12,750 MKD (employer contributions)
            'total_employee_tax' => 2300500, // 23,005 MKD (employee deductions + tax)
            'employee_count' => 1,
            'calculated_at' => now(),
            'approved_at' => now(),
            'approved_by' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        // Post to GL
        $transactionId = $this->glService->postPayrollRun($run);

        $this->assertNotNull($transactionId);

        // Verify payroll run was updated
        $run->refresh();
        $this->assertEquals($transactionId, $run->ifrs_transaction_id);

        // Verify transaction was created
        $this->assertDatabaseHas('ifrs_transactions', [
            'id' => $transactionId,
            'transaction_type' => Transaction::JN, // Journal Entry
        ]);

        // Verify transaction was posted (not in draft)
        $transaction = Transaction::find($transactionId);
        $this->assertTrue($transaction->posted);
    }

    /** @test */
    public function it_creates_correct_debit_entries_for_expenses()
    {
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_APPROVED,
            'total_gross' => 10000000, // 100,000 MKD
            'total_net' => 7699500,
            'total_employer_tax' => 1275000, // 12,750 MKD
            'total_employee_tax' => 2300500,
            'employee_count' => 1,
            'calculated_at' => now(),
            'approved_at' => now(),
            'approved_by' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        $transactionId = $this->glService->postPayrollRun($run);
        $transaction = Transaction::find($transactionId);

        // Check salary expense debit (account 420)
        $salaryExpenseLine = $transaction->lineItems()
            ->whereHas('account', function ($q) {
                $q->where('code', '420');
            })
            ->where('credited', false)
            ->first();

        $this->assertNotNull($salaryExpenseLine);
        $this->assertEquals(100000, $salaryExpenseLine->amount); // 100,000 MKD

        // Check employer contribution debit (account 421)
        $employerContribLine = $transaction->lineItems()
            ->whereHas('account', function ($q) {
                $q->where('code', '421');
            })
            ->where('credited', false)
            ->first();

        $this->assertNotNull($employerContribLine);
        $this->assertEquals(12750, $employerContribLine->amount); // 12,750 MKD
    }

    /** @test */
    public function it_creates_correct_credit_entries_for_liabilities()
    {
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_APPROVED,
            'total_gross' => 10000000, // 100,000 MKD
            'total_net' => 7699500, // 76,995 MKD
            'total_employer_tax' => 1275000, // 12,750 MKD
            'total_employee_tax' => 2300500, // 23,005 MKD
            'employee_count' => 1,
            'calculated_at' => now(),
            'approved_at' => now(),
            'approved_by' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        $transactionId = $this->glService->postPayrollRun($run);
        $transaction = Transaction::find($transactionId);

        // Check net salary payable credit (account 240)
        $netPayableLine = $transaction->lineItems()
            ->whereHas('account', function ($q) {
                $q->where('code', '240');
            })
            ->where('credited', true)
            ->first();

        $this->assertNotNull($netPayableLine);
        $this->assertEquals(76995, $netPayableLine->amount); // 76,995 MKD

        // Check tax liability credit (account 241)
        $taxLiabilityLine = $transaction->lineItems()
            ->whereHas('account', function ($q) {
                $q->where('code', '241');
            })
            ->where('credited', true)
            ->first();

        $this->assertNotNull($taxLiabilityLine);
        // Tax liability = Employee deductions + Employer contributions
        // 23,005 + 12,750 = 35,755 MKD
        $this->assertEquals(35755, $taxLiabilityLine->amount);
    }

    /** @test */
    public function it_ensures_debits_equal_credits()
    {
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_APPROVED,
            'total_gross' => 10000000,
            'total_net' => 7699500,
            'total_employer_tax' => 1275000,
            'total_employee_tax' => 2300500,
            'employee_count' => 1,
            'calculated_at' => now(),
            'approved_at' => now(),
            'approved_by' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        $transactionId = $this->glService->postPayrollRun($run);
        $transaction = Transaction::find($transactionId);

        // Calculate total debits
        $totalDebits = $transaction->lineItems()
            ->where('credited', false)
            ->sum('amount');

        // Calculate total credits
        $totalCredits = $transaction->lineItems()
            ->where('credited', true)
            ->sum('amount');

        // Verify they balance
        $this->assertEquals($totalDebits, $totalCredits, 'Debits must equal credits');

        // Expected: Gross (100,000) + Employer tax (12,750) = 112,750
        $this->assertEquals(112750, $totalDebits);
        $this->assertEquals(112750, $totalCredits);
    }

    /** @test */
    public function it_uses_correct_account_types()
    {
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_APPROVED,
            'total_gross' => 10000000,
            'total_net' => 7699500,
            'total_employer_tax' => 1275000,
            'total_employee_tax' => 2300500,
            'employee_count' => 1,
            'calculated_at' => now(),
            'approved_at' => now(),
            'approved_by' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        $this->glService->postPayrollRun($run);

        // Verify account 420 (Salary Expense) is OPERATING_EXPENSE
        $account420 = Account::where('code', '420')
            ->where('entity_id', $this->entity->id)
            ->first();
        $this->assertNotNull($account420);
        $this->assertEquals(Account::OPERATING_EXPENSE, $account420->account_type);

        // Verify account 421 (Employer Contributions) is OPERATING_EXPENSE
        $account421 = Account::where('code', '421')
            ->where('entity_id', $this->entity->id)
            ->first();
        $this->assertNotNull($account421);
        $this->assertEquals(Account::OPERATING_EXPENSE, $account421->account_type);

        // Verify account 240 (Net Salary Payable) is CURRENT_LIABILITY
        $account240 = Account::where('code', '240')
            ->where('entity_id', $this->entity->id)
            ->first();
        $this->assertNotNull($account240);
        $this->assertEquals(Account::CURRENT_LIABILITY, $account240->account_type);

        // Verify account 241 (Tax Liability) is CURRENT_LIABILITY
        $account241 = Account::where('code', '241')
            ->where('entity_id', $this->entity->id)
            ->first();
        $this->assertNotNull($account241);
        $this->assertEquals(Account::CURRENT_LIABILITY, $account241->account_type);
    }

    /** @test */
    public function it_creates_accounts_with_macedonian_names()
    {
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_APPROVED,
            'total_gross' => 10000000,
            'total_net' => 7699500,
            'total_employer_tax' => 1275000,
            'total_employee_tax' => 2300500,
            'employee_count' => 1,
            'calculated_at' => now(),
            'approved_at' => now(),
            'approved_by' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        $this->glService->postPayrollRun($run);

        // Verify Macedonian account names
        $this->assertDatabaseHas('ifrs_accounts', [
            'code' => '420',
            'name' => 'Плати на вработени',
        ]);

        $this->assertDatabaseHas('ifrs_accounts', [
            'code' => '421',
            'name' => 'Придонеси на товар на работодавач',
        ]);

        $this->assertDatabaseHas('ifrs_accounts', [
            'code' => '240',
            'name' => 'Обврски за нето плати',
        ]);

        $this->assertDatabaseHas('ifrs_accounts', [
            'code' => '241',
            'name' => 'Обврски за придонеси',
        ]);
    }

    /** @test */
    public function it_prevents_double_posting()
    {
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_APPROVED,
            'total_gross' => 10000000,
            'total_net' => 7699500,
            'total_employer_tax' => 1275000,
            'total_employee_tax' => 2300500,
            'employee_count' => 1,
            'calculated_at' => now(),
            'approved_at' => now(),
            'approved_by' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        // First posting
        $firstTransactionId = $this->glService->postPayrollRun($run);
        $this->assertNotNull($firstTransactionId);

        // Attempt second posting
        $secondTransactionId = $this->glService->postPayrollRun($run);

        // Should return same transaction ID (idempotent)
        $this->assertEquals($firstTransactionId, $secondTransactionId);

        // Verify only one transaction exists
        $transactionCount = Transaction::where('id', $firstTransactionId)->count();
        $this->assertEquals(1, $transactionCount);
    }

    /** @test */
    public function it_cannot_post_unapproved_payroll_run()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only approved payroll runs can be posted');

        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_CALCULATED, // Not approved
            'total_gross' => 10000000,
            'total_net' => 7699500,
            'calculated_at' => now(),
            'creator_id' => $this->user->id,
        ]);

        $this->glService->postPayrollRun($run);
    }

    /** @test */
    public function it_can_reverse_posted_payroll_run()
    {
        // Create and post payroll run
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_APPROVED,
            'total_gross' => 10000000,
            'total_net' => 7699500,
            'total_employer_tax' => 1275000,
            'total_employee_tax' => 2300500,
            'employee_count' => 1,
            'calculated_at' => now(),
            'approved_at' => now(),
            'approved_by' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        $originalTransactionId = $this->glService->postPayrollRun($run);

        // Reverse the posting
        $this->glService->reversePayrollRun($run);

        // Verify ifrs_transaction_id was cleared
        $run->refresh();
        $this->assertNull($run->ifrs_transaction_id);

        // Verify a reversing transaction was created
        $reversingTransaction = Transaction::where('narration', 'like', 'REVERSAL:%')
            ->latest()
            ->first();
        $this->assertNotNull($reversingTransaction);
    }

    /** @test */
    public function it_provides_journal_preview_before_posting()
    {
        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_APPROVED,
            'total_gross' => 10000000, // 100,000 MKD
            'total_net' => 7699500, // 76,995 MKD
            'total_employer_tax' => 1275000, // 12,750 MKD
            'total_employee_tax' => 2300500,
            'employee_count' => 1,
            'calculated_at' => now(),
            'approved_at' => now(),
            'approved_by' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        $preview = $this->glService->getJournalPreview($run);

        $this->assertIsArray($preview);
        $this->assertArrayHasKey('narration', $preview);
        $this->assertArrayHasKey('lines', $preview);
        $this->assertArrayHasKey('totals', $preview);

        // Verify 4 lines (2 debits, 2 credits)
        $this->assertCount(4, $preview['lines']);

        // Verify totals balance
        $this->assertEquals(
            $preview['totals']['debit'],
            $preview['totals']['credit'],
            'Preview totals should balance'
        );

        // Verify debit total = 112,750 MKD
        $this->assertEquals(112750, $preview['totals']['debit']);
    }

    /** @test */
    public function it_checks_if_payroll_run_can_be_posted()
    {
        // Draft run - cannot post
        $draftRun = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_DRAFT,
            'employee_count' => 1,
            'creator_id' => $this->user->id,
        ]);

        $this->assertFalse($this->glService->canPost($draftRun));

        // Approved run with employees - can post
        $approvedRun = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->subMonth()->year,
            'period_month' => now()->subMonth()->month,
            'period_start' => now()->subMonth()->startOfMonth(),
            'period_end' => now()->subMonth()->endOfMonth(),
            'status' => PayrollRun::STATUS_APPROVED,
            'total_gross' => 10000000,
            'total_net' => 7699500,
            'employee_count' => 1,
            'calculated_at' => now(),
            'approved_at' => now(),
            'approved_by' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        $this->assertTrue($this->glService->canPost($approvedRun));
    }

    /** @test */
    public function it_skips_posting_when_ifrs_is_disabled()
    {
        // Disable IFRS
        \App\Models\CompanySetting::setSetting('ifrs_enabled', 'NO', $this->company->id);

        $run = PayrollRun::create([
            'company_id' => $this->company->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => PayrollRun::STATUS_APPROVED,
            'total_gross' => 10000000,
            'total_net' => 7699500,
            'total_employer_tax' => 1275000,
            'total_employee_tax' => 2300500,
            'employee_count' => 1,
            'calculated_at' => now(),
            'approved_at' => now(),
            'approved_by' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        $transactionId = $this->glService->postPayrollRun($run);

        // Should return null when IFRS is disabled
        $this->assertNull($transactionId);

        // Verify no transaction was created
        $run->refresh();
        $this->assertNull($run->ifrs_transaction_id);
    }

    /**
     * Check if IFRS package is available
     */
    protected function isIfrsAvailable(): bool
    {
        return class_exists(\IFRS\Models\Transaction::class);
    }
}

// LLM-CHECKPOINT
