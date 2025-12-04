<?php

namespace App\Jobs;

use App\Models\BankTransaction;
use App\Models\GatewayWebhookEvent;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public GatewayWebhookEvent $event
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if already processed (idempotency)
        if ($this->event->status === 'processed') {
            Log::info("Webhook event {$this->event->id} already processed, skipping");

            return;
        }

        try {
            // Route to appropriate handler based on provider
            $handler = match ($this->event->provider) {
                'paddle' => $this->handlePaddle(...),
                'cpay' => $this->handleCpay(...),
                'nlb' => $this->handleBankNlb(...),
                'stopanska' => $this->handleBankStopanska(...),
                'stripe' => $this->handleStripe(...),
                default => throw new \Exception("Unknown provider: {$this->event->provider}"),
            };

            // Call the handler
            $handler();

            // Mark as processed
            $this->event->markAsProcessed();

            Log::info("Webhook event {$this->event->id} processed successfully", [
                'provider' => $this->event->provider,
                'event_type' => $this->event->event_type,
            ]);
        } catch (\Exception $e) {
            // Mark as failed
            $this->event->markAsFailed($e->getMessage());

            Log::error("Webhook event {$this->event->id} processing failed", [
                'provider' => $this->event->provider,
                'event_type' => $this->event->event_type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Retry if possible
            if ($this->event->canRetry()) {
                throw $e;
            }
        }
    }

    /**
     * Handle Paddle webhook events
     */
    protected function handlePaddle(): void
    {
        $payload = $this->event->payload;
        $eventType = $payload['event_type'] ?? null;

        match ($eventType) {
            'transaction.completed' => $this->handlePaddleTransactionCompleted($payload),
            'transaction.payment_failed' => $this->handlePaddlePaymentFailed($payload),
            'subscription.created' => $this->handlePaddleSubscriptionCreated($payload),
            'subscription.cancelled' => $this->handlePaddleSubscriptionCancelled($payload),
            default => Log::info("Unhandled Paddle event: {$eventType}"),
        };
    }

    /**
     * Handle Paddle transaction.completed event
     */
    protected function handlePaddleTransactionCompleted(array $payload): void
    {
        $invoiceId = $payload['data']['custom_data']['invoice_id'] ?? null;

        if (! $invoiceId) {
            throw new \Exception('Missing invoice_id in Paddle webhook');
        }

        $invoice = Invoice::find($invoiceId);
        if (! $invoice) {
            throw new \Exception("Invoice {$invoiceId} not found");
        }

        // Extract payment details
        $gross = ($payload['data']['details']['totals']['total'] ?? 0) / 100;
        $fee = ($payload['data']['details']['totals']['fee'] ?? 0) / 100;
        $net = $gross - $fee;

        // Create payment
        Payment::create([
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'amount' => $net,
            'payment_date' => now(),
            'payment_number' => 'PADDLE-'.($payload['data']['id'] ?? uniqid()),
            'payment_method' => 'CREDIT_CARD',
            'notes' => "Paddle payment. Fee: {$fee} MKD",
        ]);

        Log::info("Paddle payment created for invoice {$invoiceId}");
    }

    /**
     * Handle Paddle payment failed event
     */
    protected function handlePaddlePaymentFailed(array $payload): void
    {
        $invoiceId = $payload['data']['custom_data']['invoice_id'] ?? null;

        if ($invoiceId) {
            Log::warning("Paddle payment failed for invoice {$invoiceId}");
        }
    }

    /**
     * Handle Paddle subscription created event
     */
    protected function handlePaddleSubscriptionCreated(array $payload): void
    {
        Log::info('Paddle subscription created', ['payload' => $payload]);
    }

    /**
     * Handle Paddle subscription cancelled event
     */
    protected function handlePaddleSubscriptionCancelled(array $payload): void
    {
        Log::info('Paddle subscription cancelled', ['payload' => $payload]);
    }

    /**
     * Handle CPAY webhook events
     */
    protected function handleCpay(): void
    {
        $payload = $this->event->payload;
        $status = $payload['status'] ?? null;

        match ($status) {
            'success', 'completed' => $this->handleCpaySuccess($payload),
            'failed' => $this->handleCpayFailed($payload),
            'cancelled' => $this->handleCpayCancelled($payload),
            default => Log::info("Unhandled CPAY status: {$status}"),
        };
    }

    /**
     * Handle CPAY successful payment
     */
    protected function handleCpaySuccess(array $payload): void
    {
        $orderReference = $payload['order_id'] ?? null;

        if (! $orderReference) {
            throw new \Exception('Missing order_id in CPAY webhook');
        }

        // Find invoice by invoice_number
        $invoice = Invoice::where('invoice_number', $orderReference)
            ->where('company_id', $this->event->company_id)
            ->first();

        if (! $invoice) {
            throw new \Exception("Invoice with number {$orderReference} not found");
        }

        // Create payment
        Payment::create([
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'amount' => $payload['amount'] ?? 0,
            'payment_date' => now(),
            'payment_number' => 'CPAY-'.($payload['transaction_id'] ?? uniqid()),
            'payment_method' => 'CREDIT_CARD',
            'transaction_reference' => $payload['transaction_id'] ?? null,
        ]);

        Log::info("CPAY payment created for invoice {$invoice->id}");
    }

    /**
     * Handle CPAY failed payment
     */
    protected function handleCpayFailed(array $payload): void
    {
        $orderReference = $payload['order_id'] ?? null;
        Log::warning("CPAY payment failed for order {$orderReference}");
    }

    /**
     * Handle CPAY cancelled payment
     */
    protected function handleCpayCancelled(array $payload): void
    {
        $orderReference = $payload['order_id'] ?? null;
        Log::info("CPAY payment cancelled for order {$orderReference}");
    }

    /**
     * Handle NLB bank webhook events
     */
    protected function handleBankNlb(): void
    {
        $payload = $this->event->payload;
        $eventType = $payload['event_type'] ?? 'transaction.created';

        match ($eventType) {
            'transaction.created' => $this->handleBankTransaction($payload, 'nlb'),
            default => Log::info("Unhandled NLB event: {$eventType}"),
        };
    }

    /**
     * Handle Stopanska bank webhook events
     */
    protected function handleBankStopanska(): void
    {
        $payload = $this->event->payload;
        $eventType = $payload['event_type'] ?? 'transaction.created';

        match ($eventType) {
            'transaction.created' => $this->handleBankTransaction($payload, 'stopanska'),
            default => Log::info("Unhandled Stopanska event: {$eventType}"),
        };
    }

    /**
     * Handle bank transaction webhook
     */
    protected function handleBankTransaction(array $payload, string $provider): void
    {
        $transactionData = $payload['transaction'] ?? $payload;

        // Create or update bank transaction
        BankTransaction::updateOrCreate(
            [
                'company_id' => $this->event->company_id,
                'external_reference' => $transactionData['transaction_id'] ?? null,
            ],
            [
                'bank_account_id' => $transactionData['account_id'] ?? null,
                'amount' => $transactionData['amount'] ?? 0,
                'currency' => $transactionData['currency'] ?? 'MKD',
                'transaction_type' => $transactionData['type'] ?? 'credit',
                'transaction_date' => $transactionData['date'] ?? now(),
                'description' => $transactionData['description'] ?? null,
                'creditor_name' => $transactionData['creditor_name'] ?? null,
                'debtor_name' => $transactionData['debtor_name'] ?? null,
                'source' => 'webhook',
            ]
        );

        Log::info("Bank transaction created from {$provider} webhook");
    }

    /**
     * Handle Stripe webhook events
     */
    protected function handleStripe(): void
    {
        $payload = $this->event->payload;
        $eventType = $this->event->event_type;

        match ($eventType) {
            'payment_intent.succeeded' => $this->handleStripePaymentSucceeded($payload),
            'payment_intent.payment_failed' => $this->handleStripePaymentFailed($payload),
            'checkout.session.completed' => $this->handleStripeCheckoutCompleted($payload),
            'invoice.paid' => $this->handleStripeInvoicePaid($payload),
            'invoice.payment_failed' => $this->handleStripeInvoicePaymentFailed($payload),
            'charge.succeeded' => $this->handleStripeChargeSucceeded($payload),
            'charge.failed' => $this->handleStripeChargeFailed($payload),
            default => Log::info("Unhandled Stripe event: {$eventType}", ['event_id' => $this->event->event_id]),
        };
    }

    /**
     * Handle Stripe payment_intent.succeeded event
     */
    protected function handleStripePaymentSucceeded(array $payload): void
    {
        $paymentIntent = $payload['data']['object'] ?? [];
        $metadata = $paymentIntent['metadata'] ?? [];

        Log::info('Stripe payment succeeded', [
            'payment_intent_id' => $paymentIntent['id'] ?? null,
            'amount' => $paymentIntent['amount'] ?? 0,
            'metadata' => $metadata,
        ]);

        // If we have an invoice_id in metadata, create a payment record
        $invoiceId = $metadata['invoice_id'] ?? null;
        if ($invoiceId && $this->event->company_id) {
            $invoice = Invoice::where('id', $invoiceId)
                ->where('company_id', $this->event->company_id)
                ->first();

            if ($invoice) {
                $amount = ($paymentIntent['amount'] ?? 0) / 100; // Stripe uses cents

                Payment::create([
                    'invoice_id' => $invoice->id,
                    'company_id' => $invoice->company_id,
                    'amount' => $amount * 100, // Convert back to smallest currency unit
                    'payment_date' => now(),
                    'payment_number' => 'STRIPE-'.($paymentIntent['id'] ?? uniqid()),
                    'payment_method' => 'CREDIT_CARD',
                    'transaction_reference' => $paymentIntent['id'] ?? null,
                    'notes' => 'Stripe payment',
                ]);

                Log::info("Stripe payment created for invoice {$invoiceId}");
            }
        }
    }

    /**
     * Handle Stripe payment_intent.payment_failed event
     */
    protected function handleStripePaymentFailed(array $payload): void
    {
        $paymentIntent = $payload['data']['object'] ?? [];
        $error = $paymentIntent['last_payment_error'] ?? [];

        Log::warning('Stripe payment failed', [
            'payment_intent_id' => $paymentIntent['id'] ?? null,
            'error_code' => $error['code'] ?? 'unknown',
            'error_message' => $error['message'] ?? 'Unknown error',
        ]);
    }

    /**
     * Handle Stripe checkout.session.completed event
     */
    protected function handleStripeCheckoutCompleted(array $payload): void
    {
        $session = $payload['data']['object'] ?? [];
        $metadata = $session['metadata'] ?? [];

        Log::info('Stripe checkout completed', [
            'session_id' => $session['id'] ?? null,
            'payment_status' => $session['payment_status'] ?? null,
            'metadata' => $metadata,
        ]);

        // Payment handling will be done by payment_intent.succeeded event
    }

    /**
     * Handle Stripe invoice.paid event (for subscriptions)
     */
    protected function handleStripeInvoicePaid(array $payload): void
    {
        $invoice = $payload['data']['object'] ?? [];

        Log::info('Stripe invoice paid', [
            'invoice_id' => $invoice['id'] ?? null,
            'subscription_id' => $invoice['subscription'] ?? null,
            'amount_paid' => $invoice['amount_paid'] ?? 0,
        ]);
    }

    /**
     * Handle Stripe invoice.payment_failed event
     */
    protected function handleStripeInvoicePaymentFailed(array $payload): void
    {
        $invoice = $payload['data']['object'] ?? [];

        Log::warning('Stripe invoice payment failed', [
            'invoice_id' => $invoice['id'] ?? null,
            'subscription_id' => $invoice['subscription'] ?? null,
        ]);
    }

    /**
     * Handle Stripe charge.succeeded event
     */
    protected function handleStripeChargeSucceeded(array $payload): void
    {
        $charge = $payload['data']['object'] ?? [];

        Log::info('Stripe charge succeeded', [
            'charge_id' => $charge['id'] ?? null,
            'amount' => $charge['amount'] ?? 0,
            'payment_intent' => $charge['payment_intent'] ?? null,
        ]);

        // Payment creation handled by payment_intent.succeeded
    }

    /**
     * Handle Stripe charge.failed event
     */
    protected function handleStripeChargeFailed(array $payload): void
    {
        $charge = $payload['data']['object'] ?? [];
        $failureMessage = $charge['failure_message'] ?? 'Unknown failure';

        Log::warning('Stripe charge failed', [
            'charge_id' => $charge['id'] ?? null,
            'failure_message' => $failureMessage,
            'failure_code' => $charge['failure_code'] ?? null,
        ]);
    }
}
// CLAUDE-CHECKPOINT
