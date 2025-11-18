<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
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

            if (! empty($params['status'])) {
                $query->where('status', $params['status']);
            }

            if (! empty($params['from_date'])) {
                $query->where('invoice_date', '>=', $params['from_date']);
            }

            if (! empty($params['to_date'])) {
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

    /**
     * Get monthly revenue and expense trends for the past N months
     *
     * @param  int  $months  Number of months to retrieve (default: 12)
     * @return array<int, array{month: string, revenue: float, expenses: float, profit: float, invoice_count: int}>
     */
    public function getMonthlyTrends(Company $company, int $months = 12): array
    {
        try {
            Log::info('[McpDataProvider] Fetching monthly trends', [
                'company_id' => $company->id,
                'months' => $months,
            ]);

            $startDate = now()->subMonths($months)->startOfMonth();

            // Get monthly revenue (paid invoices)
            $monthlyRevenue = Invoice::where('company_id', $company->id)
                ->where('paid_status', 'PAID')
                ->where('invoice_date', '>=', $startDate)
                ->selectRaw('DATE_FORMAT(invoice_date, "%Y-%m") as month')
                ->selectRaw('SUM(total) as revenue')
                ->selectRaw('COUNT(*) as invoice_count')
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy('month');

            // Get monthly expenses
            $monthlyExpenses = \App\Models\Expense::where('company_id', $company->id)
                ->where('expense_date', '>=', $startDate)
                ->selectRaw('DATE_FORMAT(expense_date, "%Y-%m") as month')
                ->selectRaw('SUM(amount) as expenses')
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy('month');

            // Build complete monthly array (fill gaps with zeros)
            $trends = [];
            for ($i = $months - 1; $i >= 0; $i--) {
                $month = now()->subMonths($i)->format('Y-m');

                $revenue = isset($monthlyRevenue[$month]) ? (float) $monthlyRevenue[$month]->revenue : 0.0;
                $expenses = isset($monthlyExpenses[$month]) ? (float) $monthlyExpenses[$month]->expenses : 0.0;
                $invoiceCount = isset($monthlyRevenue[$month]) ? (int) $monthlyRevenue[$month]->invoice_count : 0;

                $trends[] = [
                    'month' => $month,
                    'revenue' => $revenue,
                    'expenses' => $expenses,
                    'profit' => $revenue - $expenses,
                    'invoice_count' => $invoiceCount,
                ];
            }

            Log::info('[McpDataProvider] Monthly trends calculated', [
                'months_returned' => count($trends),
            ]);

            return $trends;

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get monthly trends', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Get customer growth trends
     *
     * @param  int  $months  Number of months to retrieve
     * @return array<int, array{month: string, new_customers: int, total_customers: int}>
     */
    public function getCustomerGrowth(Company $company, int $months = 12): array
    {
        try {
            $startDate = now()->subMonths($months)->startOfMonth();

            // Get monthly new customers
            $monthlyCustomers = Customer::where('company_id', $company->id)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month')
                ->selectRaw('COUNT(*) as new_customers')
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy('month');

            $growth = [];
            $runningTotal = Customer::where('company_id', $company->id)
                ->where('created_at', '<', $startDate)
                ->count();

            for ($i = $months - 1; $i >= 0; $i--) {
                $month = now()->subMonths($i)->format('Y-m');

                $newCustomers = isset($monthlyCustomers[$month]) ? (int) $monthlyCustomers[$month]->new_customers : 0;
                $runningTotal += $newCustomers;

                $growth[] = [
                    'month' => $month,
                    'new_customers' => $newCustomers,
                    'total_customers' => $runningTotal,
                ];
            }

            return $growth;

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get customer growth', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get payment timing analysis
     *
     * @return array{avg_days_to_payment: float, on_time_percentage: float, late_percentage: float}
     */
    public function getPaymentTimingAnalysis(Company $company): array
    {
        try {
            // Get paid invoices with due dates and payment dates
            $paidInvoices = Invoice::where('company_id', $company->id)
                ->where('paid_status', 'PAID')
                ->whereNotNull('due_date')
                ->whereHas('payments')
                ->with('payments')
                ->get();

            if ($paidInvoices->isEmpty()) {
                return [
                    'avg_days_to_payment' => 0.0,
                    'on_time_percentage' => 0.0,
                    'late_percentage' => 0.0,
                ];
            }

            $totalDays = 0;
            $onTimeCount = 0;
            $lateCount = 0;
            $validCount = 0;

            foreach ($paidInvoices as $invoice) {
                $lastPayment = $invoice->payments->sortByDesc('payment_date')->first();
                if (! $lastPayment || ! $lastPayment->payment_date) {
                    continue;
                }

                $validCount++;
                $dueDate = \Carbon\Carbon::parse($invoice->due_date);
                $paymentDate = \Carbon\Carbon::parse($lastPayment->payment_date);

                $daysToPayment = $dueDate->diffInDays($paymentDate, false);
                $totalDays += abs($daysToPayment);

                if ($paymentDate->lte($dueDate)) {
                    $onTimeCount++;
                } else {
                    $lateCount++;
                }
            }

            $avgDays = $validCount > 0 ? $totalDays / $validCount : 0.0;
            $onTimePercentage = $validCount > 0 ? ($onTimeCount / $validCount) * 100 : 0.0;
            $latePercentage = $validCount > 0 ? ($lateCount / $validCount) * 100 : 0.0;

            return [
                'avg_days_to_payment' => round($avgDays, 1),
                'on_time_percentage' => round($onTimePercentage, 1),
                'late_percentage' => round($latePercentage, 1),
            ];

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get payment timing analysis', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'avg_days_to_payment' => 0.0,
                'on_time_percentage' => 0.0,
                'late_percentage' => 0.0,
            ];
        }
    }

    /**
     * Get top customers by revenue
     *
     * @param  int  $limit  Number of customers to return
     * @return array<int, array{customer_name: string, revenue: float, invoice_count: int}>
     */
    public function getTopCustomers(Company $company, int $limit = 10): array
    {
        try {
            $topCustomers = Invoice::where('company_id', $company->id)
                ->where('paid_status', 'PAID')
                ->select('customer_id')
                ->selectRaw('SUM(total) as revenue')
                ->selectRaw('COUNT(*) as invoice_count')
                ->groupBy('customer_id')
                ->orderByDesc('revenue')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'customer_name' => $item->customer->name ?? 'Unknown',
                        'revenue' => (float) $item->revenue,
                        'invoice_count' => (int) $item->invoice_count,
                    ];
                })
                ->toArray();

            return $topCustomers;

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get top customers', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}

// CLAUDE-CHECKPOINT
