<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\DailyClosing;
use App\Models\Partner;
use App\Services\PeriodLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Partner Daily Closing Controller
 *
 * Manages daily closings for companies linked to the partner.
 */
class PartnerDailyClosingController extends Controller
{
    protected PeriodLockService $lockService;

    public function __construct(PeriodLockService $lockService)
    {
        $this->lockService = $lockService;
    }

    /**
     * List all daily closings for a company.
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

        $closings = $this->lockService->getClosedDays(
            $company,
            $request->from_date,
            $request->to_date
        );

        return response()->json([
            'success' => true,
            'data' => $closings,
        ]);
    }

    /**
     * Create a daily closing.
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
            'date' => 'required|date',
            'type' => 'nullable|string|in:all,cash,invoices',
            'notes' => 'nullable|string|max:1000',
        ]);

        $type = $request->type ?? DailyClosing::TYPE_ALL;

        // Check if already closed
        if (DailyClosing::isDateClosed($company, $request->date, $type)) {
            return response()->json([
                'success' => false,
                'message' => 'This date is already closed.',
            ], 422);
        }

        $closing = $this->lockService->closeDay(
            $company,
            $request->date,
            $type,
            auth()->id(),
            $request->notes
        );

        return response()->json([
            'success' => true,
            'message' => 'Day closed successfully.',
            'data' => $closing->load('closedBy'),
        ], 201);
    }

    /**
     * Delete a daily closing.
     */
    public function destroy(Request $request, int $company, int $dailyClosing): JsonResponse
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

        $closing = DailyClosing::where('company_id', $company)
            ->findOrFail($dailyClosing);

        $closing->delete();

        return response()->json([
            'success' => true,
            'message' => 'Day unlocked successfully.',
        ]);
    }

    /**
     * Get partner from authenticated request.
     * For super admin, returns a "fake" partner object to allow access.
     */
    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        // Super admin gets a fake partner to pass validation
        if ($user->role === 'super admin') {
            $fakePartner = new Partner();
            $fakePartner->id = 0;
            $fakePartner->user_id = $user->id;
            $fakePartner->name = 'Super Admin';
            $fakePartner->email = $user->email;
            $fakePartner->is_super_admin = true;
            return $fakePartner;
        }

        return Partner::where('user_id', $user->id)->first();
    }

    /**
     * Check if partner has access to a company.
     * Super admin has access to all companies.
     */
    protected function hasCompanyAccess(Partner $partner, int $companyId): bool
    {
        // Super admin has access to all companies
        if ($partner->is_super_admin ?? false) {
            return true;
        }

        return $partner->companies()
            ->where('companies.id', $companyId)
            ->where('partner_company_links.is_active', true)
            ->exists();
    }
}

// CLAUDE-CHECKPOINT
