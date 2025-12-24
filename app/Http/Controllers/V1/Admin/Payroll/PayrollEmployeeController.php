<?php

namespace App\Http\Controllers\V1\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayrollEmployeeRequest;
use App\Models\Company;
use App\Models\PayrollEmployee;
use App\Services\UsageLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollEmployeeController extends Controller
{
    /**
     * @var UsageLimitService
     */
    protected $usageLimitService;

    public function __construct(UsageLimitService $usageLimitService)
    {
        $this->usageLimitService = $usageLimitService;
    }
    /**
     * Display a listing of payroll employees.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PayrollEmployee::class);

        // Check tier access
        $companyId = $request->header('company');
        $company = Company::find($companyId);

        if (!$this->usageLimitService->hasPayrollAccess($company)) {
            return response()->json([
                'error' => 'limit_exceeded',
                'feature' => 'payroll',
                'feature_name' => __('payroll.payroll_management'),
                'message' => config('subscriptions.upgrade_messages.payroll'),
                'required_tier' => 'business',
                'current_tier' => $this->usageLimitService->getCompanyTier($company),
            ], 403);
        }

        $limit = $request->has('limit') ? $request->limit : 10;

        $employees = PayrollEmployee::with(['currency', 'creator', 'currentSalaryStructure'])
            ->forCompany($companyId)
            ->when($request->has('status'), function ($query) use ($request) {
                if ($request->status === 'active') {
                    $query->active();
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when($request->has('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', '%'.$search.'%')
                        ->orWhere('last_name', 'LIKE', '%'.$search.'%')
                        ->orWhere('employee_number', 'LIKE', '%'.$search.'%')
                        ->orWhere('email', 'LIKE', '%'.$search.'%')
                        ->orWhere('embg', 'LIKE', '%'.$search.'%');
                });
            })
            ->when($request->has('employment_type'), function ($query) use ($request) {
                $query->where('employment_type', $request->employment_type);
            })
            ->when($request->has('department'), function ($query) use ($request) {
                $query->where('department', $request->department);
            })
            ->orderBy($request->get('orderBy', 'created_at'), $request->get('orderByType', 'desc'))
            ->paginate($limit);

        return response()->json([
            'data' => $employees->items(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'from' => $employees->firstItem(),
                'last_page' => $employees->lastPage(),
                'path' => $employees->path(),
                'per_page' => $employees->perPage(),
                'to' => $employees->lastItem(),
                'total' => $employees->total(),
                'employee_total_count' => PayrollEmployee::forCompany($companyId)->count(),
                'active_employees_count' => PayrollEmployee::forCompany($companyId)->active()->count(),
            ],
        ]);
    }

    /**
     * Store a newly created payroll employee.
     */
    public function store(PayrollEmployeeRequest $request): JsonResponse
    {
        $this->authorize('create', PayrollEmployee::class);

        // Check tier access
        $companyId = $request->header('company');
        $company = Company::find($companyId);

        if (!$this->usageLimitService->hasPayrollAccess($company)) {
            return response()->json([
                'error' => 'limit_exceeded',
                'feature' => 'payroll',
                'feature_name' => __('payroll.payroll_management'),
                'message' => config('subscriptions.upgrade_messages.payroll'),
                'required_tier' => 'business',
                'current_tier' => $this->usageLimitService->getCompanyTier($company),
            ], 403);
        }

        // Check employee limit
        if (!$this->usageLimitService->canUse($company, 'payroll_employees')) {
            return response()->json(
                $this->usageLimitService->buildLimitExceededResponse($company, 'payroll_employees'),
                403
            );
        }

        $employee = PayrollEmployee::create($request->getPayrollEmployeePayload());

        $employee->load(['currency', 'creator', 'currentSalaryStructure']);

        return response()->json([
            'data' => $employee,
            'message' => 'Employee created successfully.',
        ], 201);
    }

    /**
     * Display the specified payroll employee.
     */
    public function show(PayrollEmployee $payrollEmployee): JsonResponse
    {
        $this->authorize('view', $payrollEmployee);

        $payrollEmployee->load([
            'currency',
            'creator',
            'currentSalaryStructure',
            'salaryStructures' => function ($query) {
                $query->orderBy('effective_from', 'desc');
            },
            'payrollLines' => function ($query) {
                $query->with('payrollRun')
                    ->orderBy('created_at', 'desc')
                    ->limit(12); // Last 12 payroll periods
            },
        ]);

        return response()->json([
            'data' => $payrollEmployee,
        ]);
    }

    /**
     * Update the specified payroll employee.
     */
    public function update(PayrollEmployeeRequest $request, PayrollEmployee $payrollEmployee): JsonResponse
    {
        $this->authorize('update', $payrollEmployee);

        $payrollEmployee->update($request->getPayrollEmployeePayload());

        $payrollEmployee->load(['currency', 'creator', 'currentSalaryStructure']);

        return response()->json([
            'data' => $payrollEmployee,
            'message' => 'Employee updated successfully.',
        ]);
    }

    /**
     * Terminate an employee (soft delete with termination date).
     */
    public function terminate(Request $request, PayrollEmployee $payrollEmployee): JsonResponse
    {
        $this->authorize('delete', $payrollEmployee);

        $request->validate([
            'termination_date' => [
                'required',
                'date',
                'after_or_equal:' . $payrollEmployee->employment_date->format('Y-m-d'),
            ],
            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        $payrollEmployee->update([
            'termination_date' => $request->termination_date,
            'is_active' => false,
        ]);

        // Mark current salary structure as inactive
        if ($payrollEmployee->currentSalaryStructure) {
            $payrollEmployee->currentSalaryStructure->update([
                'is_current' => false,
                'effective_to' => $request->termination_date,
            ]);
        }

        return response()->json([
            'data' => $payrollEmployee,
            'message' => 'Employee terminated successfully.',
        ]);
    }

    /**
     * Remove the specified payroll employee (soft delete).
     */
    public function destroy(PayrollEmployee $payrollEmployee): JsonResponse
    {
        $this->authorize('delete', $payrollEmployee);

        // Check if employee has payroll run lines
        $hasPayrollLines = $payrollEmployee->payrollLines()->exists();

        if ($hasPayrollLines) {
            return response()->json([
                'error' => 'cannot_delete_employee_with_payroll',
                'message' => 'Cannot delete employee with existing payroll records. Use terminate instead.',
            ], 422);
        }

        $payrollEmployee->delete();

        return response()->json([
            'message' => 'Employee deleted successfully.',
        ]);
    }

    /**
     * Restore a soft-deleted employee.
     */
    public function restore(int $id): JsonResponse
    {
        $employee = PayrollEmployee::withTrashed()->findOrFail($id);

        $this->authorize('update', $employee);

        $employee->restore();

        return response()->json([
            'data' => $employee,
            'message' => 'Employee restored successfully.',
        ]);
    }

    /**
     * Get unique departments for filtering.
     */
    public function departments(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        $departments = PayrollEmployee::forCompany($companyId)
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->sort()
            ->values();

        return response()->json([
            'data' => $departments,
        ]);
    }
}

// LLM-CHECKPOINT
