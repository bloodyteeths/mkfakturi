<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ExpenseDuplicateTest
 *
 * Feature tests for Phase 1.3 - Expense Duplicate Protection functionality
 *
 * Coverage:
 * - Duplicate detection based on supplier_id + invoice_number
 * - Warning response when duplicates found
 * - Allow duplicate override functionality
 * - Different supplier/invoice_number does not trigger warning
 */
class ExpenseDuplicateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Currency $currency;

    protected ExpenseCategory $category;

    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test currency
        $this->currency = Currency::factory()->create([
            'code' => 'MKD',
            'name' => 'Macedonian Denar',
            'symbol' => 'ден',
            'precision' => 0,
        ]);

        // Create company and user
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
        ]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id);

        // Create expense category
        $this->category = ExpenseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Office Supplies',
        ]);

        // Create supplier
        $this->supplier = Supplier::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Supplier',
        ]);
    }

    // ========================================
    // DUPLICATE DETECTION TESTS
    // ========================================

    /** @test */
    public function first_expense_creates_successfully_without_warning()
    {
        $expenseData = [
            'expense_date' => '2025-01-15',
            'expense_category_id' => $this->category->id,
            'amount' => 5000,
            'currency_id' => $this->currency->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-001',
            'notes' => 'First expense',
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses', $expenseData);

        $response->assertStatus(200);

        // Should not have duplicate warning
        $this->assertFalse($response->json('is_duplicate_warning', false));

        // Expense should be created
        $this->assertDatabaseHas('expenses', [
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-001',
        ]);
    }

    /** @test */
    public function second_expense_with_same_supplier_and_invoice_number_triggers_warning()
    {
        // Create first expense
        Expense::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-001',
            'expense_category_id' => $this->category->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'expense_date' => '2025-01-10',
        ]);

        // Try to create second expense with same supplier + invoice number
        $expenseData = [
            'expense_date' => '2025-01-15',
            'expense_category_id' => $this->category->id,
            'amount' => 7500,
            'currency_id' => $this->currency->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-001',
            'notes' => 'Potential duplicate',
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses', $expenseData);

        $response->assertStatus(200);

        // Should have duplicate warning
        $this->assertTrue($response->json('is_duplicate_warning'));
        $this->assertNotEmpty($response->json('duplicates'));

        // Second expense should NOT be created yet
        $this->assertEquals(1, Expense::where('company_id', $this->company->id)->count());
    }

    /** @test */
    public function duplicate_warning_includes_existing_expense_details()
    {
        // Create first expense
        $existingExpense = Expense::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-002',
            'expense_category_id' => $this->category->id,
            'currency_id' => $this->currency->id,
            'amount' => 10000,
            'expense_date' => '2025-01-10',
        ]);

        // Try to create duplicate
        $expenseData = [
            'expense_date' => '2025-01-15',
            'expense_category_id' => $this->category->id,
            'amount' => 10000,
            'currency_id' => $this->currency->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-002',
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses', $expenseData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'is_duplicate_warning',
                'message',
                'duplicates' => [
                    '*' => [
                        'id',
                        'expense_date',
                        'amount',
                        'invoice_number',
                    ],
                ],
            ]);

        // Verify duplicate info matches existing expense
        $duplicates = $response->json('duplicates');
        $this->assertEquals($existingExpense->id, $duplicates[0]['id']);
        $this->assertEquals('INV-002', $duplicates[0]['invoice_number']);
    }

    // ========================================
    // ALLOW DUPLICATE OVERRIDE TESTS
    // ========================================

    /** @test */
    public function allow_duplicate_flag_creates_expense_despite_warning()
    {
        // Create first expense
        Expense::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-003',
            'expense_category_id' => $this->category->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'expense_date' => '2025-01-10',
        ]);

        // Create duplicate with allow_duplicate flag
        $expenseData = [
            'expense_date' => '2025-01-15',
            'expense_category_id' => $this->category->id,
            'amount' => 7500,
            'currency_id' => $this->currency->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-003',
            'allow_duplicate' => true,
            'notes' => 'Intentional duplicate',
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses', $expenseData);

        $response->assertStatus(200);

        // Should NOT have duplicate warning (expense was created)
        $this->assertFalse($response->json('is_duplicate_warning', false));

        // Both expenses should exist
        $this->assertEquals(2, Expense::where('company_id', $this->company->id)
            ->where('supplier_id', $this->supplier->id)
            ->where('invoice_number', 'INV-003')
            ->count());
    }

    // ========================================
    // NO WARNING SCENARIOS
    // ========================================

    /** @test */
    public function different_supplier_same_invoice_number_does_not_trigger_warning()
    {
        // Create second supplier
        $supplier2 = Supplier::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Second Supplier',
        ]);

        // Create expense for first supplier
        Expense::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-SAME',
            'expense_category_id' => $this->category->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'expense_date' => '2025-01-10',
        ]);

        // Create expense for second supplier with same invoice number
        $expenseData = [
            'expense_date' => '2025-01-15',
            'expense_category_id' => $this->category->id,
            'amount' => 8000,
            'currency_id' => $this->currency->id,
            'supplier_id' => $supplier2->id,
            'invoice_number' => 'INV-SAME', // Same invoice number, different supplier
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses', $expenseData);

        $response->assertStatus(200);

        // Should NOT have duplicate warning
        $this->assertFalse($response->json('is_duplicate_warning', false));

        // Both expenses should be created
        $this->assertEquals(2, Expense::where('company_id', $this->company->id)
            ->where('invoice_number', 'INV-SAME')
            ->count());
    }

    /** @test */
    public function same_supplier_different_invoice_number_does_not_trigger_warning()
    {
        // Create first expense
        Expense::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-A001',
            'expense_category_id' => $this->category->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'expense_date' => '2025-01-10',
        ]);

        // Create expense with same supplier but different invoice number
        $expenseData = [
            'expense_date' => '2025-01-15',
            'expense_category_id' => $this->category->id,
            'amount' => 6000,
            'currency_id' => $this->currency->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-A002', // Different invoice number
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses', $expenseData);

        $response->assertStatus(200);

        // Should NOT have duplicate warning
        $this->assertFalse($response->json('is_duplicate_warning', false));

        // Both expenses should be created
        $this->assertEquals(2, Expense::where('company_id', $this->company->id)
            ->where('supplier_id', $this->supplier->id)
            ->count());
    }

    /** @test */
    public function expense_without_supplier_does_not_trigger_warning()
    {
        // Create expense without supplier
        Expense::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => null,
            'invoice_number' => 'INV-NOSUP',
            'expense_category_id' => $this->category->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'expense_date' => '2025-01-10',
        ]);

        // Create another expense without supplier but same invoice number
        $expenseData = [
            'expense_date' => '2025-01-15',
            'expense_category_id' => $this->category->id,
            'amount' => 6000,
            'currency_id' => $this->currency->id,
            'supplier_id' => null,
            'invoice_number' => 'INV-NOSUP',
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses', $expenseData);

        $response->assertStatus(200);

        // Should NOT have duplicate warning (no supplier to check against)
        $this->assertFalse($response->json('is_duplicate_warning', false));
    }

    /** @test */
    public function expense_without_invoice_number_does_not_trigger_warning()
    {
        // Create expense without invoice number
        Expense::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => null,
            'expense_category_id' => $this->category->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'expense_date' => '2025-01-10',
        ]);

        // Create another expense with same supplier but no invoice number
        $expenseData = [
            'expense_date' => '2025-01-15',
            'expense_category_id' => $this->category->id,
            'amount' => 6000,
            'currency_id' => $this->currency->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => null,
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses', $expenseData);

        $response->assertStatus(200);

        // Should NOT have duplicate warning (no invoice number to check)
        $this->assertFalse($response->json('is_duplicate_warning', false));
    }

    // ========================================
    // UPDATE EXPENSE DUPLICATE CHECK
    // ========================================

    /** @test */
    public function updating_expense_excludes_itself_from_duplicate_check()
    {
        // Create expense
        $expense = Expense::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-UPDATE',
            'expense_category_id' => $this->category->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'expense_date' => '2025-01-10',
        ]);

        // Update the expense with same supplier + invoice number (itself)
        $updateData = [
            'expense_date' => '2025-01-20',
            'expense_category_id' => $this->category->id,
            'amount' => 7500, // Changed amount
            'currency_id' => $this->currency->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-UPDATE', // Same invoice number
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->putJson("/api/v1/expenses/{$expense->id}", $updateData);

        $response->assertStatus(200);

        // Should NOT have duplicate warning (updating itself)
        $this->assertFalse($response->json('is_duplicate_warning', false));

        // Amount should be updated
        $expense->refresh();
        $this->assertEquals(7500, $expense->amount);
    }

    /** @test */
    public function updating_expense_to_match_another_triggers_warning()
    {
        // Create two expenses
        $expense1 = Expense::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-FIRST',
            'expense_category_id' => $this->category->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'expense_date' => '2025-01-10',
        ]);

        $expense2 = Expense::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-SECOND',
            'expense_category_id' => $this->category->id,
            'currency_id' => $this->currency->id,
            'amount' => 6000,
            'expense_date' => '2025-01-15',
        ]);

        // Try to update expense2 to have same invoice number as expense1
        $updateData = [
            'expense_date' => '2025-01-15',
            'expense_category_id' => $this->category->id,
            'amount' => 6000,
            'currency_id' => $this->currency->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-FIRST', // Same as expense1
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->putJson("/api/v1/expenses/{$expense2->id}", $updateData);

        $response->assertStatus(200);

        // Should have duplicate warning
        $this->assertTrue($response->json('is_duplicate_warning'));

        // expense2 invoice_number should NOT be changed
        $expense2->refresh();
        $this->assertEquals('INV-SECOND', $expense2->invoice_number);
    }

    // ========================================
    // CROSS-COMPANY ISOLATION
    // ========================================

    /** @test */
    public function duplicate_check_is_scoped_to_company()
    {
        // Create another company
        $otherCompany = Company::factory()->create([
            'name' => 'Other Company',
        ]);

        $otherSupplier = Supplier::factory()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Supplier',
        ]);

        // Create expense in other company
        Expense::factory()->create([
            'company_id' => $otherCompany->id,
            'supplier_id' => $otherSupplier->id,
            'invoice_number' => 'INV-CROSS',
            'expense_category_id' => $this->category->id,
            'currency_id' => $this->currency->id,
            'amount' => 5000,
            'expense_date' => '2025-01-10',
        ]);

        // Create expense in our company with same invoice number
        $expenseData = [
            'expense_date' => '2025-01-15',
            'expense_category_id' => $this->category->id,
            'amount' => 8000,
            'currency_id' => $this->currency->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-CROSS', // Same invoice number as other company
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/expenses', $expenseData);

        $response->assertStatus(200);

        // Should NOT have duplicate warning (different company)
        $this->assertFalse($response->json('is_duplicate_warning', false));

        // Our expense should be created
        $this->assertDatabaseHas('expenses', [
            'company_id' => $this->company->id,
            'invoice_number' => 'INV-CROSS',
        ]);
    }
}

// CLAUDE-CHECKPOINT
