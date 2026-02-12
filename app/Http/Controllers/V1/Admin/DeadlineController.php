<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deadline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Admin (Company) Deadline Controller (P8-02)
 *
 * Allows company users to view their own deadlines and mark
 * them as completed. Company users cannot create or delete
 * system deadlines -- only partners/admins can manage those.
 */
class DeadlineController extends Controller
{
    /**
     * List own company deadlines.
     *
     * Supports filtering by type and status.
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id ?? $user->companies()->first()?->id;

        if (! $companyId) {
            return response()->json(['error' => 'No company found for user'], 404);
        }

        $request->validate([
            'type' => ['nullable', Rule::in(['vat_return', 'mpin', 'cit_advance', 'annual_fs', 'custom'])],
            'status' => ['nullable', Rule::in(['upcoming', 'due_today', 'overdue', 'completed'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Deadline::forCompany($companyId)
            ->with(['completedBy:id,name,email'])
            ->orderBy('due_date', 'asc');

        if ($request->filled('type')) {
            $query->ofType($request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = $request->input('per_page', 25);
        $deadlines = $query->paginate($perPage);

        // Append computed attributes
        $deadlines->getCollection()->transform(function ($deadline) {
            $deadline->append(['days_remaining', 'type_label', 'type_label_en']);

            return $deadline;
        });

        return response()->json($deadlines);
    }

    /**
     * Mark a company deadline as completed.
     *
     * @return JsonResponse
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id ?? $user->companies()->first()?->id;

        if (! $companyId) {
            return response()->json(['error' => 'No company found for user'], 404);
        }

        $deadline = Deadline::forCompany($companyId)->find($id);

        if (! $deadline) {
            return response()->json(['error' => 'Deadline not found'], 404);
        }

        if ($deadline->status === Deadline::STATUS_COMPLETED) {
            return response()->json(['error' => 'Deadline is already completed'], 422);
        }

        $deadline->complete($user->id);

        $deadline->load(['completedBy:id,name,email']);
        $deadline->append(['days_remaining', 'type_label', 'type_label_en']);

        Log::info('Company user completed deadline', [
            'user_id' => $user->id,
            'deadline_id' => $deadline->id,
            'company_id' => $companyId,
        ]);

        return response()->json([
            'data' => $deadline,
            'message' => 'Deadline marked as completed.',
        ]);
    }
}
// CLAUDE-CHECKPOINT
