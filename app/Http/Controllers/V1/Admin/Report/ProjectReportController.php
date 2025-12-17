<?php

namespace App\Http\Controllers\V1\Admin\Report;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Project Report Controller
 *
 * Generates financial reports for projects, showing income vs expenses.
 * Part of Phase 1.1 - Project Dimension reporting feature.
 */
class ProjectReportController extends Controller
{
    /**
     * Get list of all projects with financial summary.
     *
     * Returns all projects with aggregated totals:
     * - total_invoiced: Sum of invoice amounts
     * - total_paid: Sum of payment amounts
     * - total_expenses: Sum of expense amounts
     * - net_profit: total_invoiced - total_expenses
     *
     * Supports optional date range filtering via from_date and to_date params.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $companyId = $request->header('company');
        $company = Company::find($companyId);

        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $this->authorize('view report', $company);

        // Get query params for filtering
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        // Fetch all projects for the company with relationships
        $projectsQuery = Project::whereCompanyId($companyId)
            ->with(['customer:id,name,email', 'currency:id,name,code,symbol']);

        // Apply project-level filters if needed
        if ($request->has('customer_id')) {
            $projectsQuery->whereCustomer($request->input('customer_id'));
        }

        if ($request->has('status')) {
            $projectsQuery->whereStatus($request->input('status'));
        }

        $projects = $projectsQuery->get();

        // Calculate financial summary for each project
        $projectReports = $projects->map(function ($project) use ($fromDate, $toDate) {
            // Use the getSummary method from Project model if date range provided
            if ($fromDate && $toDate) {
                $summary = $project->getSummary($fromDate, $toDate);

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'code' => $project->code,
                    'customer' => $project->customer,
                    'currency' => $project->currency,
                    'status' => $project->status,
                    'total_invoiced' => $summary['total_invoiced'],
                    'total_paid' => $summary['total_payments'],
                    'total_expenses' => $summary['total_expenses'],
                    'total_bills' => $summary['total_bills'],
                    'net_profit' => $summary['net_result'],
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ];
            }

            // Without date filter, use model accessors
            $summary = $project->getSummary();

            return [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
                'customer' => $project->customer,
                'currency' => $project->currency,
                'status' => $project->status,
                'total_invoiced' => $project->totalInvoiced,
                'total_paid' => $project->totalPayments,
                'total_expenses' => $project->totalExpenses,
                'total_bills' => $summary['total_bills'],
                'net_profit' => $project->netResult,
                'from_date' => null,
                'to_date' => null,
            ];
        });

        // Calculate grand totals across all projects
        $grandTotal = [
            'total_invoiced' => $projectReports->sum('total_invoiced'),
            'total_paid' => $projectReports->sum('total_paid'),
            'total_expenses' => $projectReports->sum('total_expenses'),
            'total_bills' => $projectReports->sum('total_bills'),
            'net_profit' => $projectReports->sum('net_profit'),
        ];

        return response()->json([
            'projects' => $projectReports,
            'grand_total' => $grandTotal,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
    }

    /**
     * Get detailed report for a single project.
     *
     * Shows breakdown by month with:
     * - Monthly invoiced amounts
     * - Monthly expenses
     * - Monthly net result
     * - List of invoices, expenses, and payments
     *
     * @param  int  $id  Project ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $companyId = $request->header('company');
        $company = Company::find($companyId);

        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $this->authorize('view report', $company);

        // Fetch the project with all relationships
        $project = Project::whereCompanyId($companyId)
            ->with([
                'customer:id,name,email',
                'currency:id,name,code,symbol',
                'invoices' => function ($query) use ($request) {
                    if ($request->has('from_date') && $request->has('to_date')) {
                        $query->whereBetween('invoice_date', [
                            $request->input('from_date'),
                            $request->input('to_date'),
                        ]);
                    }
                    $query->orderBy('invoice_date', 'desc');
                },
                'expenses' => function ($query) use ($request) {
                    if ($request->has('from_date') && $request->has('to_date')) {
                        $query->whereBetween('expense_date', [
                            $request->input('from_date'),
                            $request->input('to_date'),
                        ]);
                    }
                    $query->orderBy('expense_date', 'desc');
                },
                'payments' => function ($query) use ($request) {
                    if ($request->has('from_date') && $request->has('to_date')) {
                        $query->whereBetween('payment_date', [
                            $request->input('from_date'),
                            $request->input('to_date'),
                        ]);
                    }
                    $query->orderBy('payment_date', 'desc');
                },
                'bills' => function ($query) use ($request) {
                    if ($request->has('from_date') && $request->has('to_date')) {
                        $query->whereBetween('bill_date', [
                            $request->input('from_date'),
                            $request->input('to_date'),
                        ]);
                    }
                    $query->orderBy('bill_date', 'desc');
                },
            ])
            ->findOrFail($id);

        // Get summary with date range if provided
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $summary = $fromDate && $toDate
            ? $project->getSummary($fromDate, $toDate)
            : $project->getSummary();

        // Group by month for breakdown
        $monthlyBreakdown = $this->getMonthlyBreakdown($project, $fromDate, $toDate);

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
                'description' => $project->description,
                'customer' => $project->customer,
                'currency' => $project->currency,
                'status' => $project->status,
                'budget_amount' => $project->budget_amount,
                'start_date' => $project->start_date?->format('Y-m-d'),
                'end_date' => $project->end_date?->format('Y-m-d'),
            ],
            'summary' => $summary,
            'monthly_breakdown' => $monthlyBreakdown,
            'invoices' => $project->invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date instanceof \Carbon\Carbon
                        ? $invoice->invoice_date->format('Y-m-d')
                        : $invoice->invoice_date,
                    'total' => $invoice->base_total,
                    'status' => $invoice->status,
                    'paid_status' => $invoice->paid_status,
                ];
            }),
            'expenses' => $project->expenses->map(function ($expense) {
                return [
                    'id' => $expense->id,
                    'expense_date' => $expense->expense_date instanceof \Carbon\Carbon
                        ? $expense->expense_date->format('Y-m-d')
                        : $expense->expense_date,
                    'amount' => $expense->base_amount ?? $expense->amount,
                    'category' => $expense->expense_category?->name ?? $expense->expenseCategory?->name,
                    'notes' => $expense->notes,
                ];
            }),
            'payments' => $project->payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'payment_date' => $payment->payment_date instanceof \Carbon\Carbon
                        ? $payment->payment_date->format('Y-m-d')
                        : $payment->payment_date,
                    'amount' => $payment->base_amount ?? $payment->amount,
                    'payment_method' => $payment->payment_method?->name ?? $payment->paymentMethod?->name,
                ];
            }),
            'bills' => $project->bills->map(function ($bill) {
                return [
                    'id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'bill_date' => $bill->bill_date instanceof \Carbon\Carbon
                        ? $bill->bill_date->format('Y-m-d')
                        : $bill->bill_date,
                    'total' => $bill->total * ($bill->exchange_rate ?? 1),
                    'supplier' => $bill->supplier?->name ?? $bill->vendor?->name,
                    'status' => $bill->status,
                ];
            }),
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
    }

    /**
     * Generate monthly breakdown for project.
     */
    private function getMonthlyBreakdown(Project $project, ?string $fromDate = null, ?string $toDate = null): array
    {
        // Determine date range for breakdown
        $startDate = $fromDate ? Carbon::parse($fromDate) : Carbon::now()->subYear();
        $endDate = $toDate ? Carbon::parse($toDate) : Carbon::now();

        $months = [];
        $current = $startDate->copy()->startOfMonth();

        // Generate month-by-month breakdown
        while ($current->lte($endDate)) {
            $monthStart = $current->format('Y-m-d');
            $monthEnd = $current->copy()->endOfMonth()->format('Y-m-d');

            // Calculate totals for this month using COALESCE to handle NULL base values
            $invoiced = $project->invoices()
                ->whereBetween('invoice_date', [$monthStart, $monthEnd])
                ->selectRaw('COALESCE(SUM(COALESCE(base_total, total * COALESCE(exchange_rate, 1))), 0) as total')
                ->value('total') ?? 0;

            $expenses = $project->expenses()
                ->whereBetween('expense_date', [$monthStart, $monthEnd])
                ->selectRaw('COALESCE(SUM(COALESCE(base_amount, amount * COALESCE(exchange_rate, 1))), 0) as total')
                ->value('total') ?? 0;

            $payments = $project->payments()
                ->whereBetween('payment_date', [$monthStart, $monthEnd])
                ->selectRaw('COALESCE(SUM(COALESCE(base_amount, amount * COALESCE(exchange_rate, 1))), 0) as total')
                ->value('total') ?? 0;

            $bills = $project->bills()
                ->whereBetween('bill_date', [$monthStart, $monthEnd])
                ->selectRaw('COALESCE(SUM(total * COALESCE(exchange_rate, 1)), 0) as total')
                ->value('total') ?? 0;

            $months[] = [
                'month' => $current->format('Y-m'),
                'month_name' => $current->format('F Y'),
                'total_invoiced' => $invoiced,
                'total_expenses' => $expenses,
                'total_bills' => $bills,
                'total_payments' => $payments,
                'net_result' => $invoiced - $expenses - $bills,
            ];

            $current->addMonth();
        }

        return $months;
    }
}

// CLAUDE-CHECKPOINT
