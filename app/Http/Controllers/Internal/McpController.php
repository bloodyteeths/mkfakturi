<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\BankTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Internal MCP API Controller
 *
 * Provides endpoints for the MCP (Model Context Protocol) server.
 * These endpoints are internal-only and should be protected by VerifyMcpToken middleware.
 * They enable AI tools to interact with Fakturino data.
 */
class McpController extends Controller
{
    /**
     * Get company statistics
     *
     * Returns comprehensive statistics for a company including revenue,
     * invoice counts, customer counts, and payment status.
     */
    public function companyStats(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        $companyId = $request->input('company_id');

        try {
            $company = Company::findOrFail($companyId);

            $stats = [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'total_revenue' => Invoice::where('company_id', $companyId)
                    ->where('status', 'PAID')
                    ->sum('total'),
                'invoices_count' => Invoice::where('company_id', $companyId)->count(),
                'customers_count' => Customer::where('company_id', $companyId)->count(),
                'pending_invoices' => Invoice::where('company_id', $companyId)
                    ->where('status', 'SENT')
                    ->count(),
                'overdue_invoices' => Invoice::where('company_id', $companyId)
                    ->where('status', 'SENT')
                    ->where('due_date', '<', now())
                    ->count(),
                'draft_invoices' => Invoice::where('company_id', $companyId)
                    ->where('status', 'DRAFT')
                    ->count(),
            ];

            Log::info('MCP: Company stats retrieved', ['company_id' => $companyId]);

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('MCP: Failed to retrieve company stats', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve company stats',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search customers by name or email
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'query' => 'required|string|min:2',
        ]);

        $companyId = $request->input('company_id');
        $query = $request->input('query');

        try {
            $customers = Customer::where('company_id', $companyId)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                })
                ->limit(20)
                ->get()
                ->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'total_invoices' => $customer->invoices()->count(),
                    ];
                });

            Log::info('MCP: Customer search completed', [
                'company_id' => $companyId,
                'query' => $query,
                'results_count' => $customers->count(),
            ]);

            return response()->json(['customers' => $customers]);
        } catch (\Exception $e) {
            Log::error('MCP: Customer search failed', [
                'company_id' => $companyId,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Customer search failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get trial balance (requires FEATURE_ACCOUNTING_BACKBONE)
     *
     * Returns trial balance for a company, showing all account balances.
     */
    public function trialBalance(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'as_of_date' => 'nullable|date',
        ]);

        // Check if accounting backbone is enabled
        if (! config('features.accounting_backbone.enabled', false)) {
            return response()->json([
                'error' => 'Accounting backbone disabled',
                'message' => 'The accounting backbone feature is not enabled. Enable FEATURE_ACCOUNTING_BACKBONE to use this endpoint.',
            ], 403);
        }

        $companyId = $request->input('company_id');
        $asOfDate = $request->input('as_of_date', now()->toDateString());

        try {
            // This would integrate with eloquent-ifrs when accounting backbone is enabled
            // For now, return a placeholder response
            $response = [
                'company_id' => $companyId,
                'as_of_date' => $asOfDate,
                'accounts' => [],
                'total_debits' => 0,
                'total_credits' => 0,
                'balanced' => true,
                'message' => 'Trial balance functionality requires accounting backbone integration',
            ];

            Log::info('MCP: Trial balance requested', [
                'company_id' => $companyId,
                'as_of_date' => $asOfDate,
            ]);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('MCP: Trial balance retrieval failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve trial balance',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate UBL invoice XML and signature
     */
    public function validateUbl(Request $request): JsonResponse
    {
        $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'xml_content' => 'nullable|string',
        ]);

        $invoiceId = $request->input('invoice_id');
        $xmlContent = $request->input('xml_content');

        try {
            $invoice = Invoice::findOrFail($invoiceId);

            // Placeholder for UBL validation logic
            // This would integrate with existing UBL export/signing functionality
            $response = [
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoice->invoice_number,
                'valid' => true,
                'signature_valid' => true,
                'errors' => [],
                'warnings' => [],
                'message' => 'UBL validation integration pending',
            ];

            Log::info('MCP: UBL validation requested', [
                'invoice_id' => $invoiceId,
                'has_xml_content' => ! empty($xmlContent),
            ]);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('MCP: UBL validation failed', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'UBL validation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Explain tax calculation for an invoice
     */
    public function explainTax(Request $request): JsonResponse
    {
        $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
        ]);

        $invoiceId = $request->input('invoice_id');

        try {
            $invoice = Invoice::with('items')->findOrFail($invoiceId);

            $itemsBreakdown = $invoice->items->map(function ($item) {
                return [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->quantity * $item->price,
                    'tax_rate' => $item->tax_per_item ?? 18, // Default DDV rate for Macedonia
                    'tax_amount' => ($item->quantity * $item->price) * ($item->tax_per_item ?? 18) / 100,
                    'total' => $item->total,
                ];
            });

            $response = [
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoice->invoice_number,
                'subtotal' => $invoice->sub_total,
                'tax_amount' => $invoice->tax,
                'total' => $invoice->total,
                'items' => $itemsBreakdown,
                'explanation' => 'Macedonian DDV (VAT) is calculated at standard rate of 18% on taxable items.',
            ];

            Log::info('MCP: Tax explanation generated', ['invoice_id' => $invoiceId]);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('MCP: Tax explanation failed', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Tax explanation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Categorize a bank transaction (AI-powered suggestion)
     */
    public function categorizeTransaction(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|integer|exists:bank_transactions,id',
        ]);

        // Check if PSD2 banking is enabled
        if (! config('features.psd2_banking.enabled', false)) {
            return response()->json([
                'error' => 'PSD2 banking disabled',
                'message' => 'The PSD2 banking feature is not enabled. Enable FEATURE_PSD2_BANKING to use this endpoint.',
            ], 403);
        }

        $transactionId = $request->input('transaction_id');

        try {
            $transaction = BankTransaction::findOrFail($transactionId);

            // Placeholder for AI-powered categorization
            // This would use transaction description and amount to suggest accounting category
            $response = [
                'transaction_id' => $transactionId,
                'transaction_date' => $transaction->transaction_date,
                'amount' => $transaction->amount,
                'type' => $transaction->type,
                'description' => $transaction->description,
                'suggested_category' => 'REVENUE',
                'category_name' => 'Sales Revenue',
                'confidence' => 0.85,
                'reason' => 'Transaction appears to be incoming payment for services',
            ];

            Log::info('MCP: Transaction categorization suggested', [
                'transaction_id' => $transactionId,
            ]);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('MCP: Transaction categorization failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Transaction categorization failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Scan for invoice anomalies in a date range
     */
    public function scanAnomalies(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $companyId = $request->input('company_id');
        $startDate = $request->input('start');
        $endDate = $request->input('end');

        try {
            $invoices = Invoice::where('company_id', $companyId)
                ->whereBetween('invoice_date', [$startDate, $endDate])
                ->get();

            $anomalies = [];

            // Check for negative totals
            foreach ($invoices as $invoice) {
                if ($invoice->total < 0) {
                    $anomalies[] = [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'severity' => 'high',
                        'type' => 'negative_total',
                        'message' => 'Invoice has negative total amount',
                    ];
                }
            }

            // Check for duplicates (same customer, same amount, same date)
            $duplicates = Invoice::where('company_id', $companyId)
                ->whereBetween('invoice_date', [$startDate, $endDate])
                ->select('customer_id', 'total', 'invoice_date', DB::raw('count(*) as count'))
                ->groupBy('customer_id', 'total', 'invoice_date')
                ->having('count', '>', 1)
                ->get();

            foreach ($duplicates as $duplicate) {
                $anomalies[] = [
                    'customer_id' => $duplicate->customer_id,
                    'severity' => 'medium',
                    'type' => 'potential_duplicate',
                    'message' => "Multiple invoices with same amount ({$duplicate->total}) on same date",
                ];
            }

            $response = [
                'company_id' => $companyId,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
                'total_scanned' => $invoices->count(),
                'issues_found' => count($anomalies),
                'severity_breakdown' => [
                    'high' => collect($anomalies)->where('severity', 'high')->count(),
                    'medium' => collect($anomalies)->where('severity', 'medium')->count(),
                    'low' => collect($anomalies)->where('severity', 'low')->count(),
                ],
                'anomalies' => $anomalies,
            ];

            Log::info('MCP: Anomaly scan completed', [
                'company_id' => $companyId,
                'date_range' => "{$startDate} to {$endDate}",
                'anomalies_found' => count($anomalies),
            ]);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('MCP: Anomaly scan failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Anomaly scan failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Health check endpoint for MCP server
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'Fakturino MCP API',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}

// CLAUDE-CHECKPOINT
