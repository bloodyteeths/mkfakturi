<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FixedAsset;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            return [
                'id' => $asset->id,
                'name' => $asset->name,
                'asset_code' => $asset->asset_code,
                'category' => $asset->category,
                'acquisition_date' => $asset->acquisition_date->toDateString(),
                'acquisition_cost' => (float) $asset->acquisition_cost,
                'residual_value' => (float) $asset->residual_value,
                'useful_life_months' => $asset->useful_life_months,
                'depreciation_method' => $asset->depreciation_method,
                'depreciation_rate' => $asset->depreciation_rate,
                'accumulated_depreciation' => $asset->getAccumulatedDepreciation($asOfDate),
                'net_book_value' => $asset->getNetBookValue($asOfDate),
                'status' => $asset->status,
                'account' => $asset->account ? ['code' => $asset->account->code, 'name' => $asset->account->name] : null,
            ];
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
