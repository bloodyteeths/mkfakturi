<?php

namespace Modules\Mk\Partner\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\AffiliateEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PartnerDashboardController extends Controller
{
    /**
     * Get partner dashboard data including KPIs, earnings history, and recent commissions
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get partner record for the authenticated user
        $partner = Partner::where('user_id', $user->id)->firstOrFail();

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
                'amount' => number_format($amount, 2, '.', '')
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
                    'created_at' => $event->created_at->toIso8601String(),
                    'paid_at' => $event->paid_at ? $event->paid_at->toIso8601String() : null,
                ];
            });

        return response()->json([
            'totalEarnings' => $totalEarnings,
            'monthlyEarnings' => $monthlyEarnings,
            'pendingPayout' => $pendingPayout,
            'activeClients' => $activeClients,
            'nextPayout' => $nextPayout ? [
                'amount' => $nextPayout->amount,
                'date' => $nextPayout->payout_date->toIso8601String(),
            ] : null,
            'earningsHistory' => $earningsHistory,
            'recentCommissions' => $recentCommissions,
        ]);
    }
}

// CLAUDE-CHECKPOINT
