<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Mk\Models\CostCenter;
use Modules\Mk\Models\CostCenterRule;
use Modules\Mk\Services\CostCenterLedgerService;
use Modules\Mk\Services\CostCenterService;

/**
 * Partner Cost Center Controller
 *
 * Provides cost center management for partner's client companies.
 */
class PartnerCostCenterController extends Controller
{
    protected CostCenterService $service;

    protected CostCenterLedgerService $ledgerService;

    public function __construct(CostCenterService $service, CostCenterLedgerService $ledgerService)
    {
        $this->service = $service;
        $this->ledgerService = $ledgerService;
    }

    /**
     * List cost centers for a client company.
     */
    public function index(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $tree = (bool) $request->query('tree', false);
        $data = $this->service->list($company, $tree);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Show a single cost center.
     */
    public function show(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $cc = CostCenter::forCompany($company)->with('children')->find($id);

        if (! $cc) {
            return response()->json(['success' => false, 'message' => 'Cost center not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cc->id,
                'company_id' => $cc->company_id,
                'parent_id' => $cc->parent_id,
                'name' => $cc->name,
                'code' => $cc->code,
                'color' => $cc->color,
                'description' => $cc->description,
                'is_active' => $cc->is_active,
                'sort_order' => $cc->sort_order,
                'full_path' => $cc->fullPath(),
                'children' => $cc->children->map(fn ($child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'code' => $child->code,
                    'color' => $child->color,
                    'is_active' => $child->is_active,
                ]),
                'created_at' => $cc->created_at?->toIso8601String(),
                'updated_at' => $cc->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a new cost center for a client company.
     */
    public function store(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:20',
            'color' => 'nullable|string|max:7',
            'parent_id' => 'nullable|integer|exists:cost_centers,id',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        // Check unique code within company
        $code = $request->input('code');
        if ($code) {
            $existing = CostCenter::forCompany($company)->where('code', $code)->exists();
            if ($existing) {
                return response()->json(['success' => false, 'message' => 'A cost center with this code already exists.'], 422);
            }
        }

        try {
            $cc = $this->service->create($company, $validator->validated());

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $cc->id,
                    'name' => $cc->name,
                    'code' => $cc->code,
                    'color' => $cc->color,
                    'parent_id' => $cc->parent_id,
                    'description' => $cc->description,
                    'is_active' => $cc->is_active,
                    'sort_order' => $cc->sort_order,
                    'full_path' => $cc->fullPath(),
                ],
                'message' => 'Cost center created successfully.',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Update a cost center.
     */
    public function update(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $cc = CostCenter::forCompany($company)->find($id);

        if (! $cc) {
            return response()->json(['success' => false, 'message' => 'Cost center not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:150',
            'code' => 'nullable|string|max:20',
            'color' => 'nullable|string|max:7',
            'parent_id' => 'nullable|integer',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        // Check unique code within company (excluding self)
        $code = $request->input('code');
        if ($code) {
            $existing = CostCenter::forCompany($company)
                ->where('code', $code)
                ->where('id', '<>', $id)
                ->exists();
            if ($existing) {
                return response()->json(['success' => false, 'message' => 'A cost center with this code already exists.'], 422);
            }
        }

        try {
            $cc = $this->service->update($cc, $validator->validated());

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $cc->id,
                    'name' => $cc->name,
                    'code' => $cc->code,
                    'color' => $cc->color,
                    'parent_id' => $cc->parent_id,
                    'description' => $cc->description,
                    'is_active' => $cc->is_active,
                    'sort_order' => $cc->sort_order,
                    'full_path' => $cc->fullPath(),
                ],
                'message' => 'Cost center updated successfully.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete a cost center.
     */
    public function destroy(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $cc = CostCenter::forCompany($company)->find($id);

        if (! $cc) {
            return response()->json(['success' => false, 'message' => 'Cost center not found'], 404);
        }

        try {
            $this->service->delete($cc);

            return response()->json([
                'success' => true,
                'message' => 'Cost center deleted successfully.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Reorder cost centers (update sort_order and parent_id in bulk).
     */
    public function reorder(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer|exists:cost_centers,id',
            'orders.*.sort_order' => 'required|integer|min:0',
            'orders.*.parent_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $this->service->reorder($company, $request->input('orders'));

        return response()->json([
            'success' => true,
            'message' => 'Cost centers reordered successfully.',
        ]);
    }

    // ---- Rules ----

    /**
     * List assignment rules for a client company.
     */
    public function rules(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $rules = CostCenterRule::where('company_id', $company)
            ->with('costCenter:id,name,code,color')
            ->orderBy('priority', 'desc')
            ->orderBy('id')
            ->get()
            ->map(fn (CostCenterRule $rule) => [
                'id' => $rule->id,
                'cost_center_id' => $rule->cost_center_id,
                'cost_center' => $rule->costCenter ? [
                    'id' => $rule->costCenter->id,
                    'name' => $rule->costCenter->name,
                    'code' => $rule->costCenter->code,
                    'color' => $rule->costCenter->color,
                ] : null,
                'match_type' => $rule->match_type,
                'match_value' => $rule->match_value,
                'priority' => $rule->priority,
                'is_active' => $rule->is_active,
            ]);

        return response()->json([
            'success' => true,
            'data' => $rules,
        ]);
    }

    /**
     * Create a new assignment rule.
     */
    public function storeRule(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'cost_center_id' => 'required|integer|exists:cost_centers,id',
            'match_type' => 'required|in:vendor,account,description,item',
            'match_value' => 'required|string|max:255',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $cc = CostCenter::forCompany($company)->find($request->input('cost_center_id'));
        if (! $cc) {
            return response()->json(['success' => false, 'message' => 'Cost center not found for this company.'], 422);
        }

        $rule = CostCenterRule::create(array_merge($validator->validated(), [
            'company_id' => $company,
        ]));

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $rule->id,
                'cost_center_id' => $rule->cost_center_id,
                'match_type' => $rule->match_type,
                'match_value' => $rule->match_value,
                'priority' => $rule->priority,
                'is_active' => $rule->is_active,
            ],
            'message' => 'Assignment rule created successfully.',
        ], 201);
    }

    /**
     * Update an assignment rule.
     */
    public function updateRule(Request $request, int $company, int $ruleId): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $rule = CostCenterRule::where('company_id', $company)->find($ruleId);

        if (! $rule) {
            return response()->json(['success' => false, 'message' => 'Rule not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'cost_center_id' => 'sometimes|required|integer|exists:cost_centers,id',
            'match_type' => 'sometimes|required|in:vendor,account,description,item',
            'match_value' => 'sometimes|required|string|max:255',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        if ($request->has('cost_center_id')) {
            $cc = CostCenter::forCompany($company)->find($request->input('cost_center_id'));
            if (! $cc) {
                return response()->json(['success' => false, 'message' => 'Cost center not found for this company.'], 422);
            }
        }

        $rule->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $rule->id,
                'cost_center_id' => $rule->cost_center_id,
                'match_type' => $rule->match_type,
                'match_value' => $rule->match_value,
                'priority' => $rule->priority,
                'is_active' => $rule->is_active,
            ],
            'message' => 'Assignment rule updated successfully.',
        ]);
    }

    /**
     * Delete an assignment rule.
     */
    public function deleteRule(Request $request, int $company, int $ruleId): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $rule = CostCenterRule::where('company_id', $company)->find($ruleId);

        if (! $rule) {
            return response()->json(['success' => false, 'message' => 'Rule not found'], 404);
        }

        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Assignment rule deleted successfully.',
        ]);
    }

    /**
     * Suggest a cost center for a document context.
     */
    public function suggest(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $context = [
            'vendor_id' => $request->input('vendor_id'),
            'account_code' => $request->input('account_code'),
            'description' => $request->input('description'),
            'item_ids' => $request->input('item_ids', []),
        ];

        $suggestion = $this->service->suggestForDocument($company, $context);

        return response()->json([
            'success' => true,
            'data' => $suggestion,
        ]);
    }

    /**
     * Get cost center financial summary for a date range.
     */
    public function summary(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $fromDate = $request->query('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        $summary = $this->ledgerService->getCostCenterSummary($company, $fromDate, $toDate);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get filtered trial balance for a specific cost center.
     */
    public function trialBalance(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $cc = CostCenter::forCompany($company)->find($id);
        if (! $cc) {
            return response()->json(['success' => false, 'message' => 'Cost center not found'], 404);
        }

        $fromDate = $request->query('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        $trialBalance = $this->ledgerService->getFilteredTrialBalance($company, $fromDate, $toDate, $id);

        return response()->json([
            'success' => true,
            'cost_center' => [
                'id' => $cc->id,
                'name' => $cc->name,
                'code' => $cc->code,
                'color' => $cc->color,
            ],
            'trial_balance' => $trialBalance,
        ]);
    }

    /**
     * Get P&L for a specific cost center.
     */
    public function profitLoss(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $cc = CostCenter::forCompany($company)->find($id);
        if (! $cc) {
            return response()->json(['success' => false, 'message' => 'Cost center not found'], 404);
        }

        $fromDate = $request->query('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        $pl = $this->ledgerService->getCostCenterProfitLoss($company, $fromDate, $toDate, $id);

        return response()->json([
            'success' => true,
            'cost_center' => [
                'id' => $cc->id,
                'name' => $cc->name,
                'code' => $cc->code,
                'color' => $cc->color,
            ],
            'profit_loss' => $pl,
        ]);
    }

    // ---- Access Helpers ----

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
