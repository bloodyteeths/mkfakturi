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
 * Allows company users to view their deadlines, mark them as
 * completed, and create/delete custom deadlines.
 * System recurring deadlines cannot be deleted by company users.
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
        $companyId = $request->header('company');

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
     * KPI summary counts for company deadlines.
     *
     * @return JsonResponse
     */
    public function summary(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'No company found for user'], 404);
        }

        $today = now()->startOfDay();
        $endOfWeek = now()->endOfWeek();
        $endOfMonth = now()->endOfMonth();

        return response()->json([
            'overdue_count' => Deadline::forCompany($companyId)
                ->where('status', Deadline::STATUS_OVERDUE)->count(),
            'due_this_week' => Deadline::forCompany($companyId)->notCompleted()
                ->whereBetween('due_date', [$today, $endOfWeek])->count(),
            'due_this_month' => Deadline::forCompany($companyId)->notCompleted()
                ->whereBetween('due_date', [$today, $endOfMonth])->count(),
            'completed_this_month' => Deadline::forCompany($companyId)
                ->where('status', Deadline::STATUS_COMPLETED)
                ->whereMonth('completed_at', now()->month)
                ->whereYear('completed_at', now()->year)->count(),
        ]);
    }

    /**
     * Mark a company deadline as completed.
     *
     * @return JsonResponse
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $request->header('company');

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

    /**
     * Create a custom deadline for the company.
     *
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'No company found for user'], 404);
        }

        // Check usage limit
        $usageService = app(\App\Services\UsageLimitService::class);
        $company = \App\Models\Company::find($companyId);
        if ($company && ! $usageService->canUse($company, 'deadlines_custom')) {
            return response()->json($usageService->buildLimitExceededResponse($company, 'deadlines_custom'), 402);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_mk' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'due_date' => 'required|date|after_or_equal:today',
            'deadline_type' => ['nullable', Rule::in(['vat_return', 'mpin', 'cit_advance', 'annual_fs', 'custom'])],
        ]);

        $deadline = Deadline::create([
            'company_id' => $companyId,
            'title' => $validated['title'],
            'title_mk' => $validated['title_mk'] ?? $validated['title'],
            'description' => $validated['description'] ?? null,
            'deadline_type' => $validated['deadline_type'] ?? Deadline::TYPE_CUSTOM,
            'due_date' => $validated['due_date'],
            'status' => Deadline::STATUS_UPCOMING,
            'reminder_days_before' => [7, 3, 1],
            'is_recurring' => false,
        ]);

        $deadline->append(['days_remaining', 'type_label', 'type_label_en']);

        Log::info('Company user created deadline', [
            'user_id' => $user->id,
            'deadline_id' => $deadline->id,
            'company_id' => $companyId,
        ]);

        return response()->json(['data' => $deadline], 201);
    }

    /**
     * Update a custom (non-recurring) deadline.
     *
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'No company found for user'], 404);
        }

        $deadline = Deadline::forCompany($companyId)->find($id);

        if (! $deadline) {
            return response()->json(['error' => 'Deadline not found'], 404);
        }

        if ($deadline->is_recurring) {
            return response()->json(['error' => 'System recurring deadlines cannot be edited'], 422);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'title_mk' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'due_date' => 'sometimes|required|date|after_or_equal:today',
            'deadline_type' => ['sometimes', Rule::in(['vat_return', 'mpin', 'cit_advance', 'annual_fs', 'custom'])],
            'reminder_days_before' => 'nullable|array',
            'reminder_days_before.*' => 'integer|min:1|max:30',
        ]);

        $deadline->update($validated);
        $deadline->load(['completedBy:id,name,email']);
        $deadline->append(['days_remaining', 'type_label', 'type_label_en']);

        return response()->json(['data' => $deadline]);
    }

    /**
     * Delete a custom (non-recurring) deadline.
     *
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'No company found for user'], 404);
        }

        $deadline = Deadline::forCompany($companyId)->find($id);

        if (! $deadline) {
            return response()->json(['error' => 'Deadline not found'], 404);
        }

        if ($deadline->is_recurring) {
            return response()->json(['error' => 'System recurring deadlines cannot be deleted'], 422);
        }

        $deadline->delete();

        Log::info('Company user deleted deadline', [
            'user_id' => $user->id,
            'deadline_id' => $id,
            'company_id' => $companyId,
        ]);

        return response()->json(['message' => 'Deadline deleted successfully']);
    }
}
// CLAUDE-CHECKPOINT
