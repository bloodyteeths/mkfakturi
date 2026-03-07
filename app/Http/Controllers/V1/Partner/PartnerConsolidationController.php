<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Mk\Services\ConsolidationService;

/**
 * Partner Consolidation Controller
 *
 * F12: Financial Consolidation (Enhanced) with intercompany
 * transaction detection and elimination entries.
 */
class PartnerConsolidationController extends Controller
{
    protected ConsolidationService $service;

    public function __construct(ConsolidationService $service)
    {
        $this->service = $service;
    }

    /**
     * List consolidation groups for the current partner.
     */
    public function index(Request $request): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        try {
            $groups = $this->service->listGroups($partner->id);

            return response()->json([
                'success' => true,
                'data' => $groups,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a new consolidation group.
     */
    public function store(Request $request): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'parent_company_id' => 'required|integer|exists:companies,id',
            'currency_code' => 'nullable|string|max:3',
            'notes' => 'nullable|string|max:2000',
            'members' => 'required|array|min:1',
            'members.*.company_id' => 'required|integer|exists:companies,id',
            'members.*.ownership_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        // Verify partner has access to the parent company and all member companies
        $allCompanyIds = array_merge(
            [$validated['parent_company_id']],
            array_column($validated['members'], 'company_id')
        );

        foreach (array_unique($allCompanyIds) as $companyId) {
            if (! $this->hasCompanyAccess($partner, (int) $companyId)) {
                return response()->json([
                    'success' => false,
                    'message' => "Access denied to company ID {$companyId}",
                ], 403);
            }
        }

        try {
            $validated['partner_id'] = $partner->id;
            $group = $this->service->createGroup($validated);

            return response()->json([
                'success' => true,
                'message' => 'Consolidation group created.',
                'data' => $group,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create consolidation group: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a single consolidation group.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        try {
            $group = $this->service->getGroup($id);

            // Verify group belongs to this partner
            if (! $this->canAccessGroup($partner, $group)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            $summary = $this->service->summary($id);

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Group not found'], 404);
        }
    }

    /**
     * Update a consolidation group.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        // Verify group belongs to this partner
        try {
            $group = $this->service->getGroup($id);
            if (! $this->canAccessGroup($partner, $group)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Group not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:150',
            'parent_company_id' => 'nullable|integer|exists:companies,id',
            'currency_code' => 'nullable|string|max:3',
            'notes' => 'nullable|string|max:2000',
            'members' => 'nullable|array|min:1',
            'members.*.company_id' => 'required_with:members|integer|exists:companies,id',
            'members.*.ownership_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        // Verify access to any new companies
        if (! empty($validated['members'])) {
            $newCompanyIds = array_column($validated['members'], 'company_id');
            if (! empty($validated['parent_company_id'])) {
                $newCompanyIds[] = $validated['parent_company_id'];
            }

            foreach (array_unique($newCompanyIds) as $companyId) {
                if (! $this->hasCompanyAccess($partner, (int) $companyId)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Access denied to company ID {$companyId}",
                    ], 403);
                }
            }
        }

        try {
            $updated = $this->service->updateGroup($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Consolidation group updated.',
                'data' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update consolidation group: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a consolidation group.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        try {
            $group = $this->service->getGroup($id);
            if (! $this->canAccessGroup($partner, $group)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            $this->service->deleteGroup($id);

            return response()->json([
                'success' => true,
                'message' => 'Consolidation group deleted.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Group not found'], 404);
        }
    }

    /**
     * Detect intercompany transactions for a group.
     */
    public function intercompany(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        try {
            $group = $this->service->getGroup($id);
            if (! $this->canAccessGroup($partner, $group)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Group not found'], 404);
        }

        $startDate = $request->query('start_date', Carbon::now()->startOfYear()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->toDateString());

        try {
            $transactions = $this->service->detectIntercompany($id, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => $transactions,
                    'count' => count($transactions),
                    'total_amount' => round(array_sum(array_column($transactions, 'amount')), 2),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate elimination entries for a group.
     */
    public function eliminations(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        try {
            $group = $this->service->getGroup($id);
            if (! $this->canAccessGroup($partner, $group)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Group not found'], 404);
        }

        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        try {
            $eliminations = $this->service->generateEliminations($id, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $eliminations,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get consolidated trial balance for a group.
     */
    public function trialBalance(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        try {
            $group = $this->service->getGroup($id);
            if (! $this->canAccessGroup($partner, $group)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Group not found'], 404);
        }

        $startDate = $request->query('start_date', Carbon::now()->startOfYear()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->toDateString());

        try {
            $data = $this->service->consolidatedTrialBalance($id, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get consolidated income statement for a group.
     */
    public function incomeStatement(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        try {
            $group = $this->service->getGroup($id);
            if (! $this->canAccessGroup($partner, $group)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Group not found'], 404);
        }

        $startDate = $request->query('start_date', Carbon::now()->startOfYear()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->toDateString());

        try {
            $data = $this->service->consolidatedIncomeStatement($id, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get consolidated balance sheet for a group.
     */
    public function balanceSheet(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        try {
            $group = $this->service->getGroup($id);
            if (! $this->canAccessGroup($partner, $group)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Group not found'], 404);
        }

        $date = $request->query('date', Carbon::now()->toDateString());

        try {
            $data = $this->service->consolidatedBalanceSheet($id, $date);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---- Access Helpers ----

    /**
     * Check if partner can access a consolidation group.
     */
    protected function canAccessGroup(Partner $partner, $group): bool
    {
        // Super admin can access everything
        if ($partner->is_super_admin ?? false) {
            return true;
        }

        return (int) $group->partner_id === (int) $partner->id;
    }

    /**
     * Get partner from authenticated request.
     * For super admin, returns a "fake" partner object to allow access.
     */
    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();

        if (! $user) {
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
