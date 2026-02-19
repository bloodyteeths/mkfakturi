<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Deadline;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Partner Deadline Controller (P8-02)
 *
 * Manages deadline tracking for partner-managed companies.
 * Partners can view, create, complete, and delete deadlines
 * across all their managed companies.
 */
class DeadlineController extends Controller
{
    /**
     * List all deadlines across managed companies.
     *
     * Supports filtering by type, status, date range, and company_id.
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json(['error' => 'Partner not found'], 404);
        }

        $request->validate([
            'type' => ['nullable', Rule::in(['vat_return', 'mpin', 'cit_advance', 'annual_fs', 'custom'])],
            'status' => ['nullable', Rule::in(['upcoming', 'due_today', 'overdue', 'completed'])],
            'company_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $companyIds = $partner->activeCompanies()->pluck('companies.id');

        $query = Deadline::whereIn('company_id', $companyIds)
            ->with(['company:id,name', 'completedBy:id,name,email'])
            ->orderBy('due_date', 'asc');

        // Apply filters
        if ($request->filled('type')) {
            $query->ofType($request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('company_id')) {
            $companyId = (int) $request->input('company_id');
            // Verify the partner manages this company
            if ($companyIds->contains($companyId)) {
                $query->forCompany($companyId);
            } else {
                return response()->json(['error' => 'Company not managed by this partner'], 403);
            }
        }

        if ($request->filled('date_from')) {
            $query->where('due_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('due_date', '<=', $request->input('date_to'));
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
     * Get deadline summary KPIs.
     *
     * Returns: overdue_count, due_this_week, due_this_month, completed_this_month.
     *
     * @return JsonResponse
     */
    public function summary(Request $request): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json(['error' => 'Partner not found'], 404);
        }

        $companyIds = $partner->activeCompanies()->pluck('companies.id');

        $overdueCount = Deadline::whereIn('company_id', $companyIds)
            ->where('status', Deadline::STATUS_OVERDUE)
            ->count();

        $dueThisWeek = Deadline::whereIn('company_id', $companyIds)
            ->where('status', '!=', Deadline::STATUS_COMPLETED)
            ->where('due_date', '>=', Carbon::today())
            ->where('due_date', '<=', Carbon::today()->endOfWeek())
            ->count();

        $dueThisMonth = Deadline::whereIn('company_id', $companyIds)
            ->where('status', '!=', Deadline::STATUS_COMPLETED)
            ->where('due_date', '>=', Carbon::today())
            ->where('due_date', '<=', Carbon::today()->endOfMonth())
            ->count();

        $completedThisMonth = Deadline::whereIn('company_id', $companyIds)
            ->where('status', Deadline::STATUS_COMPLETED)
            ->whereMonth('completed_at', Carbon::now()->month)
            ->whereYear('completed_at', Carbon::now()->year)
            ->count();

        return response()->json([
            'data' => [
                'overdue_count' => $overdueCount,
                'due_this_week' => $dueThisWeek,
                'due_this_month' => $dueThisMonth,
                'completed_this_month' => $completedThisMonth,
            ],
        ]);
    }

    /**
     * Create a custom deadline for a managed company.
     *
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json(['error' => 'Partner not found'], 404);
        }

        $validated = $request->validate([
            'company_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:200'],
            'title_mk' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'deadline_type' => ['nullable', Rule::in(['vat_return', 'mpin', 'cit_advance', 'annual_fs', 'custom'])],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'reminder_days_before' => ['nullable', 'array'],
            'reminder_days_before.*' => ['integer', 'min:0', 'max:90'],
        ]);

        // Verify the partner manages this company
        $companyIds = $partner->activeCompanies()->pluck('companies.id');

        if (! $companyIds->contains((int) $validated['company_id'])) {
            return response()->json(['error' => 'Company not managed by this partner'], 403);
        }

        $deadline = Deadline::create([
            'company_id' => $validated['company_id'],
            'partner_id' => $partner->id,
            'title' => $validated['title'],
            'title_mk' => $validated['title_mk'] ?? null,
            'description' => $validated['description'] ?? null,
            'deadline_type' => $validated['deadline_type'] ?? Deadline::TYPE_CUSTOM,
            'due_date' => $validated['due_date'],
            'status' => Deadline::STATUS_UPCOMING,
            'reminder_days_before' => $validated['reminder_days_before'] ?? [7, 3, 1],
            'is_recurring' => false,
            'recurrence_rule' => null,
        ]);

        $deadline->load(['company:id,name', 'completedBy:id,name,email']);
        $deadline->append(['days_remaining', 'type_label', 'type_label_en']);

        Log::info('Partner created custom deadline', [
            'partner_id' => $partner->id,
            'deadline_id' => $deadline->id,
            'company_id' => $validated['company_id'],
        ]);

        return response()->json([
            'data' => $deadline,
            'message' => 'Deadline created successfully.',
        ], 201);
    }

    /**
     * Update a deadline.
     *
     * For custom deadlines, all fields can be edited.
     * For system recurring deadlines, only status/completion can be edited.
     *
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json(['error' => 'Partner not found'], 404);
        }

        $companyIds = $partner->activeCompanies()->pluck('companies.id');
        $deadline = Deadline::whereIn('company_id', $companyIds)->find($id);

        if (! $deadline) {
            return response()->json(['error' => 'Deadline not found'], 404);
        }

        // For recurring system deadlines, only allow status changes
        if ($deadline->is_recurring && $deadline->deadline_type !== Deadline::TYPE_CUSTOM) {
            $validated = $request->validate([
                'status' => ['nullable', Rule::in(['upcoming', 'due_today', 'overdue', 'completed'])],
            ]);

            if (isset($validated['status'])) {
                if ($validated['status'] === Deadline::STATUS_COMPLETED) {
                    $deadline->complete($request->user()->id);
                } else {
                    $deadline->update(['status' => $validated['status']]);
                }
            }
        } else {
            $validated = $request->validate([
                'title' => ['sometimes', 'string', 'max:200'],
                'title_mk' => ['nullable', 'string', 'max:200'],
                'description' => ['nullable', 'string', 'max:2000'],
                'due_date' => ['sometimes', 'date'],
                'reminder_days_before' => ['nullable', 'array'],
                'reminder_days_before.*' => ['integer', 'min:0', 'max:90'],
                'status' => ['nullable', Rule::in(['upcoming', 'due_today', 'overdue', 'completed'])],
            ]);

            if (isset($validated['status']) && $validated['status'] === Deadline::STATUS_COMPLETED) {
                $deadline->complete($request->user()->id);
                unset($validated['status']);
            }

            $deadline->update($validated);
        }

        $deadline->load(['company:id,name', 'completedBy:id,name,email']);
        $deadline->append(['days_remaining', 'type_label', 'type_label_en']);

        return response()->json([
            'data' => $deadline,
            'message' => 'Deadline updated successfully.',
        ]);
    }

    /**
     * Mark a deadline as completed.
     *
     * @return JsonResponse
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json(['error' => 'Partner not found'], 404);
        }

        $companyIds = $partner->activeCompanies()->pluck('companies.id');
        $deadline = Deadline::whereIn('company_id', $companyIds)->find($id);

        if (! $deadline) {
            return response()->json(['error' => 'Deadline not found'], 404);
        }

        if ($deadline->status === Deadline::STATUS_COMPLETED) {
            return response()->json(['error' => 'Deadline is already completed'], 422);
        }

        $deadline->complete($request->user()->id);

        $deadline->load(['company:id,name', 'completedBy:id,name,email']);
        $deadline->append(['days_remaining', 'type_label', 'type_label_en']);

        Log::info('Partner completed deadline', [
            'partner_id' => $partner->id,
            'deadline_id' => $deadline->id,
        ]);

        return response()->json([
            'data' => $deadline,
            'message' => 'Deadline marked as completed.',
        ]);
    }

    /**
     * Delete a custom deadline.
     *
     * System recurring deadlines cannot be deleted.
     *
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json(['error' => 'Partner not found'], 404);
        }

        $companyIds = $partner->activeCompanies()->pluck('companies.id');
        $deadline = Deadline::whereIn('company_id', $companyIds)->find($id);

        if (! $deadline) {
            return response()->json(['error' => 'Deadline not found'], 404);
        }

        // Only custom deadlines can be deleted
        if ($deadline->is_recurring && $deadline->deadline_type !== Deadline::TYPE_CUSTOM) {
            return response()->json([
                'error' => 'System recurring deadlines cannot be deleted.',
            ], 403);
        }

        Log::info('Partner deleted deadline', [
            'partner_id' => $partner->id,
            'deadline_id' => $deadline->id,
        ]);

        $deadline->delete();

        return response()->json([
            'message' => 'Deadline deleted successfully.',
        ]);
    }

    /**
     * Get partner from authenticated request.
     */
    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        return Partner::where('user_id', $user->id)->first();
    }
}
