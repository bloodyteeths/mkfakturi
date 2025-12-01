<?php

namespace App\Http\Controllers\V1\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AccountSuggestionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Account Suggestion Controller
 *
 * Manages AI-powered account suggestions for transactions
 */
class AccountSuggestionController extends Controller
{
    protected AccountSuggestionService $suggestionService;

    public function __construct(AccountSuggestionService $suggestionService)
    {
        $this->suggestionService = $suggestionService;
    }

    /**
     * Get account suggestion for a specific transaction.
     *
     * @param  string  $type  invoice|expense|payment
     */
    public function suggest(Request $request, string $type, int $id): JsonResponse
    {
        $model = $this->findModel($type, $id);

        if (! $model) {
            return response()->json([
                'error' => 'Transaction not found',
            ], 404);
        }

        // Check company access
        if ($model->company_id !== (int) $request->header('company')) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        $suggestion = match ($type) {
            'invoice' => $this->suggestionService->suggestForInvoice($model),
            'expense' => $this->suggestionService->suggestForExpense($model),
            'payment' => $this->suggestionService->suggestForPayment($model),
            default => null,
        };

        if (! $suggestion) {
            return response()->json([
                'error' => 'Invalid transaction type',
            ], 400);
        }

        // Update model with suggestion if not already confirmed
        if (! $model->account_confirmed_at) {
            $model->update([
                'suggested_debit_account_id' => $suggestion['debit_account_id'],
                'suggested_credit_account_id' => $suggestion['credit_account_id'],
            ]);
        }

        return response()->json([
            'data' => array_merge($suggestion, [
                'type' => $type,
                'id' => $id,
                'is_confirmed' => $model->account_confirmed_at !== null,
            ]),
        ]);
    }

    /**
     * Confirm or update account assignment for a transaction.
     */
    public function confirm(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:invoice,expense,payment',
                'id' => 'required|integer',
                'debit_account_id' => 'required|integer|exists:accounts,id',
                'credit_account_id' => 'required|integer|exists:accounts,id',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        }

        $model = $this->findModel($validated['type'], $validated['id']);

        if (! $model) {
            return response()->json([
                'error' => 'Transaction not found',
            ], 404);
        }

        // Check company access
        if ($model->company_id !== (int) $request->header('company')) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        $this->suggestionService->confirmSuggestion(
            $model,
            $validated['debit_account_id'],
            $validated['credit_account_id'],
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Account assignment confirmed successfully',
            'data' => [
                'type' => $validated['type'],
                'id' => $validated['id'],
                'confirmed_at' => $model->fresh()->account_confirmed_at,
            ],
        ]);
    }

    /**
     * Get all pending (unconfirmed) transactions for review.
     */
    public function pending(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $limit = $request->get('limit', 50);
        $type = $request->get('type'); // Optional filter by type
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $results = [];

        // Gather pending invoices
        if (! $type || $type === 'invoice') {
            $invoices = Invoice::where('company_id', $companyId)
                ->whereNull('account_confirmed_at')
                ->with(['customer:id,name', 'currency:id,symbol,code'])
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    $query->whereBetween('invoice_date', [$fromDate, $toDate]);
                })
                ->orderBy('invoice_date', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'type' => 'invoice',
                        'id' => $invoice->id,
                        'date' => $invoice->invoice_date,
                        'reference' => $invoice->invoice_number,
                        'description' => $invoice->customer?->name ?? 'N/A',
                        'amount' => $invoice->total,
                        'currency' => $invoice->currency?->code ?? 'USD',
                        'suggested_debit_account_id' => $invoice->suggested_debit_account_id,
                        'suggested_credit_account_id' => $invoice->suggested_credit_account_id,
                    ];
                });

            $results = array_merge($results, $invoices->toArray());
        }

        // Gather pending expenses
        if (! $type || $type === 'expense') {
            $expenses = Expense::where('company_id', $companyId)
                ->whereNull('account_confirmed_at')
                ->with(['category:id,name', 'currency:id,symbol,code'])
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    $query->whereBetween('expense_date', [$fromDate, $toDate]);
                })
                ->orderBy('expense_date', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($expense) {
                    return [
                        'type' => 'expense',
                        'id' => $expense->id,
                        'date' => $expense->expense_date,
                        'reference' => $expense->invoice_number ?? 'EXP-'.$expense->id,
                        'description' => $expense->category?->name ?? 'N/A',
                        'amount' => $expense->amount,
                        'currency' => $expense->currency?->code ?? 'USD',
                        'suggested_debit_account_id' => $expense->suggested_debit_account_id,
                        'suggested_credit_account_id' => $expense->suggested_credit_account_id,
                    ];
                });

            $results = array_merge($results, $expenses->toArray());
        }

        // Gather pending payments
        if (! $type || $type === 'payment') {
            $payments = Payment::where('company_id', $companyId)
                ->whereNull('account_confirmed_at')
                ->with(['customer:id,name', 'currency:id,symbol,code'])
                ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                    $query->whereBetween('payment_date', [$fromDate, $toDate]);
                })
                ->orderBy('payment_date', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($payment) {
                    return [
                        'type' => 'payment',
                        'id' => $payment->id,
                        'date' => $payment->payment_date,
                        'reference' => $payment->payment_number,
                        'description' => $payment->customer?->name ?? 'N/A',
                        'amount' => $payment->amount,
                        'currency' => $payment->currency?->code ?? 'USD',
                        'suggested_debit_account_id' => $payment->suggested_debit_account_id,
                        'suggested_credit_account_id' => $payment->suggested_credit_account_id,
                    ];
                });

            $results = array_merge($results, $payments->toArray());
        }

        // Sort by date descending
        usort($results, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        // Limit to requested count
        $results = array_slice($results, 0, $limit);

        return response()->json([
            'data' => $results,
            'count' => count($results),
        ]);
    }

    /**
     * Confirm multiple transactions at once.
     */
    public function bulkConfirm(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'items' => 'required|array',
                'items.*.type' => 'required|in:invoice,expense,payment',
                'items.*.id' => 'required|integer',
                'items.*.debit_account_id' => 'required|integer|exists:accounts,id',
                'items.*.credit_account_id' => 'required|integer|exists:accounts,id',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        }

        $companyId = (int) $request->header('company');
        $user = $request->user();
        $confirmed = [];
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($validated['items'] as $item) {
                $model = $this->findModel($item['type'], $item['id']);

                if (! $model) {
                    $errors[] = [
                        'type' => $item['type'],
                        'id' => $item['id'],
                        'error' => 'Transaction not found',
                    ];

                    continue;
                }

                // Check company access
                if ($model->company_id !== $companyId) {
                    $errors[] = [
                        'type' => $item['type'],
                        'id' => $item['id'],
                        'error' => 'Unauthorized',
                    ];

                    continue;
                }

                $this->suggestionService->confirmSuggestion(
                    $model,
                    $item['debit_account_id'],
                    $item['credit_account_id'],
                    $user
                );

                $confirmed[] = [
                    'type' => $item['type'],
                    'id' => $item['id'],
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($confirmed).' transaction(s) confirmed successfully',
                'data' => [
                    'confirmed' => $confirmed,
                    'errors' => $errors,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Bulk confirmation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Find model instance by type and ID.
     */
    protected function findModel(string $type, int $id): ?Model
    {
        return match ($type) {
            'invoice' => Invoice::find($id),
            'expense' => Expense::find($id),
            'payment' => Payment::find($id),
            default => null,
        };
    }
}
// CLAUDE-CHECKPOINT
