<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaddlePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Pennant\Feature;

/**
 * Paddle Webhook Controller
 *
 * Receives and processes Paddle payment webhooks.
 * Verifies signatures and delegates to PaddlePaymentService.
 *
 * Security:
 * - CSRF protection excluded via VerifyCsrfToken middleware
 * - Signature verification required for all webhooks
 * - Idempotency enforced via service layer
 *
 * @version 1.0.0
 *
 * @ticket B-31 series - Paddle Payment Integration
 *
 * @author Claude Code - Paddle agent
 */
class PaddleWebhookController extends Controller
{
    /**
     * @var PaddlePaymentService
     */
    protected $paddleService;

    /**
     * Constructor
     */
    public function __construct(PaddlePaymentService $paddleService)
    {
        $this->paddleService = $paddleService;
    }

    /**
     * Handle incoming Paddle webhook
     */
    public function handle(Request $request): Response
    {
        // Check if advanced payments feature is enabled
        if (! Feature::active('advanced-payments')) {
            Log::warning('Paddle webhook received but FEATURE_ADVANCED_PAYMENTS is disabled');

            return response('Feature disabled', 403);
        }

        try {
            // Get signature from header
            $signature = $request->header('Paddle-Signature');
            if (! $signature) {
                Log::warning('Paddle webhook missing signature header');

                return response('Missing signature', 400);
            }

            // Get payload
            $payload = $request->all();

            // Log webhook receipt
            Log::info('Paddle webhook received', [
                'event_type' => $payload['event_type'] ?? 'unknown',
                'event_id' => $payload['event_id'] ?? 'unknown',
            ]);

            // Process webhook
            $this->paddleService->handleWebhook($payload, $signature);

            return response('Webhook processed', 200);
        } catch (\Exception $e) {
            Log::error('Paddle webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return 200 to prevent Paddle from retrying on our errors
            // (signature verification failures will throw and return 500)
            return response('Error: '.$e->getMessage(), 500);
        }
    }
}

// CLAUDE-CHECKPOINT
