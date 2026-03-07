<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Mk\Models\CustomReportTemplate;
use Modules\Mk\Services\CustomReportService;

/**
 * Partner Custom Report Controller
 *
 * Provides custom report builder access for partner's client companies.
 */
class PartnerCustomReportController extends Controller
{
    protected CustomReportService $service;

    public function __construct(CustomReportService $service)
    {
        $this->service = $service;
    }

    /**
     * List templates for a client company.
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

        $filters = [
            'search' => $request->query('search'),
        ];

        $data = $this->service->list($company, array_filter($filters));

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Show a single template.
     */
    public function show(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $template = CustomReportTemplate::forCompany($company)
            ->with('createdBy:id,name')
            ->find($id);

        if (! $template) {
            return response()->json(['success' => false, 'message' => 'Template not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $template->id,
                'company_id' => $template->company_id,
                'name' => $template->name,
                'account_filter' => $template->account_filter,
                'columns' => $template->columns,
                'period_type' => $template->period_type,
                'group_by' => $template->group_by,
                'comparison' => $template->comparison,
                'schedule_cron' => $template->schedule_cron,
                'schedule_emails' => $template->schedule_emails,
                'created_by_user' => $template->createdBy ? [
                    'id' => $template->createdBy->id,
                    'name' => $template->createdBy->name,
                ] : null,
                'created_at' => $template->created_at?->toIso8601String(),
                'updated_at' => $template->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Preview a report from ad-hoc config.
     */
    public function preview(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'account_filter' => 'required|array',
            'account_filter.type' => 'required|string|in:range,category,specific,all',
            'columns' => 'required|array|min:1',
            'period_type' => 'nullable|string|in:month,quarter,year,custom',
            'group_by' => 'nullable|string|in:month,quarter,cost_center',
            'comparison' => 'nullable|string|in:previous_year,budget',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $data = $this->service->preview($company, $validator->validated());

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Execute a saved template.
     */
    public function execute(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $template = CustomReportTemplate::forCompany($company)->find($id);

        if (! $template) {
            return response()->json(['success' => false, 'message' => 'Template not found'], 404);
        }

        try {
            $overrides = [
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
            ];

            $data = $this->service->execute($id, array_filter($overrides));

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
