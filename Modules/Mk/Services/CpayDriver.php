<?php

namespace Modules\Mk\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * CPAY Payment Gateway Driver
 *
 * Handles payment processing for CASYS CPAY payment gateway.
 * Provides functionality for:
 * - Creating checkout URLs with signature verification
 * - Processing payment callbacks
 * - Signature generation and verification
 * - Idempotency checks to prevent duplicate payments
 * - Subscription management (recurring payments)
 *
 * @version 1.1.0
 * @author Claude Code - CPAY Integration Agent
 */
class CpayDriver
{
    /**
     * Create recurring payment agreement for subscription
     *
     * @param \App\Models\Company $company
     * @param string $tier
     * @param float $monthlyPrice
     * @return array Contains 'checkout_url' and 'params'
     * @throws \Exception
     */
    public function createSubscription($company, string $tier, float $monthlyPrice): array
    {
        // Check feature flag
        if (!config('mk.features.advanced_payments', false)) {
            throw new \Exception('Advanced payments feature is disabled');
        }

        // Get CPAY configuration
        $merchantId = config('mk.payment_gateways.cpay.merchant_id');
        $paymentUrl = config('mk.payment_gateways.cpay.subscription_url', config('mk.payment_gateways.cpay.payment_url'));

        if (!$merchantId || !$paymentUrl) {
            throw new \Exception('CPAY configuration is missing');
        }

        // Generate unique subscription reference
        $subscriptionRef = 'SUB-' . $company->id . '-' . time();

        // Build subscription parameters
        $params = [
            'merchant_id' => $merchantId,
            'amount' => number_format($monthlyPrice, 2, '.', ''),
            'currency' => 'MKD',
            'subscription_ref' => $subscriptionRef,
            'company_id' => $company->id,
            'tier' => $tier,
            'interval' => 'monthly',
            'callback_url' => route('cpay.subscription.callback'),
        ];

        // Generate signature
        $params['signature'] = $this->generateSignature($params);

        // Build checkout URL
        $checkoutUrl = $paymentUrl . '?' . http_build_query($params);

        Log::info('CPAY subscription checkout URL generated', [
            'company_id' => $company->id,
            'tier' => $tier,
            'monthly_price' => $monthlyPrice,
            'subscription_ref' => $subscriptionRef,
        ]);

        return [
            'checkout_url' => $checkoutUrl,
            'params' => $params,
            'subscription_ref' => $subscriptionRef,
        ];
    }

    /**
     * Cancel CPAY recurring payment agreement
     *
     * @param string $subscriptionRef CPAY subscription reference
     * @return bool Success status
     * @throws \Exception
     */
    public function cancelSubscription(string $subscriptionRef): bool
    {
        // Check feature flag
        if (!config('mk.features.advanced_payments', false)) {
            throw new \Exception('Advanced payments feature is disabled');
        }

        // Get CPAY API configuration
        $merchantId = config('mk.payment_gateways.cpay.merchant_id');
        $apiUrl = config('mk.payment_gateways.cpay.api_url');

        if (!$merchantId || !$apiUrl) {
            throw new \Exception('CPAY API configuration is missing');
        }

        // Build cancellation request
        $params = [
            'merchant_id' => $merchantId,
            'subscription_ref' => $subscriptionRef,
            'action' => 'cancel',
        ];

        // Generate signature
        $params['signature'] = $this->generateSignature($params);

        try {
            // Send cancellation request to CPAY API
            $response = \Illuminate\Support\Facades\Http::post($apiUrl . '/subscription/cancel', $params);

            if ($response->successful()) {
                Log::info('CPAY subscription cancelled', [
                    'subscription_ref' => $subscriptionRef,
                ]);

                return true;
            }

            Log::error('CPAY subscription cancellation failed', [
                'subscription_ref' => $subscriptionRef,
                'response' => $response->body(),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('CPAY subscription cancellation error', [
                'subscription_ref' => $subscriptionRef,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    } // CLAUDE-CHECKPOINT: Added subscription management methods


    /**
     * Create checkout URL for invoice payment
     *
     * Generates a payment URL with all required parameters and signature.
     * The signature is calculated using SHA256 hash of pipe-delimited values.
     *
     * @param Invoice $invoice The invoice to create payment for
     * @return array Contains 'checkout_url' and 'params' keys
     * @throws \Exception If feature is disabled or configuration is missing
     */
    public function createCheckout(Invoice $invoice): array
    {
        // Check feature flag
        if (!config('mk.features.advanced_payments', false)) {
            throw new \Exception('Advanced payments feature is disabled');
        }

        // Get CPAY configuration
        $merchantId = config('mk.payment_gateways.cpay.merchant_id');
        $paymentUrl = config('mk.payment_gateways.cpay.payment_url');

        if (!$merchantId || !$paymentUrl) {
            throw new \Exception('CPAY configuration is missing');
        }

        // Convert amount from cents to decimal format (e.g., 10000 -> 100.00)
        $amount = number_format($invoice->total / 100, 2, '.', '');

        // Build payment parameters
        $params = [
            'merchant_id' => $merchantId,
            'amount' => $amount,
            'currency' => 'MKD',
            'order_id' => $invoice->invoice_number,
        ];

        // Generate signature
        $params['signature'] = $this->generateSignature($params);

        // Build checkout URL with query parameters
        $checkoutUrl = $paymentUrl . '?' . http_build_query($params);

        Log::info('CPAY checkout URL generated', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'amount' => $amount,
        ]);

        return [
            'checkout_url' => $checkoutUrl,
            'params' => $params,
        ];
    }

    /**
     * Handle payment callback from CPAY
     *
     * Processes the payment callback, verifies signature, checks idempotency,
     * creates payment record, and updates invoice status.
     *
     * @param Request $request The callback request from CPAY
     * @return void
     * @throws \Exception If signature is invalid, feature is disabled, or invoice not found
     */
    public function handleCallback(Request $request): void
    {
        // Check feature flag
        if (!config('mk.features.advanced_payments', false)) {
            throw new \Exception('Advanced payments feature is disabled');
        }

        // Verify signature
        if (!$this->verifySignature($request->all())) {
            Log::error('CPAY callback signature verification failed', [
                'data' => $request->all(),
            ]);
            throw new \Exception('Invalid CPAY signature');
        }

        // Get transaction ID for idempotency check
        $transactionId = $request->input('transaction_id');
        $cacheKey = "cpay_txn_{$transactionId}";

        // Check if transaction was already processed (idempotency)
        if (Cache::has($cacheKey)) {
            Log::info('CPAY callback already processed (idempotent)', [
                'transaction_id' => $transactionId,
            ]);
            return;
        }

        // Mark transaction as processed (7-day cache)
        Cache::put($cacheKey, true, now()->addDays(7));

        // Find invoice by order_id (invoice_number)
        $orderNumber = $request->input('order_id');
        $invoice = Invoice::where('invoice_number', $orderNumber)->firstOrFail();

        // Convert amount from decimal to cents (e.g., 100.00 -> 10000)
        $amount = (int) round((float) $request->input('amount') * 100);

        // Create payment record
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'customer_id' => $invoice->customer_id,
            'user_id' => $invoice->creator_id ?? 1, // Use invoice creator or default user
            'currency_id' => $invoice->currency_id,
            'amount' => $amount,
            'payment_date' => now(),
            'payment_number' => 'CPAY-' . $transactionId,
            'gateway' => Payment::GATEWAY_CPAY,
            'gateway_transaction_id' => $transactionId,
            'gateway_status' => Payment::GATEWAY_STATUS_COMPLETED,
            'gateway_response' => $request->all(),
            'notes' => 'Payment processed via CPAY gateway',
        ]);

        Log::info('CPAY payment created', [
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'transaction_id' => $transactionId,
        ]);

        // Update invoice status to PAID if fully paid
        $totalPaid = $invoice->payments()->sum('amount');
        if ($totalPaid >= $invoice->total) {
            $invoice->status = Invoice::STATUS_PAID;
            $invoice->save();

            Log::info('Invoice marked as PAID', [
                'invoice_id' => $invoice->id,
                'total_paid' => $totalPaid,
                'invoice_total' => $invoice->total,
            ]);
        }
    }

    /**
     * Generate SHA256 signature for CPAY request
     *
     * The signature is calculated by:
     * 1. Sorting parameters alphabetically by key
     * 2. Concatenating values with pipe delimiter
     * 3. Appending secret key
     * 4. Generating SHA256 hash
     *
     * Note: 'signature' and 'timestamp' fields are excluded from signature calculation
     *
     * @param array $params Payment parameters
     * @return string SHA256 hash signature
     */
    public function generateSignature(array $params): string
    {
        $secretKey = config('mk.payment_gateways.cpay.secret_key');

        if (!$secretKey) {
            throw new \Exception('CPAY secret key is not configured');
        }

        // Prepare data for signature (exclude signature and timestamp fields)
        $signatureData = $params;
        unset($signatureData['signature']);
        unset($signatureData['timestamp']);

        // Sort alphabetically by key
        ksort($signatureData);

        // Build signature string with pipe delimiter
        $signatureString = implode('|', $signatureData) . '|' . $secretKey;

        // Generate SHA256 hash
        return hash('sha256', $signatureString);
    }

    /**
     * Verify signature from CPAY callback
     *
     * Compares the received signature with the expected signature
     * using timing-safe comparison to prevent timing attacks.
     *
     * @param array $data Callback data including signature
     * @return bool True if signature is valid, false otherwise
     */
    public function verifySignature(array $data): bool
    {
        // Extract received signature
        $receivedSignature = $data['signature'] ?? '';

        if (empty($receivedSignature)) {
            return false;
        }

        // Generate expected signature
        $expectedSignature = $this->generateSignature($data);

        // Use timing-safe comparison
        return hash_equals($expectedSignature, $receivedSignature);
    }
}
// CLAUDE-CHECKPOINT
