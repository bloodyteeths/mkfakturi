<?php

namespace Modules\Mk\Bitrix\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Bitrix\Models\HubSpotLeadMap;
use Modules\Mk\Bitrix\Models\Suppression;
use Modules\Mk\Bitrix\Models\UnsubscribeToken;
use Modules\Mk\Bitrix\Services\HubSpotApiClient;

/**
 * Unsubscribe Controller
 *
 * Handles email unsubscribe requests from outreach campaigns.
 * When a recipient clicks unsubscribe:
 * 1. Validates the token
 * 2. Adds email to suppression list (type: unsub)
 * 3. Logs note to HubSpot contact
 * 4. Moves deal to Lost stage
 * 5. Updates local lead status to lost
 */
class UnsubscribeController extends Controller
{
    /**
     * HubSpot API client.
     */
    protected HubSpotApiClient $hubspot;

    /**
     * Create a new controller instance.
     *
     * @param HubSpotApiClient $hubspot
     */
    public function __construct(HubSpotApiClient $hubspot)
    {
        $this->hubspot = $hubspot;
    }

    /**
     * Show unsubscribe confirmation page
     *
     * GET /unsubscribe?token=...
     */
    public function show(Request $request)
    {
        $token = $request->query('token');

        if (! $token) {
            return view('outreach.unsubscribe-invalid', [
                'message' => 'No unsubscribe token provided.',
            ]);
        }

        // Validate token exists, not used, not expired
        $unsubToken = DB::table('outreach_unsubscribe_tokens')
            ->where('token', $token)
            ->first();

        if (! $unsubToken) {
            return view('outreach.unsubscribe-invalid', [
                'message' => 'Invalid unsubscribe token.',
            ]);
        }

        if ($unsubToken->used_at) {
            return view('outreach.unsubscribe-invalid', [
                'message' => 'This unsubscribe link has already been used.',
            ]);
        }

        // Check expiration (tokens valid for 30 days)
        $expiresAt = \Carbon\Carbon::parse($unsubToken->created_at)->addDays(30);
        if (now()->gt($expiresAt)) {
            return view('outreach.unsubscribe-invalid', [
                'message' => 'This unsubscribe link has expired.',
            ]);
        }

        // Get associated email for display
        $email = $unsubToken->email ?? 'your email';

        return view('outreach.unsubscribe', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Process unsubscribe request
     *
     * POST /unsubscribe
     */
    public function process(Request $request)
    {
        $token = $request->input('token');

        if (! $token) {
            return view('outreach.unsubscribe-invalid', [
                'message' => 'No unsubscribe token provided.',
            ]);
        }

        // Validate token exists, not used, not expired
        $unsubToken = DB::table('outreach_unsubscribe_tokens')
            ->where('token', $token)
            ->first();

        if (! $unsubToken) {
            return view('outreach.unsubscribe-invalid', [
                'message' => 'Invalid unsubscribe token.',
            ]);
        }

        if ($unsubToken->used_at) {
            return view('outreach.unsubscribe-invalid', [
                'message' => 'This unsubscribe link has already been used.',
            ]);
        }

        try {
            DB::beginTransaction();

            $email = $unsubToken->email;

            // 1. Add to suppression list (type: unsub) using model
            Suppression::suppress(
                $email,
                Suppression::TYPE_UNSUBSCRIBE,
                Suppression::SOURCE_USER,
                ['token' => $token]
            );

            // 2. Mark token as used
            DB::table('outreach_unsubscribe_tokens')
                ->where('token', $token)
                ->update([
                    'used_at' => now(),
                    'updated_at' => now(),
                ]);

            // 3. Sync to HubSpot - log note and update deal stage to Lost
            $this->syncUnsubscribeToHubSpot($email);

            DB::commit();

            Log::info('Email unsubscribed', [
                'email' => $email,
            ]);

            return view('outreach.unsubscribe-success', [
                'email' => $email,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Unsubscribe failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return view('outreach.unsubscribe-invalid', [
                'message' => 'An error occurred processing your request. Please try again later.',
            ]);
        }
    }

    /**
     * Sync unsubscribe event to HubSpot.
     *
     * 1. Logs a note to the HubSpot contact
     * 2. Moves the associated deal to Lost stage
     * 3. Updates the local lead status to lost
     *
     * @param string $email
     * @return void
     */
    protected function syncUnsubscribeToHubSpot(string $email): void
    {
        try {
            // Find HubSpot mapping by email
            $mapping = HubSpotLeadMap::findByEmail($email);

            if (!$mapping) {
                Log::info('No HubSpot mapping found for unsubscribe', ['email' => $email]);
                return;
            }

            // 1. Log note to HubSpot contact
            if ($mapping->hubspot_contact_id) {
                $this->hubspot->createNote(
                    $mapping->hubspot_contact_id,
                    "Unsubscribed via email link - added to suppression list"
                );
            }

            // 2. Move deal to Lost stage
            if ($mapping->hubspot_deal_id) {
                $lostStageId = config('hubspot.deal_stages.lost');

                if ($lostStageId) {
                    $this->hubspot->updateDealStage($mapping->hubspot_deal_id, $lostStageId);
                    $mapping->update(['deal_stage' => 'lost']);
                }
            }

            // 3. Update local lead status to lost
            $mapping->outreachLead?->update(['status' => 'lost']);

        } catch (\Exception $e) {
            Log::error('Failed to sync unsubscribe to HubSpot', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

// CLAUDE-CHECKPOINT
