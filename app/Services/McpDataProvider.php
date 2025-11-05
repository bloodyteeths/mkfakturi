<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MCP Data Provider
 * 
 * Provides direct database access to financial data for AI analysis.
 * Bypasses HTTP layer to avoid self-calling issues.
 */
class McpDataProvider
{
    /**
     * Get company statistics
     */
    public function getCompanyStats(Company $company): array
    {
        try {
            Log::info('[McpDataProvider] Fetching company stats', [
                'company_id' => $company->id,
                'company_name' => $company->name,
            ]);

            // Get all invoices for debugging
            $allInvoices = Invoice::where('company_id', $company->id)->get();
            Log::info('[McpDataProvider] Raw invoices query result', [
                'company_id' => $company->id,
                'total_invoices' => $allInvoices->count(),
                'invoice_statuses' => $allInvoices->pluck('status')->toArray(),
                'invoice_ids' => $allInvoices->pluck('id')->toArray(),
            ]);

            // Revenue = invoices with paid_status = PAID (not status field!)
            $totalRevenue = (float) Invoice::where('company_id', $company->id)
                ->where('paid_status', 'PAID')
                ->sum('total');

            $invoicesCount = Invoice::where('company_id', $company->id)->count();
            $customersCount = Customer::where('company_id', $company->id)->count();
            $pendingInvoices = Invoice::where('company_id', $company->id)
                ->where('status', 'SENT')
                ->count();
            $overdueInvoices = Invoice::where('company_id', $company->id)
                ->where('status', 'SENT')
                ->where('due_date', '<', now())
                ->count();
            $draftInvoices = Invoice::where('company_id', $company->id)
                ->where('status', 'DRAFT')
                ->count();

            // Calculate outstanding amount (unpaid invoices - use paid_status)
            $outstandingAmount = (float) Invoice::where('company_id', $company->id)
                ->where('paid_status', '!=', 'PAID')
                ->whereIn('status', ['SENT', 'COMPLETED'])
                ->sum('due_amount');

            // Aggregate expenses
            $expenses = (float) \App\Models\Expense::where('company_id', $company->id)
                ->sum('amount');

            // Aggregate payments received
            $totalPayments = (float) \App\Models\Payment::where('company_id', $company->id)
                ->sum('amount');

            // Calculate payment reconciliation variance
            // Positive = more revenue than payments (outstanding)
            // Negative = more payments than revenue (overpayment/credit)
            $paymentVariance = $totalRevenue - $totalPayments;

            $stats = [
                'company_id' => $company->id,
                'company_name' => $company->name,
                // Keys that match what AI prompts expect:
                'revenue' => $totalRevenue,
                'expenses' => $expenses,
                'outstanding' => $outstandingAmount,
                'customers' => $customersCount,
                'invoices_count' => $invoicesCount,
                // Payment reconciliation data:
                'payments_received' => $totalPayments,
                'payment_variance' => $paymentVariance,
                // Additional detail fields:
                'pending_invoices' => $pendingInvoices,
                'overdue_invoices' => $overdueInvoices,
                'draft_invoices' => $draftInvoices,
            ];

            Log::info('[McpDataProvider] Company stats calculated', $stats);

            return $stats;
        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get company stats', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'revenue' => 0,
                'expenses' => 0,
                'outstanding' => 0,
                'customers' => 0,
                'invoices_count' => 0,
                'payments_received' => 0,
                'payment_variance' => 0,
                'pending_invoices' => 0,
                'overdue_invoices' => 0,
                'draft_invoices' => 0,
            ];
        }
    }

    /**
     * Get trial balance
     */
    public function getTrialBalance(Company $company): array
    {
        try {
            Log::info('[McpDataProvider] Fetching trial balance', [
                'company_id' => $company->id,
            ]);

            // Simplified trial balance - can be expanded with actual accounting logic
            // Debits = all billed invoices (SENT or COMPLETED)
            $totalDebits = (float) Invoice::where('company_id', $company->id)
                ->whereIn('status', ['SENT', 'COMPLETED'])
                ->sum('total');

            // Credits = all paid invoices (paid_status = PAID)
            $totalCredits = (float) Invoice::where('company_id', $company->id)
                ->where('paid_status', 'PAID')
                ->sum('total');

            $balance = [
                'debits' => $totalDebits,
                'credits' => $totalCredits,
                'balance' => $totalDebits - $totalCredits,
            ];

            Log::info('[McpDataProvider] Trial balance calculated', $balance);

            return $balance;
        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get trial balance', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'debits' => 0,
                'credits' => 0,
                'balance' => 0,
            ];
        }
    }

    /**
     * Search customers
     */
    public function searchCustomers(Company $company, string $query): array
    {
        try {
            $customers = Customer::where('company_id', $company->id)
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
                    ];
                })
                ->toArray();
                
            return $customers;
        } catch (\Exception $e) {
            Log::error('Failed to search customers', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    /**
     * Search invoices
     */
    public function searchInvoices(Company $company, array $params = []): array
    {
        try {
            $query = Invoice::where('company_id', $company->id);
            
            if (!empty($params['status'])) {
                $query->where('status', $params['status']);
            }
            
            if (!empty($params['from_date'])) {
                $query->where('invoice_date', '>=', $params['from_date']);
            }
            
            if (!empty($params['to_date'])) {
                $query->where('invoice_date', '<=', $params['to_date']);
            }
            
            $invoices = $query->limit(50)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'customer_name' => $invoice->customer?->name ?? 'N/A',
                        'total' => (float) $invoice->total,
                        'due_amount' => (float) $invoice->due_amount,
                        'status' => $invoice->status,
                        'paid_status' => $invoice->paid_status,
                        'invoice_date' => $invoice->invoice_date,
                        'due_date' => $invoice->due_date,
                    ];
                })
                ->toArray();
                
            return $invoices;
        } catch (\Exception $e) {
            Log::error('Failed to search invoices', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
}

// CLAUDE-CHECKPOINT
