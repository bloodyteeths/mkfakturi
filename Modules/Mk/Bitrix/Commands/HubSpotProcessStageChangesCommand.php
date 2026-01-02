<?php

namespace Modules\Mk\Bitrix\Commands;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Mk\Bitrix\Mail\PartnerInviteMail;
use Modules\Mk\Bitrix\Models\HubSpotLeadMap;
use Modules\Mk\Bitrix\Models\OutreachLead;
use Modules\Mk\Bitrix\Services\HubSpotService;

/**
 * HubSpotProcessStageChangesCommand
 *
 * Polls HubSpot for deals that moved to "interested" stage and creates partner accounts.
 * Scheduled to run every 10 minutes to detect stage changes.
 *
 * Usage:
 *   php artisan hubspot:process-stage-changes
 *   php artisan hubspot:process-stage-changes --dry-run
 */
class HubSpotProcessStageChangesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hubspot:process-stage-changes
                            {--dry-run : Show what would happen without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll HubSpot for deals in "interested" stage and create partner accounts';

    /**
     * Execute the console command.
     *
     * @param HubSpotService $hubspot
     * @return int
     */
    public function handle(HubSpotService $hubspot): int
    {
        $dryRun = $this->option('dry-run');

        // Check if HubSpot is configured
        if (!$hubspot->isConfigured()) {
            $this->error('HubSpot is not configured. Set HUBSPOT_ACCESS_TOKEN in .env');
            return self::FAILURE;
        }

        // Get the "interested" stage ID
        $interestedStageId = $this->getInterestedStageId($hubspot);

        if (!$interestedStageId) {
            $this->error('Could not find "Interested" stage. Run hubspot:setup first.');
            return self::FAILURE;
        }

        $this->info('Polling HubSpot for deals in "interested" stage...');

        if ($dryRun) {
            $this->warn('[DRY RUN] No changes will be made');
            $this->newLine();
        }

        try {
            // Get deals in "interested" stage
            $deals = $hubspot->getDealsByStage($interestedStageId, [
                'facturino_lead_id',
                'facturino_partner_id',
            ]);

            $this->info('Found ' . count($deals) . " deals in 'interested' stage");
            $this->newLine();

            $processed = 0;
            $skipped = 0;
            $errors = 0;

            foreach ($deals as $deal) {
                $result = $this->processDeal($deal, $hubspot, $dryRun);

                if ($result === 'processed') {
                    $processed++;
                } elseif ($result === 'skipped') {
                    $skipped++;
                } else {
                    $errors++;
                }
            }

            $this->newLine();
            $this->info('Summary:');
            $this->info("  Processed: {$processed}");
            $this->info("  Skipped: {$skipped}");
            $this->info("  Errors: {$errors}");

            return $errors > 0 ? self::FAILURE : self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to poll HubSpot: ' . $e->getMessage());
            Log::error('HubSpot process-stage-changes failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }

    /**
     * Get the "Interested" stage ID from HubSpot.
     *
     * @param HubSpotService $hubspot
     * @return string|null
     */
    protected function getInterestedStageId(HubSpotService $hubspot): ?string
    {
        // First try config
        $configuredStage = config('hubspot.deal_stages.interested');
        if ($configuredStage) {
            return $configuredStage;
        }

        // Otherwise look up by label
        return $hubspot->getStageIdByLabel('Interested');
    }

    /**
     * Process a single deal.
     *
     * @param array $deal
     * @param HubSpotService $hubspot
     * @param bool $dryRun
     * @return string 'processed', 'skipped', or 'error'
     */
    protected function processDeal(array $deal, HubSpotService $hubspot, bool $dryRun): string
    {
        $dealId = $deal['id'];
        $dealName = $deal['properties']['dealname'] ?? 'Unknown';
        $partnerId = $deal['properties']['facturino_partner_id'] ?? null;
        $facturinoLeadId = $deal['properties']['facturino_lead_id'] ?? null;

        // Skip if already has partner_id
        if (!empty($partnerId)) {
            $this->line("  Skipping {$dealName} - already has partner_id: {$partnerId}");
            return 'skipped';
        }

        $this->info("Processing: {$dealName} (Deal ID: {$dealId})");

        try {
            // Find local mapping by deal ID
            $mapping = HubSpotLeadMap::findByDealId($dealId);

            if (!$mapping && $facturinoLeadId) {
                // Try to find by lead ID
                $lead = OutreachLead::find($facturinoLeadId);
                if ($lead) {
                    $mapping = $lead->hubspotMapping;
                }
            }

            if (!$mapping) {
                $this->warn("  No local mapping found for deal {$dealId}");
                return 'skipped';
            }

            $lead = $mapping->outreachLead;
            if (!$lead) {
                $this->warn("  No lead found for mapping");
                return 'skipped';
            }

            if ($dryRun) {
                $this->info("  [DRY RUN] Would create partner for: {$lead->email}");
                return 'processed';
            }

            // Create partner account
            $partner = $this->createPartner($lead);

            if (!$partner) {
                $this->error("  Failed to create partner for {$lead->email}");
                return 'error';
            }

            // Send invite email via Postmark transactional
            $this->sendPartnerInvite($partner, $lead);

            // Update HubSpot deal
            $hubspot->updateDeal($dealId, [
                'facturino_partner_id' => (string) $partner->id,
            ]);

            // Move deal to invite_sent stage
            $inviteSentStageId = $this->getInviteSentStageId($hubspot);
            if ($inviteSentStageId) {
                $hubspot->updateDeal($dealId, [
                    'dealstage' => $inviteSentStageId,
                ]);
            }

            // Log note to HubSpot
            if ($mapping->hubspot_contact_id) {
                try {
                    $hubspot->createNote(
                        $mapping->hubspot_contact_id,
                        "Partner account created!\nPartner ID: {$partner->id}\nInvite email sent to: {$lead->email}"
                    );
                } catch (\Exception $e) {
                    Log::debug('Failed to add HubSpot note', ['error' => $e->getMessage()]);
                }
            }

            // Update local records
            $lead->update([
                'status' => OutreachLead::STATUS_INVITE_SENT,
                'partner_id' => $partner->id,
            ]);
            $mapping->updateDealStage('invite_sent');

            $this->info("  Partner created: {$partner->id}, invite sent");

            Log::info('Partner created from HubSpot stage change', [
                'partner_id' => $partner->id,
                'email' => $lead->email,
                'hubspot_deal_id' => $dealId,
            ]);

            return 'processed';

        } catch (\Exception $e) {
            $this->error("  Error: " . $e->getMessage());
            Log::error('Failed to process HubSpot deal', [
                'deal_id' => $dealId,
                'error' => $e->getMessage(),
            ]);
            return 'error';
        }
    }

    /**
     * Get the "Invite Sent" stage ID from HubSpot.
     *
     * @param HubSpotService $hubspot
     * @return string|null
     */
    protected function getInviteSentStageId(HubSpotService $hubspot): ?string
    {
        // First try config
        $configuredStage = config('hubspot.deal_stages.invite_sent');
        if ($configuredStage) {
            return $configuredStage;
        }

        // Otherwise look up by label
        return $hubspot->getStageIdByLabel('Invite Sent');
    }

    /**
     * Create a partner account from an outreach lead.
     *
     * @param OutreachLead $lead
     * @return Partner|null
     */
    protected function createPartner(OutreachLead $lead): ?Partner
    {
        // Check if partner already exists by email
        $existingUser = User::where('email', $lead->email)->first();

        if ($existingUser?->partner) {
            return $existingUser->partner;
        }

        DB::beginTransaction();

        try {
            // Create user account
            $user = User::firstOrCreate(
                ['email' => $lead->email],
                [
                    'name' => $lead->contact_name ?: $lead->company_name ?: 'Partner',
                    'password' => bcrypt(Str::random(32)), // Random password, they'll reset
                    'email_verified_at' => null,
                ]
            );

            // Check if partner already exists for this user
            $existingPartner = Partner::where('user_id', $user->id)->first();
            if ($existingPartner) {
                DB::commit();
                return $existingPartner;
            }

            // Create partner record
            $partner = Partner::create([
                'user_id' => $user->id,
                'name' => $lead->contact_name ?: $lead->company_name ?: 'Partner',
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company_name' => $lead->company_name,
                'is_active' => false, // Activate after they accept invite
                'kyc_status' => 'pending',
                'commission_rate' => config('affiliate.direct_rate', 0.20),
            ]);

            DB::commit();
            return $partner;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create partner', [
                'email' => $lead->email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Send partner invitation email.
     *
     * @param Partner $partner
     * @param OutreachLead $lead
     * @return void
     */
    protected function sendPartnerInvite(Partner $partner, OutreachLead $lead): void
    {
        try {
            // Generate password reset / activation token
            $token = Str::random(64);

            // Store token (use password_resets table)
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $lead->email],
                [
                    'token' => bcrypt($token),
                    'created_at' => now(),
                ]
            );

            $activationUrl = url("/partner/activate?token={$token}&email=" . urlencode($lead->email));

            // Send via Postmark transactional stream
            Mail::to($lead->email)->send(new PartnerInviteMail(
                $partner->name,
                $lead->email,
                $activationUrl
            ));

            Log::info('Partner invite email sent', [
                'partner_id' => $partner->id,
                'email' => $lead->email,
            ]);

        } catch (\Exception $e) {
            $this->warn("  Failed to send invite email: " . $e->getMessage());
            Log::error('Failed to send partner invite email', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

// CLAUDE-CHECKPOINT
