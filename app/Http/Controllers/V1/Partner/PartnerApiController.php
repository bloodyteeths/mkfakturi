<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Partner;
use App\Services\Partner\CommissionCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Partner Portal API Controller
 *
 * Provides API endpoints for partner dashboard with mandatory mocked data safety flag.
 * All endpoints respect FEATURE_PARTNER_MOCKED_DATA flag to prevent accidental
 * real commission processing.
 *
 * @package App\Http\Controllers\V1\Partner
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
     * Returns mocked data when FEATURE_PARTNER_MOCKED_DATA is ON (default).
     * Only returns real data when flag is explicitly disabled.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(Request $request)
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'error' => 'Partner not found'
            ], 404);
        }

        // SAFETY: Return mocked data if flag is ON
        if ($this->isMockedDataEnabled()) {
            Log::info('Partner dashboard accessed with mocked data', [
                'partner_id' => $partner->id,
                'mocked' => true
            ]);

            return response()->json([
                'mocked' => true,
                'warning' => 'Using mocked data for safety. Set FEATURE_PARTNER_MOCKED_DATA=false to use real data.',
                'data' => [
                    'active_clients' => 12,
                    'monthly_commissions' => 85000,
                    'processed_invoices' => 234,
                    'commission_rate' => 5.0,
                    'pending_payout' => 127500,
                    'total_earned' => 458300,
                ],
            ]);
        }

        // Real data when flag OFF (requires explicit opt-in)
        Log::info('Partner dashboard accessed with REAL data', [
            'partner_id' => $partner->id,
            'mocked' => false
        ]);

        $stats = $this->calculator->getStats($partner);

        return response()->json([
            'mocked' => false,
            'data' => $stats
        ]);
    }

    /**
     * Get partner commissions list
     *
     * Returns paginated list of commissions with invoice and company details.
     * Returns mocked empty data when flag is ON.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function commissions(Request $request)
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'error' => 'Partner not found'
            ], 404);
        }

        // SAFETY: Return mocked data if flag is ON
        if ($this->isMockedDataEnabled()) {
            Log::info('Partner commissions accessed with mocked data', [
                'partner_id' => $partner->id,
                'mocked' => true
            ]);

            return response()->json([
                'mocked' => true,
                'warning' => 'Using mocked data for safety.',
                'data' => [
                    [
                        'id' => 1,
                        'commission_amount' => 4250,
                        'status' => 'pending',
                        'created_at' => now()->subDays(5)->toISOString(),
                        'invoice' => [
                            'invoice_number' => 'INV-2025-001',
                            'total' => 85000,
                        ],
                        'company' => [
                            'name' => 'Demo Company Ltd',
                        ],
                    ],
                ],
                'total' => 1,
                'per_page' => 25,
                'current_page' => 1,
            ]);
        }

        // Real data when flag OFF
        Log::info('Partner commissions accessed with REAL data', [
            'partner_id' => $partner->id,
            'mocked' => false
        ]);

        $commissions = Commission::where('partner_id', $partner->id)
            ->with(['invoice', 'company', 'payment', 'currency'])
            ->latest()
            ->paginate(25);

        return response()->json([
            'mocked' => false,
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
     * Returns mocked empty data when flag is ON.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clients(Request $request)
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'error' => 'Partner not found'
            ], 404);
        }

        // SAFETY: Return mocked data if flag is ON
        if ($this->isMockedDataEnabled()) {
            Log::info('Partner clients accessed with mocked data', [
                'partner_id' => $partner->id,
                'mocked' => true
            ]);

            return response()->json([
                'mocked' => true,
                'warning' => 'Using mocked data for safety.',
                'data' => [
                    [
                        'id' => 1,
                        'name' => 'Demo Client 1',
                        'unique_hash' => 'ABC123',
                        'created_at' => now()->subMonths(3)->toISOString(),
                        'invoice_count' => 15,
                        'total_revenue' => 450000,
                    ],
                    [
                        'id' => 2,
                        'name' => 'Demo Client 2',
                        'unique_hash' => 'XYZ789',
                        'created_at' => now()->subMonths(1)->toISOString(),
                        'invoice_count' => 8,
                        'total_revenue' => 280000,
                    ],
                ],
                'total' => 12,
                'per_page' => 25,
                'current_page' => 1,
            ]);
        }

        // Real data when flag OFF
        Log::info('Partner clients accessed with REAL data', [
            'partner_id' => $partner->id,
            'mocked' => false
        ]);

        $companies = Company::whereHas('partnerLinks', function ($query) use ($partner) {
            $query->where('partner_id', $partner->id)
                  ->where('is_active', true);
        })
        ->withCount('invoices')
        ->latest()
        ->paginate(25);

        return response()->json([
            'mocked' => false,
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
     * Returns partner's own profile data (always real, never mocked).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'error' => 'Partner not found'
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
            ]
        ]);
    }

    /**
     * Get partner from authenticated request
     *
     * @param Request $request
     * @return Partner|null
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
     * Check if mocked data is enabled
     *
     * @return bool
     */
    protected function isMockedDataEnabled(): bool
    {
        return config('features.partner_mocked_data.enabled', true);
    }
}

// CLAUDE-CHECKPOINT
