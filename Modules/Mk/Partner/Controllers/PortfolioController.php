<?php

namespace Modules\Mk\Partner\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Mk\Partner\Services\PortfolioTierService;

class PortfolioController extends Controller
{
    /**
     * Activate the portfolio program for the authenticated partner.
     * Sets grace period to 45 days from now.
     */
    public function activate(Request $request): JsonResponse
    {
        $partner = $this->getPartner();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 404);
        }

        if ($partner->portfolio_enabled) {
            return response()->json(['error' => 'Portfolio already activated'], 422);
        }

        $graceDays = config('subscriptions.portfolio.grace_period_days', 45);

        $partner->update([
            'portfolio_enabled' => true,
            'portfolio_activated_at' => now(),
            'portfolio_grace_ends_at' => now()->addDays($graceDays),
        ]);

        // Convert existing partner_company_links to portfolio-managed
        \DB::table('partner_company_links')
            ->where('partner_id', $partner->id)
            ->where('is_active', true)
            ->update(['is_portfolio_managed' => true]);

        // Mark existing managed companies as portfolio-managed
        $companyIds = \DB::table('partner_company_links')
            ->where('partner_id', $partner->id)
            ->where('is_portfolio_managed', true)
            ->pluck('company_id');

        if ($companyIds->isNotEmpty()) {
            \App\Models\Company::whereIn('id', $companyIds)
                ->update([
                    'is_portfolio_managed' => true,
                    'managing_partner_id' => $partner->id,
                ]);
        }

        // Recalculate tiers (all get 'standard' during grace)
        $tierService = app(PortfolioTierService::class);
        $tierService->recalculate($partner->fresh());

        return response()->json([
            'message' => 'Portfolio activated successfully',
            'grace_ends_at' => $partner->fresh()->portfolio_grace_ends_at->toISOString(),
            'stats' => $partner->fresh()->getPortfolioStats(),
        ]);
    }

    /**
     * Get portfolio statistics.
     */
    public function stats(): JsonResponse
    {
        $partner = $this->getPartner();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 404);
        }

        if (! $partner->portfolio_enabled) {
            return response()->json([
                'portfolio_enabled' => false,
                'message' => 'Portfolio not activated. Activate to manage companies.',
            ]);
        }

        $stats = $partner->getPortfolioStats();

        // Calculate commission info
        $payingCompanies = $partner->portfolioCompanies()
            ->whereHas('subscription', function ($q) {
                $q->whereIn('status', ['trial', 'active'])
                    ->where('plan', '!=', 'free');
            })
            ->with('subscription')
            ->get();

        $monthlyRevenue = $payingCompanies->sum(fn ($c) => $c->subscription->price_monthly ?? 0);
        $commissionRate = $partner->getEffectiveCommissionRate();
        $monthlyCommission = $monthlyRevenue * $commissionRate;

        return response()->json([
            'portfolio_enabled' => true,
            'stats' => $stats,
            'commission' => [
                'rate' => $commissionRate,
                'monthly_revenue' => round($monthlyRevenue, 2),
                'monthly_commission' => round($monthlyCommission, 2),
            ],
        ]);
    }

    /**
     * Get partner from authenticated user.
     */
    protected function getPartner(): ?Partner
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if ($user->role === 'super admin') {
            $partnerId = request()->query('partner_id');
            if ($partnerId) {
                return Partner::find($partnerId);
            }

            return null;
        }

        return Partner::where('user_id', $user->id)->first();
    }
}
// CLAUDE-CHECKPOINT
