<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWebhookEvent;
use App\Models\GatewayWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle Paddle billing webhooks
     */
    public function paddle(Request $request)
    {
        try {
            $signature = $request->header('Paddle-Signature');
            $payload = $request->all();

            // Extract event ID and company from payload
            $eventId = $payload['event_id'] ?? null;
            $companyId = $payload['data']['custom_data']['company_id'] ?? null;

            if (!$companyId) {
                Log::warning('Paddle webhook missing company_id', ['payload' => $payload]);

                return response()->json(['error' => 'Missing company_id'], 400);
            }

            // Store webhook event
            $event = GatewayWebhookEvent::create([
                'company_id' => $companyId,
                'provider' => 'paddle',
                'event_type' => $payload['event_type'] ?? 'unknown',
                'event_id' => $eventId,
                'payload' => $payload,
                'signature' => $signature,
                'status' => 'pending',
            ]);

            // Dispatch job for async processing
            ProcessWebhookEvent::dispatch($event);

            return response()->json(['status' => 'received'], 200);
        } catch (\Exception $e) {
            Log::error('Paddle webhook error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Handle CASYS Cpay webhooks
     */
    public function cpay(Request $request)
    {
        try {
            $signature = $request->input('signature');
            $payload = $request->all();

            // Extract transaction ID and company from payload
            $eventId = $payload['transaction_id'] ?? null;
            $companyId = $payload['merchant_data']['company_id'] ?? null;

            if (!$companyId) {
                Log::warning('CPAY webhook missing company_id', ['payload' => $payload]);

                return response()->json(['error' => 'Missing company_id'], 400);
            }

            // Store webhook event
            $event = GatewayWebhookEvent::create([
                'company_id' => $companyId,
                'provider' => 'cpay',
                'event_type' => $payload['status'] ?? 'unknown',
                'event_id' => $eventId,
                'payload' => $payload,
                'signature' => $signature,
                'status' => 'pending',
            ]);

            // Dispatch job for async processing
            ProcessWebhookEvent::dispatch($event);

            return response()->json(['status' => 'received'], 200);
        } catch (\Exception $e) {
            Log::error('CPAY webhook error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Handle NLB bank webhooks
     */
    public function bankNlb(Request $request)
    {
        try {
            $signature = $request->header('X-NLB-Signature');
            $payload = $request->all();

            // Extract event ID and company from payload
            $eventId = $payload['notification_id'] ?? null;
            $companyId = $payload['account_data']['company_id'] ?? null;

            if (!$companyId) {
                Log::warning('NLB webhook missing company_id', ['payload' => $payload]);

                return response()->json(['error' => 'Missing company_id'], 400);
            }

            // Store webhook event
            $event = GatewayWebhookEvent::create([
                'company_id' => $companyId,
                'provider' => 'nlb',
                'event_type' => $payload['event_type'] ?? 'transaction.created',
                'event_id' => $eventId,
                'payload' => $payload,
                'signature' => $signature,
                'status' => 'pending',
            ]);

            // Dispatch job for async processing
            ProcessWebhookEvent::dispatch($event);

            return response()->json(['status' => 'received'], 200);
        } catch (\Exception $e) {
            Log::error('NLB webhook error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Handle Stopanska bank webhooks
     */
    public function bankStopanska(Request $request)
    {
        try {
            $signature = $request->header('X-Stopanska-Signature');
            $payload = $request->all();

            // Extract event ID and company from payload
            $eventId = $payload['notification_id'] ?? null;
            $companyId = $payload['account_data']['company_id'] ?? null;

            if (!$companyId) {
                Log::warning('Stopanska webhook missing company_id', ['payload' => $payload]);

                return response()->json(['error' => 'Missing company_id'], 400);
            }

            // Store webhook event
            $event = GatewayWebhookEvent::create([
                'company_id' => $companyId,
                'provider' => 'stopanska',
                'event_type' => $payload['event_type'] ?? 'transaction.created',
                'event_id' => $eventId,
                'payload' => $payload,
                'signature' => $signature,
                'status' => 'pending',
            ]);

            // Dispatch job for async processing
            ProcessWebhookEvent::dispatch($event);

            return response()->json(['status' => 'received'], 200);
        } catch (\Exception $e) {
            Log::error('Stopanska webhook error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }
    /**
     * Handle Stripe webhooks
     */
    public function stripe(Request $request)
    {
        try {
            $signature = $request->header('Stripe-Signature');
            $payload = $request->getContent();
            $secret = config('services.stripe.webhook.secret');

            if ($secret) {
                try {
                    // Verify signature
                    \Stripe\Webhook::constructEvent($payload, $signature, $secret);
                } catch (\Exception $e) {
                    Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
                    return response()->json(['error' => 'Invalid signature'], 400);
                }
            }

            $data = json_decode($payload, true);
            $eventId = $data['id'] ?? null;
            $eventType = $data['type'] ?? 'unknown';

            // Extract company_id from metadata if available
            $companyId = $data['data']['object']['metadata']['company_id'] ?? null;

            // Store webhook event
            $event = GatewayWebhookEvent::create([
                'company_id' => $companyId, // Can be null for account-level events
                'provider' => 'stripe',
                'event_type' => $eventType,
                'event_id' => $eventId,
                'payload' => $data,
                'signature' => $signature,
                'status' => 'pending',
            ]);

            // Dispatch job for async processing
            ProcessWebhookEvent::dispatch($event);

            return response()->json(['status' => 'received'], 200);
        } catch (\Exception $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}
// CLAUDE-CHECKPOINT
