<?php

namespace App\Http\Controllers\V1\Admin\Expense;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteExpensesRequest;
use App\Http\Requests\ExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\UsageLimitService;
use Illuminate\Http\Request;

class ExpensesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Expense::class);

        $limit = $request->has('limit') ? $request->limit : 10;

        $expenses = Expense::with(['category', 'creator', 'fields', 'customer:id,name', 'supplier:id,name'])
            ->whereCompany()
            ->applyFilters($request->all())
            ->latest()
            ->paginateData($limit);

        // Get company and usage stats
        $companyId = $request->header('company');
        $company = \App\Models\Company::find($companyId);
        $usageStats = null;

        if ($company) {
            $usageService = app(UsageLimitService::class);
            $usageStats = $usageService->getUsage($company, 'expenses_per_month');
        }

        return ExpenseResource::collection($expenses)
            ->additional(['meta' => [
                'expense_total_count' => Expense::whereCompany()->count(),
                'usage' => $usageStats,
            ]]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ExpenseRequest $request)
    {
        $this->authorize('create', Expense::class);

        // Get company from request
        $companyId = $request->header('company');
        $company = \App\Models\Company::find($companyId);

        if (! $company) {
            return response()->json([
                'error' => 'company_not_found',
                'message' => 'Company not found',
            ], 404);
        }

        // Check usage limit for expenses
        $usageService = app(UsageLimitService::class);
        if (! $usageService->canUse($company, 'expenses_per_month')) {
            return response()->json(
                $usageService->buildLimitExceededResponse($company, 'expenses_per_month'),
                403
            );
        }

        // Check for duplicates if supplier_id and invoice_number are provided
        $supplierId = $request->input('supplier_id');
        $invoiceNumber = $request->input('invoice_number');

        if ($supplierId && $invoiceNumber && ! $request->allowsDuplicate()) {
            $duplicates = Expense::findPotentialDuplicates(
                (int) $companyId,
                (int) $supplierId,
                $invoiceNumber
            );

            if ($duplicates->isNotEmpty()) {
                // Return warning response with duplicate info
                return response()->json([
                    'is_duplicate_warning' => true,
                    'message' => __('expenses.duplicate_warning'),
                    'duplicates' => $duplicates->map(function ($expense) {
                        return [
                            'id' => $expense->id,
                            'expense_date' => $expense->formattedExpenseDate,
                            'amount' => $expense->amount,
                            'category' => $expense->category?->name,
                            'invoice_number' => $expense->invoice_number,
                        ];
                    }),
                ], 200); // Use 200 so frontend can handle it gracefully
            }
        }

        $expense = Expense::createExpense($request);

        // Increment usage after successful creation
        $usageService->incrementUsage($company, 'expenses_per_month');

        return new ExpenseResource($expense);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Expense $expense)
    {
        $this->authorize('view', $expense);

        $expense->load(['currency', 'category', 'customer', 'company', 'creator', 'paymentMethod', 'supplier', 'fields.customField']);

        return new ExpenseResource($expense);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ExpenseRequest $request, Expense $expense)
    {
        $this->authorize('update', $expense);

        // Check for duplicates if supplier_id and invoice_number are provided
        $supplierId = $request->input('supplier_id');
        $invoiceNumber = $request->input('invoice_number');
        $companyId = $request->header('company');

        if ($supplierId && $invoiceNumber && ! $request->allowsDuplicate()) {
            $duplicates = Expense::findPotentialDuplicates(
                (int) $companyId,
                (int) $supplierId,
                $invoiceNumber,
                $expense->id // Exclude current expense
            );

            if ($duplicates->isNotEmpty()) {
                // Return warning response with duplicate info
                return response()->json([
                    'is_duplicate_warning' => true,
                    'message' => __('expenses.duplicate_warning'),
                    'duplicates' => $duplicates->map(function ($exp) {
                        return [
                            'id' => $exp->id,
                            'expense_date' => $exp->formattedExpenseDate,
                            'amount' => $exp->amount,
                            'category' => $exp->category?->name,
                            'invoice_number' => $exp->invoice_number,
                        ];
                    }),
                ], 200);
            }
        }

        $expense->updateExpense($request);

        return new ExpenseResource($expense);
    }

    public function delete(DeleteExpensesRequest $request)
    {
        $this->authorize('delete multiple expenses');

        Expense::destroy($request->ids);

        return response()->json([
            'success' => true,
        ]);
    }

    public function clone(Expense $expense)
    {
        $this->authorize('create', Expense::class);

        $clone = $expense->replicate(['id', 'expense_number', 'created_at', 'updated_at', 'ifrs_transaction_id']);
        $clone->expense_date = now()->format('Y-m-d');
        $clone->status = 'draft';
        $clone->save();

        // Auto-generate expense number
        $companyId = $clone->company_id;
        $lastNumber = Expense::where('company_id', $companyId)
            ->whereNotNull('expense_number')
            ->orderByRaw("CAST(REPLACE(expense_number, 'EXP-', '') AS UNSIGNED) DESC")
            ->value('expense_number');

        $nextNum = 1;
        if ($lastNumber) {
            $nextNum = (int) str_replace('EXP-', '', $lastNumber) + 1;
        }
        $clone->update(['expense_number' => 'EXP-'.str_pad($nextNum, 6, '0', STR_PAD_LEFT)]);

        return new ExpenseResource($clone);
    }

    public function approve(Expense $expense)
    {
        $this->authorize('update', $expense);

        $expense->update(['status' => 'approved']);

        return new ExpenseResource($expense);
    }
}
