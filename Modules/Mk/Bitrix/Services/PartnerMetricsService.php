<?php

namespace Modules\Mk\Bitrix\Services;

use App\Models\AffiliateEvent;
use App\Models\Commission;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PartnerMetricsService
 *
 * Calculates and aggregates partner performance metrics for HubSpot sync.
 * Provides commission data, activity metrics, and health scores.
 *
 * All primary methods accept partner ID (int) for flexibility.
 * Legacy methods accepting Partner objects are preserved for backward compatibility.
 */
class PartnerMetricsService
{
    /**
     * Get total revenue from paid invoices for a partner's companies.
     *
     * Sums up all invoices with paid_status = 'PAID' from companies
     * linked to the partner via partner_company_links table.
     *
     * @param int $partnerId Partner ID
     * @return float Total revenue in decimal (converted from cents)
     */
    public function getTotalRevenue(int $partnerId): float
    {
        $partner = Partner::find($partnerId);

        if (!$partner) {
            Log::warning('PartnerMetricsService: Partner not found', ['partner_id' => $partnerId]);
            return 0.0;
        }

        return $this->getTotalRevenueForPartner($partner);
    }

    /**
     * Get commission summary for a partner.
     *
     * Returns an array with earned, paid, pending amounts and effective rate.
     * Uses both Commission model (legacy) and AffiliateEvent model (new).
     *
     * @param int $partnerId Partner ID
     * @return array{earned: float, paid: float, pending: float, rate: float}
     */
    public function getCommissionSummary(int $partnerId): array
    {
        $partner = Partner::find($partnerId);

        if (!$partner) {
            Log::warning('PartnerMetricsService: Partner not found', ['partner_id' => $partnerId]);
            return [
                'earned' => 0.0,
                'paid' => 0.0,
                'pending' => 0.0,
                'rate' => 0.0,
            ];
        }

        // Calculate from AffiliateEvent (primary source for affiliate commissions)
        $affiliateEarned = AffiliateEvent::where('affiliate_partner_id', $partnerId)
            ->where('is_clawed_back', false)
            ->sum('amount');

        $affiliatePaid = AffiliateEvent::where('affiliate_partner_id', $partnerId)
            ->whereNotNull('paid_at')
            ->where('is_clawed_back', false)
            ->sum('amount');

        // Also include upline commissions if this partner is an upline
        $uplineEarned = AffiliateEvent::where('upline_partner_id', $partnerId)
            ->where('is_clawed_back', false)
            ->sum('upline_amount');

        $uplinePaid = AffiliateEvent::where('upline_partner_id', $partnerId)
            ->whereNotNull('paid_at')
            ->where('is_clawed_back', false)
            ->sum('upline_amount');

        // Calculate from Commission model (legacy/direct commissions)
        $commissionEarned = Commission::where('partner_id', $partnerId)
            ->whereIn('status', [Commission::STATUS_PENDING, Commission::STATUS_APPROVED, Commission::STATUS_PAID])
            ->sum('commission_amount');

        $commissionPaid = Commission::where('partner_id', $partnerId)
            ->where('status', Commission::STATUS_PAID)
            ->sum('commission_amount');

        $totalEarned = (float) $affiliateEarned + (float) $uplineEarned + (float) $commissionEarned;
        $totalPaid = (float) $affiliatePaid + (float) $uplinePaid + (float) $commissionPaid;
        $pending = $totalEarned - $totalPaid;

        // Get effective commission rate
        $rate = $partner->getEffectiveCommissionRate();

        return [
            'earned' => round($totalEarned, 2),
            'paid' => round($totalPaid, 2),
            'pending' => round(max(0, $pending), 2),
            'rate' => $rate,
        ];
    }

    /**
     * Get invoice statistics for a partner's companies.
     *
     * Returns count and total value of all invoices (any status)
     * from companies linked to the partner.
     *
     * @param int $partnerId Partner ID
     * @return array{count: int, total: float}
     */
    public function getInvoiceStats(int $partnerId): array
    {
        $partner = Partner::find($partnerId);

        if (!$partner) {
            Log::warning('PartnerMetricsService: Partner not found', ['partner_id' => $partnerId]);
            return [
                'count' => 0,
                'total' => 0.0,
            ];
        }

        // Get all company IDs linked to this partner (active links only)
        $companyIds = $partner->activeCompanies()->pluck('companies.id')->toArray();

        if (empty($companyIds)) {
            return [
                'count' => 0,
                'total' => 0.0,
            ];
        }

        $stats = Invoice::whereIn('company_id', $companyIds)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();

        return [
            'count' => (int) ($stats->count ?? 0),
            'total' => round(($stats->total ?? 0) / 100, 2), // Convert cents to decimal
        ];
    }

    /**
     * Get activity data for a partner.
     *
     * Returns last login timestamp, last activity timestamp, and active status.
     * Activity is based on the partner's associated user account.
     *
     * @param int $partnerId Partner ID
     * @return array{last_login: string|null, last_activity: string|null, is_active: bool}
     */
    public function getActivityData(int $partnerId): array
    {
        $partner = Partner::with('user')->find($partnerId);

        if (!$partner) {
            Log::warning('PartnerMetricsService: Partner not found', ['partner_id' => $partnerId]);
            return [
                'last_login' => null,
                'last_activity' => null,
                'is_active' => false,
            ];
        }

        $user = $partner->user;

        if (!$user) {
            return [
                'last_login' => null,
                'last_activity' => null,
                'is_active' => $partner->is_active ?? false,
            ];
        }

        // Get last login from user (if tracked)
        $lastLogin = null;
        if (isset($user->last_login_at)) {
            $lastLogin = Carbon::parse($user->last_login_at)->toIso8601String();
        } elseif (isset($user->last_login)) {
            $lastLogin = Carbon::parse($user->last_login)->toIso8601String();
        }

        // Get last activity - use updated_at as proxy for recent activity
        $lastActivity = null;

        // Check for recent invoice activity from partner's companies
        $companyIds = $partner->activeCompanies()->pluck('companies.id')->toArray();

        if (!empty($companyIds)) {
            $lastInvoice = Invoice::whereIn('company_id', $companyIds)
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($lastInvoice) {
                $lastActivity = Carbon::parse($lastInvoice->updated_at)->toIso8601String();
            }
        }

        // If no invoice activity, use user's updated_at
        if (!$lastActivity && $user->updated_at) {
            $lastActivity = Carbon::parse($user->updated_at)->toIso8601String();
        }

        return [
            'last_login' => $lastLogin,
            'last_activity' => $lastActivity,
            'is_active' => (bool) ($partner->is_active ?? false),
        ];
    }

    /**
     * Calculate health score for a partner.
     *
     * Returns a 0-100 score based on:
     * - Login recency (30 pts): Activity within last 30 days
     * - Recent invoice activity (30 pts): Invoices created in last 30 days
     * - Company count (20 pts): Number of active companies
     * - Partner status (20 pts): Is partner active and verified
     *
     * @param int $partnerId Partner ID
     * @return int Health score 0-100
     */
    public function calculateHealthScore(int $partnerId): int
    {
        $partner = Partner::with('user')->find($partnerId);

        if (!$partner) {
            Log::warning('PartnerMetricsService: Partner not found', ['partner_id' => $partnerId]);
            return 0;
        }

        return $this->calculateHealthScoreForPartner($partner);
    }

    /**
     * Get all metrics for a partner.
     *
     * Combines all individual metric methods into a single comprehensive result.
     * Useful for HubSpot sync and dashboard display.
     *
     * @param int $partnerId Partner ID
     * @return array{
     *     partner_id: int,
     *     partner_name: string|null,
     *     partner_email: string|null,
     *     total_revenue: float,
     *     commission: array{earned: float, paid: float, pending: float, rate: float},
     *     invoice_stats: array{count: int, total: float},
     *     activity: array{last_login: string|null, last_activity: string|null, is_active: bool},
     *     health_score: int,
     *     company_count: int,
     *     is_partner_plus: bool,
     *     calculated_at: string
     * }
     */
    public function getAllMetrics(int $partnerId): array
    {
        $partner = Partner::with('user')->find($partnerId);

        if (!$partner) {
            Log::warning('PartnerMetricsService: Partner not found', ['partner_id' => $partnerId]);
            return [
                'partner_id' => $partnerId,
                'partner_name' => null,
                'partner_email' => null,
                'total_revenue' => 0.0,
                'commission' => [
                    'earned' => 0.0,
                    'paid' => 0.0,
                    'pending' => 0.0,
                    'rate' => 0.0,
                ],
                'invoice_stats' => [
                    'count' => 0,
                    'total' => 0.0,
                ],
                'activity' => [
                    'last_login' => null,
                    'last_activity' => null,
                    'is_active' => false,
                ],
                'health_score' => 0,
                'company_count' => 0,
                'is_partner_plus' => false,
                'calculated_at' => Carbon::now()->toIso8601String(),
            ];
        }

        $companyCount = $partner->activeCompanies()->count();

        return [
            'partner_id' => $partnerId,
            'partner_name' => $partner->name ?? $partner->user?->name,
            'partner_email' => $partner->email ?? $partner->user?->email,
            'total_revenue' => $this->getTotalRevenue($partnerId),
            'commission' => $this->getCommissionSummary($partnerId),
            'invoice_stats' => $this->getInvoiceStats($partnerId),
            'activity' => $this->getActivityData($partnerId),
            'health_score' => $this->calculateHealthScore($partnerId),
            'company_count' => $companyCount,
            'is_partner_plus' => $partner->isPartnerPlus(),
            'calculated_at' => Carbon::now()->toIso8601String(),
        ];
    }

    // =========================================================================
    // LEGACY METHODS (Partner object as parameter) - Backward Compatibility
    // =========================================================================

    /**
     * Get comprehensive metrics for a partner.
     *
     * @deprecated Use getAllMetrics(int $partnerId) instead
     * @param Partner $partner
     * @return array
     */
    public function getMetrics(Partner $partner): array
    {
        return [
            // Commission metrics
            'total_revenue' => $this->getTotalRevenueForPartner($partner),
            'commission_rate' => $this->getCommissionRate($partner),
            'commission_earned' => $this->getCommissionEarned($partner),
            'commission_paid' => $this->getCommissionPaid($partner),
            'commission_pending' => $this->getCommissionPending($partner),

            // Activity metrics
            'invoice_count' => $this->getInvoiceCount($partner),
            'total_invoiced' => $this->getTotalInvoiced($partner),
            'last_login_date' => $this->getLastLoginDate($partner),
            'last_activity_date' => $this->getLastActivityDate($partner),

            // Status metrics
            'partner_status' => $this->getPartnerStatus($partner),
            'health_score' => $this->calculateHealthScoreForPartner($partner),
            'company_count' => $this->getCompanyCount($partner),

            // Links
            'facturino_url' => $this->getFacturinoUrl($partner),
        ];
    }

    /**
     * Get total revenue generated by partner's companies.
     *
     * @param Partner $partner
     * @return float
     */
    protected function getTotalRevenueForPartner(Partner $partner): float
    {
        $companyIds = $partner->activeCompanies()->pluck('companies.id');

        if ($companyIds->isEmpty()) {
            return 0;
        }

        return Invoice::whereIn('company_id', $companyIds)
            ->whereIn('paid_status', [Invoice::STATUS_PAID, Invoice::STATUS_PARTIALLY_PAID])
            ->sum('total') / 100; // Convert from cents
    }

    /**
     * Get partner's effective commission rate as percentage.
     *
     * @param Partner $partner
     * @return float
     */
    public function getCommissionRate(Partner $partner): float
    {
        return $partner->getEffectiveCommissionRate() * 100; // Return as percentage
    }

    /**
     * Get total commission earned (all time).
     *
     * @param Partner $partner
     * @return float
     */
    public function getCommissionEarned(Partner $partner): float
    {
        return AffiliateEvent::where('affiliate_partner_id', $partner->id)
            ->where('is_clawed_back', false)
            ->sum('amount');
    }

    /**
     * Get total commission paid out.
     *
     * @param Partner $partner
     * @return float
     */
    public function getCommissionPaid(Partner $partner): float
    {
        return AffiliateEvent::where('affiliate_partner_id', $partner->id)
            ->whereNotNull('paid_at')
            ->where('is_clawed_back', false)
            ->sum('amount');
    }

    /**
     * Get pending (unpaid) commission amount.
     *
     * @param Partner $partner
     * @return float
     */
    public function getCommissionPending(Partner $partner): float
    {
        return $partner->getUnpaidCommissionsTotal();
    }

    /**
     * Get total invoice count for partner's companies.
     *
     * @param Partner $partner
     * @return int
     */
    public function getInvoiceCount(Partner $partner): int
    {
        $companyIds = $partner->activeCompanies()->pluck('companies.id');

        if ($companyIds->isEmpty()) {
            return 0;
        }

        return Invoice::whereIn('company_id', $companyIds)->count();
    }

    /**
     * Get total invoiced amount for partner's companies.
     *
     * @param Partner $partner
     * @return float
     */
    public function getTotalInvoiced(Partner $partner): float
    {
        $companyIds = $partner->activeCompanies()->pluck('companies.id');

        if ($companyIds->isEmpty()) {
            return 0;
        }

        return Invoice::whereIn('company_id', $companyIds)->sum('total') / 100; // Convert from cents
    }

    /**
     * Get partner's last login date.
     *
     * @param Partner $partner
     * @return string|null ISO date string or null
     */
    public function getLastLoginDate(Partner $partner): ?string
    {
        if (!$partner->user) {
            return null;
        }

        $lastLogin = $partner->user->last_login_at ?? $partner->user->updated_at;

        return $lastLogin ? Carbon::parse($lastLogin)->format('Y-m-d') : null;
    }

    /**
     * Get partner's last activity date (last invoice created by their companies).
     *
     * @param Partner $partner
     * @return string|null ISO date string or null
     */
    public function getLastActivityDate(Partner $partner): ?string
    {
        $companyIds = $partner->activeCompanies()->pluck('companies.id');

        if ($companyIds->isEmpty()) {
            return null;
        }

        $lastInvoice = Invoice::whereIn('company_id', $companyIds)
            ->latest('created_at')
            ->first();

        if (!$lastInvoice) {
            return null;
        }

        return Carbon::parse($lastInvoice->created_at)->format('Y-m-d');
    }

    /**
     * Get partner status as a string.
     *
     * @param Partner $partner
     * @return string
     */
    public function getPartnerStatus(Partner $partner): string
    {
        if (!$partner->is_active) {
            return 'inactive';
        }

        if ($partner->kyc_status === 'pending') {
            return 'pending_kyc';
        }

        if ($partner->kyc_status === 'rejected') {
            return 'kyc_rejected';
        }

        if ($partner->isPartnerPlus()) {
            return 'partner_plus';
        }

        return 'active';
    }

    /**
     * Calculate partner health score (0-100) for a Partner object.
     *
     * Factors:
     * - Active companies (30 points max)
     * - Recent activity (30 points max)
     * - Commission earnings (20 points max)
     * - Login frequency (20 points max)
     *
     * @param Partner $partner
     * @return int
     */
    protected function calculateHealthScoreForPartner(Partner $partner): int
    {
        $score = 0;
        $now = Carbon::now();

        // 1. Active companies score (max 30 points)
        $companyCount = $this->getCompanyCount($partner);
        if ($companyCount >= 10) {
            $score += 30;
        } elseif ($companyCount >= 5) {
            $score += 25;
        } elseif ($companyCount >= 3) {
            $score += 20;
        } elseif ($companyCount >= 1) {
            $score += 10;
        }

        // 2. Recent activity score (max 30 points)
        $lastActivityDate = $this->getLastActivityDate($partner);
        if ($lastActivityDate) {
            $daysSinceActivity = Carbon::parse($lastActivityDate)->diffInDays($now);
            if ($daysSinceActivity <= 7) {
                $score += 30;
            } elseif ($daysSinceActivity <= 14) {
                $score += 25;
            } elseif ($daysSinceActivity <= 30) {
                $score += 20;
            } elseif ($daysSinceActivity <= 60) {
                $score += 10;
            } elseif ($daysSinceActivity <= 90) {
                $score += 5;
            }
        }

        // 3. Commission earnings score (max 20 points)
        $totalEarned = $this->getCommissionEarned($partner);
        if ($totalEarned >= 1000) {
            $score += 20;
        } elseif ($totalEarned >= 500) {
            $score += 15;
        } elseif ($totalEarned >= 100) {
            $score += 10;
        } elseif ($totalEarned > 0) {
            $score += 5;
        }

        // 4. Login frequency / Partner status score (max 20 points)
        if ($partner->is_active) {
            $score += 10;
        }

        // KYC verification bonus
        if ($partner->kyc_status === 'approved' || $partner->kyc_status === 'verified') {
            $score += 10;
        } elseif ($partner->kyc_status === 'pending') {
            $score += 5;
        }

        return min(100, $score);
    }

    /**
     * Get count of active companies managed by partner.
     *
     * @param Partner $partner
     * @return int
     */
    public function getCompanyCount(Partner $partner): int
    {
        return $partner->activeCompanies()->count();
    }

    /**
     * Get URL to view partner in Facturino admin.
     *
     * @param Partner $partner
     * @return string
     */
    public function getFacturinoUrl(Partner $partner): string
    {
        $baseUrl = config('app.url', 'https://app.facturino.mk');

        return "{$baseUrl}/admin/partners/{$partner->id}";
    }

    /**
     * Get metrics for multiple partners at once (batch operation).
     *
     * @deprecated Use getBatchMetrics(array $partnerIds) instead
     * @param array $partnerIds
     * @return array Keyed by partner ID
     */
    public function getMetricsBatch(array $partnerIds): array
    {
        $partners = Partner::whereIn('id', $partnerIds)->get();
        $results = [];

        foreach ($partners as $partner) {
            $results[$partner->id] = $this->getMetrics($partner);
        }

        return $results;
    }

    /**
     * Get metrics for multiple partners at once.
     *
     * Useful for batch processing or reporting.
     *
     * @param array<int> $partnerIds Array of partner IDs
     * @return array<int, array> Metrics keyed by partner ID
     */
    public function getBatchMetrics(array $partnerIds): array
    {
        $results = [];

        foreach ($partnerIds as $partnerId) {
            $results[$partnerId] = $this->getAllMetrics($partnerId);
        }

        return $results;
    }

    /**
     * Get metrics for all active partners.
     *
     * @return array<int, array> Metrics for all active partners
     */
    public function getAllActivePartnerMetrics(): array
    {
        $partnerIds = Partner::active()->pluck('id')->toArray();
        return $this->getBatchMetrics($partnerIds);
    }
}

// CLAUDE-CHECKPOINT
