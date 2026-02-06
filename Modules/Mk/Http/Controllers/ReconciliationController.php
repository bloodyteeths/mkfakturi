<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Reconciliation;
use App\Models\ReconciliationSplit;
use App\Services\Reconciliation\ReconciliationPostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $matcher = new Matcher($company->id);

        // Get unmatched transactions (P0-13: explicit tenant scope)
        $transactions = BankTransaction::forCompany($company->id)
            ->whereNull('matched_invoice_id')
            ->where('amount', '>', 0)
            ->orderBy('transaction_date', 'desc')
            ->paginate($request->get('limit', 20));

        // Get suggested matches for each transaction (without creating payments)
        $transactionsWithMatches = $transactions->getCollection()->map(function ($transaction) use ($matcher) {
            $match = $matcher->suggestMatch($transaction);

            return [
                'id' => $transaction->id,
                'transaction_date' => $transaction->transaction_date,
                'amount' => (float) $transaction->amount,
                'currency' => $transaction->currency,
                'description' => $transaction->description,
                'remittance_info' => $transaction->remittance_info,
                'counterparty_name' => $transaction->creditor_name ?? $transaction->debtor_name,
                'external_reference' => $transaction->external_reference,
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
        $matcher = new Matcher(
            $company->id,
            $request->get('lookback_days', 7),
            $request->get('amount_tolerance', 0.01)
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
            ->where('status', 'SENT')
            ->firstOrFail();

        // Create match using Matcher service (confidence = 100 for manual match)
        $matcher = new Matcher($company->id);
        $match = [
            'transaction_id' => $transaction->id,
            'invoice_id' => $invoice->id,
            'amount' => (float) $transaction->amount,
            'confidence' => 100.0,
            'invoice_number' => $invoice->invoice_number,
            'invoice_total' => (float) $invoice->total,
        ];

        // Use reflection to call the protected method or duplicate logic here
        // For now, do the matching inline
        $this->createPaymentAndUpdateInvoice($transaction, $invoice, $match);

        return response()->json([
            'success' => true,
            'message' => 'Transaction matched successfully',
            'match' => $match,
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
            ->where('status', 'SENT')
            ->with('customer:id,name')
            ->orderBy('due_date', 'asc')
            ->get(['id', 'invoice_number', 'total', 'due_date', 'customer_id']);

        return response()->json([
            'data' => $invoices->map(fn ($inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'total' => (float) $inv->total,
                'due_date' => $inv->due_date,
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
                'invoice_total' => (float) ($split->invoice->total ?? 0),
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
                'reconciliation_id' => 'required|integer',
                'splits' => 'required|array|min:1',
                'splits.*.invoice_id' => 'required|integer|exists:invoices,id',
                'splits.*.amount' => 'required|numeric|min:0.01',
            ]);

            $recon = Reconciliation::forCompany($company->id)
                ->where('id', $request->reconciliation_id)
                ->firstOrFail();

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

            // Update invoice status
            $invoice->status = 'PAID';
            $invoice->paid_status = \App\Models\Payment::STATUS_COMPLETED;
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

// CLAUDE-CHECKPOINT
