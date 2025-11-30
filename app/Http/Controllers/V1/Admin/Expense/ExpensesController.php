<?php

namespace App\Http\Controllers\V1\Admin\Expense;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteExpensesRequest;
use App\Http\Requests\ExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
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

        $expenses = Expense::with('category', 'creator', 'fields')
            ->whereCompany()
            ->leftJoin('customers', 'customers.id', '=', 'expenses.customer_id')
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->applyFilters($request->all())
            ->select('expenses.*', 'expense_categories.name', 'customers.name as user_name')
            ->paginateData($limit);

        return ExpenseResource::collection($expenses)
            ->additional(['meta' => [
                'expense_total_count' => Expense::whereCompany()->count(),
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

        // Check for duplicates if supplier_id and invoice_number are provided
        $supplierId = $request->input('supplier_id');
        $invoiceNumber = $request->input('invoice_number');
        $companyId = $request->header('company');

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
}
