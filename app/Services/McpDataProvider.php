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
            return [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'total_revenue' => (float) Invoice::where('company_id', $company->id)
                    ->where('status', 'PAID')
                    ->sum('total'),
                'invoices_count' => Invoice::where('company_id', $company->id)->count(),
                'customers_count' => Customer::where('company_id', $company->id)->count(),
                'pending_invoices' => Invoice::where('company_id', $company->id)
                    ->where('status', 'SENT')
                    ->count(),
                'overdue_invoices' => Invoice::where('company_id', $company->id)
                    ->where('status', 'SENT')
                    ->where('due_date', '<', now())
                    ->count(),
                'draft_invoices' => Invoice::where('company_id', $company->id)
                    ->where('status', 'DRAFT')
                    ->count(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get company stats', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'total_revenue' => 0,
                'invoices_count' => 0,
                'customers_count' => 0,
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
            // Simplified trial balance - can be expanded with actual accounting logic
            $totalDebits = (float) Invoice::where('company_id', $company->id)
                ->whereIn('status', ['SENT', 'PAID'])
                ->sum('total');
                
            $totalCredits = (float) Invoice::where('company_id', $company->id)
                ->where('status', 'PAID')
                ->sum('total');
                
            return [
                'debits' => $totalDebits,
                'credits' => $totalCredits,
                'balance' => $totalDebits - $totalCredits,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get trial balance', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
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
                        'status' => $invoice->status,
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
