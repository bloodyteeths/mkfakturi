<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\PayrollRun;
use App\Models\Reconciliation;
use App\Models\ReconciliationSplit;
use App\Services\Reconciliation\ReconciliationPostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Mk\Services\Matcher;

/**
 * Reconciliation Controller
 *
 * Handles invoice-to-transaction matching operations
 * for bank reconciliation workflows.
 */
class ReconciliationController extends Controller
{
    /**
     * Get unmatched transactions with suggested matches
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->getCompany();
        $locale = $request->input('locale', app()->getLocale() ?: 'mk');
        $matcher = new Matcher($company->id, 90, 0.01, $locale);

        // Get unreconciled transactions — both credits and debits (P0-13: explicit tenant scope)
        $transactions = BankTransaction::forCompany($company->id)
            ->unreconciled()
            ->orderBy('transaction_date', 'desc')
            ->paginate($request->get('limit', 20));

        // Get suggested matches for credit transactions (without creating payments)
        $transactionsWithMatches = $transactions->getCollection()->map(function ($transaction) use ($matcher) {
            // Only suggest invoice matches for credit (incoming) transactions
            $match = $transaction->transaction_type === BankTransaction::TYPE_CREDIT
                ? $matcher->suggestMatch($transaction)
                : null;

            return [
                'id' => $transaction->id,
                'transaction_date' => $transaction->transaction_date,
                'amount' => (float) $transaction->amount,
                'currency' => $transaction->currency,
                'transaction_type' => $transaction->transaction_type,
                'description' => $transaction->description,
                'remittance_info' => $transaction->remittance_info,
                'counterparty_name' => $transaction->creditor_name ?? $transaction->debtor_name,
                'external_reference' => $transaction->external_reference,
                'ai_category' => $transaction->ai_category,
                'ai_match_reason' => $transaction->ai_match_reason,
                'suggested_match' => $match,
            ];
        });

        return response()->json([
            'data' => $transactionsWithMatches,
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Run auto-matching for all unmatched transactions
     */
    public function autoMatch(Request $request): JsonResponse
    {
        $company = $this->getCompany();
        $locale = $request->input('locale', app()->getLocale() ?: 'mk');
        $matcher = new Matcher(
            $company->id,
            $request->get('lookback_days', 90),
            $request->get('amount_tolerance', 0.01),
            $locale
        );

        $matches = $matcher->matchAllTransactions();

        return response()->json([
            'success' => true,
            'matches_found' => count($matches),
            'matches' => $matches,
        ]);
    }

    /**
     * Manually match a transaction to an invoice
     */
    public function manualMatch(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|integer|exists:bank_transactions,id',
            'invoice_id' => 'required|integer|exists:invoices,id',
        ]);

        $company = $this->getCompany();

        // P0-13: explicit tenant scope
        $transaction = BankTransaction::forCompany($company->id)
            ->where('id', $request->transaction_id)
            ->whereNull('matched_invoice_id')
            ->firstOrFail();

        $invoice = Invoice::where('company_id', $company->id)
            ->where('id', $request->invoice_id)
            ->whereIn('status', [
                Invoice::STATUS_DRAFT,
                Invoice::STATUS_SENT,
                Invoice::STATUS_VIEWED,
                Invoice::STATUS_PARTIALLY_PAID,
            ])
            ->firstOrFail();

        // Create a Reconciliation record and use the proper posting service
        // which correctly converts to cents and calls subtractInvoicePayment()
        $recon = Reconciliation::firstOrCreate(
            [
                'company_id' => $company->id,
                'bank_transaction_id' => $transaction->id,
            ],
            [
                'invoice_id' => $invoice->id,
                'status' => Reconciliation::STATUS_MATCHED,
                'match_type' => Reconciliation::MATCH_TYPE_MANUAL,
                'confidence' => 100.0,
                'matched_by' => $request->user()->id,
                'matched_at' => now(),
            ]
        );

        // Ensure invoice_id is set (in case recon already existed without it)
        if (! $recon->invoice_id) {
            $recon->update(['invoice_id' => $invoice->id]);
        }

        $service = new ReconciliationPostingService();
        $result = $service->post($recon);

        if (! $result->ok) {
            return response()->json([
                'success' => false,
                'message' => $result->errorMessage ?? 'Failed to post payment',
            ], 422);
        }

        // Bank transaction is already marked as matched by ReconciliationPostingService::post()

        return response()->json([
            'success' => true,
            'message' => 'Transaction matched successfully',
            'match' => [
                'transaction_id' => $transaction->id,
                'invoice_id' => $invoice->id,
                'amount' => (float) $transaction->amount,
                'confidence' => 100.0,
                'invoice_number' => $invoice->invoice_number,
                'invoice_total' => (float) $invoice->total / 100,
            ],
        ]);
    }

    /**
     * Get matching statistics
     */
    public function stats(): JsonResponse
    {
        $company = $this->getCompany();
        $matcher = new Matcher($company->id);

        return response()->json([
            'success' => true,
            'stats' => $matcher->getMatchingStats(),
        ]);
    }

    /**
     * Get unpaid invoices for manual matching
     */
    public function getUnpaidInvoices(Request $request): JsonResponse
    {
        $company = $this->getCompany();

        $invoices = Invoice::where('company_id', $company->id)
            ->whereIn('status', [
                Invoice::STATUS_DRAFT,
                Invoice::STATUS_SENT,
                Invoice::STATUS_VIEWED,
                Invoice::STATUS_PARTIALLY_PAID,
            ])
            ->with('customer:id,name')
            ->orderBy('due_date', 'asc')
            ->get(['id', 'invoice_number', 'total', 'due_date', 'status', 'customer_id']);

        return response()->json([
            'data' => $invoices->map(fn ($inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'total' => (float) $inv->total / 100,
                'due_date' => $inv->due_date,
                'status' => $inv->status,
                'customer_name' => $inv->customer->name ?? 'Unknown',
            ]),
        ]);
    }

    /**
     * Post a split payment: one transaction allocated across multiple invoices.
     *
     * P0-14: Partial Payments + Multi-Invoice Settlement.
     *
     * @param  Request  $request
     * @param  int  $id  Reconciliation ID
     * @return JsonResponse
     */
    public function splitPayment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'splits' => 'required|array|min:1',
            'splits.*.invoice_id' => 'required|integer|exists:invoices,id',
            'splits.*.amount' => 'required|numeric|min:0.01',
        ]);

        $company = $this->getCompany();

        // P0-13: explicit tenant scope
        $recon = Reconciliation::forCompany($company->id)
            ->where('id', $id)
            ->firstOrFail();

        // Validate all invoices belong to the same company
        $invoiceIds = array_column($request->splits, 'invoice_id');
        $validInvoiceCount = Invoice::where('company_id', $company->id)
            ->whereIn('id', $invoiceIds)
            ->count();

        if ($validInvoiceCount !== count(array_unique($invoiceIds))) {
            return response()->json([
                'success' => false,
                'message' => 'One or more invoices not found for this company',
            ], 422);
        }

        $service = new ReconciliationPostingService();
        $results = $service->postSplit($recon, $request->splits);

        // Check if any result is an error
        $hasErrors = collect($results)->contains(fn ($r) => $r->isError());
        $successCount = collect($results)->filter(fn ($r) => $r->ok)->count();

        return response()->json([
            'success' => ! $hasErrors || $successCount > 0,
            'message' => $hasErrors
                ? 'Split payment completed with some errors'
                : 'Split payment posted successfully',
            'results' => collect($results)->map(fn ($r) => $r->toArray())->all(),
            'splits_created' => $successCount,
        ], $hasErrors && $successCount === 0 ? 422 : 200);
    }

    /**
     * Get split allocations for a reconciliation.
     *
     * P0-14: Partial Payments + Multi-Invoice Settlement.
     *
     * @param  int  $id  Reconciliation ID
     * @return JsonResponse
     */
    public function getSplits(int $id): JsonResponse
    {
        $company = $this->getCompany();

        // P0-13: explicit tenant scope
        $recon = Reconciliation::forCompany($company->id)
            ->where('id', $id)
            ->firstOrFail();

        $splits = ReconciliationSplit::where('reconciliation_id', $recon->id)
            ->with(['invoice:id,invoice_number,total,due_amount,customer_id', 'payment:id,payment_number,amount'])
            ->get();

        return response()->json([
            'data' => $splits->map(fn ($split) => [
                'id' => $split->id,
                'reconciliation_id' => $split->reconciliation_id,
                'invoice_id' => $split->invoice_id,
                'invoice_number' => $split->invoice->invoice_number ?? null,
                'invoice_total' => (float) ($split->invoice->total ?? 0) / 100,
                'allocated_amount' => (float) $split->allocated_amount,
                'payment_id' => $split->payment_id,
                'payment_number' => $split->payment->payment_number ?? null,
                'created_at' => $split->created_at,
            ]),
        ]);
    }

    /**
     * Confirm a match with optional split or partial payment support.
     *
     * If 'splits' array is provided, calls postSplit(). If 'partial' flag is true,
     * calls postPartial(). Otherwise falls through to existing manualMatch logic.
     *
     * P0-14: Partial Payments + Multi-Invoice Settlement.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function confirmMatch(Request $request): JsonResponse
    {
        $company = $this->getCompany();

        // If splits are provided, delegate to split payment
        if ($request->has('splits') && is_array($request->splits) && count($request->splits) > 0) {
            $request->validate([
                'reconciliation_id' => 'nullable|integer',
                'transaction_id' => 'nullable|integer|exists:bank_transactions,id',
                'splits' => 'required|array|min:1',
                'splits.*.invoice_id' => 'required|integer|exists:invoices,id',
                'splits.*.amount' => 'required|numeric|min:0.01',
            ]);

            // Find existing reconciliation or create one from transaction_id
            if ($request->reconciliation_id) {
                $recon = Reconciliation::forCompany($company->id)
                    ->where('id', $request->reconciliation_id)
                    ->firstOrFail();
            } elseif ($request->transaction_id) {
                $transaction = BankTransaction::forCompany($company->id)
                    ->where('id', $request->transaction_id)
                    ->firstOrFail();

                $recon = Reconciliation::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'bank_transaction_id' => $transaction->id,
                    ],
                    [
                        'status' => Reconciliation::STATUS_PENDING,
                        'match_type' => Reconciliation::MATCH_TYPE_MANUAL,
                        'confidence' => 100.0,
                        'matched_by' => $request->user()->id,
                        'matched_at' => now(),
                    ]
                );
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Either reconciliation_id or transaction_id is required',
                ], 422);
            }

            $service = new ReconciliationPostingService();
            $results = $service->postSplit($recon, $request->splits);

            $hasErrors = collect($results)->contains(fn ($r) => $r->isError());

            return response()->json([
                'success' => ! $hasErrors,
                'message' => $hasErrors ? 'Split payment had errors' : 'Split payment posted',
                'results' => collect($results)->map(fn ($r) => $r->toArray())->all(),
            ]);
        }

        // If partial flag is set, delegate to partial payment
        if ($request->boolean('partial')) {
            $request->validate([
                'reconciliation_id' => 'required|integer',
                'invoice_id' => 'required|integer|exists:invoices,id',
            ]);

            $recon = Reconciliation::forCompany($company->id)
                ->where('id', $request->reconciliation_id)
                ->firstOrFail();

            // Set the invoice on the reconciliation if not set
            if (! $recon->invoice_id) {
                $recon->update(['invoice_id' => $request->invoice_id]);
            }

            $service = new ReconciliationPostingService();
            $result = $service->postPartial($recon);

            return response()->json([
                'success' => $result->ok,
                'message' => $result->ok ? 'Partial payment posted' : $result->errorMessage,
                'result' => $result->toArray(),
            ], $result->ok ? 200 : 422);
        }

        // Fall through to regular manual match
        return $this->manualMatch($request);
    }

    /**
     * Record a bank transaction as an expense.
     */
    public function recordAsExpense(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|integer|exists:bank_transactions,id',
            'expense_category_id' => 'required|integer|exists:expense_categories,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $company = $this->getCompany();

        $transaction = BankTransaction::forCompany($company->id)
            ->where('id', $request->transaction_id)
            ->firstOrFail();

        // Verify expense category belongs to this company
        ExpenseCategory::where('company_id', $company->id)
            ->where('id', $request->expense_category_id)
            ->firstOrFail();

        $expense = Expense::create([
            'expense_date' => $transaction->transaction_date,
            'amount' => (int) round(abs((float) $transaction->amount) * 100),
            'expense_category_id' => $request->expense_category_id,
            'company_id' => $company->id,
            'creator_id' => Auth::id(),
            'notes' => $request->notes ?? $transaction->description,
        ]);

        $transaction->markAsReconciled(BankTransaction::LINKED_EXPENSE, $expense->id);

        return response()->json([
            'success' => true,
            'message' => 'Transaction recorded as expense',
            'expense_id' => $expense->id,
        ]);
    }

    /**
     * Link a bank transaction to an existing bill (creates a bill payment).
     */
    public function linkToBill(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|integer|exists:bank_transactions,id',
            'bill_id' => 'required|integer|exists:bills,id',
        ]);

        $company = $this->getCompany();

        $transaction = BankTransaction::forCompany($company->id)
            ->where('id', $request->transaction_id)
            ->firstOrFail();

        $bill = Bill::where('company_id', $company->id)
            ->where('id', $request->bill_id)
            ->firstOrFail();

        DB::transaction(function () use ($transaction, $bill, $company) {
            // Generate payment number
            $year = date('Y');
            $sequence = BillPayment::where('company_id', $company->id)
                ->whereYear('created_at', $year)
                ->count() + 1;
            $paymentNumber = sprintf('BPAY-%d-%06d', $year, $sequence);

            $billPayment = BillPayment::create([
                'bill_id' => $bill->id,
                'company_id' => $company->id,
                'amount' => (int) round(abs((float) $transaction->amount) * 100),
                'payment_date' => $transaction->transaction_date,
                'payment_number' => $paymentNumber,
                'creator_id' => Auth::id(),
            ]);

            $bill->updatePaidStatus();

            $transaction->markAsReconciled(BankTransaction::LINKED_BILL_PAYMENT, $billPayment->id);
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaction linked to bill',
        ]);
    }

    /**
     * Link a bank transaction to a payroll run (marks it as paid).
     */
    public function linkToPayroll(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|integer|exists:bank_transactions,id',
            'payroll_run_id' => 'required|integer|exists:payroll_runs,id',
        ]);

        $company = $this->getCompany();

        $transaction = BankTransaction::forCompany($company->id)
            ->where('id', $request->transaction_id)
            ->firstOrFail();

        // Allow linking to runs that are already 'paid' — payroll has 6 separate
        // bank charges (net, ФПИОМ, ФЗОМ, employment, additional, PIT)
        $payrollRun = PayrollRun::where('company_id', $company->id)
            ->where('id', $request->payroll_run_id)
            ->whereIn('status', ['approved', 'posted', 'paid'])
            ->firstOrFail();

        // Only mark as 'paid' if not already paid
        if ($payrollRun->status !== 'paid') {
            $payrollRun->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        $transaction->markAsReconciled(BankTransaction::LINKED_PAYROLL_RUN, $payrollRun->id);

        return response()->json([
            'success' => true,
            'message' => 'Transaction linked to payroll',
        ]);
    }

    /**
     * Mark a transaction as reviewed without creating any record.
     */
    public function markAsReviewed(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|integer|exists:bank_transactions,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $company = $this->getCompany();

        $transaction = BankTransaction::forCompany($company->id)
            ->where('id', $request->transaction_id)
            ->firstOrFail();

        if ($request->notes) {
            $transaction->update(['processing_notes' => $request->notes]);
        }

        $transaction->markAsReconciled(BankTransaction::LINKED_REVIEWED);

        return response()->json([
            'success' => true,
            'message' => 'Transaction marked as reviewed',
        ]);
    }

    /**
     * Bulk mark multiple transactions as reviewed.
     */
    public function bulkMarkAsReviewed(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_ids' => 'required|array|min:1|max:100',
            'transaction_ids.*' => 'integer|exists:bank_transactions,id',
        ]);

        $company = $this->getCompany();

        $transactions = BankTransaction::forCompany($company->id)
            ->whereIn('id', $request->transaction_ids)
            ->unreconciled()
            ->get();

        $count = 0;
        foreach ($transactions as $transaction) {
            $transaction->markAsReconciled(BankTransaction::LINKED_REVIEWED);
            $count++;
        }

        return response()->json([
            'success' => true,
            'count' => $count,
            'message' => "{$count} transactions marked as reviewed",
        ]);
    }

    /**
     * Get expense categories for the company.
     */
    public function getExpenseCategories(): JsonResponse
    {
        $company = $this->getCompany();

        $categories = ExpenseCategory::where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['data' => $categories]);
    }

    /**
     * Get unpaid bills for the company.
     */
    public function getUnpaidBills(): JsonResponse
    {
        $company = $this->getCompany();

        $bills = Bill::where('company_id', $company->id)
            ->whereIn('paid_status', [Bill::PAID_STATUS_UNPAID, Bill::PAID_STATUS_PARTIALLY_PAID])
            ->with('supplier:id,name')
            ->orderBy('due_date', 'asc')
            ->get(['id', 'bill_number', 'total', 'due_date', 'paid_status', 'supplier_id']);

        return response()->json([
            'data' => $bills->map(fn ($bill) => [
                'id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'total' => (float) $bill->total / 100,
                'due_date' => $bill->due_date,
                'paid_status' => $bill->paid_status,
                'supplier_name' => $bill->supplier->name ?? 'Unknown',
            ]),
        ]);
    }

    /**
     * Get payroll runs available for linking.
     */
    public function getPayrollRuns(): JsonResponse
    {
        $company = $this->getCompany();

        // Include 'paid' status too — payroll has 6 separate bank charges
        // (net salary, ФПИОМ, ФЗОМ, employment, additional, PIT)
        // so the run may already be partially reconciled
        $runs = PayrollRun::where('company_id', $company->id)
            ->whereIn('status', ['approved', 'posted', 'paid'])
            ->orderBy('period_start', 'desc')
            ->limit(24) // Last 2 years
            ->get(['id', 'period_year', 'period_month', 'total_net', 'total_gross', 'status']);

        return response()->json([
            'data' => $runs->map(fn ($run) => [
                'id' => $run->id,
                'period' => sprintf('%d-%02d', $run->period_year, $run->period_month),
                'total_net' => (float) $run->total_net / 100,
                'total_gross' => (float) $run->total_gross / 100,
                'status' => $run->status,
            ]),
        ]);
    }

    /**
     * Smart suggestion for any bank transaction (credit or debit).
     */
    public function smartSuggest(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|integer|exists:bank_transactions,id',
        ]);

        $company = $this->getCompany();
        $locale = $request->input('locale', app()->getLocale() ?: 'mk');

        $transaction = BankTransaction::forCompany($company->id)
            ->where('id', $request->transaction_id)
            ->firstOrFail();

        $service = new \App\Services\Reconciliation\SmartReconciliationService();
        $suggestion = $service->suggest($transaction, $locale);

        return response()->json([
            'success' => true,
            'suggestion' => $suggestion->toArray(),
        ]);
    }

    /**
     * Bulk smart suggestions for multiple transactions.
     */
    public function bulkSmartSuggest(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_ids' => 'required|array|min:1|max:50',
            'transaction_ids.*' => 'integer|exists:bank_transactions,id',
        ]);

        $company = $this->getCompany();
        $locale = $request->input('locale', app()->getLocale() ?: 'mk');

        $transactions = BankTransaction::forCompany($company->id)
            ->whereIn('id', $request->transaction_ids)
            ->unreconciled()
            ->get();

        $service = new \App\Services\Reconciliation\SmartReconciliationService();
        $suggestions = [];

        foreach ($transactions as $tx) {
            $suggestions[$tx->id] = $service->suggest($tx, $locale)->toArray();
        }

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Record a credit transaction as non-invoice income.
     */
    public function recordIncome(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|integer|exists:bank_transactions,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $company = $this->getCompany();

        $transaction = BankTransaction::forCompany($company->id)
            ->where('id', $request->transaction_id)
            ->firstOrFail();

        $amountCents = (int) round(abs((float) $transaction->amount) * 100);

        // Create a standalone payment (no invoice) to track the income
        $year = date('Y');
        $sequence = \App\Models\Payment::where('company_id', $company->id)
            ->whereYear('created_at', $year)
            ->count() + 1;
        $paymentNumber = sprintf('INC-%d-%06d', $year, $sequence);

        $payment = \App\Models\Payment::create([
            'company_id' => $company->id,
            'user_id' => Auth::id(),
            'amount' => $amountCents,
            'base_amount' => $amountCents,
            'payment_date' => $transaction->transaction_date,
            'payment_number' => $paymentNumber,
            'notes' => $request->notes ?? $transaction->description ?? 'Non-invoice income',
        ]);

        $transaction->markAsReconciled('income', $payment->id);

        return response()->json([
            'success' => true,
            'message' => 'Transaction recorded as income',
            'payment_id' => $payment->id,
        ]);
    }

    /**
     * Undo reconciliation — reset a transaction to unreconciled state.
     * Does NOT delete linked records (expenses, payments, etc.) — only unlinks.
     * User can then re-reconcile with a different action.
     */
    public function undoReconciliation(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|integer|exists:bank_transactions,id',
        ]);

        $company = $this->getCompany();

        $transaction = BankTransaction::forCompany($company->id)
            ->where('id', $request->transaction_id)
            ->firstOrFail();

        if (! $transaction->isReconciled()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction is not reconciled',
            ], 422);
        }

        $previousType = $transaction->linked_type ?? 'invoice_match';
        $previousId = $transaction->linked_id ?? $transaction->matched_invoice_id;

        $transaction->undoReconciliation();

        Log::info('[Reconciliation] Undo reconciliation', [
            'transaction_id' => $transaction->id,
            'previous_type' => $previousType,
            'previous_linked_id' => $previousId,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reconciliation undone — transaction can now be re-classified',
            'previous_type' => $previousType,
        ]);
    }

    /**
     * Record a bank transaction as a financial/equity transaction.
     *
     * Handles: owner_contribution, owner_withdrawal, loan_received,
     * loan_repayment, tax_payment, internal_transfer.
     *
     * These create a reconciliation record with the correct linked_type
     * and post to IFRS GL accounts when available.
     */
    public function recordFinancialTransaction(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|integer|exists:bank_transactions,id',
            'action' => 'required|string|in:owner_contribution,owner_withdrawal,loan_received,loan_repayment,tax_payment,internal_transfer,cash_deposit,cash_withdrawal,advance_received,advance_paid',
            'notes' => 'nullable|string|max:500',
            'sub_type' => 'nullable|string|max:100', // e.g. "ДДВ", "данок на добивка", "ФПИОМ" for tax
            'interest_amount' => 'nullable|numeric|min:0', // For loan_repayment: interest portion
        ]);

        $company = $this->getCompany();

        $transaction = BankTransaction::forCompany($company->id)
            ->where('id', $request->transaction_id)
            ->firstOrFail();

        $action = $request->action;
        $amountCents = (int) round(abs((float) $transaction->amount) * 100);
        $notes = $request->notes ?? $transaction->description ?? '';
        $subType = $request->sub_type;

        // Map action to linked_type constant
        $linkedTypeMap = [
            'owner_contribution' => BankTransaction::LINKED_OWNER_CONTRIBUTION,
            'owner_withdrawal' => BankTransaction::LINKED_OWNER_WITHDRAWAL,
            'loan_received' => BankTransaction::LINKED_LOAN_RECEIVED,
            'loan_repayment' => BankTransaction::LINKED_LOAN_REPAYMENT,
            'tax_payment' => BankTransaction::LINKED_TAX_PAYMENT,
            'internal_transfer' => BankTransaction::LINKED_INTERNAL_TRANSFER,
            'cash_deposit' => BankTransaction::LINKED_CASH_DEPOSIT,
            'cash_withdrawal' => BankTransaction::LINKED_CASH_WITHDRAWAL,
            'advance_received' => BankTransaction::LINKED_ADVANCE_RECEIVED,
            'advance_paid' => BankTransaction::LINKED_ADVANCE_PAID,
        ];

        // MK Chart of Accounts GL mapping (Правилник 174/2011)
        $glMapping = [
            'owner_contribution' => ['debit' => '1000', 'credit' => '3010'], // Жиро-сметка / Основна главнина
            'owner_withdrawal' => ['debit' => '3220', 'credit' => '1000'], // Повлечен капитал / Жиро-сметка
            'loan_received' => ['debit' => '1000', 'credit' => '2900'], // Жиро-сметка / Долгорочни кредити
            'loan_repayment' => ['debit' => '2900', 'credit' => '1000'], // Долгорочни кредити / Жиро-сметка
            'tax_payment' => ['debit' => '2700', 'credit' => '1000'], // Обврски за даноци / Жиро-сметка
            'internal_transfer' => ['debit' => '1001', 'credit' => '1000'], // Девизна сметка / Жиро-сметка
            'cash_deposit' => ['debit' => '1000', 'credit' => '1020'], // Жиро-сметка / Каса-благајна
            'cash_withdrawal' => ['debit' => '1020', 'credit' => '1000'], // Каса-благајна / Жиро-сметка
            'advance_received' => ['debit' => '1000', 'credit' => '2410'], // Жиро-сметка / Примени аванси
            'advance_paid' => ['debit' => '1500', 'credit' => '1000'], // Дадени аванси / Жиро-сметка
        ];

        // Tax sub-type GL refinement
        if ($action === 'tax_payment' && $subType) {
            $taxGlMap = [
                // VAT
                'ддв' => '2700',         // ДДВ обврска
                'ddv' => '2700',
                'vat' => '2700',
                'tvsh' => '2700',
                // Profit tax
                'добивка' => '2710',      // Данок на добивка
                'profit' => '2710',
                // Payroll contributions (per MPIN declaration)
                'фпиом' => '2520',        // Фонд за ПИОМ (пензиско осигурување)
                'fpiom' => '2520',
                'pension' => '2520',
                'пензиско' => '2520',
                'пио' => '2520',
                'фзом' => '2530',         // Фонд за здравствено осигурување
                'fzom' => '2530',
                'health' => '2530',
                'здравствено' => '2530',
                'здравств' => '2530',
                'вработување' => '2550',  // Фонд за вработување (невработеност)
                'employment' => '2550',
                'невработеност' => '2550',
                'професионален' => '2560', // Професионален дополнителен придонес
                'additional' => '2560',
                'дополнителен' => '2560',
                // Personal income tax
                'персонален' => '2540',   // Персонален данок од доход
                'personal' => '2540',
                'pit' => '2540',
                'данок од плата' => '2540',
                // Advance tax
                'аконтација' => '2700',
                // Customs duties / Царина
                'царина' => '1310',       // ДДВ при увоз / Царински давачки
                'customs' => '1310',
                'doganë' => '1310',
                'gümrük' => '1310',
                'увоз' => '1310',
                // Excise tax / Акциза
                'акциза' => '4310',       // Акцизи
                'excise' => '4310',
                'akcizë' => '4310',
                // Communal tax / Комунална такса
                'комунална такса' => '4650', // Комунални такси
                'komunale' => '4650',
                'komunal' => '4650',
                'фирмарина' => '4650',     // Firmarina (business sign tax)
            ];
            $subTypeLower = mb_strtolower($subType);
            foreach ($taxGlMap as $keyword => $account) {
                if (str_contains($subTypeLower, $keyword)) {
                    $glMapping['tax_payment']['debit'] = $account;
                    break;
                }
            }
        }

        // Store processing notes with GL info for future IFRS posting
        $processingNotes = json_encode([
            'action' => $action,
            'sub_type' => $subType,
            'notes' => $notes,
            'amount_cents' => $amountCents,
            'gl_debit' => $glMapping[$action]['debit'] ?? null,
            'gl_credit' => $glMapping[$action]['credit'] ?? null,
            'recorded_by' => Auth::id(),
            'recorded_at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE);

        $transaction->update(['processing_notes' => $processingNotes]);
        $transaction->markAsReconciled($linkedTypeMap[$action]);

        // Post to IFRS if adapter available
        try {
            $ifrs = app(\App\Domain\Accounting\IfrsAdapter::class);
            $gl = $glMapping[$action] ?? null;
            if ($ifrs && $gl) {
                $narration = ($subType ? "[{$subType}] " : '').($notes ?: $action);
                $counterparty = $transaction->creditor_name ?? $transaction->debtor_name ?? '';

                // MK account names for GL codes
                $accountNames = [
                    '1000' => 'Жиро-сметка', '1001' => 'Девизна сметка',
                    '1020' => 'Каса-благајна', '1310' => 'ДДВ при увоз / Царина',
                    '1500' => 'Дадени аванси', '2410' => 'Примени аванси',
                    '2520' => 'Обврски за ФПИОМ (пензиско)', '2530' => 'Обврски за ФЗОМ (здравствено)',
                    '2540' => 'Обврски за персонален данок', '2550' => 'Обврски за вработување',
                    '2560' => 'Обврски за професионален придонес', '2700' => 'Обврски за ДДВ',
                    '2710' => 'Обврски за данок на добивка', '2900' => 'Долгорочни кредити',
                    '3010' => 'Основна главнина', '3220' => 'Повлечен капитал',
                    '4310' => 'Акцизи', '4650' => 'Комунални такси',
                    '4720' => 'Расходи за камати',
                ];

                // Loan repayment with interest split — post two GL entries
                $interestAmount = $request->interest_amount ? (int) round(abs((float) $request->interest_amount) * 100) : 0;
                if ($action === 'loan_repayment' && $interestAmount > 0 && $interestAmount < $amountCents) {
                    $principalCents = $amountCents - $interestAmount;
                    $ifrs->postJournalEntry($company, [
                        'date' => $transaction->transaction_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
                        'narration' => $narration,
                        'reference' => "BANK-TX-{$transaction->id}",
                        'line_items' => [
                            // Principal: DR 2900 / CR 1000
                            [
                                'account_code' => '2900',
                                'account_name' => $accountNames['2900'],
                                'amount' => $principalCents,
                                'credited' => false,
                                'counterparty_name' => $counterparty,
                            ],
                            [
                                'account_code' => '1000',
                                'account_name' => $accountNames['1000'],
                                'amount' => $principalCents,
                                'credited' => true,
                                'counterparty_name' => $counterparty,
                            ],
                            // Interest: DR 4720 / CR 1000
                            [
                                'account_code' => '4720',
                                'account_name' => $accountNames['4720'],
                                'amount' => $interestAmount,
                                'credited' => false,
                                'counterparty_name' => $counterparty,
                            ],
                            [
                                'account_code' => '1000',
                                'account_name' => $accountNames['1000'],
                                'amount' => $interestAmount,
                                'credited' => true,
                                'counterparty_name' => $counterparty,
                            ],
                        ],
                    ]);
                } else {
                    // Standard single GL entry
                    $ifrs->postJournalEntry($company, [
                        'date' => $transaction->transaction_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
                        'narration' => $narration,
                        'reference' => "BANK-TX-{$transaction->id}",
                        'line_items' => [
                            [
                                'account_code' => $gl['debit'],
                                'account_name' => $accountNames[$gl['debit']] ?? $gl['debit'],
                                'amount' => $amountCents,
                                'credited' => false,
                                'counterparty_name' => $counterparty,
                            ],
                            [
                                'account_code' => $gl['credit'],
                                'account_name' => $accountNames[$gl['credit']] ?? $gl['credit'],
                                'amount' => $amountCents,
                                'credited' => true,
                                'counterparty_name' => $counterparty,
                            ],
                        ],
                    ]);
                }
            }
        } catch (\Exception $e) {
            // IFRS posting is non-critical — transaction is still reconciled
            Log::warning('[Reconciliation] IFRS posting failed for financial transaction', [
                'transaction_id' => $transaction->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }

        $labels = [
            'owner_contribution' => 'owner capital contribution',
            'owner_withdrawal' => 'owner capital withdrawal',
            'loan_received' => 'loan received',
            'loan_repayment' => 'loan repayment',
            'tax_payment' => 'tax payment',
            'internal_transfer' => 'internal transfer',
            'cash_deposit' => 'cash deposit',
            'cash_withdrawal' => 'cash withdrawal',
            'advance_received' => 'advance payment received',
            'advance_paid' => 'advance payment to supplier',
        ];

        return response()->json([
            'success' => true,
            'message' => 'Transaction recorded as '.($labels[$action] ?? $action),
            'linked_type' => $linkedTypeMap[$action],
        ]);
    }

    /**
     * Get the current company from the authenticated user.
     *
     * P0-13: Resolves company from request header and validates user access.
     * Falls back to user's first company if no header is provided.
     */
    protected function getCompany(): Company
    {
        $user = Auth::user();
        $companyIdHeader = request()->header('company');

        if ($companyIdHeader) {
            $companyId = (int) $companyIdHeader;

            // Validate user has access to this company
            if ($user->hasCompany($companyId)) {
                $company = $user->companies()->where('companies.id', $companyId)->first();
                if ($company) {
                    return $company;
                }
            }
        }

        // Fall back to user's first company
        return $user->companies()->firstOrFail();
    }

    /**
     * Generate PP30 payment slip from a bank transaction.
     */
    public function generatePp30(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|integer|exists:bank_transactions,id',
        ]);

        $company = $this->getCompany();

        $transaction = BankTransaction::forCompany($company->id)
            ->where('id', $request->transaction_id)
            ->firstOrFail();

        $service = new \Modules\Mk\Services\Pp30PdfService();
        $pdf = $service->generateFromTransaction($transaction, $company);

        $filename = 'PP30-' . ($transaction->transaction_reference ?? $transaction->id) . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Create payment and update invoice status
     */
    protected function createPaymentAndUpdateInvoice(BankTransaction $transaction, Invoice $invoice, array $match): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($transaction, $invoice, $match) {
            // Generate payment number
            $year = date('Y');
            $sequence = \App\Models\Payment::where('company_id', $invoice->company_id)
                ->whereYear('created_at', $year)
                ->count() + 1;
            $paymentNumber = sprintf('PAY-%d-%06d', $year, $sequence);

            // Create payment record
            $payment = \App\Models\Payment::create([
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => $match['amount'],
                'payment_date' => $transaction->transaction_date,
                'payment_number' => $paymentNumber,
                'gateway' => \App\Models\Payment::GATEWAY_BANK_TRANSFER,
                'gateway_status' => \App\Models\Payment::GATEWAY_STATUS_COMPLETED,
                'gateway_transaction_id' => $transaction->external_reference ?? $transaction->transaction_reference,
                'notes' => "Manually matched from bank transaction. Confidence: {$match['confidence']}%",
            ]);

            // Update invoice status based on payment amount vs total
            $totalPaid = $invoice->payments()->sum('amount') + $match['amount'];
            if ($totalPaid >= $invoice->total) {
                $invoice->status = Invoice::STATUS_PAID;
                $invoice->paid_status = Invoice::STATUS_PAID;
            } else {
                $invoice->paid_status = Invoice::STATUS_PARTIALLY_PAID;
            }
            $invoice->save();

            // Mark transaction as matched
            $transaction->update([
                'matched_invoice_id' => $invoice->id,
                'matched_payment_id' => $payment->id,
                'matched_at' => now(),
                'match_confidence' => $match['confidence'],
                'processing_status' => BankTransaction::STATUS_PROCESSED,
                'processed_at' => now(),
            ]);
        });
    }
}

