<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\PeriodLock;
use App\Services\PeriodLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Partner Period Lock Controller
 *
 * Manages period locks for companies linked to the partner.
 */
class PartnerPeriodLockController extends Controller
{
    protected PeriodLockService $lockService;

    public function __construct(PeriodLockService $lockService)
    {
        $this->lockService = $lockService;
    }

    /**
     * List all period locks for a company.
     */
    public function index(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $locks = $this->lockService->getPeriodLocks($company);

        return response()->json([
            'success' => true,
            'data' => $locks,
        ]);
    }

    /**
     * Create a period lock.
     */
    public function store(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check for overlapping locks
        $overlapping = PeriodLock::getOverlappingLocks(
            $company,
            $request->period_start,
            $request->period_end
        );

        if ($overlapping->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'This period overlaps with an existing lock.',
                'overlapping' => $overlapping,
            ], 422);
        }

        $lock = $this->lockService->lockPeriod(
            $company,
            $request->period_start,
            $request->period_end,
            auth()->id(),
            $request->notes
        );

        return response()->json([
            'success' => true,
            'message' => 'Period locked successfully.',
            'data' => $lock->load('lockedBy'),
        ], 201);
    }

    /**
     * Delete a period lock.
     */
    public function destroy(Request $request, int $company, int $periodLock): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $lock = PeriodLock::where('company_id', $company)
            ->findOrFail($periodLock);

        $lock->delete();

        return response()->json([
            'success' => true,
            'message' => 'Period unlocked successfully.',
        ]);
    }

    /**
     * Get partner from authenticated request.
     */
    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        return Partner::where('user_id', $user->id)->first();
    }

    /**
     * Check if partner has access to a company.
     */
    protected function hasCompanyAccess(Partner $partner, int $companyId): bool
    {
        return $partner->companies()
            ->where('companies.id', $companyId)
            ->where('partner_company_links.is_active', true)
            ->exists();
    }
}

// CLAUDE-CHECKPOINT
