<?php

namespace App\Http\Controllers\V1\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollEmployee;
use App\Models\PayrollRun;
use App\Models\PayrollRunLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Mk\Payroll\Services\BankPaymentFileService;
use Modules\Mk\Payroll\Services\PayrollCalculationService;
use Modules\Mk\Payroll\Services\PayrollGLService;

class PayrollRunController extends Controller
{
    public function __construct(
        private PayrollCalculationService $calculationService,
        private PayrollGLService $glService,
        private BankPaymentFileService $bankFileService
    ) {
    }

    /**
     * Display a listing of payroll runs.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PayrollRun::class);

        $limit = $request->has('limit') ? $request->limit : 10;
        $companyId = $request->header('company');

        $query = PayrollRun::with(['creator', 'approver'])
            ->forCompany($companyId)
            ->withCount('lines');

        // Filter by status
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        // Filter by year
        if ($request->has('year')) {
            $query->where('period_year', $request->year);
        }

        // Filter by month
        if ($request->has('month')) {
            $query->where('period_month', $request->month);
        }

        $runs = $query
            ->orderBy($request->get('orderByField', 'period_start'), $request->get('orderBy', 'desc'))
            ->paginate($limit);

        return response()->json([
            'data' => $runs->items(),
            'meta' => [
                'current_page' => $runs->currentPage(),
                'from' => $runs->firstItem(),
                'last_page' => $runs->lastPage(),
                'path' => $runs->path(),
                'per_page' => $runs->perPage(),
                'to' => $runs->lastItem(),
                'total' => $runs->total(),
            ],
        ]);
    }

    /**
     * Store a newly created payroll run.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', PayrollRun::class);

        $validated = $request->validate([
            'period_year' => 'required|integer|min:2020|max:2100',
            'period_month' => 'required|integer|min:1|max:12',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
        ]);

        $companyId = $request->header('company');

        // Check for duplicate period
        $exists = PayrollRun::forCompany($companyId)
            ->forPeriod($validated['period_year'], $validated['period_month'])
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'duplicate_period',
                'message' => 'A payroll run already exists for this period.',
            ], 422);
        }

        $run = PayrollRun::create([
            'company_id' => $companyId,
            'period_year' => $validated['period_year'],
            'period_month' => $validated['period_month'],
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'status' => PayrollRun::STATUS_DRAFT,
            'creator_id' => $request->user()->id,
        ]);

        return response()->json([
            'data' => $run,
            'message' => 'Payroll run created successfully.',
        ], 201);
    }

    /**
     * Display the specified payroll run.
     */
    public function show(PayrollRun $payrollRun): JsonResponse
    {
        $this->authorize('view', $payrollRun);

        $payrollRun->load([
            'creator',
            'approver',
            'lines' => function ($query) {
                $query->with(['employee.currency']);
            },
        ]);

        return response()->json([
            'data' => $payrollRun,
        ]);
    }

    /**
     * Update the specified payroll run (only allowed in draft status).
     */
    public function update(Request $request, PayrollRun $payrollRun): JsonResponse
    {
        $this->authorize('update', $payrollRun);

        if (!$payrollRun->isEditable()) {
            return response()->json([
                'error' => 'not_editable',
                'message' => 'Only draft or calculated payroll runs can be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'period_start' => 'sometimes|required|date',
            'period_end' => 'sometimes|required|date|after:period_start',
        ]);

        $payrollRun->update($validated);

        return response()->json([
            'data' => $payrollRun,
            'message' => 'Payroll run updated successfully.',
        ]);
    }

    /**
     * Calculate payroll run (draft → calculated).
     */
    public function calculate(PayrollRun $payrollRun): JsonResponse
    {
        $this->authorize('update', $payrollRun);

        if (!$payrollRun->canCalculate()) {
            return response()->json([
                'error' => 'cannot_calculate',
                'message' => 'Only draft payroll runs can be calculated.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Get active employees
            $employees = PayrollEmployee::forCompany($payrollRun->company_id)
                ->active()
                ->with('currentSalaryStructure')
                ->get();

            if ($employees->isEmpty()) {
                return response()->json([
                    'error' => 'no_active_employees',
                    'message' => 'No active employees found for this payroll run.',
                ], 422);
            }

            // Delete existing lines (recalculation)
            $payrollRun->lines()->delete();

            $totalGross = 0;
            $totalNet = 0;
            $totalEmployerTax = 0;
            $totalEmployeeTax = 0;

            // Calculate for each employee
            foreach ($employees as $employee) {
                $salaryStructure = $employee->currentSalaryStructure;

                if (!$salaryStructure) {
                    continue; // Skip employees without salary structure
                }

                // Use the service to calculate
                $calculation = $this->calculationService->calculateEmployeePayroll(
                    $salaryStructure,
                    $payrollRun->period_start,
                    $payrollRun->period_end
                );

                // Create payroll line
                PayrollRunLine::create([
                    'payroll_run_id' => $payrollRun->id,
                    'employee_id' => $employee->id,
                    'working_days' => $calculation['working_days'],
                    'worked_days' => $calculation['worked_days'],
                    'gross_salary' => $calculation['gross_salary'],
                    'net_salary' => $calculation['net_salary'],
                    'income_tax_amount' => $calculation['income_tax'],
                    'pension_contribution_employee' => $calculation['pension_employee'],
                    'pension_contribution_employer' => $calculation['pension_employer'],
                    'health_contribution_employee' => $calculation['health_employee'],
                    'health_contribution_employer' => $calculation['health_employer'],
                    'unemployment_contribution' => $calculation['unemployment'],
                    'additional_contribution' => $calculation['additional_contribution'],
                    'transport_allowance' => $calculation['transport_allowance'],
                    'meal_allowance' => $calculation['meal_allowance'],
                    'other_additions' => $calculation['other_additions'] ?? [],
                    'deductions' => $calculation['deductions'] ?? [],
                    'status' => 'included',
                ]);

                $totalGross += $calculation['gross_salary'];
                $totalNet += $calculation['net_salary'];
                $totalEmployerTax += $calculation['pension_employer'] + $calculation['health_employer'];
                $totalEmployeeTax += $calculation['pension_employee'] + $calculation['health_employee']
                    + $calculation['unemployment'] + $calculation['additional_contribution']
                    + $calculation['income_tax'];
            }

            // Update totals and status
            $payrollRun->update([
                'total_gross' => $totalGross,
                'total_net' => $totalNet,
                'total_employer_tax' => $totalEmployerTax,
                'total_employee_tax' => $totalEmployeeTax,
                'status' => PayrollRun::STATUS_CALCULATED,
                'calculated_at' => now(),
            ]);

            DB::commit();

            $payrollRun->load(['lines.employee']);

            return response()->json([
                'data' => $payrollRun,
                'message' => 'Payroll run calculated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'calculation_failed',
                'message' => 'Failed to calculate payroll: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve payroll run (calculated → approved).
     */
    public function approve(PayrollRun $payrollRun): JsonResponse
    {
        $this->authorize('update', $payrollRun);

        if (!$payrollRun->canApprove()) {
            return response()->json([
                'error' => 'cannot_approve',
                'message' => 'Only calculated payroll runs can be approved.',
            ], 422);
        }

        $payrollRun->update([
            'status' => PayrollRun::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return response()->json([
            'data' => $payrollRun,
            'message' => 'Payroll run approved successfully.',
        ]);
    }

    /**
     * Post to IFRS GL (approved → posted).
     */
    public function post(PayrollRun $payrollRun): JsonResponse
    {
        $this->authorize('update', $payrollRun);

        if (!$payrollRun->canPost()) {
            return response()->json([
                'error' => 'cannot_post',
                'message' => 'Only approved payroll runs can be posted to GL.',
            ], 422);
        }

        try {
            // Post to IFRS
            $transactionId = $this->glService->postPayrollToGL($payrollRun);

            $payrollRun->update([
                'status' => PayrollRun::STATUS_POSTED,
                'posted_at' => now(),
                'ifrs_transaction_id' => $transactionId,
            ]);

            return response()->json([
                'data' => $payrollRun,
                'message' => 'Payroll run posted to general ledger successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'posting_failed',
                'message' => 'Failed to post to GL: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark as paid and generate bank file (posted → paid).
     */
    public function markPaid(PayrollRun $payrollRun): JsonResponse
    {
        $this->authorize('update', $payrollRun);

        if (!$payrollRun->canMarkPaid()) {
            return response()->json([
                'error' => 'cannot_mark_paid',
                'message' => 'Only posted payroll runs can be marked as paid.',
            ], 422);
        }

        try {
            // Generate bank payment file
            $filePath = $this->bankFileService->generatePaymentFile($payrollRun);

            $payrollRun->update([
                'status' => PayrollRun::STATUS_PAID,
                'paid_at' => now(),
                'bank_file_path' => $filePath,
                'bank_file_generated_at' => now(),
            ]);

            return response()->json([
                'data' => $payrollRun,
                'message' => 'Payroll run marked as paid. Bank file generated.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'bank_file_failed',
                'message' => 'Failed to generate bank file: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download bank payment file.
     */
    public function downloadBankFile(PayrollRun $payrollRun)
    {
        $this->authorize('view', $payrollRun);

        if (!$payrollRun->bank_file_path || !file_exists(storage_path($payrollRun->bank_file_path))) {
            return response()->json([
                'error' => 'file_not_found',
                'message' => 'Bank payment file not found.',
            ], 404);
        }

        return response()->download(
            storage_path($payrollRun->bank_file_path),
            'payroll_'.$payrollRun->period_name.'_bank_payment.xml'
        );
    }

    /**
     * Delete payroll run (only drafts).
     */
    public function destroy(PayrollRun $payrollRun): JsonResponse
    {
        $this->authorize('delete', $payrollRun);

        if ($payrollRun->status !== PayrollRun::STATUS_DRAFT) {
            return response()->json([
                'error' => 'cannot_delete',
                'message' => 'Only draft payroll runs can be deleted.',
            ], 422);
        }

        $payrollRun->lines()->delete();
        $payrollRun->delete();

        return response()->json([
            'message' => 'Payroll run deleted successfully.',
        ]);
    }
}

// LLM-CHECKPOINT
