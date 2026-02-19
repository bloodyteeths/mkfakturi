<?php

namespace App\Http\Controllers\V1\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Leave Type Controller
 *
 * Returns available leave types for the current company.
 */
class LeaveTypeController extends Controller
{
    /**
     * Display a listing of leave types for the current company.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        $leaveTypes = LeaveType::forCompany($companyId)
            ->active()
            ->orderBy('code')
            ->get();

        return response()->json([
            'data' => $leaveTypes,
        ]);
    }
}

