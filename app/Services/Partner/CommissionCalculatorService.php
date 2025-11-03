<?php

namespace App\Services\Partner;

use App\Models\Commission;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Commission Calculator Service
 *
 * Handles commission calculation logic for partner portal.
 * Provides statistics and commission calculation methods.
 *
 * @package App\Services\Partner
 */
class CommissionCalculatorService
{
    /**
     * Get partner statistics
     *
     * Calculates real-time stats for a partner including:
     * - Active clients count
     * - Monthly commissions total
     * - Processed invoices count
     * - Pending payout amount
     * - Total earned (all time)
     *
     * @param Partner $partner
     * @return array
     */
    public function getStats(Partner $partner): array
    {
        $stats = [
            'active_clients' => $this->getActiveClientsCount($partner),
            'monthly_commissions' => $this->getMonthlyCommissions($partner),
            'processed_invoices' => $this->getProcessedInvoicesCount($partner),
            'commission_rate' => (float) $partner->commission_rate,
            'pending_payout' => $this->getPendingPayout($partner),
            'total_earned' => $this->getTotalEarned($partner),
        ];

        Log::info('Partner stats calculated', [
            'partner_id' => $partner->id,
            'stats' => $stats
        ]);

        return $stats;
    }

    /**
     * Calculate commission for an invoice
     *
     * Uses partner's commission rate or override rate from partner-company link.
     * Default rate is 5% (0.05) if not specified.
     *
     * @param Invoice $invoice
     * @param float|null $rate Override commission rate (optional)
     * @return float Commission amount in cents
     */
    public function calculateCommission(Invoice $invoice, ?float $rate = null): float
    {
        // Get partner for this invoice's company
        $partner = $this->getPartnerForInvoice($invoice);

        if (!$partner) {
            Log::warning('Cannot calculate commission: no partner for invoice', [
                'invoice_id' => $invoice->id,
                'company_id' => $invoice->company_id
            ]);
            return 0.0;
        }

        // Use provided rate, or get from partner-company link, or fall back to partner default
        if ($rate === null) {
            $rate = $this->getCommissionRate($partner, $invoice->company_id);
        }

        // Calculate commission: invoice total * rate
        // Invoice total is stored in cents
        $commissionAmount = $invoice->total * ($rate / 100);

        Log::info('Commission calculated', [
            'invoice_id' => $invoice->id,
            'partner_id' => $partner->id,
            'invoice_total' => $invoice->total,
            'rate' => $rate,
            'commission_amount' => $commissionAmount
        ]);

        return $commissionAmount;
    }

    /**
     * Create commission record for a paid invoice
     *
     * Called when an invoice is paid to create the commission record.
     * Only creates commission if partner exists and is active.
     *
     * @param Invoice $invoice
     * @param Payment $payment
     * @return Commission|null
     */
    public function createCommissionForPayment(Invoice $invoice, Payment $payment): ?Commission
    {
        $partner = $this->getPartnerForInvoice($invoice);

        if (!$partner || !$partner->is_active) {
            return null;
        }

        $rate = $this->getCommissionRate($partner, $invoice->company_id);
        $amount = $this->calculateCommission($invoice, $rate);

        $commission = Commission::create([
            'partner_id' => $partner->id,
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
            'commission_type' => Commission::TYPE_PAYMENT,
            'base_amount' => $invoice->total,
            'commission_rate' => $rate,
            'commission_amount' => $amount,
            'currency_id' => $invoice->currency_id,
            'status' => Commission::STATUS_PENDING,
            'notes' => "Commission for invoice {$invoice->invoice_number}",
        ]);

        Log::info('Commission created for payment', [
            'commission_id' => $commission->id,
            'partner_id' => $partner->id,
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
            'amount' => $amount
        ]);

        return $commission;
    }

    /**
     * Get active clients count for partner
     *
     * @param Partner $partner
     * @return int
     */
    protected function getActiveClientsCount(Partner $partner): int
    {
        return Company::whereHas('partnerLinks', function ($query) use ($partner) {
            $query->where('partner_id', $partner->id)
                  ->where('is_active', true);
        })->count();
    }

    /**
     * Get monthly commissions total (current month)
     *
     * @param Partner $partner
     * @return float
     */
    protected function getMonthlyCommissions(Partner $partner): float
    {
        return Commission::where('partner_id', $partner->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('commission_amount') ?? 0.0;
    }

    /**
     * Get processed invoices count
     *
     * @param Partner $partner
     * @return int
     */
    protected function getProcessedInvoicesCount(Partner $partner): int
    {
        return Invoice::whereHas('company.partnerLinks', function ($query) use ($partner) {
            $query->where('partner_id', $partner->id)
                  ->where('is_active', true);
        })->count();
    }

    /**
     * Get pending payout amount (approved but not paid commissions)
     *
     * @param Partner $partner
     * @return float
     */
    protected function getPendingPayout(Partner $partner): float
    {
        return Commission::where('partner_id', $partner->id)
            ->whereIn('status', [Commission::STATUS_PENDING, Commission::STATUS_APPROVED])
            ->sum('commission_amount') ?? 0.0;
    }

    /**
     * Get total earned amount (all time paid commissions)
     *
     * @param Partner $partner
     * @return float
     */
    protected function getTotalEarned(Partner $partner): float
    {
        return Commission::where('partner_id', $partner->id)
            ->where('status', Commission::STATUS_PAID)
            ->sum('commission_amount') ?? 0.0;
    }

    /**
     * Get partner for an invoice
     *
     * @param Invoice $invoice
     * @return Partner|null
     */
    protected function getPartnerForInvoice(Invoice $invoice): ?Partner
    {
        // Get partner through company's partner links
        $partnerLink = DB::table('partner_company_links')
            ->where('company_id', $invoice->company_id)
            ->where('is_active', true)
            ->first();

        if (!$partnerLink) {
            return null;
        }

        return Partner::find($partnerLink->partner_id);
    }

    /**
     * Get commission rate for partner-company relationship
     *
     * @param Partner $partner
     * @param int $companyId
     * @return float
     */
    protected function getCommissionRate(Partner $partner, int $companyId): float
    {
        // Check for override rate in partner-company link
        $partnerLink = DB::table('partner_company_links')
            ->where('partner_id', $partner->id)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if ($partnerLink && $partnerLink->override_commission_rate !== null) {
            return (float) $partnerLink->override_commission_rate;
        }

        // Fall back to partner's default rate
        return (float) $partner->commission_rate ?? 5.0;
    }
}

// CLAUDE-CHECKPOINT
