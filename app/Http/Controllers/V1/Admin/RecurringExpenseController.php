<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\RecurringExpense;
use Illuminate\Http\Request;

class RecurringExpenseController extends Controller
{
    /**
     * List recurring expenses for the company
     */
    public function index(Request $request, Company $company)
    {
        $this->authorize('view', $company);

        $recurringExpenses = RecurringExpense::whereCompany($company->id)
            ->with(['category', 'vendor', 'currency', 'creator'])
            ->when($request->input('is_active'), function ($query, $isActive) {
                $query->where('is_active', $isActive === 'true');
            })
            ->latest()
            ->paginate(25);

        return response()->json($recurringExpenses);
    }

    /**
     * Create a new recurring expense
     */
    public function store(Request $request, Company $company)
    {
        $this->authorize('view', $company);

        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'vendor_id' => 'nullable|exists:customers,id',
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'next_occurrence_at' => 'required|date|after_or_equal:today',
            'ends_at' => 'nullable|date|after:next_occurrence_at',
            'is_active' => 'nullable|boolean',
        ]);

        $recurringExpense = RecurringExpense::create([
            'company_id' => $company->id,
            'expense_category_id' => $validated['expense_category_id'],
            'vendor_id' => $validated['vendor_id'] ?? null,
            'currency_id' => $validated['currency_id'],
            'amount' => $validated['amount'],
            'notes' => $validated['notes'] ?? null,
            'frequency' => $validated['frequency'],
            'next_occurrence_at' => $validated['next_occurrence_at'],
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'recurring_expense' => $recurringExpense->load(['category', 'vendor', 'currency']),
            'message' => 'Recurring expense created successfully',
        ], 201);
    }

    /**
     * Show a specific recurring expense
     */
    public function show(Request $request, Company $company, RecurringExpense $recurringExpense)
    {
        $this->authorize('view', $company);

        if ($recurringExpense->company_id !== $company->id) {
            abort(403, 'Recurring expense does not belong to this company');
        }

        return response()->json(
            $recurringExpense->load(['category', 'vendor', 'currency', 'creator'])
        );
    }

    /**
     * Update a recurring expense
     */
    public function update(Request $request, Company $company, RecurringExpense $recurringExpense)
    {
        $this->authorize('view', $company);

        if ($recurringExpense->company_id !== $company->id) {
            abort(403, 'Recurring expense does not belong to this company');
        }

        $validated = $request->validate([
            'expense_category_id' => 'sometimes|exists:expense_categories,id',
            'vendor_id' => 'nullable|exists:customers,id',
            'currency_id' => 'sometimes|exists:currencies,id',
            'amount' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string',
            'frequency' => 'sometimes|in:daily,weekly,monthly,quarterly,yearly',
            'next_occurrence_at' => 'sometimes|date|after_or_equal:today',
            'ends_at' => 'nullable|date|after:next_occurrence_at',
            'is_active' => 'nullable|boolean',
        ]);

        $recurringExpense->update($validated);

        return response()->json([
            'recurring_expense' => $recurringExpense->load(['category', 'vendor', 'currency']),
            'message' => 'Recurring expense updated successfully',
        ]);
    }

    /**
     * Delete a recurring expense
     */
    public function destroy(Request $request, Company $company, RecurringExpense $recurringExpense)
    {
        $this->authorize('view', $company);

        if ($recurringExpense->company_id !== $company->id) {
            abort(403, 'Recurring expense does not belong to this company');
        }

        $recurringExpense->delete();

        return response()->json([
            'message' => 'Recurring expense deleted successfully',
        ]);
    }

    /**
     * Manually process a recurring expense (create expense now)
     */
    public function processNow(Request $request, Company $company, RecurringExpense $recurringExpense)
    {
        $this->authorize('view', $company);

        if ($recurringExpense->company_id !== $company->id) {
            abort(403, 'Recurring expense does not belong to this company');
        }

        try {
            $expense = $recurringExpense->generateExpense();

            return response()->json([
                'expense' => $expense,
                'message' => 'Expense created successfully from recurring expense',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create expense: '.$e->getMessage(),
            ], 500);
        }
    }
}
// CLAUDE-CHECKPOINT
