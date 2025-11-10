<?php

namespace App\Observers;

use App\Models\Payment;
use App\Domain\Accounting\IfrsAdapter;
use Illuminate\Support\Facades\Log;

/**
 * Payment Observer
 *
 * Automatically posts payment transactions to the IFRS ledger
 * when FEATURE_ACCOUNTING_BACKBONE is enabled.
 *
 * @package App\Observers
 */
class PaymentObserver
{
    protected IfrsAdapter $ifrsAdapter;

    public function __construct(IfrsAdapter $ifrsAdapter)
    {
        $this->ifrsAdapter = $ifrsAdapter;
    }

    /**
     * Handle the Payment "created" event.
     *
     * Post to ledger only when payment is marked as COMPLETED
     *
     * @param Payment $payment
     * @return void
     */
    public function created(Payment $payment): void
    {
        if ($this->shouldPostToLedger($payment)) {
            try {
                $this->ifrsAdapter->postPayment($payment);

                // If there's a gateway fee, post it as well
                if ($this->hasGatewayFee($payment)) {
                    $fee = $this->calculateGatewayFee($payment);
                    $this->ifrsAdapter->postFee($payment, $fee);
                }
            } catch (\Exception $e) {
                Log::error('PaymentObserver: Failed to post payment to ledger', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't throw - we don't want to block payment creation
            }
        }
    }

    /**
     * Handle the Payment "updating" event.
     *
     * Prevent updates if the payment falls within a locked tax period.
     *
     * @param Payment $payment
     * @return bool|null
     */
    public function updating(Payment $payment): ?bool
    {
        // Check if payment date falls within a locked tax period
        if ($this->isInLockedPeriod($payment)) {
            throw new \Exception('Cannot edit payment. Tax period is locked.');
        }

        return true;
    }

    /**
     * Handle the Payment "updated" event.
     *
     * If status changes to COMPLETED, post to ledger (idempotent).
     *
     * @param Payment $payment
     * @return void
     */
    public function updated(Payment $payment): void
    {
        // Check if status changed to COMPLETED
        if ($payment->wasChanged('gateway_status') &&
            $payment->gateway_status === Payment::GATEWAY_STATUS_COMPLETED &&
            $this->shouldPostToLedger($payment) &&
            !$payment->ifrs_transaction_id) {

            try {
                $this->ifrsAdapter->postPayment($payment);

                // If there's a gateway fee, post it as well
                if ($this->hasGatewayFee($payment)) {
                    $fee = $this->calculateGatewayFee($payment);
                    $this->ifrsAdapter->postFee($payment, $fee);
                }
            } catch (\Exception $e) {
                Log::error('PaymentObserver: Failed to post updated payment to ledger', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the Payment "deleting" event.
     *
     * Prevent deletion if the payment falls within a locked tax period.
     *
     * @param Payment $payment
     * @return bool|null
     */
    public function deleting(Payment $payment): ?bool
    {
        // Check if payment date falls within a locked tax period
        if ($this->isInLockedPeriod($payment)) {
            throw new \Exception('Cannot delete payment. Tax period is locked.');
        }

        return true;
    }

    /**
     * Determine if payment should be posted to ledger
     *
     * @param Payment $payment
     * @return bool
     */
    protected function shouldPostToLedger(Payment $payment): bool
    {
        // Check if feature is enabled
        if (!$this->isFeatureEnabled()) {
            return false;
        }

        // Only post completed payments
        return $payment->gateway_status === Payment::GATEWAY_STATUS_COMPLETED ||
               $payment->status === Payment::STATUS_COMPLETED;
    }

    /**
     * Check if payment has a gateway fee
     *
     * @param Payment $payment
     * @return bool
     */
    protected function hasGatewayFee(Payment $payment): bool
    {
        // Check if gateway response contains fee information
        if (isset($payment->gateway_response['fee'])) {
            return true;
        }

        // Check for CPAY or Paddle gateways which typically charge fees
        return in_array($payment->gateway, [
            Payment::GATEWAY_CPAY,
            Payment::GATEWAY_PADDLE,
        ]);
    }

    /**
     * Calculate gateway fee from payment data
     *
     * @param Payment $payment
     * @return float Fee amount in cents
     */
    protected function calculateGatewayFee(Payment $payment): float
    {
        // If fee is explicitly in gateway response, use it
        if (isset($payment->gateway_response['fee'])) {
            return (float) $payment->gateway_response['fee'];
        }

        // Otherwise, estimate based on gateway
        // CPAY typically charges 2.5% + 25 MKD
        if ($payment->gateway === Payment::GATEWAY_CPAY) {
            return ($payment->amount * 0.025) + 2500; // 25 MKD = 2500 cents
        }

        // Paddle typically charges 5% + $0.50
        if ($payment->gateway === Payment::GATEWAY_PADDLE) {
            return ($payment->amount * 0.05) + 50; // $0.50 = 50 cents
        }

        return 0;
    }

    /**
     * Check if accounting backbone feature is enabled
     *
     * @return bool
     */
    protected function isFeatureEnabled(): bool
    {
        // Check Laravel Pennant feature flag or config
        if (function_exists('feature')) {
            return feature('accounting_backbone');
        }

        return config('ifrs.enabled', false) ||
               env('FEATURE_ACCOUNTING_BACKBONE', false);
    }

    /**
     * Check if payment falls within a locked tax period.
     *
     * @param Payment $payment
     * @return bool
     */
    protected function isInLockedPeriod(Payment $payment): bool
    {
        // Check if tax period locking is enabled
        if (!config('tax.period_locking_enabled', true)) {
            return false;
        }

        // Find locked periods that contain this payment date
        $lockedPeriod = \App\Models\TaxReportPeriod::where('company_id', $payment->company_id)
            ->where('start_date', '<=', $payment->payment_date)
            ->where('end_date', '>=', $payment->payment_date)
            ->where(function ($query) {
                $query->where('status', \App\Models\TaxReportPeriod::STATUS_CLOSED)
                    ->orWhere('status', \App\Models\TaxReportPeriod::STATUS_FILED)
                    ->orWhere('status', \App\Models\TaxReportPeriod::STATUS_AMENDED);
            })
            ->exists();

        return $lockedPeriod;
    }
}

// CLAUDE-CHECKPOINT
