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
     * Get customer dependency analysis for risk assessment
     *
     * Analyzes revenue concentration across top customers to identify business risks
     * from customer dependency. Includes concentration metrics, risk flags,
     * customer profiles, and what-if scenarios.
     *
     * @return array{concentration: array, risk_flags: array, top_customers: array, scenarios: array, risk_score: int}
     */
    public function getCustomerDependencyAnalysis(Company $company): array
    {
        try {
            Log::info('[McpDataProvider] Fetching customer dependency analysis', [
                'company_id' => $company->id,
            ]);

            // Get total revenue from paid invoices
            $totalRevenue = (float) Invoice::where('company_id', $company->id)
                ->where('paid_status', 'PAID')
                ->sum('total');

            // Convert from cents to standard currency
            $totalRevenue = $totalRevenue / 100;

            // Get expenses for profit calculations
            $totalExpenses = (float) \App\Models\Expense::where('company_id', $company->id)
                ->sum('amount');

            // Get top customers by revenue with detailed metrics
            $topCustomersData = Invoice::where('company_id', $company->id)
                ->where('paid_status', 'PAID')
                ->select('customer_id')
                ->selectRaw('SUM(total) as total_revenue')
                ->selectRaw('COUNT(*) as invoice_count')
                ->selectRaw('AVG(total) as avg_invoice_value')
                ->selectRaw('MAX(invoice_date) as last_invoice_date')
                ->groupBy('customer_id')
                ->orderByDesc('total_revenue')
                ->limit(10)
                ->get();

            // Calculate payment reliability for each customer
            $topCustomers = $topCustomersData->map(function ($item) use ($totalRevenue) {
                $customer = Customer::find($item->customer_id);
                $customerRevenue = $item->total_revenue / 100; // Convert from cents

                // Calculate payment reliability (% paid on time)
                $totalInvoices = Invoice::where('customer_id', $item->customer_id)
                    ->whereIn('status', ['SENT', 'COMPLETED'])
                    ->where('paid_status', 'PAID')
                    ->count();

                $onTimePayments = Invoice::where('customer_id', $item->customer_id)
                    ->where('paid_status', 'PAID')
                    ->whereHas('payments', function ($query) use ($item) {
                        $query->whereRaw('payment_date <= (SELECT due_date FROM invoices WHERE id = invoice_id)');
                    })
                    ->count();

                $paymentReliability = $totalInvoices > 0 ? ($onTimePayments / $totalInvoices) * 100 : 0;

                return [
                    'customer_id' => $item->customer_id,
                    'customer_name' => $customer ? $customer->name : 'Unknown Customer',
                    'total_revenue' => round($customerRevenue, 2),
                    'revenue_share_percent' => $totalRevenue > 0 ? round(($customerRevenue / $totalRevenue) * 100, 2) : 0,
                    'invoice_count' => (int) $item->invoice_count,
                    'avg_invoice_value' => round($item->avg_invoice_value / 100, 2),
                    'last_invoice_date' => $item->last_invoice_date,
                    'payment_reliability' => round($paymentReliability, 1),
                ];
            })->toArray();

            // Calculate concentration metrics
            $concentration = [
                'top1_percent' => isset($topCustomers[0]) ? $topCustomers[0]['revenue_share_percent'] : 0,
                'top3_percent' => array_sum(array_slice(array_column($topCustomers, 'revenue_share_percent'), 0, 3)),
                'top5_percent' => array_sum(array_slice(array_column($topCustomers, 'revenue_share_percent'), 0, 5)),
                'top10_percent' => array_sum(array_slice(array_column($topCustomers, 'revenue_share_percent'), 0, 10)),
            ];

            // Round concentration percentages
            $concentration = array_map(fn($v) => round($v, 2), $concentration);

            // Identify risk flags
            $riskFlags = [
                'single_customer_dominance' => $concentration['top1_percent'] > 20,
                'top3_concentration' => $concentration['top3_percent'] > 50,
            ];

            // Calculate profit margin
            $currentProfit = $totalRevenue - $totalExpenses;
            $profitMargin = $totalRevenue > 0 ? $currentProfit / $totalRevenue : 0;

            // Build what-if scenarios
            $scenarios = [];

            // Scenario 1: Lose top customer
            if (isset($topCustomers[0])) {
                $top1Revenue = $topCustomers[0]['total_revenue'];
                $scenarios['if_lose_top1'] = [
                    'revenue_loss' => round($top1Revenue, 2),
                    'profit_impact' => round($top1Revenue * $profitMargin, 2),
                ];
            }

            // Scenario 2: Lose top 3 customers
            $top3Revenue = array_sum(array_slice(array_column($topCustomers, 'total_revenue'), 0, 3));
            $scenarios['if_lose_top3'] = [
                'revenue_loss' => round($top3Revenue, 2),
                'profit_impact' => round($top3Revenue * $profitMargin, 2),
            ];

            // Scenario 3: Lose top 5 customers
            $top5Revenue = array_sum(array_slice(array_column($topCustomers, 'total_revenue'), 0, 5));
            $scenarios['if_lose_top5'] = [
                'revenue_loss' => round($top5Revenue, 2),
                'profit_impact' => round($top5Revenue * $profitMargin, 2),
            ];

            // Calculate risk score (0-100)
            $riskScore = $this->calculateCustomerDependencyRiskScore(
                $concentration,
                $riskFlags,
                $topCustomers
            );

            $analysis = [
                'concentration' => $concentration,
                'risk_flags' => $riskFlags,
                'top_customers' => $topCustomers,
                'scenarios' => $scenarios,
                'risk_score' => $riskScore,
            ];

            Log::info('[McpDataProvider] Customer dependency analysis calculated', [
                'risk_score' => $riskScore,
                'top1_percent' => $concentration['top1_percent'],
            ]);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to get customer dependency analysis', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'concentration' => [
                    'top1_percent' => 0,
                    'top3_percent' => 0,
                    'top5_percent' => 0,
                    'top10_percent' => 0,
                ],
                'risk_flags' => [
                    'single_customer_dominance' => false,
                    'top3_concentration' => false,
                ],
                'top_customers' => [],
                'scenarios' => [],
                'risk_score' => 0,
            ];
        }
    }

    /**
     * Calculate customer dependency risk score (0-100)
     *
     * Higher score = higher risk
     *
     * @param array $concentration Concentration percentages
     * @param array $riskFlags Risk flags
     * @param array $topCustomers Top customers data
     * @return int Risk score from 0 to 100
     */
    private function calculateCustomerDependencyRiskScore(array $concentration, array $riskFlags, array $topCustomers): int
    {
        $score = 0;

        // Factor 1: Single customer dominance (0-40 points)
        // >50% = 40, >30% = 30, >20% = 20, >10% = 10
        $top1Percent = $concentration['top1_percent'];
        if ($top1Percent > 50) {
            $score += 40;
        } elseif ($top1Percent > 30) {
            $score += 30;
        } elseif ($top1Percent > 20) {
            $score += 20;
        } elseif ($top1Percent > 10) {
            $score += 10;
        }

        // Factor 2: Top 3 concentration (0-30 points)
        // >80% = 30, >60% = 20, >50% = 15, >40% = 10
        $top3Percent = $concentration['top3_percent'];
        if ($top3Percent > 80) {
            $score += 30;
        } elseif ($top3Percent > 60) {
            $score += 20;
        } elseif ($top3Percent > 50) {
            $score += 15;
        } elseif ($top3Percent > 40) {
            $score += 10;
        }

        // Factor 3: Recent activity check (0-20 points)
        // Inactive top customers reduce real risk
        $inactiveTopCustomers = 0;
        $threeMonthsAgo = now()->subMonths(3);

        foreach (array_slice($topCustomers, 0, 3) as $customer) {
            if ($customer['last_invoice_date'] && \Carbon\Carbon::parse($customer['last_invoice_date'])->lt($threeMonthsAgo)) {
                $inactiveTopCustomers++;
            }
        }

        // Reduce score if top customers are inactive (they're already lost)
        if ($inactiveTopCustomers > 0) {
            $score -= (10 * $inactiveTopCustomers);
        } else {
            // Add risk if all top customers are active (high dependency)
            $score += 20;
        }

        // Factor 4: Payment reliability (0-10 points)
        // Poor payment reliability = higher risk
        $avgReliability = 0;
        $topCustomersCount = min(3, count($topCustomers));

        if ($topCustomersCount > 0) {
            $totalReliability = array_sum(array_slice(array_column($topCustomers, 'payment_reliability'), 0, 3));
            $avgReliability = $totalReliability / $topCustomersCount;
        }

        if ($avgReliability < 50) {
            $score += 10;
        } elseif ($avgReliability < 70) {
            $score += 5;
        }

        // Ensure score is between 0 and 100
        $score = max(0, min(100, $score));

        return $score;
    }

    /**
     * Get financial projections and forecasting
     *
     * Analyzes historical data to project future financial performance
     *
     * @param int $forecastMonths Number of months to forecast (default: 6)
     * @return array{growth_rates: array, projections: array, targets: array, break_even: array, confidence_level: string, data_months_used: int}
     */
    public function getFinancialProjections(Company $company, int $forecastMonths = 6): array
    {
        try {
            Log::info('[McpDataProvider] Generating financial projections', [
                'company_id' => $company->id,
                'forecast_months' => $forecastMonths,
            ]);

            // Step 1: Get historical data (last 12 months for better analysis)
            $historicalMonths = 12;
            $trends = $this->getMonthlyTrends($company, $historicalMonths);

            // Filter out months with no data
            $trendsWithData = array_filter($trends, function($month) {
                return $month['revenue'] > 0 || $month['expenses'] > 0;
            });

            $dataMonthsUsed = count($trendsWithData);

            // If insufficient data, return empty projections
            if ($dataMonthsUsed < 3) {
                return [
                    'growth_rates' => [
                        'revenue_monthly_percent' => 0,
                        'expense_monthly_percent' => 0,
                        'customer_growth_percent' => 0,
                    ],
                    'projections' => [],
                    'targets' => [
                        '1m_mkd' => ['achievable' => false, 'estimated_date' => 'insufficient_data'],
                        '5m_mkd' => ['achievable' => false, 'estimated_date' => 'insufficient_data'],
                        '10m_mkd' => ['achievable' => false, 'estimated_date' => 'insufficient_data'],
                    ],
                    'break_even' => [
                        'monthly_fixed_costs' => 0,
                        'profit_margin_percent' => 0,
                        'break_even_revenue' => 0,
                        'currently_above_break_even' => false,
                    ],
                    'seasonality' => [],
                    'confidence_level' => 'insufficient_data',
                    'data_months_used' => $dataMonthsUsed,
                ];
            }

            // Step 2: Calculate growth rates using linear regression
            $revenueGrowth = $this->calculateGrowthRate($trendsWithData, 'revenue');
            $expenseGrowth = $this->calculateGrowthRate($trendsWithData, 'expenses');

            // Customer growth
            $customerGrowth = $this->getCustomerGrowth($company, $historicalMonths);
            $customerGrowthRate = $this->calculateGrowthRate(
                array_filter($customerGrowth, fn($m) => $m['new_customers'] > 0),
                'new_customers'
            );

            // Average invoice value trend
            $avgInvoiceValues = array_map(function($month) {
                return $month['invoice_count'] > 0 ? $month['revenue'] / $month['invoice_count'] : 0;
            }, $trendsWithData);
            $avgInvoiceValue = array_sum($avgInvoiceValues) / count($avgInvoiceValues);

            // Step 3: Generate projections
            $projections = [];
            $lastMonth = end($trendsWithData);
            $lastRevenue = $lastMonth['revenue'];
            $lastExpenses = $lastMonth['expenses'];

            for ($i = 1; $i <= $forecastMonths; $i++) {
                $projectedRevenue = $lastRevenue * pow(1 + ($revenueGrowth / 100), $i);
                $projectedExpenses = $lastExpenses * pow(1 + ($expenseGrowth / 100), $i);

                $forecastMonth = now()->addMonths($i)->format('Y-m');

                $projections[] = [
                    'month' => $forecastMonth,
                    'revenue' => round($projectedRevenue, 2),
                    'expenses' => round($projectedExpenses, 2),
                    'profit' => round($projectedRevenue - $projectedExpenses, 2),
                ];
            }

            // Step 4: Target achievement projection
            $targets = [
                '1m_mkd' => $this->estimateTargetDate($projections, $lastRevenue, $revenueGrowth, 1000000),
                '5m_mkd' => $this->estimateTargetDate($projections, $lastRevenue, $revenueGrowth, 5000000),
                '10m_mkd' => $this->estimateTargetDate($projections, $lastRevenue, $revenueGrowth, 10000000),
            ];

            // Step 5: Break-even analysis
            $avgExpenses = array_sum(array_column($trendsWithData, 'expenses')) / $dataMonthsUsed;
            $avgRevenue = array_sum(array_column($trendsWithData, 'revenue')) / $dataMonthsUsed;
            $avgProfit = $avgRevenue - $avgExpenses;
            $profitMargin = $avgRevenue > 0 ? ($avgProfit / $avgRevenue) * 100 : 0;

            // Estimate fixed costs (average of lowest 3 months expenses)
            $expenseValues = array_column($trendsWithData, 'expenses');
            sort($expenseValues);
            $fixedCosts = array_sum(array_slice($expenseValues, 0, min(3, count($expenseValues)))) / min(3, count($expenseValues));

            $breakEvenRevenue = $profitMargin > 0 ? $fixedCosts / ($profitMargin / 100) : 0;

            $breakEven = [
                'monthly_fixed_costs' => round($fixedCosts, 2),
                'profit_margin_percent' => round($profitMargin, 2),
                'break_even_revenue' => round($breakEvenRevenue, 2),
                'currently_above_break_even' => $avgRevenue >= $breakEvenRevenue,
            ];

            // Step 6: Seasonality detection
            $seasonality = $this->detectSeasonality($trendsWithData);

            // Step 7: Confidence level
            $confidenceLevel = $this->calculateConfidenceLevel($dataMonthsUsed, $trendsWithData);

            $result = [
                'growth_rates' => [
                    'revenue_monthly_percent' => round($revenueGrowth, 2),
                    'expense_monthly_percent' => round($expenseGrowth, 2),
                    'customer_growth_percent' => round($customerGrowthRate, 2),
                    'avg_invoice_value' => round($avgInvoiceValue, 2),
                ],
                'projections' => $projections,
                'targets' => $targets,
                'break_even' => $breakEven,
                'seasonality' => $seasonality,
                'confidence_level' => $confidenceLevel,
                'data_months_used' => $dataMonthsUsed,
            ];

            Log::info('[McpDataProvider] Financial projections calculated', [
                'confidence' => $confidenceLevel,
                'data_months' => $dataMonthsUsed,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to generate financial projections', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'growth_rates' => [
                    'revenue_monthly_percent' => 0,
                    'expense_monthly_percent' => 0,
                    'customer_growth_percent' => 0,
                ],
                'projections' => [],
                'targets' => [],
                'break_even' => [],
                'seasonality' => [],
                'confidence_level' => 'error',
                'data_months_used' => 0,
            ];
        }
    }

    /**
     * Calculate growth rate using simple linear regression
     *
     * @param array $data Historical data
     * @param string $field Field to analyze
     * @return float Monthly growth rate as percentage
     */
    private function calculateGrowthRate(array $data, string $field): float
    {
        if (count($data) < 2) {
            return 0;
        }

        $values = array_values(array_column($data, $field));
        $n = count($values);

        // Simple month-over-month average growth
        $growthRates = [];
        for ($i = 1; $i < $n; $i++) {
            if ($values[$i - 1] > 0) {
                $growthRates[] = (($values[$i] - $values[$i - 1]) / $values[$i - 1]) * 100;
            }
        }

        return count($growthRates) > 0 ? array_sum($growthRates) / count($growthRates) : 0;
    }

    /**
     * Estimate when a revenue target will be reached
     *
     * @param array $projections Projected months
     * @param float $currentRevenue Current monthly revenue
     * @param float $growthRate Monthly growth rate (%)
     * @param float $target Target revenue
     * @return array
     */
    private function estimateTargetDate(array $projections, float $currentRevenue, float $growthRate, float $target): array
    {
        // Check if target is already achieved
        if ($currentRevenue >= $target) {
            return [
                'achievable' => true,
                'estimated_date' => 'already_achieved',
            ];
        }

        // Check if we're growing (or shrinking)
        if ($growthRate <= 0) {
            return [
                'achievable' => false,
                'estimated_date' => 'negative_growth',
            ];
        }

        // Check in projections first
        foreach ($projections as $projection) {
            if ($projection['revenue'] >= $target) {
                return [
                    'achievable' => true,
                    'estimated_date' => $projection['month'],
                ];
            }
        }

        // Calculate months needed beyond projections
        // Formula: target = current * (1 + growth)^months
        // months = log(target/current) / log(1 + growth)
        $monthsNeeded = log($target / $currentRevenue) / log(1 + ($growthRate / 100));

        if ($monthsNeeded > 24) {
            return [
                'achievable' => true,
                'estimated_date' => 'more_than_2_years',
            ];
        }

        $estimatedDate = now()->addMonths(ceil($monthsNeeded))->format('Y-m');

        return [
            'achievable' => true,
            'estimated_date' => $estimatedDate,
        ];
    }

    /**
     * Detect seasonality patterns in revenue
     *
     * @param array $data Historical monthly data
     * @return array Seasonality factors by month (1-12)
     */
    private function detectSeasonality(array $data): array
    {
        if (count($data) < 12) {
            return [];
        }

        // Group by month (1-12)
        $monthlyData = array_fill(1, 12, []);
        foreach ($data as $entry) {
            $monthNum = (int) date('n', strtotime($entry['month'] . '-01'));
            $monthlyData[$monthNum][] = $entry['revenue'];
        }

        // Calculate average for each month
        $overallAvg = array_sum(array_column($data, 'revenue')) / count($data);
        $seasonalityFactors = [];

        foreach ($monthlyData as $month => $revenues) {
            if (count($revenues) > 0) {
                $monthAvg = array_sum($revenues) / count($revenues);
                $factor = $overallAvg > 0 ? ($monthAvg / $overallAvg) : 1;

                // Only include if significantly different (>10% variance)
                if (abs($factor - 1) > 0.1) {
                    $seasonalityFactors[$month] = round($factor, 2);
                }
            }
        }

        return $seasonalityFactors;
    }

    /**
     * Calculate confidence level based on data quality
     *
     * @param int $monthsUsed Number of months with data
     * @param array $data Historical data
     * @return string 'low', 'medium', or 'high'
     */
    private function calculateConfidenceLevel(int $monthsUsed, array $data): string
    {
        // Calculate variance in revenue
        $revenues = array_column($data, 'revenue');
        $avgRevenue = array_sum($revenues) / count($revenues);

        $variance = 0;
        foreach ($revenues as $revenue) {
            $variance += pow($revenue - $avgRevenue, 2);
        }
        $variance = $variance / count($revenues);
        $stdDev = sqrt($variance);

        $coefficientOfVariation = $avgRevenue > 0 ? ($stdDev / $avgRevenue) : 1;

        // Confidence based on data months and consistency
        if ($monthsUsed >= 10 && $coefficientOfVariation < 0.3) {
            return 'high';
        } elseif ($monthsUsed >= 6 && $coefficientOfVariation < 0.5) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get cash flow forecast based on receivables, payables, and historical patterns
     *
     * @param  Company  $company
     * @param  int  $forecastDays  Number of days to forecast
     * @return array
     */
    public function getCashFlowForecast(Company $company, int $forecastDays = 90): array
    {
        try {
            Log::info('[McpDataProvider] Generating cash flow forecast', [
                'company_id' => $company->id,
                'forecast_days' => $forecastDays,
            ]);

            $today = now()->startOfDay();
            $endDate = now()->addDays($forecastDays);

            // Get expected incoming (receivables)
            $receivables = Invoice::where('company_id', $company->id)
                ->whereIn('status', [
                    Invoice::STATUS_SENT,
                    Invoice::STATUS_VIEWED,
                    Invoice::STATUS_UNPAID,
                    Invoice::STATUS_PARTIALLY_PAID,
                ])
                ->where('due_date', '>=', $today)
                ->where('due_date', '<=', $endDate)
                ->select('due_date', 'total', 'due_amount', 'customer_id', 'status')
                ->with('customer:id,name')
                ->get()
                ->groupBy(function ($invoice) {
                    return \Carbon\Carbon::parse($invoice->due_date)->format('Y-W'); // Group by week
                });

            // Get expected outgoing (payables) from bills
            $payables = Bill::where('company_id', $company->id)
                ->whereIn('status', ['SENT', 'UNPAID', 'PARTIALLY_PAID'])
                ->where('due_date', '>=', $today)
                ->where('due_date', '<=', $endDate)
                ->select('due_date', 'total', 'due_amount', 'supplier_id', 'status')
                ->with('supplier:id,name')
                ->get()
                ->groupBy(function ($bill) {
                    return \Carbon\Carbon::parse($bill->due_date)->format('Y-W');
                });

            // Get recurring invoice patterns
            $recurringIncome = RecurringInvoice::where('company_id', $company->id)
                ->where('status', 'ACTIVE')
                ->where(function ($q) use ($endDate) {
                    $q->whereNull('ends_at')
                      ->orWhere('ends_at', '>', now());
                })
                ->select('total', 'frequency', 'next_invoice_at')
                ->get();

            // Build weekly forecast
            $weeklyForecast = [];
            $currentWeek = $today->copy();
            $runningBalance = 0;

            // Get current bank balance (estimated from paid invoices - paid bills)
            $paidInvoicesTotal = Invoice::where('company_id', $company->id)
                ->where('status', Invoice::STATUS_PAID)
                ->whereDate('invoice_date', '>=', now()->subMonths(12))
                ->sum('total');
            $paidBillsTotal = Bill::where('company_id', $company->id)
                ->where('status', 'PAID')
                ->whereDate('bill_date', '>=', now()->subMonths(12))
                ->sum('total');
            $estimatedStartingBalance = ($paidInvoicesTotal - $paidBillsTotal) / 100; // Convert from cents

            $runningBalance = $estimatedStartingBalance;

            while ($currentWeek <= $endDate) {
                $weekKey = $currentWeek->format('Y-W');
                $weekStart = $currentWeek->copy()->startOfWeek();
                $weekEnd = $currentWeek->copy()->endOfWeek();

                // Expected incoming this week
                $weeklyIncoming = 0;
                $incomingDetails = [];
                if (isset($receivables[$weekKey])) {
                    foreach ($receivables[$weekKey] as $invoice) {
                        $amount = ($invoice->due_amount ?? $invoice->total) / 100;
                        $weeklyIncoming += $amount;
                        $incomingDetails[] = [
                            'customer' => $invoice->customer->name ?? 'Unknown',
                            'amount' => $amount,
                            'status' => $invoice->status,
                        ];
                    }
                }

                // Expected outgoing this week
                $weeklyOutgoing = 0;
                $outgoingDetails = [];
                if (isset($payables[$weekKey])) {
                    foreach ($payables[$weekKey] as $bill) {
                        $amount = ($bill->due_amount ?? $bill->total) / 100;
                        $weeklyOutgoing += $amount;
                        $outgoingDetails[] = [
                            'supplier' => $bill->supplier->name ?? 'Unknown',
                            'amount' => $amount,
                        ];
                    }
                }

                // Add recurring income estimate
                foreach ($recurringIncome as $recurring) {
                    $nextDate = \Carbon\Carbon::parse($recurring->next_invoice_at);
                    if ($nextDate >= $weekStart && $nextDate <= $weekEnd) {
                        $weeklyIncoming += $recurring->total / 100;
                        $incomingDetails[] = [
                            'customer' => 'Recurring Invoice',
                            'amount' => $recurring->total / 100,
                            'status' => 'RECURRING',
                        ];
                    }
                }

                $netFlow = $weeklyIncoming - $weeklyOutgoing;
                $runningBalance += $netFlow;

                $weeklyForecast[] = [
                    'week' => $weekKey,
                    'week_start' => $weekStart->format('Y-m-d'),
                    'week_end' => $weekEnd->format('Y-m-d'),
                    'expected_incoming' => round($weeklyIncoming, 2),
                    'expected_outgoing' => round($weeklyOutgoing, 2),
                    'net_cash_flow' => round($netFlow, 2),
                    'projected_balance' => round($runningBalance, 2),
                    'incoming_details' => array_slice($incomingDetails, 0, 5),
                    'outgoing_details' => array_slice($outgoingDetails, 0, 5),
                ];

                $currentWeek->addWeek();
            }

            // Calculate summary metrics
            $totalIncoming = array_sum(array_column($weeklyForecast, 'expected_incoming'));
            $totalOutgoing = array_sum(array_column($weeklyForecast, 'expected_outgoing'));
            $lowestBalance = min(array_column($weeklyForecast, 'projected_balance'));
            $highestBalance = max(array_column($weeklyForecast, 'projected_balance'));

            // Identify potential cash crunch weeks
            $cashCrunchWeeks = array_filter($weeklyForecast, function ($week) {
                return $week['projected_balance'] < 0;
            });

            return [
                'forecast_period' => [
                    'start' => $today->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                    'days' => $forecastDays,
                ],
                'summary' => [
                    'starting_balance' => round($estimatedStartingBalance, 2),
                    'total_expected_incoming' => round($totalIncoming, 2),
                    'total_expected_outgoing' => round($totalOutgoing, 2),
                    'net_forecast' => round($totalIncoming - $totalOutgoing, 2),
                    'ending_balance' => round($runningBalance, 2),
                    'lowest_projected_balance' => round($lowestBalance, 2),
                    'highest_projected_balance' => round($highestBalance, 2),
                ],
                'alerts' => [
                    'cash_crunch_weeks' => count($cashCrunchWeeks),
                    'cash_crunch_risk' => $lowestBalance < 0 ? 'HIGH' : ($lowestBalance < $totalOutgoing * 0.1 ? 'MEDIUM' : 'LOW'),
                ],
                'weekly_forecast' => $weeklyForecast,
                'currency' => $company->currency ?? 'MKD',
            ];

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to generate cash flow forecast', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'error' => 'Failed to generate cash flow forecast',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get AR (Accounts Receivable) aging report
     *
     * @param  Company  $company
     * @return array
     */
    public function getARAgingReport(Company $company): array
    {
        try {
            Log::info('[McpDataProvider] Generating AR aging report', [
                'company_id' => $company->id,
            ]);

            $today = now()->startOfDay();

            // Get all unpaid/partially paid invoices
            $invoices = Invoice::where('company_id', $company->id)
                ->whereIn('status', [
                    Invoice::STATUS_SENT,
                    Invoice::STATUS_VIEWED,
                    Invoice::STATUS_UNPAID,
                    Invoice::STATUS_PARTIALLY_PAID,
                ])
                ->with('customer:id,name,email,phone')
                ->get();

            // Aging buckets
            $buckets = [
                'current' => ['min' => -999, 'max' => 0, 'invoices' => [], 'total' => 0, 'count' => 0],
                '1_30' => ['min' => 1, 'max' => 30, 'invoices' => [], 'total' => 0, 'count' => 0],
                '31_60' => ['min' => 31, 'max' => 60, 'invoices' => [], 'total' => 0, 'count' => 0],
                '61_90' => ['min' => 61, 'max' => 90, 'invoices' => [], 'total' => 0, 'count' => 0],
                'over_90' => ['min' => 91, 'max' => 9999, 'invoices' => [], 'total' => 0, 'count' => 0],
            ];

            $customerSummary = [];

            foreach ($invoices as $invoice) {
                $dueDate = \Carbon\Carbon::parse($invoice->due_date);
                $daysOverdue = $today->diffInDays($dueDate, false) * -1; // Negative if not yet due
                $dueAmount = ($invoice->due_amount ?? $invoice->total) / 100;

                // Determine bucket
                $bucketKey = 'current';
                if ($daysOverdue > 90) {
                    $bucketKey = 'over_90';
                } elseif ($daysOverdue > 60) {
                    $bucketKey = '61_90';
                } elseif ($daysOverdue > 30) {
                    $bucketKey = '31_60';
                } elseif ($daysOverdue > 0) {
                    $bucketKey = '1_30';
                }

                $invoiceData = [
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer->name ?? 'Unknown',
                    'customer_id' => $invoice->customer_id,
                    'invoice_date' => $invoice->invoice_date,
                    'due_date' => $invoice->due_date,
                    'days_overdue' => max(0, $daysOverdue),
                    'total' => $invoice->total / 100,
                    'due_amount' => $dueAmount,
                    'status' => $invoice->status,
                ];

                $buckets[$bucketKey]['invoices'][] = $invoiceData;
                $buckets[$bucketKey]['total'] += $dueAmount;
                $buckets[$bucketKey]['count']++;

                // Customer summary
                $customerId = $invoice->customer_id;
                if (!isset($customerSummary[$customerId])) {
                    $customerSummary[$customerId] = [
                        'customer_id' => $customerId,
                        'customer_name' => $invoice->customer->name ?? 'Unknown',
                        'customer_email' => $invoice->customer->email ?? null,
                        'customer_phone' => $invoice->customer->phone ?? null,
                        'total_outstanding' => 0,
                        'invoice_count' => 0,
                        'oldest_invoice_days' => 0,
                        'buckets' => [
                            'current' => 0,
                            '1_30' => 0,
                            '31_60' => 0,
                            '61_90' => 0,
                            'over_90' => 0,
                        ],
                    ];
                }

                $customerSummary[$customerId]['total_outstanding'] += $dueAmount;
                $customerSummary[$customerId]['invoice_count']++;
                $customerSummary[$customerId]['oldest_invoice_days'] = max(
                    $customerSummary[$customerId]['oldest_invoice_days'],
                    max(0, $daysOverdue)
                );
                $customerSummary[$customerId]['buckets'][$bucketKey] += $dueAmount;
            }

            // Sort customers by total outstanding (descending)
            usort($customerSummary, function ($a, $b) {
                return $b['total_outstanding'] <=> $a['total_outstanding'];
            });

            // Round totals and limit invoice lists
            foreach ($buckets as $key => &$bucket) {
                $bucket['total'] = round($bucket['total'], 2);
                // Keep only top 10 invoices per bucket for readability
                $bucket['invoices'] = array_slice($bucket['invoices'], 0, 10);
            }

            // Calculate totals
            $totalOutstanding = array_sum(array_column($buckets, 'total'));
            $totalCount = array_sum(array_column($buckets, 'count'));

            // Calculate weighted average days outstanding
            $weightedDays = 0;
            foreach ($buckets as $key => $bucket) {
                $avgDays = match($key) {
                    'current' => 0,
                    '1_30' => 15,
                    '31_60' => 45,
                    '61_90' => 75,
                    'over_90' => 120,
                };
                $weightedDays += $bucket['total'] * $avgDays;
            }
            $avgDaysOutstanding = $totalOutstanding > 0 ? round($weightedDays / $totalOutstanding, 1) : 0;

            // Risk score based on aging distribution
            $riskScore = 0;
            if ($totalOutstanding > 0) {
                $riskScore += ($buckets['1_30']['total'] / $totalOutstanding) * 20;
                $riskScore += ($buckets['31_60']['total'] / $totalOutstanding) * 40;
                $riskScore += ($buckets['61_90']['total'] / $totalOutstanding) * 60;
                $riskScore += ($buckets['over_90']['total'] / $totalOutstanding) * 100;
            }

            return [
                'summary' => [
                    'total_outstanding' => round($totalOutstanding, 2),
                    'total_invoices' => $totalCount,
                    'total_customers' => count($customerSummary),
                    'average_days_outstanding' => $avgDaysOutstanding,
                    'collection_risk_score' => round($riskScore, 0),
                    'collection_risk_level' => match(true) {
                        $riskScore >= 50 => 'HIGH',
                        $riskScore >= 30 => 'MEDIUM',
                        default => 'LOW',
                    },
                ],
                'aging_buckets' => [
                    'current' => [
                        'label' => 'Current (not yet due)',
                        'total' => $buckets['current']['total'],
                        'count' => $buckets['current']['count'],
                        'percent' => $totalOutstanding > 0 ? round($buckets['current']['total'] / $totalOutstanding * 100, 1) : 0,
                    ],
                    '1_30' => [
                        'label' => '1-30 days overdue',
                        'total' => $buckets['1_30']['total'],
                        'count' => $buckets['1_30']['count'],
                        'percent' => $totalOutstanding > 0 ? round($buckets['1_30']['total'] / $totalOutstanding * 100, 1) : 0,
                    ],
                    '31_60' => [
                        'label' => '31-60 days overdue',
                        'total' => $buckets['31_60']['total'],
                        'count' => $buckets['31_60']['count'],
                        'percent' => $totalOutstanding > 0 ? round($buckets['31_60']['total'] / $totalOutstanding * 100, 1) : 0,
                    ],
                    '61_90' => [
                        'label' => '61-90 days overdue',
                        'total' => $buckets['61_90']['total'],
                        'count' => $buckets['61_90']['count'],
                        'percent' => $totalOutstanding > 0 ? round($buckets['61_90']['total'] / $totalOutstanding * 100, 1) : 0,
                    ],
                    'over_90' => [
                        'label' => 'Over 90 days overdue',
                        'total' => $buckets['over_90']['total'],
                        'count' => $buckets['over_90']['count'],
                        'percent' => $totalOutstanding > 0 ? round($buckets['over_90']['total'] / $totalOutstanding * 100, 1) : 0,
                    ],
                ],
                'top_debtors' => array_slice(array_map(function ($c) {
                    return [
                        'customer_name' => $c['customer_name'],
                        'total_outstanding' => round($c['total_outstanding'], 2),
                        'invoice_count' => $c['invoice_count'],
                        'oldest_days' => $c['oldest_invoice_days'],
                    ];
                }, $customerSummary), 0, 10),
                'currency' => $company->currency ?? 'MKD',
                'generated_at' => now()->toDateTimeString(),
            ];

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to generate AR aging report', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'error' => 'Failed to generate AR aging report',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get AP (Accounts Payable) aging report
     *
     * @param  Company  $company
     * @return array
     */
    public function getAPAgingReport(Company $company): array
    {
        try {
            Log::info('[McpDataProvider] Generating AP aging report', [
                'company_id' => $company->id,
            ]);

            $today = now()->startOfDay();

            // Get all unpaid/partially paid bills
            $bills = Bill::where('company_id', $company->id)
                ->whereIn('status', ['SENT', 'UNPAID', 'PARTIALLY_PAID'])
                ->with('supplier:id,name,email,phone')
                ->get();

            // Aging buckets
            $buckets = [
                'current' => ['total' => 0, 'count' => 0],
                '1_30' => ['total' => 0, 'count' => 0],
                '31_60' => ['total' => 0, 'count' => 0],
                '61_90' => ['total' => 0, 'count' => 0],
                'over_90' => ['total' => 0, 'count' => 0],
            ];

            $supplierSummary = [];

            foreach ($bills as $bill) {
                $dueDate = \Carbon\Carbon::parse($bill->due_date);
                $daysOverdue = $today->diffInDays($dueDate, false) * -1;
                $dueAmount = ($bill->due_amount ?? $bill->total) / 100;

                // Determine bucket
                $bucketKey = 'current';
                if ($daysOverdue > 90) {
                    $bucketKey = 'over_90';
                } elseif ($daysOverdue > 60) {
                    $bucketKey = '61_90';
                } elseif ($daysOverdue > 30) {
                    $bucketKey = '31_60';
                } elseif ($daysOverdue > 0) {
                    $bucketKey = '1_30';
                }

                $buckets[$bucketKey]['total'] += $dueAmount;
                $buckets[$bucketKey]['count']++;

                // Supplier summary
                $supplierId = $bill->supplier_id;
                if (!isset($supplierSummary[$supplierId])) {
                    $supplierSummary[$supplierId] = [
                        'supplier_name' => $bill->supplier->name ?? 'Unknown',
                        'total_payable' => 0,
                        'bill_count' => 0,
                        'oldest_days' => 0,
                    ];
                }

                $supplierSummary[$supplierId]['total_payable'] += $dueAmount;
                $supplierSummary[$supplierId]['bill_count']++;
                $supplierSummary[$supplierId]['oldest_days'] = max(
                    $supplierSummary[$supplierId]['oldest_days'],
                    max(0, $daysOverdue)
                );
            }

            // Sort suppliers by total payable
            usort($supplierSummary, function ($a, $b) {
                return $b['total_payable'] <=> $a['total_payable'];
            });

            $totalPayable = array_sum(array_column($buckets, 'total'));
            $totalCount = array_sum(array_column($buckets, 'count'));

            return [
                'summary' => [
                    'total_payable' => round($totalPayable, 2),
                    'total_bills' => $totalCount,
                    'total_suppliers' => count($supplierSummary),
                ],
                'aging_buckets' => [
                    'current' => ['label' => 'Current', 'total' => round($buckets['current']['total'], 2), 'count' => $buckets['current']['count']],
                    '1_30' => ['label' => '1-30 days', 'total' => round($buckets['1_30']['total'], 2), 'count' => $buckets['1_30']['count']],
                    '31_60' => ['label' => '31-60 days', 'total' => round($buckets['31_60']['total'], 2), 'count' => $buckets['31_60']['count']],
                    '61_90' => ['label' => '61-90 days', 'total' => round($buckets['61_90']['total'], 2), 'count' => $buckets['61_90']['count']],
                    'over_90' => ['label' => 'Over 90 days', 'total' => round($buckets['over_90']['total'], 2), 'count' => $buckets['over_90']['count']],
                ],
                'top_creditors' => array_slice(array_map(function ($s) {
                    return [
                        'supplier_name' => $s['supplier_name'],
                        'total_payable' => round($s['total_payable'], 2),
                        'bill_count' => $s['bill_count'],
                        'oldest_days' => $s['oldest_days'],
                    ];
                }, $supplierSummary), 0, 10),
                'currency' => $company->currency ?? 'MKD',
            ];

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to generate AP aging report', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get working capital analysis
     *
     * @param  Company  $company
     * @return array
     */
    public function getWorkingCapitalAnalysis(Company $company): array
    {
        try {
            $arAging = $this->getARAgingReport($company);
            $apAging = $this->getAPAgingReport($company);

            $totalReceivables = $arAging['summary']['total_outstanding'] ?? 0;
            $totalPayables = $apAging['summary']['total_payable'] ?? 0;

            // Get inventory value
            $inventoryValue = Item::where('company_id', $company->id)
                ->sum(\DB::raw('COALESCE(cost_price, 0) * COALESCE(stock_quantity, 0)')) / 100;

            // Calculate working capital
            $currentAssets = $totalReceivables + $inventoryValue;
            $currentLiabilities = $totalPayables;
            $workingCapital = $currentAssets - $currentLiabilities;

            // Ratios
            $currentRatio = $currentLiabilities > 0 ? $currentAssets / $currentLiabilities : 0;
            $quickRatio = $currentLiabilities > 0 ? $totalReceivables / $currentLiabilities : 0;

            // Days metrics
            $avgDaysReceivable = $arAging['summary']['average_days_outstanding'] ?? 0;

            return [
                'working_capital' => round($workingCapital, 2),
                'current_assets' => round($currentAssets, 2),
                'current_liabilities' => round($currentLiabilities, 2),
                'components' => [
                    'accounts_receivable' => round($totalReceivables, 2),
                    'inventory' => round($inventoryValue, 2),
                    'accounts_payable' => round($totalPayables, 2),
                ],
                'ratios' => [
                    'current_ratio' => round($currentRatio, 2),
                    'quick_ratio' => round($quickRatio, 2),
                ],
                'health' => [
                    'status' => match(true) {
                        $currentRatio >= 2 => 'EXCELLENT',
                        $currentRatio >= 1.5 => 'GOOD',
                        $currentRatio >= 1 => 'ADEQUATE',
                        default => 'CONCERNING',
                    },
                    'days_receivable' => $avgDaysReceivable,
                ],
                'currency' => $company->currency ?? 'MKD',
            ];

        } catch (\Exception $e) {
            Log::error('[McpDataProvider] Failed to calculate working capital', [
                'error' => $e->getMessage(),
            ]);

            return ['error' => $e->getMessage()];
        }
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
                'customer_dependency' => $this->getCustomerDependencyAnalysis($company),
                'projections' => $this->getFinancialProjections($company, 6),
                // CFO-level analytics
                'cash_flow_forecast' => $this->getCashFlowForecast($company, 90),
                'ar_aging' => $this->getARAgingReport($company),
                'ap_aging' => $this->getAPAgingReport($company),
                'working_capital' => $this->getWorkingCapitalAnalysis($company),
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
