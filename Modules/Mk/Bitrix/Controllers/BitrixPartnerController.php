<?php

namespace Modules\Mk\Bitrix\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Bitrix Partner Controller
 *
 * API endpoint for creating partner accounts from Bitrix24 CRM.
 * Called directly from Bitrix workflows or automation.
 */
class BitrixPartnerController extends Controller
{
    /**
     * Create a partner account from Bitrix
     *
     * POST /api/bitrix/create-partner
     */
    public function store(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'bitrix_lead_id' => 'required|integer',
            'email' => 'required|email',
            'company_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        Log::info('Bitrix create-partner request', [
            'bitrix_lead_id' => $data['bitrix_lead_id'],
            'email' => $data['email'],
        ]);

        // Idempotent check: does partner already exist?
        $existingPartner = Partner::where('email', $data['email'])->first();

        if ($existingPartner) {
            Log::info('Partner already exists (idempotent)', [
                'email' => $data['email'],
                'partner_id' => $existingPartner->id,
            ]);

            // Update Bitrix with existing partner ID
            $this->updateBitrixWithPartnerId($data['bitrix_lead_id'], $existingPartner->id);

            return response()->json([
                'status' => 'exists',
                'partner_id' => $existingPartner->id,
                'message' => 'Partner already exists',
            ]);
        }

        try {
            DB::beginTransaction();

            // Check if user with this email exists
            $user = User::where('email', $data['email'])->first();

            if (! $user) {
                // Create user account
                $user = User::create([
                    'name' => $data['name'] ?? $data['company_name'],
                    'email' => $data['email'],
                    'password' => bcrypt(Str::random(32)), // Temporary password
                ]);
            }

            // Create partner account
            $partner = Partner::create([
                'user_id' => $user->id,
                'name' => $data['name'] ?? $data['company_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'company_name' => $data['company_name'],
                'tax_id' => $data['tax_id'] ?? null,
                'is_active' => false, // Activate after invite accepted
                'kyc_status' => 'pending',
                'commission_rate' => config('affiliate.direct_rate', 0.20),
                'notes' => "Created from Bitrix lead #{$data['bitrix_lead_id']}",
            ]);

            DB::commit();

            Log::info('Partner created via Bitrix API', [
                'partner_id' => $partner->id,
                'email' => $data['email'],
                'bitrix_lead_id' => $data['bitrix_lead_id'],
            ]);

            // Send partner invite email via Postmark transactional
            $inviteSentAt = $this->sendPartnerInviteEmail($partner, $user);

            // Update Bitrix lead
            $this->updateBitrixWithPartnerId($data['bitrix_lead_id'], $partner->id);
            $this->updateBitrixLeadStatus($data['bitrix_lead_id'], 'INVITE_SENT');
            $this->addBitrixNote(
                $data['bitrix_lead_id'],
                "Partner account created (ID: {$partner->id}). Invite email sent."
            );

            return response()->json([
                'status' => 'created',
                'partner_id' => $partner->id,
                'invite_sent_at' => $inviteSentAt?->toIso8601String(),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create partner via Bitrix API', [
                'bitrix_lead_id' => $data['bitrix_lead_id'],
                'email' => $data['email'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create partner: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send partner invite email
     *
     * @return \Carbon\Carbon|null Timestamp when invite was sent
     */
    protected function sendPartnerInviteEmail(Partner $partner, User $user): ?\Carbon\Carbon
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

            $sentAt = now();

            Log::info('Partner invite email sent', [
                'partner_id' => $partner->id,
                'email' => $partner->email,
            ]);

            return $sentAt;

        } catch (\Exception $e) {
            Log::error('Failed to send partner invite email', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Update Bitrix lead with Facturino partner ID
     */
    protected function updateBitrixWithPartnerId(int $leadId, int $partnerId): void
    {
        $webhookUrl = config('services.bitrix.webhook_url');

        if (! $webhookUrl) {
            Log::warning('Bitrix webhook URL not configured');

            return;
        }

        try {
            Http::timeout(5)->post("{$webhookUrl}/crm.lead.update", [
                'id' => $leadId,
                'fields' => [
                    'UF_FCT_FACTURINO_PARTNER_ID' => $partnerId,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update Bitrix with partner ID', [
                'lead_id' => $leadId,
                'partner_id' => $partnerId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update Bitrix lead status
     */
    protected function updateBitrixLeadStatus(int $leadId, string $status): void
    {
        $webhookUrl = config('services.bitrix.webhook_url');

        if (! $webhookUrl) {
            return;
        }

        try {
            Http::timeout(5)->post("{$webhookUrl}/crm.lead.update", [
                'id' => $leadId,
                'fields' => [
                    'STATUS_ID' => $status,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update Bitrix lead status', [
                'lead_id' => $leadId,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Add timeline note to Bitrix lead
     */
    protected function addBitrixNote(int $leadId, string $comment): void
    {
        $webhookUrl = config('services.bitrix.webhook_url');

        if (! $webhookUrl) {
            return;
        }

        try {
            Http::timeout(5)->post("{$webhookUrl}/crm.timeline.comment.add", [
                'fields' => [
                    'ENTITY_ID' => $leadId,
                    'ENTITY_TYPE' => 'lead',
                    'COMMENT' => $comment,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to add Bitrix note', [
                'lead_id' => $leadId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

// CLAUDE-CHECKPOINT
