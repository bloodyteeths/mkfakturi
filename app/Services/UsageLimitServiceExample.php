<?php

/**
 * UsageLimitService Usage Examples
 *
 * This file demonstrates how to use the UsageLimitService in your application.
 * DO NOT include this file in production - it's for documentation purposes only.
 */

namespace App\Services;

use App\Models\Company;

class UsageLimitServiceExample
{
    private UsageLimitService $usageService;

    public function __construct(UsageLimitService $usageService)
    {
        $this->usageService = $usageService;
    }

    /**
     * Example 1: Check if company can create an expense
     */
    public function canCreateExpense(Company $company): bool
    {
        // Check if the company has remaining expense capacity
        if (! $this->usageService->canUse($company, 'expenses_per_month')) {
            // Show upgrade message to user
            $usage = $this->usageService->getUsage($company, 'expenses_per_month');
            $tier = $this->usageService->getCompanyTier($company);

            // Get upgrade message from config
            $message = config("subscriptions.upgrade_messages.invoice_limit.{$tier}");

            return false;
        }

        return true;
    }

    /**
     * Example 2: Increment usage after creating an expense
     */
    public function afterCreateExpense(Company $company): void
    {
        // Increment the usage counter
        $this->usageService->incrementUsage($company, 'expenses_per_month');
    }

    /**
     * Example 3: Decrement usage after deleting an expense
     */
    public function afterDeleteExpense(Company $company): void
    {
        // Decrement the usage counter
        $this->usageService->decrementUsage($company, 'expenses_per_month');
    }

    /**
     * Example 4: Check custom field limits before creating
     */
    public function canCreateCustomField(Company $company): array
    {
        $usage = $this->usageService->getUsage($company, 'custom_fields');

        if ($usage['remaining'] === null) {
            // Unlimited - user is on Business or Max plan
            return [
                'can_create' => true,
                'message' => 'You have unlimited custom fields.',
            ];
        }

        if ($usage['remaining'] > 0) {
            return [
                'can_create' => true,
                'message' => "You can create {$usage['remaining']} more custom fields.",
            ];
        }

        // At limit
        return [
            'can_create' => false,
            'message' => "You've reached your custom field limit ({$usage['limit']}). Upgrade to create more.",
        ];
    }

    /**
     * Example 5: Show usage dashboard to user
     */
    public function getUsageDashboard(Company $company): array
    {
        $tier = $this->usageService->getCompanyTier($company);
        $allUsage = $this->usageService->getAllUsage($company);

        return [
            'tier' => $tier,
            'tier_name' => config("subscriptions.tiers.{$tier}.name"),
            'usage' => $allUsage,
            'features' => [
                'expenses' => [
                    'name' => 'Expenses',
                    'used' => $allUsage['expenses_per_month']['used'],
                    'limit' => $allUsage['expenses_per_month']['limit'],
                    'remaining' => $allUsage['expenses_per_month']['remaining'],
                    'is_unlimited' => $allUsage['expenses_per_month']['limit'] === null,
                ],
                'estimates' => [
                    'name' => 'Estimates',
                    'used' => $allUsage['estimates_per_month']['used'],
                    'limit' => $allUsage['estimates_per_month']['limit'],
                    'remaining' => $allUsage['estimates_per_month']['remaining'],
                    'is_unlimited' => $allUsage['estimates_per_month']['limit'] === null,
                ],
                'custom_fields' => [
                    'name' => 'Custom Fields',
                    'used' => $allUsage['custom_fields']['used'],
                    'limit' => $allUsage['custom_fields']['limit'],
                    'remaining' => $allUsage['custom_fields']['remaining'],
                    'is_unlimited' => $allUsage['custom_fields']['limit'] === null,
                ],
                'recurring_invoices' => [
                    'name' => 'Active Recurring Invoices',
                    'used' => $allUsage['recurring_invoices_active']['used'],
                    'limit' => $allUsage['recurring_invoices_active']['limit'],
                    'remaining' => $allUsage['recurring_invoices_active']['remaining'],
                    'is_unlimited' => $allUsage['recurring_invoices_active']['limit'] === null,
                ],
                'ai_queries' => [
                    'name' => 'AI Queries',
                    'used' => $allUsage['ai_queries_per_month']['used'],
                    'limit' => $allUsage['ai_queries_per_month']['limit'],
                    'remaining' => $allUsage['ai_queries_per_month']['remaining'],
                    'is_unlimited' => $allUsage['ai_queries_per_month']['limit'] === null,
                ],
            ],
        ];
    }

    /**
     * Example 6: Controller usage in ExpenseController
     */
    public function expenseControllerExample(Company $company): void
    {
        /*
        // In your ExpenseController@store method:

        public function store(Request $request)
        {
            $company = auth()->user()->currentCompany();

            // Check if company can create expense
            if (!app(UsageLimitService::class)->canUse($company, 'expenses_per_month')) {
                return response()->json([
                    'error' => 'You have reached your monthly expense limit. Please upgrade your plan.',
                    'usage' => app(UsageLimitService::class)->getUsage($company, 'expenses_per_month'),
                ], 403);
            }

            // Create the expense
            $expense = Expense::create([...]);

            // Increment usage counter
            app(UsageLimitService::class)->incrementUsage($company, 'expenses_per_month');

            return response()->json($expense, 201);
        }

        // In your ExpenseController@destroy method:

        public function destroy($id)
        {
            $company = auth()->user()->currentCompany();
            $expense = Expense::findOrFail($id);

            // Delete the expense
            $expense->delete();

            // Decrement usage counter
            app(UsageLimitService::class)->decrementUsage($company, 'expenses_per_month');

            return response()->json(['message' => 'Expense deleted successfully']);
        }
        */
    }
}
// CLAUDE-CHECKPOINT
