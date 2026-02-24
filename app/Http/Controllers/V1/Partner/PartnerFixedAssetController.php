<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\FixedAsset;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use PDF;

class PartnerFixedAssetController extends Controller
{
    /**
     * List fixed assets for a client company.
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

        $query = FixedAsset::forCompany($company)
            ->with(['account:id,code,name', 'depreciationAccount:id,code,name'])
            ->orderBy('acquisition_date', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('category')) {
            $query->where('category', $request->query('category'));
        }

        $assets = $query->get();
        $asOfDate = Carbon::now();

        $data = $assets->map(function (FixedAsset $asset) use ($asOfDate) {
            return $this->formatAsset($asset, $asOfDate);
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Get fixed assets register report for a client company.
     */
    public function register(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $asOfDate = Carbon::parse($request->query('as_of_date', now()->toDateString()));

        $assets = FixedAsset::forCompany($company)
            ->with(['account:id,code,name'])
            ->orderBy('category')
            ->orderBy('acquisition_date')
            ->get();

        $categories = [];
        $totals = ['acquisition_cost' => 0, 'accumulated_depreciation' => 0, 'net_book_value' => 0, 'count' => 0];

        foreach ($assets as $asset) {
            $accumulated = $asset->getAccumulatedDepreciation($asOfDate);
            $netBookValue = $asset->getNetBookValue($asOfDate);
            $cat = $asset->category;

            if (! isset($categories[$cat])) {
                $categories[$cat] = [
                    'category' => $cat,
                    'assets' => [],
                    'subtotal_cost' => 0,
                    'subtotal_depreciation' => 0,
                    'subtotal_net' => 0,
                ];
            }

            $categories[$cat]['assets'][] = [
                'id' => $asset->id,
                'name' => $asset->name,
                'asset_code' => $asset->asset_code,
                'acquisition_date' => $asset->acquisition_date->toDateString(),
                'acquisition_cost' => (float) $asset->acquisition_cost,
                'useful_life_months' => $asset->useful_life_months,
                'depreciation_rate' => $asset->depreciation_rate,
                'accumulated_depreciation' => $accumulated,
                'net_book_value' => $netBookValue,
                'status' => $asset->status,
            ];

            $categories[$cat]['subtotal_cost'] += (float) $asset->acquisition_cost;
            $categories[$cat]['subtotal_depreciation'] += $accumulated;
            $categories[$cat]['subtotal_net'] += $netBookValue;

            $totals['acquisition_cost'] += (float) $asset->acquisition_cost;
            $totals['accumulated_depreciation'] += $accumulated;
            $totals['net_book_value'] += $netBookValue;
            $totals['count']++;
        }

        foreach ($categories as &$cat) {
            $cat['subtotal_cost'] = round($cat['subtotal_cost'], 2);
            $cat['subtotal_depreciation'] = round($cat['subtotal_depreciation'], 2);
            $cat['subtotal_net'] = round($cat['subtotal_net'], 2);
        }

        $totals['acquisition_cost'] = round($totals['acquisition_cost'], 2);
        $totals['accumulated_depreciation'] = round($totals['accumulated_depreciation'], 2);
        $totals['net_book_value'] = round($totals['net_book_value'], 2);

        return response()->json([
            'success' => true,
            'data' => [
                'as_of_date' => $asOfDate->toDateString(),
                'categories' => array_values($categories),
                'totals' => $totals,
            ],
        ]);
    }

    /**
     * Export Fixed Assets Register as PDF
     */
    public function registerExport(Request $request, int $company): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            abort(404, 'Partner not found');
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            abort(403, 'No access to this company');
        }

        $companyModel = Company::find($company);
        if (! $companyModel) {
            abort(404, 'Company not found');
        }

        $companyModel->load('address');

        $asOfDate = Carbon::parse($request->query('as_of_date', now()->toDateString()));

        $assets = FixedAsset::forCompany($company)
            ->with(['account:id,code,name'])
            ->orderBy('category')
            ->orderBy('acquisition_date')
            ->get();

        $categories = [];
        $totals = ['acquisition_cost' => 0, 'accumulated_depreciation' => 0, 'net_book_value' => 0, 'count' => 0];

        foreach ($assets as $asset) {
            $accumulated = $asset->getAccumulatedDepreciation($asOfDate);
            $netBookValue = $asset->getNetBookValue($asOfDate);
            $cat = $asset->category;

            if (! isset($categories[$cat])) {
                $categories[$cat] = [
                    'category' => $cat,
                    'assets' => [],
                    'subtotal_cost' => 0,
                    'subtotal_depreciation' => 0,
                    'subtotal_net' => 0,
                ];
            }

            $categories[$cat]['assets'][] = [
                'id' => $asset->id,
                'name' => $asset->name,
                'asset_code' => $asset->asset_code,
                'acquisition_date' => $asset->acquisition_date->toDateString(),
                'acquisition_cost' => (float) $asset->acquisition_cost,
                'depreciation_rate' => $asset->depreciation_rate,
                'accumulated_depreciation' => $accumulated,
                'net_book_value' => $netBookValue,
            ];

            $categories[$cat]['subtotal_cost'] += (float) $asset->acquisition_cost;
            $categories[$cat]['subtotal_depreciation'] += $accumulated;
            $categories[$cat]['subtotal_net'] += $netBookValue;

            $totals['acquisition_cost'] += (float) $asset->acquisition_cost;
            $totals['accumulated_depreciation'] += $accumulated;
            $totals['net_book_value'] += $netBookValue;
            $totals['count']++;
        }

        foreach ($categories as &$cat) {
            $cat['subtotal_cost'] = round($cat['subtotal_cost'], 2);
            $cat['subtotal_depreciation'] = round($cat['subtotal_depreciation'], 2);
            $cat['subtotal_net'] = round($cat['subtotal_net'], 2);
        }

        $totals['acquisition_cost'] = round($totals['acquisition_cost'], 2);
        $totals['accumulated_depreciation'] = round($totals['accumulated_depreciation'], 2);
        $totals['net_book_value'] = round($totals['net_book_value'], 2);

        $currency = CompanySetting::getSetting('currency', $company);

        view()->share([
            'company' => $companyModel,
            'as_of_date' => $asOfDate->toDateString(),
            'categories' => array_values($categories),
            'totals' => $totals,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.fixed-assets-register');

        return $pdf->download("fixed_assets_register_{$asOfDate->toDateString()}.pdf");
    }

    /**
     * Show a single fixed asset with depreciation schedule.
     */
    public function show(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $asset = FixedAsset::forCompany($company)
            ->with(['account:id,code,name', 'depreciationAccount:id,code,name'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $asset->id,
                'name' => $asset->name,
                'description' => $asset->description,
                'asset_code' => $asset->asset_code,
                'category' => $asset->category,
                'acquisition_date' => $asset->acquisition_date->toDateString(),
                'acquisition_cost' => (float) $asset->acquisition_cost,
                'residual_value' => (float) $asset->residual_value,
                'useful_life_months' => $asset->useful_life_months,
                'depreciation_method' => $asset->depreciation_method,
                'depreciation_rate' => $asset->depreciation_rate,
                'monthly_depreciation' => $asset->monthly_depreciation,
                'annual_depreciation' => $asset->annual_depreciation,
                'accumulated_depreciation' => $asset->getAccumulatedDepreciation(),
                'net_book_value' => $asset->getNetBookValue(),
                'status' => $asset->status,
                'disposal_date' => $asset->disposal_date?->toDateString(),
                'disposal_amount' => $asset->disposal_amount ? (float) $asset->disposal_amount : null,
                'notes' => $asset->notes,
                'depreciation_schedule' => $asset->getDepreciationSchedule(),
            ],
        ]);
    }

    /**
     * Create a new fixed asset for a client company.
     */
    public function store(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'asset_code' => 'nullable|string|max:50',
            'category' => 'required|in:real_estate,buildings,equipment,vehicles,computers_software,other',
            'account_id' => 'nullable|integer|exists:accounts,id',
            'depreciation_account_id' => 'nullable|integer|exists:accounts,id',
            'acquisition_date' => 'required|date',
            'acquisition_cost' => 'required|numeric|min:0.01',
            'residual_value' => 'nullable|numeric|min:0',
            'useful_life_months' => 'required|integer|min:1|max:1200',
            'depreciation_method' => 'nullable|in:straight_line,declining_balance',
            'notes' => 'nullable|string',
        ]);

        $validated['company_id'] = $company;
        $validated['creator_id'] = $request->user()->id;
        $validated['residual_value'] = $validated['residual_value'] ?? 0;
        $validated['depreciation_method'] = $validated['depreciation_method'] ?? 'straight_line';
        $validated['status'] = 'active';

        $asset = FixedAsset::create($validated);

        Log::info('Partner created fixed asset', [
            'partner_id' => $partner->id,
            'company_id' => $company,
            'asset_id' => $asset->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatAsset($asset->fresh(['account:id,code,name', 'depreciationAccount:id,code,name'])),
            'message' => 'Fixed asset created successfully.',
        ], 201);
    }

    /**
     * Update a fixed asset for a client company.
     */
    public function update(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $asset = FixedAsset::forCompany($company)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'asset_code' => 'nullable|string|max:50',
            'category' => 'sometimes|required|in:real_estate,buildings,equipment,vehicles,computers_software,other',
            'account_id' => 'nullable|integer|exists:accounts,id',
            'depreciation_account_id' => 'nullable|integer|exists:accounts,id',
            'acquisition_date' => 'sometimes|required|date',
            'acquisition_cost' => 'sometimes|required|numeric|min:0.01',
            'residual_value' => 'nullable|numeric|min:0',
            'useful_life_months' => 'sometimes|required|integer|min:1|max:1200',
            'depreciation_method' => 'nullable|in:straight_line,declining_balance',
            'notes' => 'nullable|string',
        ]);

        $asset->update($validated);

        return response()->json([
            'success' => true,
            'data' => $this->formatAsset($asset->fresh(['account:id,code,name', 'depreciationAccount:id,code,name'])),
            'message' => 'Fixed asset updated successfully.',
        ]);
    }

    /**
     * Dispose of a fixed asset for a client company.
     */
    public function dispose(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $asset = FixedAsset::forCompany($company)->where('status', 'active')->findOrFail($id);

        $validated = $request->validate([
            'disposal_date' => 'required|date',
            'disposal_amount' => 'nullable|numeric|min:0',
        ]);

        $asset->update([
            'status' => FixedAsset::STATUS_DISPOSED,
            'disposal_date' => $validated['disposal_date'],
            'disposal_amount' => $validated['disposal_amount'] ?? 0,
        ]);

        Log::info('Partner disposed fixed asset', [
            'partner_id' => $partner->id,
            'company_id' => $company,
            'asset_id' => $asset->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatAsset($asset->fresh(['account:id,code,name', 'depreciationAccount:id,code,name'])),
            'message' => 'Fixed asset disposed successfully.',
        ]);
    }

    /**
     * Delete a fixed asset for a client company.
     */
    public function destroy(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $asset = FixedAsset::forCompany($company)->findOrFail($id);

        Log::info('Partner deleted fixed asset', [
            'partner_id' => $partner->id,
            'company_id' => $company,
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
        ]);

        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fixed asset deleted successfully.',
        ]);
    }

    /**
     * Format a single asset for response.
     */
    protected function formatAsset(FixedAsset $asset, ?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? Carbon::now();

        return [
            'id' => $asset->id,
            'name' => $asset->name,
            'description' => $asset->description,
            'asset_code' => $asset->asset_code,
            'category' => $asset->category,
            'account' => $asset->account ? ['id' => $asset->account->id, 'code' => $asset->account->code, 'name' => $asset->account->name] : null,
            'depreciation_account' => $asset->depreciationAccount ? ['id' => $asset->depreciationAccount->id, 'code' => $asset->depreciationAccount->code, 'name' => $asset->depreciationAccount->name] : null,
            'acquisition_date' => $asset->acquisition_date->toDateString(),
            'acquisition_cost' => (float) $asset->acquisition_cost,
            'residual_value' => (float) $asset->residual_value,
            'useful_life_months' => $asset->useful_life_months,
            'depreciation_method' => $asset->depreciation_method,
            'depreciation_rate' => $asset->depreciation_rate,
            'monthly_depreciation' => $asset->monthly_depreciation,
            'annual_depreciation' => $asset->annual_depreciation,
            'accumulated_depreciation' => $asset->getAccumulatedDepreciation($asOfDate),
            'net_book_value' => $asset->getNetBookValue($asOfDate),
            'status' => $asset->status,
            'disposal_date' => $asset->disposal_date?->toDateString(),
            'disposal_amount' => $asset->disposal_amount ? (float) $asset->disposal_amount : null,
            'notes' => $asset->notes,
            'created_at' => $asset->created_at->toDateTimeString(),
        ];
    }

    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

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

    protected function hasCompanyAccess(Partner $partner, int $companyId): bool
    {
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
