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
 * HubSpotCreatePartnerCommand
 *
 * Manually creates a partner from a specific HubSpot deal.
 * Useful for testing or manually processing deals.
 *
 * Usage:
 *   php artisan hubspot:create-partner --deal-id=123
 *   php artisan hubspot:create-partner --deal-id=123 --no-email
 *   php artisan hubspot:create-partner --deal-id=123 --dry-run
 */
class HubSpotCreatePartnerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hubspot:create-partner
                            {--deal-id= : HubSpot deal ID to create partner from}
                            {--email= : Email address to use (overrides deal lookup)}
                            {--no-email : Skip sending the invite email}
                            {--dry-run : Show what would happen without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually create a partner from a specific HubSpot deal';

    /**
     * Execute the console command.
     *
     * @param HubSpotService $hubspot
     * @return int
     */
    public function handle(HubSpotService $hubspot): int
    {
        $dealId = $this->option('deal-id');
        $email = $this->option('email');
        $noEmail = $this->option('no-email');
        $dryRun = $this->option('dry-run');

        if (!$dealId && !$email) {
            $this->error('Please provide either --deal-id or --email');
            return self::FAILURE;
        }

        // Check if HubSpot is configured
        if (!$hubspot->isConfigured()) {
            $this->error('HubSpot is not configured. Set HUBSPOT_ACCESS_TOKEN in .env');
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('[DRY RUN] No changes will be made');
            $this->newLine();
        }

        try {
            if ($dealId) {
                return $this->createFromDeal($dealId, $hubspot, $noEmail, $dryRun);
            } else {
                return $this->createFromEmail($email, $hubspot, $noEmail, $dryRun);
            }
        } catch (\Exception $e) {
            $this->error('Failed: ' . $e->getMessage());
            Log::error('HubSpot create-partner failed', [
                'deal_id' => $dealId,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return self::FAILURE;
        }
    }

    /**
     * Create partner from a HubSpot deal.
     *
     * @param string $dealId
     * @param HubSpotService $hubspot
     * @param bool $noEmail
     * @param bool $dryRun
     * @return int
     */
    protected function createFromDeal(
        string $dealId,
        HubSpotService $hubspot,
        bool $noEmail,
        bool $dryRun
    ): int {
        $this->info("Looking up HubSpot deal: {$dealId}");

        // Fetch deal from HubSpot
        $deal = $hubspot->getDeal($dealId, [
            'dealname',
            'facturino_lead_id',
            'facturino_partner_id',
        ]);

        if (!$deal) {
            $this->error("Deal not found: {$dealId}");
            return self::FAILURE;
        }

        $dealName = $deal['properties']['dealname'] ?? 'Unknown';
        $facturinoLeadId = $deal['properties']['facturino_lead_id'] ?? null;
        $existingPartnerId = $deal['properties']['facturino_partner_id'] ?? null;

        $this->info("Deal: {$dealName}");
        $this->line("  Lead ID: " . ($facturinoLeadId ?: 'Not set'));
        $this->line("  Partner ID: " . ($existingPartnerId ?: 'Not set'));
        $this->newLine();

        // Check if already has partner
        if ($existingPartnerId) {
            $this->warn("Deal already has partner ID: {$existingPartnerId}");
            if (!$this->confirm('Continue anyway?', false)) {
                return self::SUCCESS;
            }
        }

        // Find local mapping
        $mapping = HubSpotLeadMap::findByDealId($dealId);
        $lead = null;

        if ($mapping) {
            $lead = $mapping->outreachLead;
        } elseif ($facturinoLeadId) {
            $lead = OutreachLead::find($facturinoLeadId);
            if ($lead) {
                $mapping = $lead->hubspotMapping;
            }
        }

        if (!$lead) {
            $this->error("No local lead found for this deal");
            $this->line("Make sure the deal has a facturino_lead_id or HubSpot mapping exists.");
            return self::FAILURE;
        }

        $this->info("Found lead: {$lead->email}");
        $this->line("  Company: " . ($lead->company_name ?: 'N/A'));
        $this->line("  Contact: " . ($lead->contact_name ?: 'N/A'));
        $this->newLine();

        return $this->createPartnerForLead($lead, $mapping, $dealId, $hubspot, $noEmail, $dryRun);
    }

    /**
     * Create partner from an email address.
     *
     * @param string $email
     * @param HubSpotService $hubspot
     * @param bool $noEmail
     * @param bool $dryRun
     * @return int
     */
    protected function createFromEmail(
        string $email,
        HubSpotService $hubspot,
        bool $noEmail,
        bool $dryRun
    ): int {
        $this->info("Looking up lead by email: {$email}");

        // Find lead by email
        $lead = OutreachLead::where('email', strtolower($email))->first();

        if (!$lead) {
            $this->error("No lead found with email: {$email}");
            return self::FAILURE;
        }

        $mapping = $lead->hubspotMapping;
        $dealId = $mapping?->hubspot_deal_id;

        $this->info("Found lead: {$lead->email}");
        $this->line("  Company: " . ($lead->company_name ?: 'N/A'));
        $this->line("  Contact: " . ($lead->contact_name ?: 'N/A'));
        $this->line("  HubSpot Deal: " . ($dealId ?: 'Not linked'));
        $this->newLine();

        return $this->createPartnerForLead($lead, $mapping, $dealId, $hubspot, $noEmail, $dryRun);
    }

    /**
     * Create partner for a lead.
     *
     * @param OutreachLead $lead
     * @param HubSpotLeadMap|null $mapping
     * @param string|null $dealId
     * @param HubSpotService $hubspot
     * @param bool $noEmail
     * @param bool $dryRun
     * @return int
     */
    protected function createPartnerForLead(
        OutreachLead $lead,
        ?HubSpotLeadMap $mapping,
        ?string $dealId,
        HubSpotService $hubspot,
        bool $noEmail,
        bool $dryRun
    ): int {
        // Check for existing partner
        $existingPartner = Partner::where('email', $lead->email)->first();

        if ($existingPartner) {
            $this->warn("Partner already exists (ID: {$existingPartner->id})");

            if ($dealId && !$dryRun) {
                // Update HubSpot deal with existing partner
                $hubspot->updateDeal($dealId, [
                    'facturino_partner_id' => (string) $existingPartner->id,
                ]);
                $this->info("Updated HubSpot deal with partner ID");
            }

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("[DRY RUN] Would create partner:");
            $this->line("  Email: {$lead->email}");
            $this->line("  Name: " . ($lead->contact_name ?: $lead->company_name ?: 'Partner'));
            $this->line("  Would send invite email: " . ($noEmail ? 'No' : 'Yes'));
            return self::SUCCESS;
        }

        // Create partner
        $partner = $this->createPartner($lead);

        if (!$partner) {
            $this->error("Failed to create partner");
            return self::FAILURE;
        }

        $this->info("Partner created (ID: {$partner->id})");

        // Send invite email unless disabled
        if (!$noEmail) {
            $this->sendPartnerInvite($partner, $lead);
            $this->info("Invite email sent to: {$lead->email}");
        }

        // Update HubSpot deal
        if ($dealId) {
            $hubspot->updateDeal($dealId, [
                'facturino_partner_id' => (string) $partner->id,
            ]);
            $this->info("Updated HubSpot deal");

            // Move deal to invite_sent stage
            $inviteSentStageId = $hubspot->getStageIdByLabel('Invite Sent');
            if ($inviteSentStageId) {
                $hubspot->updateDeal($dealId, [
                    'dealstage' => $inviteSentStageId,
                ]);
                $this->info("Moved deal to 'Invite Sent' stage");
            }

            // Add note
            if ($mapping?->hubspot_contact_id) {
                try {
                    $hubspot->createNote(
                        $mapping->hubspot_contact_id,
                        "Partner account created manually.\nPartner ID: {$partner->id}\nCreated by: hubspot:create-partner command"
                    );
                } catch (\Exception $e) {
                    // Non-fatal
                }
            }
        }

        // Update local records
        $lead->update([
            'status' => $noEmail ? OutreachLead::STATUS_PARTNER_CREATED : OutreachLead::STATUS_INVITE_SENT,
            'partner_id' => $partner->id,
        ]);

        if ($mapping) {
            $mapping->updateDealStage($noEmail ? 'partner_created' : 'invite_sent');
        }

        Log::info('Partner created manually via command', [
            'partner_id' => $partner->id,
            'email' => $lead->email,
            'hubspot_deal_id' => $dealId,
        ]);

        $this->newLine();
        $this->info('Partner created successfully!');

        return self::SUCCESS;
    }

    /**
     * Create a partner account from an outreach lead.
     *
     * @param OutreachLead $lead
     * @return Partner|null
     */
    protected function createPartner(OutreachLead $lead): ?Partner
    {
        DB::beginTransaction();

        try {
            // Create user account
            $user = User::firstOrCreate(
                ['email' => $lead->email],
                [
                    'name' => $lead->contact_name ?: $lead->company_name ?: 'Partner',
                    'password' => bcrypt(Str::random(32)),
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
                'is_active' => false,
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
            // Generate activation token
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $lead->email],
                [
                    'token' => bcrypt($token),
                    'created_at' => now(),
                ]
            );

            $activationUrl = url("/partner/activate?token={$token}&email=" . urlencode($lead->email));

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
            $this->warn("Failed to send invite email: " . $e->getMessage());
            Log::error('Failed to send partner invite email', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

// CLAUDE-CHECKPOINT
