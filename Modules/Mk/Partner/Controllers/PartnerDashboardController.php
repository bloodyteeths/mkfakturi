<?php

namespace Modules\Mk\Partner\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AffiliateEvent;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PartnerDashboardController extends Controller
{
    /**
     * Get partner from authenticated request.
     * For super admin, returns a "fake" partner object to allow access.
     *
     * @return Partner|null
     */
    protected function getPartnerFromRequest(): ?Partner
    {
        $user = Auth::user();

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
            $fakePartner->commission_rate = 0;

            return $fakePartner;
        }

        return Partner::where('user_id', $user->id)->first();
    }

    /**
     * Check if user is super admin.
     */
    protected function isSuperAdmin(): bool
    {
        return Auth::user()?->role === 'super admin';
    }

    /**
     * Get partner dashboard data including KPIs, earnings history, and recent commissions
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        \Log::info('PartnerDashboardController::index called', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => Auth::id(),
        ]);

        $user = Auth::user();

        // Super admin gets a simplified dashboard response
        if ($user->role === 'super admin') {
            return response()->json([
                'data' => [
                    'active_clients' => \App\Models\Company::count(),
                    'monthly_commissions' => 0,
                    'processed_invoices' => 0,
                    'total_earnings' => 0,
                    'pending_payout' => 0,
                ],
                'earningsHistory' => [],
                'recentCommissions' => [],
                'nextPayout' => null,
                'is_super_admin' => true,
            ]);
        }

        // Get partner record for the authenticated user
        $partner = Partner::where('user_id', $user->id)->first();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        \Log::info('Partner found', [
            'partner_id' => $partner->id,
            'user_id' => $user->id,
        ]);

        // Calculate KPIs
        $totalEarnings = $partner->getLifetimeEarnings();

        $currentMonth = Carbon::now()->format('Y-m');
        $monthlyEarnings = AffiliateEvent::forPartner($partner->id)
            ->forMonth($currentMonth)
            ->where('is_clawed_back', false)
            ->sum('amount');

        $pendingPayout = $partner->getUnpaidCommissionsTotal();

        $activeClients = $partner->activeCompanies()->count();

        // Get next payout information
        $nextPayout = \App\Models\Payout::forPartner($partner->id)
            ->where('status', 'pending')
            ->orderBy('payout_date', 'asc')
            ->first();

        // Get earnings history for the last 12 months
        $earningsHistory = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthRef = $month->format('Y-m');

            $amount = AffiliateEvent::forPartner($partner->id)
                ->forMonth($monthRef)
                ->where('is_clawed_back', false)
                ->sum('amount');

            $earningsHistory[] = [
                'month' => $month->format('M Y'),
                'amount' => number_format($amount, 2, '.', ''),
            ];
        }

        // Get recent commissions (last 10)
        $recentCommissions = AffiliateEvent::with('company')
            ->forPartner($partner->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'company_name' => $event->company->name ?? 'N/A',
                    'event_type' => $event->event_type,
                    'amount' => $event->amount,
                    'created_at' => $event->created_at->toISOString(),
                    'paid_at' => $event->paid_at ? $event->paid_at->toISOString() : null,
                ];
            });

        return response()->json([
            'data' => [
                'active_clients' => $activeClients,
                'monthly_commissions' => $monthlyEarnings,
                'processed_invoices' => 0, // TODO: Calculate actual processed invoices if needed
                'total_earnings' => $totalEarnings,
                'pending_payout' => $pendingPayout,
            ],
            'earningsHistory' => $earningsHistory,
            'recentCommissions' => $recentCommissions,
            'nextPayout' => $nextPayout ? [
                'amount' => $nextPayout->amount,
                'date' => $nextPayout->payout_date->toISOString(),
            ] : null,
        ]);
    }

    /**
     * Get pending earnings (current month)
     * AC-01-50: Dashboard endpoint
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingEarnings(Request $request)
    {
        $partner = $this->getPartnerFromRequest();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        // Super admin gets empty pending earnings
        if ($partner->is_super_admin ?? false) {
            return response()->json([
                'success' => true,
                'month' => Carbon::now()->format('Y-m'),
                'pending_amount' => 0,
                'event_count' => 0,
                'next_payout_date' => now()->startOfMonth()->addMonth()->addDays(4)->toISOString(),
                'is_super_admin' => true,
            ]);
        }

        $currentMonth = Carbon::now()->format('Y-m');
        $pendingAmount = AffiliateEvent::forPartner($partner->id)
            ->forMonth($currentMonth)
            ->unpaid()
            ->where('is_clawed_back', false)
            ->sum('amount');

        $eventCount = AffiliateEvent::forPartner($partner->id)
            ->forMonth($currentMonth)
            ->unpaid()
            ->count();

        return response()->json([
            'success' => true,
            'month' => $currentMonth,
            'pending_amount' => $pendingAmount,
            'event_count' => $eventCount,
            'next_payout_date' => now()->startOfMonth()->addMonth()->addDays(4)->toISOString(), // 5th of next month
        ]);
    }

    /**
     * Get monthly earnings (last 12 months)
     * AC-01-50: Dashboard charts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMonthlyEarnings(Request $request)
    {
        $partner = $this->getPartnerFromRequest();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        // Super admin gets empty earnings history
        if ($partner->is_super_admin ?? false) {
            return response()->json([
                'success' => true,
                'earnings' => [],
                'is_super_admin' => true,
            ]);
        }

        $earningsHistory = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthRef = $month->format('Y-m');

            $amount = AffiliateEvent::forPartner($partner->id)
                ->forMonth($monthRef)
                ->where('is_clawed_back', false)
                ->sum('amount');

            $earningsHistory[] = [
                'month' => $month->format('M Y'),
                'month_ref' => $monthRef,
                'amount' => (float) $amount,
            ];
        }

        return response()->json([
            'success' => true,
            'earnings' => $earningsHistory,
        ]);
    }

    /**
     * Get lifetime earnings
     * AC-01-50: Dashboard KPI
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLifetimeEarnings(Request $request)
    {
        $partner = $this->getPartnerFromRequest();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        // Super admin gets zero earnings
        if ($partner->is_super_admin ?? false) {
            return response()->json([
                'success' => true,
                'lifetime_total' => 0,
                'total_paid' => 0,
                'total_pending' => 0,
                'is_super_admin' => true,
            ]);
        }

        $totalEarnings = $partner->getLifetimeEarnings();
        $totalPaid = AffiliateEvent::forPartner($partner->id)
            ->paid()
            ->sum('amount');
        $totalPending = $partner->getUnpaidCommissionsTotal();

        return response()->json([
            'success' => true,
            'lifetime_total' => $totalEarnings,
            'total_paid' => $totalPaid,
            'total_pending' => $totalPending,
        ]);
    }

    /**
     * Get active companies count and details
     * AC-01-50: Referrals overview
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveCompanies(Request $request)
    {
        $partner = $this->getPartnerFromRequest();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        // Super admin gets all companies count
        if ($partner->is_super_admin ?? false) {
            $totalCount = \App\Models\Company::count();

            return response()->json([
                'success' => true,
                'active_companies' => $totalCount,
                'total_companies' => $totalCount,
                'inactive_companies' => 0,
                'is_super_admin' => true,
            ]);
        }

        $activeCount = $partner->activeCompanies()->count();
        $totalCount = $partner->companies()->count();

        return response()->json([
            'success' => true,
            'active_companies' => $activeCount,
            'total_companies' => $totalCount,
            'inactive_companies' => $totalCount - $activeCount,
        ]);
    }

    /**
     * Get next payout estimate
     * AC-01-50: Payout estimate
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNextPayoutEstimate(Request $request)
    {
        $partner = $this->getPartnerFromRequest();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        // Super admin gets zero payout estimate
        if ($partner->is_super_admin ?? false) {
            return response()->json([
                'success' => true,
                'pending_amount' => 0,
                'minimum_threshold' => 100.00,
                'meets_threshold' => false,
                'estimated_payout_date' => null,
                'remaining_to_threshold' => 100.00,
                'is_super_admin' => true,
            ]);
        }

        $pendingAmount = $partner->getUnpaidCommissionsTotal();
        $minimumThreshold = 100.00;

        $meetsThreshold = $pendingAmount >= $minimumThreshold;
        $nextPayoutDate = now()->startOfMonth()->addMonth()->addDays(4); // 5th of next month

        return response()->json([
            'success' => true,
            'pending_amount' => $pendingAmount,
            'minimum_threshold' => $minimumThreshold,
            'meets_threshold' => $meetsThreshold,
            'estimated_payout_date' => $meetsThreshold ? $nextPayoutDate->toISOString() : null,
            'remaining_to_threshold' => max(0, $minimumThreshold - $pendingAmount),
        ]);
    }

    /**
     * Get referral list (companies brought by this partner)
     * AC-01-51: Referrals page
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReferrals(Request $request)
    {
        $partner = $this->getPartnerFromRequest();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        // Super admin gets empty referrals
        if ($partner->is_super_admin ?? false) {
            return response()->json([
                'success' => true,
                'referrals' => [],
                'pagination' => [
                    'current_page' => 1,
                    'total_pages' => 1,
                    'total' => 0,
                    'per_page' => 20,
                ],
                'is_super_admin' => true,
            ]);
        }

        $perPage = $request->get('per_page', 20);

        $companies = $partner->companies()
            ->with('subscription')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'referrals' => $companies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'tier' => $company->subscription->tier ?? 'free',
                    'status' => $company->subscription->status ?? 'inactive',
                    'joined_at' => $company->created_at->toISOString(),
                    'is_active' => $company->pivot->is_active ?? false,
                ];
            }),
            'pagination' => [
                'current_page' => $companies->currentPage(),
                'total_pages' => $companies->lastPage(),
                'total' => $companies->total(),
                'per_page' => $companies->perPage(),
            ],
        ]);
    }

    /**
     * Get commissions (recent commissions list)
     * Partner portal commissions endpoint
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function commissions(Request $request)
    {
        \Log::info('PartnerDashboardController::commissions called', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => Auth::id(),
        ]);

        $user = Auth::user();

        // Super admin gets empty commissions
        if ($user->role === 'super admin') {
            return response()->json([
                'data' => [],
                'is_super_admin' => true,
            ]);
        }

        $partner = Partner::where('user_id', $user->id)->first();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        \Log::info('Partner found for commissions', [
            'partner_id' => $partner->id,
            'user_id' => $user->id,
        ]);

        // Get recent commissions (last 10)
        $recentCommissions = AffiliateEvent::with('company')
            ->forPartner($partner->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'company_name' => $event->company->name ?? 'N/A',
                    'event_type' => $event->event_type,
                    'amount' => $event->amount,
                    'created_at' => $event->created_at->toISOString(),
                    'paid_at' => $event->paid_at ? $event->paid_at->toISOString() : null,
                ];
            });

        return response()->json([
            'data' => $recentCommissions,
        ]);
    }

    /**
     * Get earnings (paginated event history)
     * AC-01-52: Earnings page
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEarnings(Request $request)
    {
        $partner = $this->getPartnerFromRequest();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        // Super admin gets empty earnings
        if ($partner->is_super_admin ?? false) {
            return response()->json([
                'success' => true,
                'earnings' => [],
                'pagination' => [
                    'current_page' => 1,
                    'total_pages' => 1,
                    'total' => 0,
                    'per_page' => 20,
                ],
                'is_super_admin' => true,
            ]);
        }

        $perPage = $request->get('per_page', 20);
        $eventType = $request->get('event_type'); // Filter by type

        $query = AffiliateEvent::with('company')
            ->forPartner($partner->id)
            ->orderBy('created_at', 'desc');

        if ($eventType) {
            $query->where('event_type', $eventType);
        }

        $events = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'earnings' => $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'company_name' => $event->company->name ?? 'N/A',
                    'event_type' => $event->event_type,
                    'amount' => $event->amount,
                    'month_ref' => $event->month_ref,
                    'created_at' => $event->created_at->toISOString(),
                    'paid_at' => $event->paid_at?->toISOString(),
                    'payout_id' => $event->payout_id,
                    'is_clawed_back' => $event->is_clawed_back,
                ];
            }),
            'pagination' => [
                'current_page' => $events->currentPage(),
                'total_pages' => $events->lastPage(),
                'total' => $events->total(),
                'per_page' => $events->perPage(),
            ],
        ]);
    }

    /**
     * Get payout history
     * AC-01-53: Payouts page
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPayouts(Request $request)
    {
        $partner = $this->getPartnerFromRequest();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        // Super admin gets empty payouts
        if ($partner->is_super_admin ?? false) {
            return response()->json([
                'success' => true,
                'payouts' => [],
                'pagination' => [
                    'current_page' => 1,
                    'total_pages' => 1,
                    'total' => 0,
                    'per_page' => 20,
                ],
                'is_super_admin' => true,
            ]);
        }

        $perPage = $request->get('per_page', 20);

        $payouts = \App\Models\Payout::where('partner_id', $partner->id)
            ->orderBy('payout_date', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'payouts' => $payouts->map(function ($payout) {
                return [
                    'id' => $payout->id,
                    'amount' => $payout->amount,
                    'status' => $payout->status,
                    'payout_date' => $payout->payout_date->toISOString(),
                    'payment_method' => $payout->payment_method,
                    'payment_reference' => $payout->payment_reference,
                    'processed_at' => $payout->processed_at?->toISOString(),
                    'event_count' => $payout->details['event_count'] ?? 0,
                    'month_ref' => $payout->details['month_ref'] ?? null,
                ];
            }),
            'pagination' => [
                'current_page' => $payouts->currentPage(),
                'total_pages' => $payouts->lastPage(),
                'total' => $payouts->total(),
                'per_page' => $payouts->perPage(),
            ],
        ]);
    }

    /**
     * Generate referral link
     * AC-01-54: Referral link generator
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateReferralLink(Request $request)
    {
        $user = Auth::user();

        // Super admin doesn't have referral links
        if ($user->role === 'super admin') {
            return response()->json([
                'success' => false,
                'error' => 'Super admin cannot generate referral links',
                'is_super_admin' => true,
            ], 400);
        }

        $partner = Partner::where('user_id', $user->id)->first();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        // Ensure user has a ref_code
        if (! $user->ref_code) {
            $user->ref_code = \Str::upper(\Str::random(8));
            $user->save();
        }

        $baseUrl = config('app.url');
        $referralUrl = "{$baseUrl}/signup?ref={$user->ref_code}";

        // Create affiliate link record for tracking
        $affiliateLink = \App\Models\AffiliateLink::firstOrCreate([
            'partner_id' => $partner->id,
            'code' => $user->ref_code,
            'target' => 'company',
        ], [
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'referral_code' => $user->ref_code,
            'referral_url' => $referralUrl,
            'clicks' => $affiliateLink->clicks ?? 0,
            'conversions' => $affiliateLink->conversions ?? 0,
        ]);
    }
}

// CLAUDE-CHECKPOINT

// CLAUDE-CHECKPOINT
