<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Paddle\Cashier;
use Laravel\Paddle\Paddle;
use Laravel\Pennant\Feature;

/**
 * Paddle Payment Service
 *
 * Integrates Paddle payment gateway for international invoice payments.
 * Handles checkout creation, webhook processing, and payment recording.
 *
 * Features:
 * - Checkout URL generation for invoice payments
 * - Webhook signature verification
 * - Idempotent webhook processing (7-day cache)
 * - Automatic invoice status updates
 * - Optional accounting ledger integration
 * - Fee tracking and deduction
 *
 * @version 1.0.0
 * @ticket B-31 series - Paddle Payment Integration
 * @author Claude Code - Paddle agent
 */
class PaddlePaymentService
{
    /**
     * Create a Paddle checkout session for an invoice
     *
     * @param Invoice $invoice
     * @return array ['checkout_url' => string]
     * @throws \Exception
     */
    public function createCheckout(Invoice $invoice): array
    {
        if (!config('services.paddle.api_key')) {
            throw new \Exception('Paddle API key not configured');
        }

        $paddle = new \Paddle\PaddleClient(
            config('services.paddle.api_key'),
            config('services.paddle.environment', 'sandbox')
        );

        try {
            $checkout = $paddle->checkouts->create([
                'items' => [[
                    'price_id' => config('services.paddle.price_id'),
                    'quantity' => 1,
                ]],
                'custom_data' => [
                    'invoice_id' => $invoice->id,
                    'company_id' => $invoice->company_id,
                ],
                'customer_email' => $invoice->customer->email,
            ]);

            Log::info('Paddle checkout created', [
                'invoice_id' => $invoice->id,
                'checkout_id' => $checkout->id,
            ]);

            return ['checkout_url' => $checkout->url];
        } catch (\Exception $e) {
            Log::error('Paddle checkout creation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle incoming Paddle webhook
     *
     * Verifies signature, enforces idempotency, and routes to event handlers
     *
     * @param array $payload
     * @param string $signature
     * @return void
     * @throws \Exception
     */
    public function handleWebhook(array $payload, string $signature): void
    {
        // Verify signature
        $this->verifySignature($payload, $signature);

        // Idempotency check
        $eventId = $payload['event_id'] ?? null;
        if (!$eventId) {
            Log::warning('Paddle webhook missing event_id');
            return;
        }

        $cacheKey = "paddle_event_{$eventId}";
        if (Cache::has($cacheKey)) {
            Log::info('Paddle webhook already processed (idempotent)', ['event_id' => $eventId]);
            return; // Already processed
        }

        // Mark as processed (7 days cache)
        Cache::put($cacheKey, true, now()->addDays(7));

        // Process event
        $eventType = $payload['event_type'] ?? null;
        match($eventType) {
            'transaction.completed' => $this->handleTransactionCompleted($payload),
            'transaction.payment_failed' => $this->handlePaymentFailed($payload),
            default => Log::info("Unhandled Paddle event: {$eventType}", ['event_id' => $eventId]),
        };
    }

    /**
     * Verify Paddle webhook signature
     *
     * @param array $payload
     * @param string $signature
     * @return void
     * @throws \Exception
     */
    private function verifySignature(array $payload, string $signature): void
    {
        $webhookSecret = config('services.paddle.webhook_secret');

        if (!$webhookSecret) {
            throw new \Exception('Paddle webhook secret not configured');
        }

        // Paddle uses HMAC SHA256 for webhook verification
        $computedSignature = hash_hmac('sha256', json_encode($payload), $webhookSecret);

        if (!hash_equals($computedSignature, $signature)) {
            Log::warning('Paddle webhook signature verification failed', [
                'expected' => substr($computedSignature, 0, 10) . '...',
                'received' => substr($signature, 0, 10) . '...',
            ]);
            throw new \Exception('Invalid webhook signature');
        }

        Log::debug('Paddle webhook signature verified');
    }

    /**
     * Handle transaction.completed event
     *
     * Creates payment record, deducts fee, updates invoice status,
     * and optionally posts to accounting ledger
     *
     * @param array $payload
     * @return void
     */
    private function handleTransactionCompleted(array $payload): void
    {
        try {
            $invoiceId = $payload['data']['custom_data']['invoice_id'] ?? null;
            $transactionId = $payload['data']['id'] ?? null;

            if (!$invoiceId || !$transactionId) {
                Log::error('Paddle transaction missing invoice_id or transaction_id', ['payload' => $payload]);
                return;
            }

            $invoice = Invoice::find($invoiceId);
            if (!$invoice) {
                Log::error('Invoice not found for Paddle payment', ['invoice_id' => $invoiceId]);
                return;
            }

            // Extract amounts (Paddle sends amounts in cents)
            $gross = ($payload['data']['details']['totals']['total'] ?? 0) / 100;
            $fee = ($payload['data']['details']['totals']['fee'] ?? 0) / 100;
            $net = $gross - $fee;

            // Create payment record
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'company_id' => $invoice->company_id,
                'customer_id' => $invoice->customer_id,
                'amount' => $net, // Net amount after fee deduction
                'payment_date' => now(),
                'payment_number' => 'PADDLE-' . $transactionId,
                'payment_method_id' => $this->getPaddlePaymentMethodId($invoice->company_id),
                'currency_id' => $invoice->currency_id,
                'exchange_rate' => $invoice->exchange_rate,
                'base_amount' => $net * $invoice->exchange_rate,
                'notes' => "Paddle payment. Gross: {$gross}, Fee: {$fee}, Net: {$net}",
                'gateway' => Payment::GATEWAY_PADDLE,
                'gateway_transaction_id' => $transactionId,
                'gateway_data' => $payload['data'] ?? [],
                'gateway_status' => Payment::GATEWAY_STATUS_COMPLETED,
            ]);

            Log::info('Paddle payment created', [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'gross' => $gross,
                'fee' => $fee,
                'net' => $net,
            ]);

            // Update invoice
            $invoice->subtractInvoicePayment($payment->amount);

            // Post to accounting if enabled
            if (Feature::active('accounting-backbone')) {
                $this->postToLedger($payment, $fee);
            }

            Log::info('Paddle transaction completed', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'invoice_status' => $invoice->fresh()->paid_status,
            ]);
        } catch (\Exception $e) {
            Log::error('Paddle transaction processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }

    /**
     * Handle transaction.payment_failed event
     *
     * @param array $payload
     * @return void
     */
    private function handlePaymentFailed(array $payload): void
    {
        $invoiceId = $payload['data']['custom_data']['invoice_id'] ?? null;
        $transactionId = $payload['data']['id'] ?? null;

        Log::warning('Paddle payment failed', [
            'invoice_id' => $invoiceId,
            'transaction_id' => $transactionId,
            'error' => $payload['data']['error'] ?? 'Unknown error',
        ]);

        // Could send notification to customer/admin here
    }

    /**
     * Post payment and fee to accounting ledger
     *
     * Only called when FEATURE_ACCOUNTING_BACKBONE is enabled
     *
     * @param Payment $payment
     * @param float $fee
     * @return void
     */
    private function postToLedger(Payment $payment, float $fee): void
    {
        try {
            // Check if IfrsAdapter exists (Step 1 implementation)
            if (!class_exists('App\Services\Accounting\IfrsAdapter')) {
                Log::warning('IfrsAdapter not found, skipping ledger posting');
                return;
            }

            $adapter = app(\App\Services\Accounting\IfrsAdapter::class);

            // Post payment to ledger
            if (method_exists($adapter, 'postPayment')) {
                $adapter->postPayment($payment);
            }

            // Post fee as expense
            if (method_exists($adapter, 'postFee')) {
                $adapter->postFee($payment, $fee);
            }

            Log::info('Paddle payment posted to ledger', [
                'payment_id' => $payment->id,
                'fee' => $fee,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to post Paddle payment to ledger', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - payment was successful even if ledger posting failed
        }
    }

    /**
     * Get or create Paddle payment method for company
     *
     * @param int $companyId
     * @return int|null
     */
    private function getPaddlePaymentMethodId(int $companyId): ?int
    {
        $paymentMethod = \App\Models\PaymentMethod::firstOrCreate(
            [
                'name' => 'Paddle',
                'company_id' => $companyId,
            ],
            [
                'name' => 'Paddle',
                'company_id' => $companyId,
            ]
        );

        return $paymentMethod->id;
    }
}

// CLAUDE-CHECKPOINT
