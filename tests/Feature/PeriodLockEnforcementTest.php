<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Payment;
use App\Models\PeriodLock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

/**
 * Tests for Period Lock Enforcement (PLE-01 through PLE-09)
 *
 * These tests verify that documents cannot be created, edited, or deleted
 * when their dates fall within a locked period.
 */
class PeriodLockEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected Customer $customer;

    protected Currency $currency;

    protected ExpenseCategory $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::factory()->create();

        $this->company = Company::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $this->user = User::factory()->create([
            'role' => 'super admin',
        ]);
        $this->user->companies()->attach($this->company->id);

        // Grant all abilities
        Bouncer::allow($this->user)->everything();

        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
        ]);

        $this->expenseCategory = ExpenseCategory::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    protected function createPeriodLock(string $startDate, string $endDate): PeriodLock
    {
        return PeriodLock::create([
            'company_id' => $this->company->id,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'locked_by' => $this->user->id,
            'locked_at' => now(),
        ]);
    }

    // ==================== INVOICE TESTS ====================

    /** @test */
    public function cannot_create_invoice_in_locked_period()
    {
        $this->createPeriodLock('2025-01-01', '2025-01-31');

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/invoices', $this->getInvoiceData('2025-01-15', 'INV-001'));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['invoice_date']);
        $this->assertStringContainsString('locked', strtolower($response->json('errors.invoice_date.0')));
    }

    /** @test */
    public function can_create_invoice_outside_locked_period()
    {
        $this->createPeriodLock('2025-01-01', '2025-01-31');

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/invoices', $this->getInvoiceData('2025-02-15', 'INV-002'));

        // Should pass validation (may still fail for other reasons in test env)
        $this->assertNotEquals(422, $response->status(), 'Should not get validation error for unlocked date');
        $this->assertArrayNotHasKey('invoice_date', $response->json('errors') ?? []);
    }

    protected function getInvoiceData(string $date, string $number): array
    {
        return [
            'invoice_date' => $date,
            'due_date' => $date,
            'invoice_number' => $number,
            'customer_id' => $this->customer->id,
            'discount' => 0,
            'discount_val' => 0,
            'discount_type' => 'fixed',
            'sub_total' => 100000,
            'total' => 100000,
            'tax' => 0,
            'template_name' => 'invoice1',
            'currency_id' => $this->currency->id,
            'items' => [
                [
                    'name' => 'Test Item',
                    'description' => 'Test description',
                    'quantity' => 1,
                    'price' => 100000,
                    'discount' => 0,
                    'discount_val' => 0,
                    'discount_type' => 'fixed',
                    'tax' => 0,
                    'total' => 100000,
                    'unit_name' => 'pcs',
                ],
            ],
        ];
    }

    /** @test */
    public function cannot_update_invoice_to_locked_period()
    {
        // Create invoice first (before lock)
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_date' => '2025-02-15',
            'creator_id' => $this->user->id,
        ]);

        // Then create the lock
        $this->createPeriodLock('2025-01-01', '2025-01-31');

        // Try to update to a locked date
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->putJson("/api/v1/invoices/{$invoice->id}", array_merge(
                $this->getInvoiceData('2025-01-15', $invoice->invoice_number),
                ['customer_id' => $this->customer->id]
            ));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['invoice_date']);
    }

    /** @test */
    public function cannot_edit_invoice_with_original_date_in_locked_period()
    {
        // Create invoice in January
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_date' => '2025-01-15',
            'creator_id' => $this->user->id,
        ]);

        // Lock January
        $this->createPeriodLock('2025-01-01', '2025-01-31');

        // Try to edit (even moving to unlocked date should fail)
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->putJson("/api/v1/invoices/{$invoice->id}", array_merge(
                $this->getInvoiceData('2025-02-15', $invoice->invoice_number),
                ['customer_id' => $this->customer->id]
            ));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['invoice_date']);
        // Should mention original date is locked
        $errors = $response->json('errors.invoice_date');
        $hasOriginalMessage = collect($errors)->contains(fn ($e) => str_contains(strtolower($e), 'original'));
        $this->assertTrue($hasOriginalMessage, 'Should mention original date is locked');
    }

    /** @test */
    public function cannot_delete_invoice_in_locked_period()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_date' => '2025-01-15',
            'creator_id' => $this->user->id,
        ]);

        $this->createPeriodLock('2025-01-01', '2025-01-31');

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/invoices/delete', [
                'ids' => [$invoice->id],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ids']);
    }

    // ==================== EXPENSE TESTS ====================

    /** @test */
    public function cannot_create_expense_in_locked_period()
    {
        $this->createPeriodLock('2025-01-01', '2025-01-31');

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses', [
                'expense_date' => '2025-01-15',
                'expense_category_id' => $this->expenseCategory->id,
                'amount' => 500,
                'currency_id' => $this->currency->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['expense_date']);
    }

    /** @test */
    public function can_create_expense_outside_locked_period()
    {
        $this->createPeriodLock('2025-01-01', '2025-01-31');

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses', [
                'expense_date' => '2025-02-15',
                'expense_category_id' => $this->expenseCategory->id,
                'amount' => 500,
                'currency_id' => $this->currency->id,
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function cannot_update_expense_to_locked_period()
    {
        $expense = Expense::factory()->create([
            'company_id' => $this->company->id,
            'expense_category_id' => $this->expenseCategory->id,
            'expense_date' => '2025-02-15',
            'creator_id' => $this->user->id,
        ]);

        $this->createPeriodLock('2025-01-01', '2025-01-31');

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->putJson("/api/v1/expenses/{$expense->id}", [
                'expense_date' => '2025-01-15', // Locked
                'expense_category_id' => $this->expenseCategory->id,
                'amount' => 600,
                'currency_id' => $this->currency->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['expense_date']);
    }

    /** @test */
    public function cannot_delete_expense_in_locked_period()
    {
        $expense = Expense::factory()->create([
            'company_id' => $this->company->id,
            'expense_category_id' => $this->expenseCategory->id,
            'expense_date' => '2025-01-15',
            'creator_id' => $this->user->id,
        ]);

        $this->createPeriodLock('2025-01-01', '2025-01-31');

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses/delete', [
                'ids' => [$expense->id],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ids']);
    }

    // ==================== PAYMENT TESTS ====================

    /** @test */
    public function cannot_create_payment_in_locked_period()
    {
        $this->createPeriodLock('2025-01-01', '2025-01-31');

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/payments', [
                'payment_date' => '2025-01-15',
                'payment_number' => 'PAY-001',
                'customer_id' => $this->customer->id,
                'amount' => 500,
                'currency_id' => $this->currency->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_date']);
    }

    /** @test */
    public function can_create_payment_outside_locked_period()
    {
        $this->createPeriodLock('2025-01-01', '2025-01-31');

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/payments', [
                'payment_date' => '2025-02-15',
                'payment_number' => 'PAY-002',
                'customer_id' => $this->customer->id,
                'amount' => 500,
                'currency_id' => $this->currency->id,
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function cannot_update_payment_to_locked_period()
    {
        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'payment_date' => '2025-02-15',
            'creator_id' => $this->user->id,
        ]);

        $this->createPeriodLock('2025-01-01', '2025-01-31');

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->putJson("/api/v1/payments/{$payment->id}", [
                'payment_date' => '2025-01-15', // Locked
                'payment_number' => $payment->payment_number,
                'customer_id' => $this->customer->id,
                'amount' => 600,
                'currency_id' => $this->currency->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_date']);
    }

    /** @test */
    public function cannot_delete_payment_in_locked_period()
    {
        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'payment_date' => '2025-01-15',
            'creator_id' => $this->user->id,
        ]);

        $this->createPeriodLock('2025-01-01', '2025-01-31');

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/payments/delete', [
                'ids' => [$payment->id],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ids']);
    }

    // ==================== EDGE CASE TESTS ====================

    /** @test */
    public function lock_boundary_dates_are_included()
    {
        // Lock exactly Jan 15
        $this->createPeriodLock('2025-01-15', '2025-01-15');

        // Jan 15 should be locked
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses', [
                'expense_date' => '2025-01-15',
                'expense_category_id' => $this->expenseCategory->id,
                'amount' => 500,
                'currency_id' => $this->currency->id,
            ]);

        $response->assertStatus(422);

        // Jan 14 should be open
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses', [
                'expense_date' => '2025-01-14',
                'expense_category_id' => $this->expenseCategory->id,
                'amount' => 500,
                'currency_id' => $this->currency->id,
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function multiple_period_locks_work_correctly()
    {
        // Lock Q1 and Q3
        $this->createPeriodLock('2025-01-01', '2025-03-31');
        $this->createPeriodLock('2025-07-01', '2025-09-30');

        // Q1 locked
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses', [
                'expense_date' => '2025-02-15',
                'expense_category_id' => $this->expenseCategory->id,
                'amount' => 500,
                'currency_id' => $this->currency->id,
            ]);
        $response->assertStatus(422);

        // Q2 open
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses', [
                'expense_date' => '2025-05-15',
                'expense_category_id' => $this->expenseCategory->id,
                'amount' => 500,
                'currency_id' => $this->currency->id,
            ]);
        $response->assertStatus(200);

        // Q3 locked
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses', [
                'expense_date' => '2025-08-15',
                'expense_category_id' => $this->expenseCategory->id,
                'amount' => 500,
                'currency_id' => $this->currency->id,
            ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function period_lock_is_company_specific()
    {
        // Create another company
        $otherCompany = Company::factory()->create([
            'currency_id' => $this->currency->id,
        ]);
        $this->user->companies()->attach($otherCompany->id);

        $otherCategory = ExpenseCategory::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        // Lock only for original company
        $this->createPeriodLock('2025-01-01', '2025-01-31');

        // Original company - blocked
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses', [
                'expense_date' => '2025-01-15',
                'expense_category_id' => $this->expenseCategory->id,
                'amount' => 500,
                'currency_id' => $this->currency->id,
            ]);
        $response->assertStatus(422);

        // Other company - allowed
        $response = $this->actingAs($this->user)
            ->withHeader('company', $otherCompany->id)
            ->postJson('/api/v1/expenses', [
                'expense_date' => '2025-01-15',
                'expense_category_id' => $otherCategory->id,
                'amount' => 500,
                'currency_id' => $this->currency->id,
            ]);
        $response->assertStatus(200);
    }
}
