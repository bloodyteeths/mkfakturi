<?php

namespace Modules\Mk\Http;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Paddle Webhook Controller
 *
 * Handles webhook notifications from Paddle payment processor
 * Updates invoice status when payments are completed
 */
class PaddleWebhookController extends Controller
{
    /**
     * Handle incoming Paddle webhooks
     */
    public function handle(Request $request)
    {
        try {
            // Verify webhook signature
            if (! $this->verifyWebhookSignature($request)) {
                Log::warning('Paddle webhook signature verification failed', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response('Unauthorized', 401);
            }

            $eventType = $request->input('alert_name');
            $data = $request->all();

            Log::info('Paddle webhook received', [
                'event_type' => $eventType,
                'paddle_id' => $data['p_order_id'] ?? null,
            ]);

            // Handle different webhook events
            switch ($eventType) {
                case 'payment_succeeded':
                    return $this->handlePaymentSucceeded($data);

                case 'payment_failed':
                    return $this->handlePaymentFailed($data);

                case 'subscription_payment_succeeded':
                    return $this->handleSubscriptionPayment($data);

                case 'payment_refunded':
                    return $this->handlePaymentRefunded($data);

                default:
                    Log::info('Unhandled Paddle webhook event', ['event_type' => $eventType]);

                    return response('OK', 200);
            }

        } catch (\Exception $e) {
            Log::error('Paddle webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response('Internal Server Error', 500);
        }
    }

    /**
     * Handle successful payment webhook
     */
    protected function handlePaymentSucceeded(array $data): Response
    {
        $orderId = $data['p_order_id'];
        $passthrough = json_decode($data['passthrough'] ?? '{}', true);
        $customerId = $passthrough['customer_id'] ?? null;
        $invoiceId = $passthrough['invoice_id'] ?? null;

        // Find the invoice if ID is provided in passthrough
        if ($invoiceId) {
            $invoice = Invoice::find($invoiceId);

            if (! $invoice) {
                Log::warning('Invoice not found for Paddle payment', [
                    'invoice_id' => $invoiceId,
                    'order_id' => $orderId,
                ]);

                return response('Invoice not found', 404);
            }

            // Create payment record
            $payment = $this->createPaymentRecord($invoice, $data);

            // Update invoice status to PAID
            $invoice->update([
                'status' => 'PAID',
                'paid_status' => Payment::STATUS_COMPLETED,
                'payment_date' => now(),
            ]);

            Log::info('Invoice marked as paid via Paddle', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'paddle_order_id' => $orderId,
                'amount' => $data['p_sale_gross'],
            ]);
        }

        return response('OK', 200);
    }

    /**
     * Handle failed payment webhook
     */
    protected function handlePaymentFailed(array $data): Response
    {
        $orderId = $data['p_order_id'];
        $passthrough = json_decode($data['passthrough'] ?? '{}', true);
        $invoiceId = $passthrough['invoice_id'] ?? null;

        Log::warning('Paddle payment failed', [
            'order_id' => $orderId,
            'invoice_id' => $invoiceId,
            'reason' => $data['payment_method'] ?? 'Unknown',
        ]);

        // Could implement retry logic or notification here
        return response('OK', 200);
    }

    /**
     * Handle subscription payment (for recurring invoices)
     */
    protected function handleSubscriptionPayment(array $data): Response
    {
        $subscriptionId = $data['subscription_id'];
        $orderId = $data['p_order_id'];

        Log::info('Paddle subscription payment received', [
            'subscription_id' => $subscriptionId,
            'order_id' => $orderId,
            'amount' => $data['p_sale_gross'],
        ]);

        // Handle recurring invoice payments here
        return response('OK', 200);
    }

    /**
     * Handle payment refund webhook
     */
    protected function handlePaymentRefunded(array $data): Response
    {
        $orderId = $data['p_order_id'];
        $refundAmount = $data['p_gross_refund'];

        Log::info('Paddle payment refunded', [
            'order_id' => $orderId,
            'refund_amount' => $refundAmount,
        ]);

        // Find and update payment/invoice status
        $payment = Payment::where('reference', $orderId)->first();
        if ($payment) {
            $payment->update([
                'status' => 'REFUNDED',
                'notes' => 'Refunded via Paddle webhook: '.$refundAmount,
            ]);

            // Update invoice status back to SENT
            if ($payment->invoice) {
                $payment->invoice->update([
                    'status' => 'SENT',
                    'paid_status' => 'UNPAID',
                ]);
            }
        }

        return response('OK', 200);
    }

    /**
     * Create payment record from Paddle webhook data
     */
    protected function createPaymentRecord(Invoice $invoice, array $data): Payment
    {
        return Payment::create([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'amount' => $data['p_sale_gross'],
            'currency_id' => $invoice->currency_id,
            'payment_date' => Carbon::parse($data['event_time'])->format('Y-m-d'),
            'payment_number' => $this->generatePaymentNumber($invoice->company_id),
            'payment_method' => 'paddle',
            'notes' => 'Paddle payment - Order ID: '.$data['p_order_id'],
            'reference' => $data['p_order_id'],
        ]);
    }

    /**
     * Generate unique payment number
     */
    protected function generatePaymentNumber(int $companyId): string
    {
        $prefix = 'PAY-';
        $year = date('Y');
        $month = date('m');

        $lastPayment = Payment::where('company_id', $companyId)
            ->where('payment_number', 'like', $prefix.$year.$month.'%')
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = intval(substr($lastPayment->payment_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$year.$month.'-'.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Verify Paddle webhook signature
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        $webhookSecret = config('services.paddle.webhook_secret');

        if (! $webhookSecret) {
            Log::warning('Paddle webhook secret not configured');

            return false;
        }

        // Get all POST data
        $postData = $request->all();

        // Extract signature
        $signature = $postData['p_signature'] ?? null;
        unset($postData['p_signature']);

        if (! $signature) {
            return false;
        }

        // Sort data alphabetically by key
        ksort($postData);

        // Build query string
        $queryString = http_build_query($postData);

        // Verify signature
        $calculatedSignature = base64_encode(hash_hmac('sha1', $queryString, $webhookSecret, true));

        return hash_equals($signature, $calculatedSignature);
    }
}
