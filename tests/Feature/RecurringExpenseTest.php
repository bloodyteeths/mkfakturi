<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\ExpenseCategory;
use App\Models\RecurringExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_recurring_expense(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create(['company_id' => $company->id]);
        $currency = Currency::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/v1/admin/{$company->id}/recurring-expenses", [
                'expense_category_id' => $category->id,
                'currency_id' => $currency->id,
                'amount' => 1000.00,
                'notes' => 'Monthly rent',
                'frequency' => 'monthly',
                'next_occurrence_at' => now()->addMonth()->toDateString(),
                'is_active' => true,
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['recurring_expense', 'message']);

        $this->assertDatabaseHas('recurring_expenses', [
            'company_id' => $company->id,
            'expense_category_id' => $category->id,
            'amount' => 1000.00,
            'frequency' => 'monthly',
            'is_active' => true,
        ]);
    }

    public function test_process_recurring_expense_manually(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create(['company_id' => $company->id]);
        $currency = Currency::factory()->create();

        $recurringExpense = RecurringExpense::factory()->create([
            'company_id' => $company->id,
            'expense_category_id' => $category->id,
            'currency_id' => $currency->id,
            'amount' => 500.00,
            'frequency' => 'monthly',
            'next_occurrence_at' => now(),
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/admin/{$company->id}/recurring-expenses/{$recurringExpense->id}/process-now");

        $response->assertStatus(201);
        $response->assertJsonStructure(['expense', 'message']);

        $this->assertDatabaseHas('expenses', [
            'company_id' => $company->id,
            'expense_category_id' => $category->id,
            'amount' => 500.00,
        ]);
    }

    public function test_recurring_expense_due_for_processing_scope(): void
    {
        $company = Company::factory()->create();
        $category = ExpenseCategory::factory()->create(['company_id' => $company->id]);
        $currency = Currency::factory()->create();
        $user = User::factory()->create();

        // Create due recurring expense
        $dueExpense = RecurringExpense::factory()->create([
            'company_id' => $company->id,
            'expense_category_id' => $category->id,
            'currency_id' => $currency->id,
            'next_occurrence_at' => now()->subDay(),
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        // Create future recurring expense
        $futureExpense = RecurringExpense::factory()->create([
            'company_id' => $company->id,
            'expense_category_id' => $category->id,
            'currency_id' => $currency->id,
            'next_occurrence_at' => now()->addWeek(),
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $dueExpenses = RecurringExpense::dueForProcessing()->get();

        $this->assertCount(1, $dueExpenses);
        $this->assertTrue($dueExpenses->contains($dueExpense));
        $this->assertFalse($dueExpenses->contains($futureExpense));
    }
}
// CLAUDE-CHECKPOINT
