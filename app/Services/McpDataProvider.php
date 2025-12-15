<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\EInvoice;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\ProformaInvoice;
use App\Models\Project;
use App\Models\RecurringInvoice;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
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

    /**
     * Get proforma invoice statistics
     *
     * @return array{proforma_count: int, draft_proformas: int, sent_proformas: int, total_proforma_value: float, converted_to_invoice_count: int, conversion_rate: float}
     */
    public function getProformaStats(Company $company): array
    {
        try {
            Log::info('[McpDataProvider] Fetching proforma stats', [
                'company_id' => $company->id,
            ]);

            $proformaCount = ProformaInvoice::where('company_id', $company->id)->count();
            $draftProformas = ProformaInvoice::where('company_id', $company->id)
                ->where('status', ProformaInvoice::STATUS_DRAFT)
                ->count();
            $sentProformas = ProformaInvoice::where('company_id', $company->id)
                ->where('status', ProformaInvoice::STATUS_SENT)
                ->count();
            $totalProformaValue = (float) ProformaInvoice::where('company_id', $company->id)
                ->sum('total');
            $convertedToInvoiceCount = ProformaInvoice::where('company_id', $company->id)
                ->where('status', ProformaInvoice::STATUS_CONVERTED)
                ->count();
            $conversionRate = $proformaCount > 0 ? ($convertedToInvoiceCount / $proformaCount) * 100 : 0.0;

            $stats = [
                'proforma_count' => $proformaCount,
                'draft_proformas' => $draftProformas,
                'sent_proformas' => $sentProformas,
                'total_proforma_value' => $totalProformaValue,
                'converted_to_invoice_count' => $convertedToInvoiceCount,
                'conversion_rate' => round($conversionRate, 1),
            ];

            Log::info('[McpDataProvider] Proforma stats calculated', $stats);

            return $stats;

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get proforma stats', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'proforma_count' => 0,
                'draft_proformas' => 0,
                'sent_proformas' => 0,
                'total_proforma_value' => 0.0,
                'converted_to_invoice_count' => 0,
                'conversion_rate' => 0.0,
            ];
        }
    }

    /**
     * Get bills statistics
     *
     * @return array{bills_count: int, unpaid_bills: int, overdue_bills: int, total_bills_amount: float, total_unpaid_amount: float}
     */
    public function getBillsStats(Company $company): array
    {
        try {
            Log::info('[McpDataProvider] Fetching bills stats', [
                'company_id' => $company->id,
            ]);

            $billsCount = Bill::where('company_id', $company->id)->count();
            $unpaidBills = Bill::where('company_id', $company->id)
                ->where('paid_status', Bill::PAID_STATUS_UNPAID)
                ->count();
            $overdueBills = Bill::where('company_id', $company->id)
                ->where('paid_status', '!=', Bill::PAID_STATUS_PAID)
                ->where('due_date', '<', now())
                ->count();
            $totalBillsAmount = (float) Bill::where('company_id', $company->id)
                ->sum('total');
            $totalUnpaidAmount = (float) Bill::where('company_id', $company->id)
                ->where('paid_status', '!=', Bill::PAID_STATUS_PAID)
                ->sum('due_amount');

            $stats = [
                'bills_count' => $billsCount,
                'unpaid_bills' => $unpaidBills,
                'overdue_bills' => $overdueBills,
                'total_bills_amount' => $totalBillsAmount,
                'total_unpaid_amount' => $totalUnpaidAmount,
            ];

            Log::info('[McpDataProvider] Bills stats calculated', $stats);

            return $stats;

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get bills stats', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'bills_count' => 0,
                'unpaid_bills' => 0,
                'overdue_bills' => 0,
                'total_bills_amount' => 0.0,
                'total_unpaid_amount' => 0.0,
            ];
        }
    }

    /**
     * Get suppliers statistics
     *
     * @return array{suppliers_count: int, total_payables: float, top_suppliers: array}
     */
    public function getSuppliersStats(Company $company): array
    {
        try {
            Log::info('[McpDataProvider] Fetching suppliers stats', [
                'company_id' => $company->id,
            ]);

            $suppliersCount = Supplier::where('company_id', $company->id)->count();
            $totalPayables = (float) Bill::where('company_id', $company->id)
                ->where('paid_status', '!=', Bill::PAID_STATUS_PAID)
                ->sum('due_amount');

            // Get top suppliers by bill amount
            $topSuppliers = Bill::where('company_id', $company->id)
                ->select('supplier_id')
                ->selectRaw('SUM(total) as total_bills')
                ->groupBy('supplier_id')
                ->orderByDesc('total_bills')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'supplier_name' => $item->supplier->name ?? 'Unknown',
                        'total_bills' => (float) $item->total_bills,
                    ];
                })
                ->toArray();

            $stats = [
                'suppliers_count' => $suppliersCount,
                'total_payables' => $totalPayables,
                'top_suppliers' => $topSuppliers,
            ];

            Log::info('[McpDataProvider] Suppliers stats calculated', $stats);

            return $stats;

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get suppliers stats', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'suppliers_count' => 0,
                'total_payables' => 0.0,
                'top_suppliers' => [],
            ];
        }
    }

    /**
     * Get estimates statistics
     *
     * @return array{estimates_count: int, pending_estimates: int, accepted_estimates: int, rejected_estimates: int, total_estimate_value: float, conversion_rate_to_invoice: float}
     */
    public function getEstimatesStats(Company $company): array
    {
        try {
            Log::info('[McpDataProvider] Fetching estimates stats', [
                'company_id' => $company->id,
            ]);

            $estimatesCount = Estimate::where('company_id', $company->id)->count();
            $pendingEstimates = Estimate::where('company_id', $company->id)
                ->whereIn('status', [Estimate::STATUS_SENT, Estimate::STATUS_VIEWED])
                ->count();
            $acceptedEstimates = Estimate::where('company_id', $company->id)
                ->where('status', Estimate::STATUS_ACCEPTED)
                ->count();
            $rejectedEstimates = Estimate::where('company_id', $company->id)
                ->where('status', Estimate::STATUS_REJECTED)
                ->count();
            $totalEstimateValue = (float) Estimate::where('company_id', $company->id)
                ->sum('total');
            $conversionRate = $estimatesCount > 0 ? ($acceptedEstimates / $estimatesCount) * 100 : 0.0;

            $stats = [
                'estimates_count' => $estimatesCount,
                'pending_estimates' => $pendingEstimates,
                'accepted_estimates' => $acceptedEstimates,
                'rejected_estimates' => $rejectedEstimates,
                'total_estimate_value' => $totalEstimateValue,
                'conversion_rate_to_invoice' => round($conversionRate, 1),
            ];

            Log::info('[McpDataProvider] Estimates stats calculated', $stats);

            return $stats;

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get estimates stats', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'estimates_count' => 0,
                'pending_estimates' => 0,
                'accepted_estimates' => 0,
                'rejected_estimates' => 0,
                'total_estimate_value' => 0.0,
                'conversion_rate_to_invoice' => 0.0,
            ];
        }
    }

    /**
     * Get recurring invoices statistics
     *
     * @return array{active_recurring_count: int, paused_recurring_count: int, monthly_recurring_revenue: float, next_invoices_this_month: int}
     */
    public function getRecurringInvoicesStats(Company $company): array
    {
        try {
            Log::info('[McpDataProvider] Fetching recurring invoices stats', [
                'company_id' => $company->id,
            ]);

            $activeRecurringCount = RecurringInvoice::where('company_id', $company->id)
                ->where('status', RecurringInvoice::ACTIVE)
                ->count();
            $pausedRecurringCount = RecurringInvoice::where('company_id', $company->id)
                ->where('status', RecurringInvoice::ON_HOLD)
                ->count();
            $monthlyRecurringRevenue = (float) RecurringInvoice::where('company_id', $company->id)
                ->where('status', RecurringInvoice::ACTIVE)
                ->sum('total');

            // Count next invoices scheduled for this month
            $nextInvoicesThisMonth = RecurringInvoice::where('company_id', $company->id)
                ->where('status', RecurringInvoice::ACTIVE)
                ->whereBetween('next_invoice_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();

            $stats = [
                'active_recurring_count' => $activeRecurringCount,
                'paused_recurring_count' => $pausedRecurringCount,
                'monthly_recurring_revenue' => $monthlyRecurringRevenue,
                'next_invoices_this_month' => $nextInvoicesThisMonth,
            ];

            Log::info('[McpDataProvider] Recurring invoices stats calculated', $stats);

            return $stats;

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get recurring invoices stats', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'active_recurring_count' => 0,
                'paused_recurring_count' => 0,
                'monthly_recurring_revenue' => 0.0,
                'next_invoices_this_month' => 0,
            ];
        }
    }

    /**
     * Get inventory statistics
     *
     * @return array{total_items: int, items_with_stock_tracking: int, low_stock_items: int, total_inventory_value: float}
     */
    public function getInventoryStats(Company $company): array
    {
        try {
            Log::info('[McpDataProvider] Fetching inventory stats', [
                'company_id' => $company->id,
            ]);

            $totalItems = Item::where('company_id', $company->id)->count();
            $itemsWithStockTracking = Item::where('company_id', $company->id)
                ->where('track_quantity', true)
                ->count();

            // Low stock items: where current quantity < reorder_level
            // This would require querying stock movements to get current quantities
            // For simplicity, we'll set this to 0 as it requires complex logic
            $lowStockItems = 0;

            // Total inventory value from latest stock movements
            $totalInventoryValue = 0.0;
            $itemsWithStock = Item::where('company_id', $company->id)
                ->where('track_quantity', true)
                ->get();

            foreach ($itemsWithStock as $item) {
                $latestMovement = StockMovement::where('company_id', $company->id)
                    ->where('item_id', $item->id)
                    ->orderBy('movement_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($latestMovement) {
                    $totalInventoryValue += (float) $latestMovement->balance_value / 100; // Convert from cents
                }
            }

            $stats = [
                'total_items' => $totalItems,
                'items_with_stock_tracking' => $itemsWithStockTracking,
                'low_stock_items' => $lowStockItems,
                'total_inventory_value' => round($totalInventoryValue, 2),
            ];

            Log::info('[McpDataProvider] Inventory stats calculated', $stats);

            return $stats;

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get inventory stats', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'total_items' => 0,
                'items_with_stock_tracking' => 0,
                'low_stock_items' => 0,
                'total_inventory_value' => 0.0,
            ];
        }
    }

    /**
     * Get warehouse statistics
     *
     * @return array{warehouses_count: int, items_per_warehouse: array}
     */
    public function getWarehouseStats(Company $company): array
    {
        try {
            Log::info('[McpDataProvider] Fetching warehouse stats', [
                'company_id' => $company->id,
            ]);

            $warehousesCount = Warehouse::where('company_id', $company->id)->count();

            // Get items count per warehouse
            $itemsPerWarehouse = Warehouse::where('company_id', $company->id)
                ->get()
                ->map(function ($warehouse) {
                    $itemCount = StockMovement::where('warehouse_id', $warehouse->id)
                        ->distinct('item_id')
                        ->count('item_id');

                    return [
                        'warehouse_name' => $warehouse->name,
                        'item_count' => $itemCount,
                    ];
                })
                ->toArray();

            $stats = [
                'warehouses_count' => $warehousesCount,
                'items_per_warehouse' => $itemsPerWarehouse,
            ];

            Log::info('[McpDataProvider] Warehouse stats calculated', $stats);

            return $stats;

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get warehouse stats', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'warehouses_count' => 0,
                'items_per_warehouse' => [],
            ];
        }
    }

    /**
     * Get projects statistics
     *
     * @return array{projects_count: int, active_projects: int, revenue_per_project: array}
     */
    public function getProjectsStats(Company $company): array
    {
        try {
            Log::info('[McpDataProvider] Fetching projects stats', [
                'company_id' => $company->id,
            ]);

            $projectsCount = Project::where('company_id', $company->id)->count();
            $activeProjects = Project::where('company_id', $company->id)
                ->whereNotIn('status', [Project::STATUS_COMPLETED, Project::STATUS_CANCELLED])
                ->count();

            // Get revenue per project (top 5)
            $revenuePerProject = Project::where('company_id', $company->id)
                ->limit(5)
                ->get()
                ->map(function ($project) {
                    $revenue = Invoice::where('project_id', $project->id)
                        ->where('paid_status', 'PAID')
                        ->sum('total');

                    return [
                        'project_name' => $project->name,
                        'revenue' => (float) $revenue,
                    ];
                })
                ->toArray();

            $stats = [
                'projects_count' => $projectsCount,
                'active_projects' => $activeProjects,
                'revenue_per_project' => $revenuePerProject,
            ];

            Log::info('[McpDataProvider] Projects stats calculated', $stats);

            return $stats;

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get projects stats', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'projects_count' => 0,
                'active_projects' => 0,
                'revenue_per_project' => [],
            ];
        }
    }

    /**
     * Get credit notes statistics
     *
     * @return array{credit_notes_count: int, total_credit_value: float}
     */
    public function getCreditNotesStats(Company $company): array
    {
        try {
            Log::info('[McpDataProvider] Fetching credit notes stats', [
                'company_id' => $company->id,
            ]);

            $creditNotesCount = CreditNote::where('company_id', $company->id)->count();
            $totalCreditValue = (float) CreditNote::where('company_id', $company->id)
                ->sum('total');

            $stats = [
                'credit_notes_count' => $creditNotesCount,
                'total_credit_value' => $totalCreditValue,
            ];

            Log::info('[McpDataProvider] Credit notes stats calculated', $stats);

            return $stats;

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get credit notes stats', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'credit_notes_count' => 0,
                'total_credit_value' => 0.0,
            ];
        }
    }

    /**
     * Get e-invoices statistics
     *
     * @return array{einvoices_sent: int, einvoices_pending: int, einvoices_failed: int}
     */
    public function getEInvoiceStats(Company $company): array
    {
        try {
            Log::info('[McpDataProvider] Fetching e-invoices stats', [
                'company_id' => $company->id,
            ]);

            $einvoicesSent = EInvoice::where('company_id', $company->id)
                ->whereIn('status', [EInvoice::STATUS_SUBMITTED, EInvoice::STATUS_ACCEPTED])
                ->count();
            $einvoicesPending = EInvoice::where('company_id', $company->id)
                ->whereIn('status', [EInvoice::STATUS_DRAFT, EInvoice::STATUS_SIGNED])
                ->count();
            $einvoicesFailed = EInvoice::where('company_id', $company->id)
                ->whereIn('status', [EInvoice::STATUS_FAILED, EInvoice::STATUS_REJECTED])
                ->count();

            $stats = [
                'einvoices_sent' => $einvoicesSent,
                'einvoices_pending' => $einvoicesPending,
                'einvoices_failed' => $einvoicesFailed,
            ];

            Log::info('[McpDataProvider] E-invoices stats calculated', $stats);

            return $stats;

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get e-invoices stats', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'einvoices_sent' => 0,
                'einvoices_pending' => 0,
                'einvoices_failed' => 0,
            ];
        }
    }

    /**
     * Get detailed item sales analysis for profit optimization
     *
     * Returns per-item revenue, quantities, and contribution analysis
     * Essential for questions like "which items to increase to reach X profit"
     *
     * @param int $months Number of months to analyze (default: 3)
     * @return array{items: array, totals: array, analysis: array}
     */
    public function getItemSalesAnalysis(Company $company, int $months = 3): array
    {
        try {
            Log::info('[McpDataProvider] Fetching item sales analysis', [
                'company_id' => $company->id,
                'months' => $months,
            ]);

            $startDate = now()->subMonths($months)->startOfMonth();

            // Get item-level sales from paid invoices
            $itemSales = \App\Models\InvoiceItem::whereHas('invoice', function ($query) use ($company, $startDate) {
                $query->where('company_id', $company->id)
                    ->where('paid_status', 'PAID')
                    ->where('invoice_date', '>=', $startDate);
            })
                ->select(
                    'item_id',
                    'name',
                    \DB::raw('SUM(quantity) as total_quantity'),
                    \DB::raw('SUM(total) as total_revenue'),
                    \DB::raw('AVG(price) as avg_price'),
                    \DB::raw('COUNT(DISTINCT invoice_id) as invoice_count')
                )
                ->groupBy('item_id', 'name')
                ->orderByDesc('total_revenue')
                ->limit(50)
                ->get();

            // Calculate totals
            $totalRevenue = $itemSales->sum('total_revenue') / 100; // Convert from cents
            $totalQuantity = $itemSales->sum('total_quantity');

            // Get expenses for profit calculation
            $totalExpenses = (float) \App\Models\Expense::where('company_id', $company->id)
                ->where('expense_date', '>=', $startDate)
                ->sum('amount');

            $currentProfit = $totalRevenue - $totalExpenses;
            $profitMargin = $totalRevenue > 0 ? ($currentProfit / $totalRevenue) * 100 : 0;

            // Build item analysis with contribution percentages
            $items = $itemSales->map(function ($item) use ($totalRevenue) {
                $itemRevenue = $item->total_revenue / 100; // Convert from cents
                $avgPrice = $item->avg_price / 100; // Convert from cents
                $contribution = $totalRevenue > 0 ? ($itemRevenue / $totalRevenue) * 100 : 0;

                return [
                    'item_id' => $item->item_id,
                    'name' => $item->name,
                    'total_quantity' => round($item->total_quantity, 2),
                    'total_revenue' => round($itemRevenue, 2),
                    'avg_price' => round($avgPrice, 2),
                    'invoice_count' => $item->invoice_count,
                    'revenue_contribution_percent' => round($contribution, 2),
                ];
            })->toArray();

            // Get monthly revenue trend
            $monthlyRevenue = Invoice::where('company_id', $company->id)
                ->where('paid_status', 'PAID')
                ->where('invoice_date', '>=', $startDate)
                ->selectRaw('DATE_FORMAT(invoice_date, "%Y-%m") as month')
                ->selectRaw('SUM(total) as revenue')
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->pluck('revenue', 'month')
                ->map(fn($v) => round($v / 100, 2))
                ->toArray();

            $avgMonthlyRevenue = count($monthlyRevenue) > 0 ? array_sum($monthlyRevenue) / count($monthlyRevenue) : 0;
            $avgMonthlyExpenses = $totalExpenses / max($months, 1);
            $avgMonthlyProfit = $avgMonthlyRevenue - $avgMonthlyExpenses;

            $analysis = [
                'period_months' => $months,
                'total_revenue' => round($totalRevenue, 2),
                'total_expenses' => round($totalExpenses, 2),
                'current_profit' => round($currentProfit, 2),
                'profit_margin_percent' => round($profitMargin, 2),
                'avg_monthly_revenue' => round($avgMonthlyRevenue, 2),
                'avg_monthly_expenses' => round($avgMonthlyExpenses, 2),
                'avg_monthly_profit' => round($avgMonthlyProfit, 2),
                'monthly_revenue_trend' => $monthlyRevenue,
                'top_items_count' => count($items),
                'items_contributing_50_percent' => $this->countItemsForRevenueThreshold($items, 50),
                'items_contributing_80_percent' => $this->countItemsForRevenueThreshold($items, 80),
            ];

            Log::info('[McpDataProvider] Item sales analysis calculated', [
                'items_count' => count($items),
                'total_revenue' => $totalRevenue,
            ]);

            return [
                'items' => $items,
                'totals' => [
                    'revenue' => round($totalRevenue, 2),
                    'quantity' => round($totalQuantity, 2),
                    'expenses' => round($totalExpenses, 2),
                    'profit' => round($currentProfit, 2),
                ],
                'analysis' => $analysis,
            ];

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get item sales analysis', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'items' => [],
                'totals' => ['revenue' => 0, 'quantity' => 0, 'expenses' => 0, 'profit' => 0],
                'analysis' => [],
            ];
        }
    }

    /**
     * Helper: Count items needed to reach revenue threshold
     */
    private function countItemsForRevenueThreshold(array $items, float $thresholdPercent): int
    {
        $cumulative = 0;
        $count = 0;
        foreach ($items as $item) {
            $cumulative += $item['revenue_contribution_percent'];
            $count++;
            if ($cumulative >= $thresholdPercent) {
                break;
            }
        }
        return $count;
    }

    /**
     * Get comprehensive statistics across all financial modules
     *
     * @return array
     */
    public function getComprehensiveStats(Company $company): array
    {
        try {
            Log::info('[McpDataProvider] Fetching comprehensive stats', [
                'company_id' => $company->id,
            ]);

            $stats = [
                'company' => $this->getCompanyStats($company),
                'proforma' => $this->getProformaStats($company),
                'bills' => $this->getBillsStats($company),
                'suppliers' => $this->getSuppliersStats($company),
                'estimates' => $this->getEstimatesStats($company),
                'recurring_invoices' => $this->getRecurringInvoicesStats($company),
                'inventory' => $this->getInventoryStats($company),
                'warehouses' => $this->getWarehouseStats($company),
                'projects' => $this->getProjectsStats($company),
                'credit_notes' => $this->getCreditNotesStats($company),
                'einvoices' => $this->getEInvoiceStats($company),
                'trial_balance' => $this->getTrialBalance($company),
                'monthly_trends' => $this->getMonthlyTrends($company, 6),
                'customer_growth' => $this->getCustomerGrowth($company, 6),
                'payment_timing' => $this->getPaymentTimingAnalysis($company),
                'top_customers' => $this->getTopCustomers($company, 5),
                'item_sales' => $this->getItemSalesAnalysis($company, 3),
            ];

            Log::info('[McpDataProvider] Comprehensive stats calculated');

            return $stats;

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get comprehensive stats', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'error' => 'Failed to retrieve comprehensive stats',
                'message' => $e->getMessage(),
            ];
        }
    }
}

// CLAUDE-CHECKPOINT
