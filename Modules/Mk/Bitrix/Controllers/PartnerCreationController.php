<?php

namespace Modules\Mk\Bitrix\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Mk\Bitrix\Services\HubSpotService;
use Modules\Mk\Bitrix\Models\HubSpotLeadMap;

/**
 * Handles partner creation via secure webhook links.
 * 
 * Flow:
 * 1. Wife moves deal to "Interested" in HubSpot
 * 2. She clicks the "Create Partner" link (shown in deal)
 * 3. This controller creates the partner account
 * 4. Sends invite email
 * 5. Updates HubSpot deal to "Invite Sent"
 */
class PartnerCreationController extends Controller
{
    /**
     * Create partner from HubSpot deal.
     * 
     * URL: /webhooks/hubspot/create-partner/{dealId}?token={hmac}
     */
    public function createPartner(Request $request, string $dealId)
    {
        // Verify HMAC token
        $token = $request->query('token');
        $expectedToken = $this->generateToken($dealId);

        if (!hash_equals($expectedToken, $token ?? '')) {
            Log::warning('Invalid partner creation token', ['deal_id' => $dealId]);
            return response()->view('outreach.partner-error', [
                'message' => 'Invalid or expired link. Please contact support.'
            ], 403);
        }

        $hubSpotService = new HubSpotService();

        // Get deal from HubSpot
        $deal = $hubSpotService->getDeal($dealId, [
            'dealname', 'dealstage', 'fct_partner_id'
        ]);

        if (!$deal) {
            return response()->view('outreach.partner-error', [
                'message' => 'Deal not found in HubSpot.'
            ], 404);
        }

        // Check if partner already created
        $existingPartnerId = $deal['properties']['fct_partner_id'] ?? null;
        if ($existingPartnerId) {
            return response()->view('outreach.partner-exists', [
                'partner_id' => $existingPartnerId,
                'deal_name' => $deal['properties']['dealname'] ?? 'Unknown'
            ]);
        }

        // Get associated contact
        $contactId = $this->getAssociatedContactId($hubSpotService, $dealId);
        if (!$contactId) {
            return response()->view('outreach.partner-error', [
                'message' => 'No contact associated with this deal.'
            ], 400);
        }

        $contact = $hubSpotService->getContact($contactId, [
            'email', 'firstname', 'lastname', 'company', 'phone',
            'fct_contact_person_name'
        ]);

        if (!$contact) {
            return response()->view('outreach.partner-error', [
                'message' => 'Contact not found.'
            ], 404);
        }

        $email = $contact['properties']['email'] ?? null;
        $companyName = $contact['properties']['company'] ?? $deal['properties']['dealname'] ?? 'Partner';
        $contactPerson = $contact['properties']['fct_contact_person_name'] ?? '';
        $phone = $contact['properties']['phone'] ?? '';

        if (!$email) {
            return response()->view('outreach.partner-error', [
                'message' => 'Contact has no email address.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Check if user already exists
            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                // Link existing user as partner
                $partnerId = $existingUser->id;
                Log::info('Linked existing user as partner', [
                    'user_id' => $partnerId,
                    'email' => $email
                ]);
            } else {
                // Create new partner user
                $password = Str::random(12);
                
                $user = User::create([
                    'name' => $contactPerson ?: $companyName,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'phone' => $phone,
                    'role' => 'partner', // Adjust based on your user role system
                ]);

                $partnerId = $user->id;

                // Send welcome email with password
                // TODO: Use proper mail template
                Mail::raw(
                    "Добредојдовте во Facturino!\n\n" .
                    "Вашиот партнерски акаунт е креиран.\n\n" .
                    "Email: {$email}\n" .
                    "Лозинка: {$password}\n\n" .
                    "Логирајте се на: https://app.facturino.mk\n\n" .
                    "Поздрав,\nFacturino тим",
                    function ($message) use ($email, $companyName) {
                        $message->to($email)
                            ->subject("Facturino - Вашиот партнерски акаунт е готов")
                            ->from(config('mail.from.address'), config('mail.from.name'));
                    }
                );

                Log::info('Partner account created', [
                    'partner_id' => $partnerId,
                    'email' => $email,
                    'company' => $companyName
                ]);
            }

            DB::commit();

            // Update HubSpot deal
            $inviteSentStageId = config('hubspot.stages.invite_sent');
            $hubSpotService->updateDeal($dealId, [
                'dealstage' => $inviteSentStageId,
                'fct_partner_id' => (string) $partnerId,
            ]);

            // Log note to HubSpot
            $hubSpotService->createNote(
                $contactId,
                "Partner account created!\n" .
                "Partner ID: {$partnerId}\n" .
                "Created at: " . now()->toDateTimeString()
            );

            // Update local mapping
            $mapping = HubSpotLeadMap::findByEmail($email);
            if ($mapping) {
                $mapping->update([
                    'deal_stage' => 'invite_sent',
                    'last_synced_at' => now(),
                ]);
            }

            return response()->view('outreach.partner-created', [
                'partner_id' => $partnerId,
                'email' => $email,
                'company_name' => $companyName,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create partner', [
                'deal_id' => $dealId,
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return response()->view('outreach.partner-error', [
                'message' => 'Failed to create partner account. Please try again.'
            ], 500);
        }
    }

    /**
     * Generate HMAC token for deal ID.
     */
    public static function generateToken(string $dealId): string
    {
        $secret = config('app.key');
        return hash_hmac('sha256', "partner-create:{$dealId}", $secret);
    }

    /**
     * Generate partner creation URL for a deal.
     */
    public static function generateUrl(string $dealId): string
    {
        $token = self::generateToken($dealId);
        return url("/webhooks/hubspot/create-partner/{$dealId}?token={$token}");
    }

    /**
     * Get contact ID associated with a deal.
     */
    protected function getAssociatedContactId(HubSpotService $hubSpotService, string $dealId): ?string
    {
        try {
            $associations = $hubSpotService->request(
                'GET',
                "/crm/v4/objects/deals/{$dealId}/associations/contacts"
            );
            return $associations['results'][0]['toObjectId'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}

// CLAUDE-CHECKPOINT
