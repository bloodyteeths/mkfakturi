<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\Invoice;
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

        // Get unmatched transactions
        $transactions = BankTransaction::where('company_id', $company->id)
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

        $transaction = BankTransaction::where('company_id', $company->id)
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
     * Get the current company from the authenticated user
     */
    protected function getCompany(): Company
    {
        return Company::find(Auth::user()->companies()->first()->id);
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
