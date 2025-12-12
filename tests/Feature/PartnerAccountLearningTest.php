<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AccountSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PartnerAccountLearningTest extends TestCase
{
    use RefreshDatabase;

    protected User $partnerUser;

    protected Partner $partner;

    protected Company $company;

    protected Account $receivablesAccount;

    protected Account $revenueAccount;

    protected Account $expenseAccount;

    protected Account $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Create partner user
        $this->partnerUser = User::factory()->create([
            'role' => 'partner',
        ]);

        // Create partner (verified = active)
        $this->partner = Partner::factory()->verified()->create([
            'user_id' => $this->partnerUser->id,
        ]);

        // Create company
        $this->company = Company::factory()->create();

        // Link partner to company
        $this->partner->companies()->attach($this->company->id, [
            'is_active' => true,
            'linked_at' => now(),
        ]);

        // Create standard accounts
        $this->seedStandardAccounts();
    }

    protected function seedStandardAccounts(): void
    {
        $standardAccounts = json_decode(
            file_get_contents(__DIR__.'/../fixtures/accounting/standard_accounts.json'),
            true
        );

        foreach ($standardAccounts as $accountData) {
            Account::create([
                'company_id' => $this->company->id,
                'code' => $accountData['code'],
                'name' => $accountData['name'],
                'type' => $accountData['type'],
                'is_active' => true,
                'system_defined' => false,
                'description' => $accountData['description'] ?? null,
            ]);
        }

        $this->receivablesAccount = Account::where('company_id', $this->company->id)
            ->where('code', '220100')->first();
        $this->revenueAccount = Account::where('company_id', $this->company->id)
            ->where('code', '400100')->first();
        $this->expenseAccount = Account::where('company_id', $this->company->id)
            ->where('code', '540100')->first();
        $this->cashAccount = Account::where('company_id', $this->company->id)
            ->where('code', '100100')->first();
    }

    public function test_partner_can_save_learned_mapping(): void
    {
        Sanctum::actingAs($this->partnerUser);

        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->withHeaders([
            'company' => $this->company->id,
        ])->postJson("/api/v1/partner/companies/{$this->company->id}/mappings", [
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => $customer->id,
            'debit_account_id' => $this->receivablesAccount->id,
            'credit_account_id' => $this->revenueAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_INVOICE,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Account mapping created successfully.',
        ]);

        // Verify mapping was saved
        $this->assertDatabaseHas('account_mappings', [
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => $customer->id,
            'debit_account_id' => $this->receivablesAccount->id,
            'credit_account_id' => $this->revenueAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_INVOICE,
        ]);
    }

    public function test_learned_mapping_is_used_for_future_suggestions(): void
    {
        Sanctum::actingAs($this->partnerUser);

        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create learned mapping
        $this->withHeaders([
            'company' => $this->company->id,
        ])->postJson("/api/v1/partner/companies/{$this->company->id}/mappings", [
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => $customer->id,
            'debit_account_id' => $this->receivablesAccount->id,
            'credit_account_id' => $this->revenueAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_INVOICE,
        ]);

        // Create invoice for the customer
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        // Get suggestion
        $response = $this->withHeaders([
            'company' => $this->company->id,
        ])->postJson("/api/v1/partner/companies/{$this->company->id}/mappings/suggest", [
            'transaction_type' => 'invoice',
            'transaction_id' => $invoice->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'confidence' => 0.95, // High confidence due to learned mapping
                'debit_account_id' => $this->receivablesAccount->id,
                'credit_account_id' => $this->revenueAccount->id,
            ],
        ]);
    }

    public function test_partner_cannot_learn_for_unlinked_company(): void
    {
        Sanctum::actingAs($this->partnerUser);

        // Create another company not linked to partner
        $otherCompany = Company::factory()->create();

        $response = $this->withHeaders([
            'company' => $otherCompany->id,
        ])->postJson("/api/v1/partner/companies/{$otherCompany->id}/mappings", [
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => 1,
            'debit_account_id' => $this->receivablesAccount->id,
            'credit_account_id' => $this->revenueAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_INVOICE,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'No access to this company',
        ]);
    }

    public function test_bulk_accept_saves_all_high_confidence_mappings(): void
    {
        Sanctum::actingAs($this->partnerUser);

        // Create multiple customers
        $customer1 = Customer::factory()->create(['company_id' => $this->company->id]);
        $customer2 = Customer::factory()->create(['company_id' => $this->company->id]);

        // Create learned mapping for customer1
        AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => $customer1->id,
            'debit_account_id' => $this->receivablesAccount->id,
            'credit_account_id' => $this->revenueAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_INVOICE,
        ]);

        // Create invoices
        $invoice1 = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer1->id,
        ]);

        $invoice2 = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer2->id,
        ]);

        // Use the learn endpoint to save mappings for both customers
        $response = $this->withHeaders([
            'company' => $this->company->id,
        ])->postJson("/api/v1/partner/companies/{$this->company->id}/journal/learn", [
            'mappings' => [
                [
                    'entity_type' => 'customer',
                    'entity_id' => $customer1->id,
                    'account_id' => $this->receivablesAccount->id,
                    'accepted' => true,
                ],
                [
                    'entity_type' => 'customer',
                    'entity_id' => $customer2->id,
                    'account_id' => $this->receivablesAccount->id,
                    'accepted' => true,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'learned_count' => 2,
        ]);

        // Verify mappings were created for both customers
        $this->assertDatabaseHas('account_mappings', [
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => $customer1->id,
            'debit_account_id' => $this->receivablesAccount->id,
        ]);

        $this->assertDatabaseHas('account_mappings', [
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => $customer2->id,
            'debit_account_id' => $this->receivablesAccount->id,
        ]);
    }

    public function test_bulk_accept_respects_different_suggestions(): void
    {
        Sanctum::actingAs($this->partnerUser);

        $category1 = ExpenseCategory::factory()->create(['company_id' => $this->company->id]);
        $category2 = ExpenseCategory::factory()->create(['company_id' => $this->company->id]);

        $officeSuppliesAccount = Account::where('company_id', $this->company->id)
            ->where('code', '540200')->first();

        // Create mappings for different categories
        AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_EXPENSE_CATEGORY,
            'entity_id' => $category1->id,
            'debit_account_id' => $this->expenseAccount->id,
            'credit_account_id' => $this->cashAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_EXPENSE,
        ]);

        AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_EXPENSE_CATEGORY,
            'entity_id' => $category2->id,
            'debit_account_id' => $officeSuppliesAccount->id,
            'credit_account_id' => $this->cashAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_EXPENSE,
        ]);

        $expense1 = Expense::factory()->create([
            'company_id' => $this->company->id,
            'expense_category_id' => $category1->id,
        ]);

        $expense2 = Expense::factory()->create([
            'company_id' => $this->company->id,
            'expense_category_id' => $category2->id,
        ]);

        // Use the learn endpoint to save mappings for different expense categories
        $response = $this->withHeaders([
            'company' => $this->company->id,
        ])->postJson("/api/v1/partner/companies/{$this->company->id}/journal/learn", [
            'mappings' => [
                [
                    'entity_type' => 'expense_category',
                    'entity_id' => $category1->id,
                    'account_id' => $this->expenseAccount->id,
                    'accepted' => true,
                ],
                [
                    'entity_type' => 'expense_category',
                    'entity_id' => $category2->id,
                    'account_id' => $officeSuppliesAccount->id,
                    'accepted' => true,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'learned_count' => 2,
        ]);

        // Verify correct mappings exist for each category
        $this->assertDatabaseHas('account_mappings', [
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_EXPENSE_CATEGORY,
            'entity_id' => $category1->id,
            'debit_account_id' => $this->expenseAccount->id,
        ]);

        $this->assertDatabaseHas('account_mappings', [
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_EXPENSE_CATEGORY,
            'entity_id' => $category2->id,
            'debit_account_id' => $officeSuppliesAccount->id,
        ]);
    }

    public function test_override_updates_existing_mapping(): void
    {
        Sanctum::actingAs($this->partnerUser);

        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $officeSuppliesAccount = Account::where('company_id', $this->company->id)
            ->where('code', '540200')->first();

        // Create initial mapping
        $mapping = AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => $customer->id,
            'debit_account_id' => $this->receivablesAccount->id,
            'credit_account_id' => $this->revenueAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_INVOICE,
        ]);

        // Update the mapping (simulating a user correction)
        $response = $this->withHeaders([
            'company' => $this->company->id,
        ])->putJson("/api/v1/partner/companies/{$this->company->id}/mappings/{$mapping->id}", [
            'debit_account_id' => $officeSuppliesAccount->id,
            'credit_account_id' => $this->cashAccount->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Account mapping updated successfully.',
        ]);

        // Verify mapping was updated
        $mapping->refresh();
        $this->assertEquals($officeSuppliesAccount->id, $mapping->debit_account_id);
        $this->assertEquals($this->cashAccount->id, $mapping->credit_account_id);
    }

    public function test_learning_from_confirmed_transaction_creates_mapping(): void
    {
        Sanctum::actingAs($this->partnerUser);

        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        // Use the learn endpoint to save mapping for this customer
        $response = $this->withHeaders([
            'company' => $this->company->id,
        ])->postJson("/api/v1/partner/companies/{$this->company->id}/journal/learn", [
            'mappings' => [
                [
                    'entity_type' => 'customer',
                    'entity_id' => $customer->id,
                    'account_id' => $this->receivablesAccount->id,
                    'accepted' => true,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'learned_count' => 1,
        ]);

        // Verify a mapping was created
        $this->assertDatabaseHas('account_mappings', [
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => $customer->id,
            'debit_account_id' => $this->receivablesAccount->id,
        ]);
    }

    public function test_supplier_learning_creates_correct_mapping(): void
    {
        Sanctum::actingAs($this->partnerUser);

        $supplier = Supplier::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $payablesAccount = Account::where('company_id', $this->company->id)
            ->where('code', '220200')->first();

        // Use learn endpoint to save supplier mapping
        $response = $this->withHeaders([
            'company' => $this->company->id,
        ])->postJson("/api/v1/partner/companies/{$this->company->id}/journal/learn", [
            'mappings' => [
                [
                    'entity_type' => 'supplier',
                    'entity_id' => $supplier->id,
                    'account_id' => $payablesAccount->id,
                    'accepted' => true,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'learned_count' => 1,
        ]);

        // Verify supplier mapping was created
        $this->assertDatabaseHas('account_mappings', [
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_SUPPLIER,
            'entity_id' => $supplier->id,
            'debit_account_id' => $payablesAccount->id,
        ]);
    }

    public function test_expense_category_learning_creates_correct_mapping(): void
    {
        Sanctum::actingAs($this->partnerUser);

        $category = ExpenseCategory::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Use learn endpoint to save expense category mapping
        $response = $this->withHeaders([
            'company' => $this->company->id,
        ])->postJson("/api/v1/partner/companies/{$this->company->id}/journal/learn", [
            'mappings' => [
                [
                    'entity_type' => 'expense_category',
                    'entity_id' => $category->id,
                    'account_id' => $this->expenseAccount->id,
                    'accepted' => true,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'learned_count' => 1,
        ]);

        // Verify category mapping was created
        $this->assertDatabaseHas('account_mappings', [
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_EXPENSE_CATEGORY,
            'entity_id' => $category->id,
            'debit_account_id' => $this->expenseAccount->id,
        ]);
    }
}
// CLAUDE-CHECKPOINT
