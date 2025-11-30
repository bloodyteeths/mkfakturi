<?php

namespace App\Services;

use App\Models\AffiliateEvent;
use App\Models\Partner;
use App\Models\Payout;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartnerPayoutService
{
    /**
     * Process payouts for all eligible partners
     * Run this as a scheduled job (5th of each month)
     */
    public function processMonthlyPayouts()
    {
        // Get all partners with pending commissions from 30+ days ago
        $cutoffDate = Carbon::now()->subDays(30);

        $partners = Partner::whereHas('affiliateEvents', function ($query) use ($cutoffDate) {
            $query->where('paid_at', '<=', $cutoffDate)
                ->whereNull('payout_id')
                ->where('is_clawed_back', false);
        })->get();

        $payouts = [];
        foreach ($partners as $partner) {
            $payout = $this->processPartnerPayout($partner, $cutoffDate);
            if ($payout) {
                $payouts[] = $payout;
            }
        }

        return $payouts;
    }

    /**
     * Process payout for a single partner
     * Creates a pending payout record that will be processed via Wise
     */
    public function processPartnerPayout(Partner $partner, Carbon $cutoffDate)
    {
        DB::beginTransaction();

        try {
            // Get unpaid events older than 30 days
            $events = AffiliateEvent::where('partner_id', $partner->id)
                ->where('paid_at', '<=', $cutoffDate)
                ->whereNull('payout_id')
                ->where('is_clawed_back', false)
                ->get();

            if ($events->isEmpty()) {
                DB::rollBack();
                return null;
            }

            $totalAmount = $events->sum('amount');
            $monthRef = Carbon::now()->subMonth()->format('Y-m');

            // Create payout record (will be processed via Wise)
            $payout = Payout::create([
                'partner_id' => $partner->id,
                'amount' => $totalAmount,
                'currency' => 'EUR', // Wise transfers in EUR
                'status' => 'pending',
                'payout_date' => Carbon::now()->addDays(5), // 5th of next month
                'payout_method' => 'wise', // Using Wise for all partner payouts
                'payment_method' => $partner->payment_method ?? 'wise',
                'payment_reference' => null,
                'details' => [
                    'month_ref' => $monthRef,
                    'event_count' => $events->count(),
                    'commission_rate' => $partner->commission_rate,
                    'original_currency' => 'MKD',
                    'original_amount' => $totalAmount,
                ],
            ]);

            // Link events to payout
            $events->each->update(['payout_id' => $payout->id]);

            DB::commit();

            Log::info('Partner payout created', [
                'payout_id' => $payout->id,
                'partner_id' => $partner->id,
                'amount' => $totalAmount,
                'month_ref' => $monthRef,
            ]);

            return $payout;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Partner payout failed', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get pending payouts ready for Wise batch transfer
     */
    public function getPendingPayouts()
    {
        return Payout::where('status', 'pending')
            ->where('payout_method', 'wise')
            ->with('partner.user')
            ->get();
    }

    /**
     * Mark payout as processing (when Wise transfer is initiated)
     */
    public function markAsProcessing(Payout $payout, string $wiseTransferId)
    {
        $payout->update([
            'status' => 'processing',
            'payment_reference' => $wiseTransferId,
        ]);
    }

    /**
     * Mark payout as completed (when Wise transfer is confirmed)
     */
    public function markAsCompleted(Payout $payout, array $wiseMetadata = [])
    {
        $payout->update([
            'status' => 'completed',
            'processed_at' => now(),
            'details' => array_merge($payout->details ?? [], ['wise' => $wiseMetadata]),
        ]);

        Log::info('Partner payout completed via Wise', [
            'payout_id' => $payout->id,
            'partner_id' => $payout->partner_id,
            'amount' => $payout->amount,
        ]);
    }

    /**
     * Mark payout as failed
     */
    public function markAsFailed(Payout $payout, string $reason)
    {
        $payout->update([
            'status' => 'failed',
            'details' => array_merge($payout->details ?? [], ['failure_reason' => $reason]),
        ]);

        Log::error('Partner payout failed', [
            'payout_id' => $payout->id,
            'partner_id' => $payout->partner_id,
            'reason' => $reason,
        ]);
    }
}
