<?php

namespace App\Services;

use App\Models\AffiliateEvent;
use App\Models\Partner;
use App\Models\Payout;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Partner\Controllers\StripeConnectController;
use Stripe\StripeClient;

class PartnerPayoutService
{
    protected ?StripeClient $stripe = null;

    public function __construct()
    {
        if (config('services.stripe.secret')) {
            $this->stripe = new StripeClient(config('services.stripe.secret'));
        }
    }

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
     * Creates a pending payout record - uses Stripe Connect if partner has connected account
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

            // Determine payout method based on partner setup
            $payoutMethod = $partner->stripe_account_id ? 'stripe_connect' : 'bank_transfer';

            // Create payout record
            $payout = Payout::create([
                'partner_id' => $partner->id,
                'amount' => $totalAmount,
                'currency' => 'EUR', // Cross-border payouts to MK in EUR
                'status' => 'pending',
                'payout_date' => Carbon::now()->addDays(5), // 5th of next month
                'payout_method' => $payoutMethod,
                'payment_method' => $partner->payment_method ?? $payoutMethod,
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
                'method' => $payoutMethod,
            ]);

            // Auto-process via Stripe Connect if partner has connected account
            if ($payoutMethod === 'stripe_connect' && $this->stripe) {
                $this->processStripeConnectPayout($payout, $partner);
            }

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
     * Process payout via Stripe Connect (cross-border to Macedonia)
     */
    public function processStripeConnectPayout(Payout $payout, Partner $partner)
    {
        if (!$this->stripe || !$partner->stripe_account_id) {
            Log::warning('Cannot process Stripe Connect payout - missing credentials or account', [
                'payout_id' => $payout->id,
                'partner_id' => $partner->id,
            ]);
            return;
        }

        try {
            // Convert EUR amount to cents
            $amountCents = (int) round($payout->amount * 100);

            // Create transfer to connected account
            $transfer = $this->stripe->transfers->create([
                'amount' => $amountCents,
                'currency' => 'eur', // Cross-border payouts to MK in EUR
                'destination' => $partner->stripe_account_id,
                'description' => "Partner commission payout - {$payout->details['month_ref']}",
                'metadata' => [
                    'payout_id' => $payout->id,
                    'partner_id' => $partner->id,
                    'month_ref' => $payout->details['month_ref'] ?? null,
                    'type' => 'commission_payout',
                ],
            ]);

            // Update payout with Stripe transfer ID
            $payout->update([
                'status' => 'processing',
                'stripe_transfer_id' => $transfer->id,
                'payment_reference' => $transfer->id,
            ]);

            Log::info('Stripe Connect transfer created', [
                'payout_id' => $payout->id,
                'partner_id' => $partner->id,
                'transfer_id' => $transfer->id,
                'amount_cents' => $amountCents,
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe Connect transfer failed', [
                'payout_id' => $payout->id,
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);

            $payout->update([
                'status' => 'failed',
                'details' => array_merge($payout->details ?? [], [
                    'stripe_error' => $e->getMessage(),
                ]),
            ]);
        }
    }

    /**
     * Get pending payouts ready for processing
     */
    public function getPendingPayouts()
    {
        return Payout::where('status', 'pending')
            ->with('partner.user')
            ->get();
    }

    /**
     * Get pending payouts for Stripe Connect processing
     */
    public function getPendingStripeConnectPayouts()
    {
        return Payout::where('status', 'pending')
            ->where('payout_method', 'stripe_connect')
            ->with('partner.user')
            ->get();
    }

    /**
     * Get pending payouts for manual bank transfer
     */
    public function getPendingBankTransferPayouts()
    {
        return Payout::where('status', 'pending')
            ->where('payout_method', 'bank_transfer')
            ->with('partner.user')
            ->get();
    }

    /**
     * Mark payout as processing
     */
    public function markAsProcessing(Payout $payout, string $reference)
    {
        $payout->update([
            'status' => 'processing',
            'payment_reference' => $reference,
        ]);
    }

    /**
     * Mark payout as completed (webhook callback from Stripe)
     */
    public function markAsCompleted(Payout $payout, array $metadata = [])
    {
        $payout->update([
            'status' => 'completed',
            'processed_at' => now(),
            'details' => array_merge($payout->details ?? [], $metadata),
        ]);

        Log::info('Partner payout completed', [
            'payout_id' => $payout->id,
            'partner_id' => $payout->partner_id,
            'amount' => $payout->amount,
            'method' => $payout->payout_method,
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

    /**
     * Handle Stripe transfer.paid webhook event
     * Called when Stripe confirms transfer to connected account
     */
    public function handleStripeTransferPaid(string $transferId)
    {
        $payout = Payout::where('stripe_transfer_id', $transferId)->first();

        if (!$payout) {
            Log::warning('Received transfer.paid webhook for unknown transfer', [
                'transfer_id' => $transferId,
            ]);
            return;
        }

        $this->markAsCompleted($payout, [
            'stripe_confirmed_at' => now()->toISOString(),
        ]);
    }

    /**
     * Handle Stripe transfer.failed webhook event
     */
    public function handleStripeTransferFailed(string $transferId, string $reason)
    {
        $payout = Payout::where('stripe_transfer_id', $transferId)->first();

        if (!$payout) {
            Log::warning('Received transfer.failed webhook for unknown transfer', [
                'transfer_id' => $transferId,
            ]);
            return;
        }

        $this->markAsFailed($payout, "Stripe transfer failed: {$reason}");
    }
}
// CLAUDE-CHECKPOINT
