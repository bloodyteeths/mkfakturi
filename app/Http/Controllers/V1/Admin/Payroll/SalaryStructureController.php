<?php

namespace App\Http\Controllers\V1\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalaryStructureRequest;
use App\Models\PayrollEmployee;
use App\Models\SalaryStructure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryStructureController extends Controller
{
    /**
     * Display a listing of salary structures.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SalaryStructure::class);

        $limit = $request->has('limit') ? $request->limit : 10;
        $companyId = $request->header('company');

        $query = SalaryStructure::with(['employee', 'company'])
            ->forCompany($companyId);

        // Filter by employee
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by current status
        if ($request->has('is_current')) {
            $query->where('is_current', filter_var($request->is_current, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by effective date
        if ($request->has('effective_on')) {
            $query->effectiveOn($request->effective_on);
        }

        $structures = $query
            ->orderBy($request->get('orderByField', 'effective_from'), $request->get('orderBy', 'desc'))
            ->paginate($limit);

        return response()->json([
            'data' => $structures->items(),
            'meta' => [
                'current_page' => $structures->currentPage(),
                'from' => $structures->firstItem(),
                'last_page' => $structures->lastPage(),
                'path' => $structures->path(),
                'per_page' => $structures->perPage(),
                'to' => $structures->lastItem(),
                'total' => $structures->total(),
            ],
        ]);
    }

    /**
     * Store a newly created salary structure.
     */
    public function store(SalaryStructureRequest $request): JsonResponse
    {
        $this->authorize('create', SalaryStructure::class);

        $companyId = $request->header('company');
        $employeeId = $request->input('employee_id');

        // Verify employee belongs to company
        $employee = PayrollEmployee::forCompany($companyId)
            ->findOrFail($employeeId);

        // If this is set as current, unset other current structures for this employee
        if ($request->input('is_current', false)) {
            SalaryStructure::where('employee_id', $employeeId)
                ->where('company_id', $companyId)
                ->update(['is_current' => false]);
        }

        $structure = SalaryStructure::create($request->getSalaryStructurePayload());

        $structure->load(['employee', 'company']);

        return response()->json([
            'data' => $structure,
            'message' => 'Salary structure created successfully.',
        ], 201);
    }

    /**
     * Display the specified salary structure.
     */
    public function show(SalaryStructure $salaryStructure): JsonResponse
    {
        $this->authorize('view', $salaryStructure);

        $salaryStructure->load(['employee', 'company']);

        return response()->json([
            'data' => $salaryStructure,
        ]);
    }

    /**
     * Update the specified salary structure.
     */
    public function update(SalaryStructureRequest $request, SalaryStructure $salaryStructure): JsonResponse
    {
        $this->authorize('update', $salaryStructure);

        $companyId = $request->header('company');

        // If this is being set as current, unset other current structures for this employee
        if ($request->input('is_current', false)) {
            SalaryStructure::where('employee_id', $salaryStructure->employee_id)
                ->where('company_id', $companyId)
                ->where('id', '!=', $salaryStructure->id)
                ->update(['is_current' => false]);
        }

        $salaryStructure->update($request->getSalaryStructurePayload());

        $salaryStructure->load(['employee', 'company']);

        return response()->json([
            'data' => $salaryStructure,
            'message' => 'Salary structure updated successfully.',
        ]);
    }

    /**
     * Remove the specified salary structure.
     */
    public function destroy(SalaryStructure $salaryStructure): JsonResponse
    {
        $this->authorize('delete', $salaryStructure);

        // Check if this structure has been used in any payroll runs
        $hasPayrollLines = \DB::table('payroll_run_lines')
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payroll_run_lines.payroll_run_id')
            ->where('payroll_run_lines.employee_id', $salaryStructure->employee_id)
            ->where(function ($query) use ($salaryStructure) {
                $query->whereBetween('payroll_runs.period_start', [
                    $salaryStructure->effective_from,
                    $salaryStructure->effective_to ?? now(),
                ]);
            })
            ->exists();

        if ($hasPayrollLines) {
            return response()->json([
                'error' => 'cannot_delete_used_structure',
                'message' => 'Cannot delete a salary structure that has been used in payroll runs.',
            ], 422);
        }

        $salaryStructure->delete();

        return response()->json([
            'message' => 'Salary structure deleted successfully.',
        ]);
    }

    /**
     * Set a salary structure as current for the employee.
     */
    public function setCurrent(Request $request, SalaryStructure $salaryStructure): JsonResponse
    {
        $this->authorize('update', $salaryStructure);

        $companyId = $request->header('company');

        // Unset other current structures for this employee
        SalaryStructure::where('employee_id', $salaryStructure->employee_id)
            ->where('company_id', $companyId)
            ->update(['is_current' => false]);

        // Set this one as current
        $salaryStructure->update(['is_current' => true]);

        return response()->json([
            'data' => $salaryStructure,
            'message' => 'Salary structure set as current.',
        ]);
    }

    /**
     * Get salary structure history for an employee.
     */
    public function history(Request $request, int $employeeId): JsonResponse
    {
        $companyId = $request->header('company');

        // Verify employee belongs to company
        $employee = PayrollEmployee::forCompany($companyId)
            ->findOrFail($employeeId);

        $this->authorize('viewAny', SalaryStructure::class);

        $structures = SalaryStructure::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->orderBy('effective_from', 'desc')
            ->get();

        return response()->json([
            'data' => $structures,
            'meta' => [
                'employee' => $employee,
            ],
        ]);
    }
}

// LLM-CHECKPOINT
