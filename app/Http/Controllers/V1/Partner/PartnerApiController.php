<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Company;
use App\Models\Partner;
use App\Services\Partner\CommissionCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Partner Portal API Controller
 *
 * Provides API endpoints for partner dashboard with real data.
 * All endpoints return real database data.
 */
class PartnerApiController extends Controller
{
    protected CommissionCalculatorService $calculator;

    public function __construct(CommissionCalculatorService $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Get partner dashboard statistics
     *
     * Returns real partner stats from database.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(Request $request)
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json([
                'error' => 'Partner not found',
            ], 404);
        }

        Log::info('Partner dashboard accessed', [
            'partner_id' => $partner->id,
        ]);

        $stats = $this->calculator->getStats($partner);

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Get partner commissions list
     *
     * Returns paginated list of commissions with invoice and company details.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function commissions(Request $request)
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json([
                'error' => 'Partner not found',
            ], 404);
        }

        Log::info('Partner commissions accessed', [
            'partner_id' => $partner->id,
        ]);

        $commissions = Commission::where('partner_id', $partner->id)
            ->with(['invoice', 'company', 'payment', 'currency'])
            ->latest()
            ->paginate(25);

        return response()->json([
            'data' => $commissions->items(),
            'total' => $commissions->total(),
            'per_page' => $commissions->perPage(),
            'current_page' => $commissions->currentPage(),
            'last_page' => $commissions->lastPage(),
        ]);
    }

    /**
     * Get partner clients list
     *
     * Returns paginated list of companies managed by partner.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clients(Request $request)
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json([
                'error' => 'Partner not found',
            ], 404);
        }

        Log::info('Partner clients accessed', [
            'partner_id' => $partner->id,
        ]);

        $companies = Company::whereHas('partnerLinks', function ($query) use ($partner) {
            $query->where('partner_id', $partner->id)
                ->where('is_active', true);
        })
            ->withCount('invoices')
            ->latest()
            ->paginate(25);

        return response()->json([
            'data' => $companies->items(),
            'total' => $companies->total(),
            'per_page' => $companies->perPage(),
            'current_page' => $companies->currentPage(),
            'last_page' => $companies->lastPage(),
        ]);
    }

    /**
     * Get partner profile information
     *
     * Returns partner's own profile data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json([
                'error' => 'Partner not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'email' => $partner->email,
                'phone' => $partner->phone,
                'company_name' => $partner->company_name,
                'commission_rate' => $partner->commission_rate,
                'is_active' => $partner->is_active,
                'created_at' => $partner->created_at,
            ],
        ]);
    }

    /**
     * Get partner from authenticated request
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

// CLAUDE-CHECKPOINT
