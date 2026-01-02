<?php

namespace Modules\Mk\Bitrix\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Bitrix Webhook Controller
 *
 * DEPRECATED: This controller is kept as a placeholder.
 *
 * We are now using HubSpot instead of Bitrix24, and HubSpot
 * uses polling instead of webhooks for sync. See:
 * - HubSpotSyncCommand for polling-based sync
 * - PostmarkWebhookController for email event sync to HubSpot
 * - PartnerTriggerController for manual partner creation
 *
 * This file can be deleted once Bitrix24 is fully migrated.
 */
class BitrixWebhookController extends Controller
{
    /**
     * Handle incoming webhooks (placeholder)
     *
     * POST /api/bitrix/events
     */
    public function handle(Request $request): JsonResponse
    {
        Log::warning('BitrixWebhookController is deprecated. Use HubSpot integration instead.', [
            'request_data' => $request->all(),
        ]);

        return response()->json([
            'status' => 'deprecated',
            'message' => 'Bitrix24 webhooks are no longer used. Migrate to HubSpot integration.',
        ], 410); // HTTP 410 Gone
    }
}

// CLAUDE-CHECKPOINT
