<?php

namespace Modules\Mk\Bitrix\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Mk\Bitrix\Models\OutreachLead;
use Modules\Mk\Bitrix\Services\HubSpotApiClient;
use Modules\Mk\Bitrix\Services\OutreachService;

/**
 * Partner Trigger Controller
 *
 * Provides a "button link" endpoint for creating partner accounts from HubSpot.
 * The link is a signed URL that can be clicked from HubSpot deal notes.
 *
 * Flow:
 * 1. Generate signed URL with deal_id and token
 * 2. Add URL as clickable link in HubSpot deal note
 * 3. When clicked, validates token and creates partner
 * 4. Updates HubSpot deal with partner_id and stage
 */
class PartnerTriggerController extends Controller
{
    /**
     * HubSpot API client.
     */
    protected HubSpotApiClient $hubspot;

    /**
     * Outreach service.
     */
    protected OutreachService $outreachService;

    /**
     * Create a new controller instance.
     *
     * @param HubSpotApiClient $hubspot
     * @param OutreachService $outreachService
     */
    public function __construct(HubSpotApiClient $hubspot, OutreachService $outreachService)
    {
        $this->hubspot = $hubspot;
        $this->outreachService = $outreachService;
    }

    /**
     * Create a partner account from a HubSpot deal.
     *
     * GET /api/partner/create?deal_id=123&token=xxx
     *
     * This is a signed URL that can be added as a link in HubSpot deal notes.
     * When clicked, it validates the token and creates a partner account.
     */
    public function createFromDeal(Request $request): JsonResponse
    {
        $dealId = $request->query('deal_id');
        $token = $request->query('token');

        // Validate required parameters
        if (!$dealId || !$token) {
            return response()->json([
                'error' => 'Missing required parameters: deal_id and token',
            ], 400);
        }

        // Validate signed URL token
        if (!$this->validateToken($dealId, $token)) {
            Log::warning('Invalid partner creation token', [
                'deal_id' => $dealId,
            ]);

            return response()->json([
                'error' => 'Invalid token',
            ], 401);
        }

        // Get deal from HubSpot
        $deal = $this->hubspot->getDealWithProperties($dealId, [
            'facturino_lead_id',
            'facturino_partner_id',
            'dealname',
            'dealstage',
        ]);

        if (!$deal) {
            return response()->json([
                'error' => 'Deal not found',
            ], 404);
        }

        $properties = $deal['properties'] ?? [];

        // Check if partner already created
        if (!empty($properties['facturino_partner_id'])) {
            return response()->json([
                'error' => 'Partner already created',
                'partner_id' => $properties['facturino_partner_id'],
            ], 409);
        }

        // Find lead by facturino_lead_id
        $leadId = $properties['facturino_lead_id'] ?? null;
        $lead = $leadId ? OutreachLead::find($leadId) : null;

        if (!$lead) {
            return response()->json([
                'error' => 'Lead not found. Make sure the deal has a facturino_lead_id property set.',
            ], 404);
        }

        try {
            // Create partner from lead
            $partner = $this->convertLeadToPartner($lead);

            if (!$partner) {
                return response()->json([
                    'error' => 'Failed to create partner',
                ], 500);
            }

            // Update HubSpot deal with partner ID and stage
            $inviteSentStage = $this->hubspot->getStageId('invite_sent');
            $this->hubspot->updateDeal($dealId, [
                'facturino_partner_id' => (string) $partner->id,
                'dealstage' => $inviteSentStage ?? $properties['dealstage'],
            ]);

            // Log note to HubSpot contact
            $contactId = $this->hubspot->getContactIdForDeal($dealId);
            if ($contactId) {
                $this->hubspot->logNote($contactId, "Partner created: #{$partner->id}. Invite sent.");
            }

            Log::info('Partner created from HubSpot deal', [
                'deal_id' => $dealId,
                'lead_id' => $lead->id,
                'partner_id' => $partner->id,
            ]);

            return response()->json([
                'success' => true,
                'partner_id' => $partner->id,
                'invite_sent' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create partner from deal', [
                'deal_id' => $dealId,
                'lead_id' => $lead->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to create partner: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate the signed URL token.
     *
     * Uses HMAC-SHA256 with the HubSpot access token as the key.
     */
    protected function validateToken(string $dealId, string $token): bool
    {
        $expectedToken = $this->generateToken($dealId);
        return hash_equals($expectedToken, $token);
    }

    /**
     * Generate a signed token for a deal ID.
     */
    protected function generateToken(string $dealId): string
    {
        $secret = config('hubspot.access_token');
        return hash_hmac('sha256', $dealId, $secret);
    }

    /**
     * Generate a signed URL for creating a partner from a deal.
     *
     * This URL can be added to HubSpot deal notes as a clickable button.
     */
    public static function generateCreatePartnerUrl(string $dealId): string
    {
        $secret = config('hubspot.access_token');
        $token = hash_hmac('sha256', $dealId, $secret);

        return url("/api/partner/create?deal_id={$dealId}&token={$token}");
    }

    /**
     * Convert an outreach lead to a partner account.
     *
     * Creates user account, partner account, and sends invite email.
     */
    protected function convertLeadToPartner(OutreachLead $lead): ?Partner
    {
        try {
            return DB::transaction(function () use ($lead) {
                // Check if partner already exists
                $existingPartner = Partner::where('email', $lead->email)->first();

                if ($existingPartner) {
                    Log::info('Partner already exists for lead', [
                        'lead_id' => $lead->id,
                        'partner_id' => $existingPartner->id,
                    ]);

                    // Update lead status
                    $lead->update([
                        'status' => OutreachLead::STATUS_PARTNER_CREATED,
                        'partner_id' => $existingPartner->id,
                    ]);

                    return $existingPartner;
                }

                // Check if user exists
                $user = User::where('email', $lead->email)->first();

                if (!$user) {
                    // Create user account
                    $user = User::create([
                        'name' => $lead->contact_name ?? $lead->company_name ?? 'Partner',
                        'email' => $lead->email,
                        'password' => bcrypt(Str::random(32)),
                    ]);
                }

                // Create partner account
                $partner = Partner::create([
                    'name' => $lead->contact_name ?? $lead->company_name ?? 'Partner',
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'company_name' => $lead->company_name,
                    'commission_rate' => config('affiliate.direct_rate', 0.20),
                    'is_active' => false,
                    'kyc_status' => 'pending',
                    'user_id' => $user->id,
                    'notes' => 'Created from outreach lead #' . $lead->id,
                ]);

                // Update lead status
                $lead->update([
                    'status' => OutreachLead::STATUS_PARTNER_CREATED,
                    'partner_id' => $partner->id,
                ]);

                // Send partner invite email
                $this->sendPartnerInviteEmail($partner, $user);

                Log::info('Lead converted to partner', [
                    'lead_id' => $lead->id,
                    'partner_id' => $partner->id,
                    'user_id' => $user->id,
                ]);

                return $partner;
            });

        } catch (\Exception $e) {
            Log::error('Failed to convert lead to partner', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send partner invite email.
     */
    protected function sendPartnerInviteEmail(Partner $partner, User $user): void
    {
        try {
            // Generate password reset token
            $token = app('auth.password.broker')->createToken($user);

            Mail::send('emails.partner-invitation', [
                'partner' => $partner,
                'user' => $user,
                'resetUrl' => url("/reset-password/{$token}?email=" . urlencode($user->email)),
            ], function ($message) use ($partner) {
                $message->to($partner->email)
                    ->subject('Welcome to Facturino Partner Program');
            });

            Log::info('Partner invite email sent', [
                'partner_id' => $partner->id,
                'email' => $partner->email,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send partner invite email', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

// CLAUDE-CHECKPOINT
