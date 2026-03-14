<?php

namespace App\Http\Controllers\V1\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveRequestRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollEmployee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Mk\Payroll\Services\LeaveCalculationService;

/**
 * Leave Request Controller
 *
 * Manages employee leave requests with CRUD operations,
 * approval/rejection workflow, and balance tracking.
 */
class LeaveRequestController extends Controller
{
    public function __construct(
        private LeaveCalculationService $leaveService
    ) {
    }

    /**
     * Display a paginated listing of leave requests.
     *
     * Supports filtering by employee_id, status, and date range.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('company');
        $limit = $request->has('limit') ? $request->limit : 10;

        $query = LeaveRequest::with(['employee', 'leaveType', 'approver'])
            ->where('company_id', $companyId);

        // Filter by employee
        if ($request->has('employee_id')) {
            $query->forEmployee($request->employee_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->forPeriod($request->start_date, $request->end_date);
        }

        // Whitelist allowed orderBy fields to prevent SQL injection
        $allowedOrderFields = ['employee_name', 'leave_type', 'start_date', 'end_date', 'status', 'created_at'];
        $orderByField = in_array($request->get('orderByField'), $allowedOrderFields) ? $request->get('orderByField') : 'created_at';
        $orderByDirection = in_array(strtolower($request->get('orderBy', 'desc')), ['asc', 'desc']) ? $request->get('orderBy', 'desc') : 'desc';
        // CLAUDE-CHECKPOINT

        $requests = $query
            ->orderBy($orderByField, $orderByDirection)
            ->paginate($limit);

        return response()->json([
            'data' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'from' => $requests->firstItem(),
                'last_page' => $requests->lastPage(),
                'path' => $requests->path(),
                'per_page' => $requests->perPage(),
                'to' => $requests->lastItem(),
                'total' => $requests->total(),
            ],
        ]);
    }

    /**
     * Store a newly created leave request.
     *
     * Creates a pending leave request, validates no overlaps,
     * and checks remaining balance.
     */
    public function store(LeaveRequestRequest $request): JsonResponse
    {
        $companyId = $request->header('company');
        $validated = $request->validated();

        // Calculate business days
        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        $endDate = \Carbon\Carbon::parse($validated['end_date']);
        $businessDays = $this->leaveService->calculateBusinessDays($startDate, $endDate);

        // Validate remaining balance
        $employee = PayrollEmployee::findOrFail($validated['employee_id']);
        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);
        $remaining = $this->leaveService->getRemainingBalance(
            $employee,
            $leaveType,
            $startDate->year
        );

        if ($businessDays > $remaining) {
            return response()->json([
                'error' => 'insufficient_balance',
                'message' => "Insufficient leave balance. Remaining: {$remaining} days, Requested: {$businessDays} days.",
            ], 422);
        }

        $leaveRequest = LeaveRequest::create([
            'company_id' => $companyId,
            'employee_id' => $validated['employee_id'],
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'business_days' => $businessDays,
            'status' => LeaveRequest::STATUS_PENDING,
            'reason' => $validated['reason'] ?? null,
        ]);

        $leaveRequest->load(['employee', 'leaveType']);

        return response()->json([
            'data' => $leaveRequest,
            'message' => 'Leave request created successfully.',
        ], 201);
    }

    /**
     * Display the specified leave request.
     */
    public function show(int $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::with(['employee', 'leaveType', 'approver'])
            ->findOrFail($id);

        return response()->json([
            'data' => $leaveRequest,
        ]);
    }

    /**
     * Update a pending leave request.
     *
     * Only pending requests can be updated.
     */
    public function update(LeaveRequestRequest $request, int $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return response()->json([
                'error' => 'not_editable',
                'message' => 'Only pending leave requests can be updated.',
            ], 422);
        }

        $validated = $request->validated();

        // Recalculate business days if dates changed
        if (isset($validated['start_date']) || isset($validated['end_date'])) {
            $startDate = \Carbon\Carbon::parse($validated['start_date'] ?? $leaveRequest->start_date);
            $endDate = \Carbon\Carbon::parse($validated['end_date'] ?? $leaveRequest->end_date);
            $validated['business_days'] = $this->leaveService->calculateBusinessDays($startDate, $endDate);
        }

        $leaveRequest->update($validated);
        $leaveRequest->load(['employee', 'leaveType']);

        return response()->json([
            'data' => $leaveRequest,
            'message' => 'Leave request updated successfully.',
        ]);
    }

    /**
     * Cancel (soft delete) a pending leave request.
     *
     * Only pending requests can be cancelled.
     */
    public function destroy(int $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return response()->json([
                'error' => 'cannot_cancel',
                'message' => 'Only pending leave requests can be cancelled.',
            ], 422);
        }

        $leaveRequest->cancel();
        $leaveRequest->delete();

        return response()->json([
            'message' => 'Leave request cancelled successfully.',
        ]);
    }

    /**
     * Approve a pending leave request.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return response()->json([
                'error' => 'cannot_approve',
                'message' => 'Only pending leave requests can be approved.',
            ], 422);
        }

        $leaveRequest->approve($request->user()->id);
        $leaveRequest->load(['employee', 'leaveType', 'approver']);

        return response()->json([
            'data' => $leaveRequest,
            'message' => 'Leave request approved successfully.',
        ]);
    }

    /**
     * Reject a pending leave request with a reason.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return response()->json([
                'error' => 'cannot_reject',
                'message' => 'Only pending leave requests can be rejected.',
            ], 422);
        }

        $leaveRequest->reject($request->user()->id, $request->rejection_reason);
        $leaveRequest->load(['employee', 'leaveType', 'approver']);

        return response()->json([
            'data' => $leaveRequest,
            'message' => 'Leave request rejected successfully.',
        ]);
    }

    /**
     * Get remaining leave balance per leave type for an employee.
     */
    public function balance(Request $request, int $employee): JsonResponse
    {
        $companyId = $request->header('company');
        $year = $request->get('year', now()->year);

        $employeeModel = PayrollEmployee::findOrFail($employee);
        $leaveTypes = LeaveType::forCompany($companyId)->active()->get();

        $balances = [];
        foreach ($leaveTypes as $type) {
            $remaining = $this->leaveService->getRemainingBalance($employeeModel, $type, $year);
            $balances[] = [
                'leave_type_id' => $type->id,
                'leave_type_code' => $type->code,
                'leave_type_name' => $type->name,
                'leave_type_name_mk' => $type->name_mk,
                'max_days_per_year' => $type->max_days_per_year,
                'used_days' => $type->max_days_per_year - $remaining,
                'remaining_days' => $remaining,
                'pay_percentage' => $type->pay_percentage,
            ];
        }

        return response()->json([
            'data' => $balances,
            'meta' => [
                'employee_id' => $employeeModel->id,
                'employee_name' => $employeeModel->full_name,
                'year' => $year,
            ],
        ]);
    }
}

