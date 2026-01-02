<?php

namespace Modules\Mk\Bitrix\Commands;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Mk\Bitrix\Models\HubSpotLeadMap;
use Modules\Mk\Bitrix\Models\OutreachLead;
use Modules\Mk\Bitrix\Services\HubSpotService;

/**
 * HubSpotPollDealsCommand
 *
 * Polls HubSpot for deals in "Interested" stage and creates partner accounts.
 * Replaces the Bitrix webhook approach with polling.
 *
 * Usage:
 *   php artisan hubspot:poll-deals
 *   php artisan hubspot:poll-deals --dry-run
 *   php artisan hubspot:poll-deals --stage=interested
 */
class HubSpotPollDealsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hubspot:poll-deals
                            {--dry-run : Show what would be done without making changes}
                            {--stage=interested : Stage to poll for (stage label, case-insensitive)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll HubSpot for deals in Interested stage and create partner accounts';

    /**
     * Stage mapping for common labels.
     */
    protected array $stageLabels = [
        'new_lead' => 'New Lead',
        'emailed' => 'Emailed',
        'followup' => 'Follow-up',
        'follow-up' => 'Follow-up',
        'interested' => 'Interested',
        'invite_sent' => 'Invite Sent',
        'partner_created' => 'Partner Created',
        'active' => 'Active',
        'lost' => 'Lost',
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(HubSpotService $hubSpotService): int
    {
        $dryRun = $this->option('dry-run');
        $stageOption = strtolower($this->option('stage'));

        $this->info('HubSpot Deal Polling');
        $this->line('====================');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Check configuration
        if (!$hubSpotService->isConfigured()) {
            $this->error('HubSpot is not configured. Set HUBSPOT_ACCESS_TOKEN in .env');
            return self::FAILURE;
        }

        // Resolve stage label to ID
        $stageLabel = $this->stageLabels[$stageOption] ?? ucfirst($stageOption);
        $stageId = $hubSpotService->getStageIdByLabel($stageLabel);

        if (!$stageId) {
            $this->error("Could not find stage: {$stageLabel}");
            $this->line('Available stages:');
            $this->listAvailableStages($hubSpotService);
            return self::FAILURE;
        }

        $this->info("Polling for deals in stage: {$stageLabel} (ID: {$stageId})");
        $this->newLine();

        try {
            // Get deals in the target stage
            $deals = $hubSpotService->getDealsByStage($stageId, [
                'facturino_lead_id',
                'facturino_partner_id',
            ]);

            if (empty($deals)) {
                $this->info('No deals found in this stage.');
                return self::SUCCESS;
            }

            $this->info("Found " . count($deals) . " deals in stage.");
            $this->newLine();

            $stats = [
                'processed' => 0,
                'partners_created' => 0,
                'invites_sent' => 0,
                'skipped_no_lead' => 0,
                'skipped_has_partner' => 0,
                'errors' => [],
            ];

            foreach ($deals as $deal) {
                $result = $this->processDeal($deal, $hubSpotService, $dryRun);
                $stats['processed']++;

                foreach ($result as $key => $value) {
                    if (isset($stats[$key]) && is_int($value)) {
                        $stats[$key] += $value;
                    } elseif ($key === 'error') {
                        $stats['errors'][] = $value;
                    }
                }
            }

            $this->displayResults($stats);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to poll deals: ' . $e->getMessage());
            Log::error('HubSpot poll-deals failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }

    /**
     * Process a single deal.
     *
     * @param array $deal
     * @param HubSpotService $hubSpotService
     * @param bool $dryRun
     * @return array
     */
    protected function processDeal(array $deal, HubSpotService $hubSpotService, bool $dryRun): array
    {
        $dealId = $deal['id'];
        $dealName = $deal['properties']['dealname'] ?? 'Unknown';
        $facturinoLeadId = $deal['properties']['facturino_lead_id'] ?? null;
        $facturinoPartnerId = $deal['properties']['facturino_partner_id'] ?? null;

        $this->line("Processing deal: {$dealName} (ID: {$dealId})");

        // Skip if no facturino_lead_id
        if (empty($facturinoLeadId)) {
            $this->line("  [SKIP] No facturino_lead_id");
            return ['skipped_no_lead' => 1];
        }

        // Skip if already has a partner
        if (!empty($facturinoPartnerId)) {
            $this->line("  [SKIP] Already has partner ID: {$facturinoPartnerId}");
            return ['skipped_has_partner' => 1];
        }

        // Look up local lead
        $lead = OutreachLead::find($facturinoLeadId);

        if (!$lead) {
            $this->warn("  [WARN] Lead not found locally: {$facturinoLeadId}");
            return ['error' => "Deal {$dealId}: Lead {$facturinoLeadId} not found"];
        }

        // Check if partner already exists for this email
        $existingPartner = Partner::where('email', $lead->email)->first();

        if ($existingPartner) {
            $this->line("  Partner already exists (ID: {$existingPartner->id})");

            if (!$dryRun) {
                // Update deal with existing partner ID
                $this->updateDealWithPartner($dealId, $existingPartner->id, $hubSpotService);
            }

            return ['skipped_has_partner' => 1];
        }

        if ($dryRun) {
            $this->info("  [DRY-RUN] Would create partner for: {$lead->email}");
            return ['partners_created' => 1, 'invites_sent' => 1];
        }

        // Create partner
        try {
            $result = $this->createPartnerFromLead($lead, $dealId, $hubSpotService);
            return $result;
        } catch (\Exception $e) {
            $this->error("  [ERROR] " . $e->getMessage());
            return ['error' => "Deal {$dealId}: " . $e->getMessage()];
        }
    }

    /**
     * Create a partner from an outreach lead.
     *
     * @param OutreachLead $lead
     * @param string $dealId
     * @param HubSpotService $hubSpotService
     * @return array
     */
    protected function createPartnerFromLead(
        OutreachLead $lead,
        string $dealId,
        HubSpotService $hubSpotService
    ): array {
        DB::beginTransaction();

        try {
            // Create user account
            $user = User::create([
                'name' => $lead->contact_name ?: $lead->company_name ?: 'Partner',
                'email' => $lead->email,
                'password' => bcrypt(Str::random(32)), // Temporary password
            ]);

            // Create partner account
            $partner = Partner::create([
                'user_id' => $user->id,
                'name' => $lead->contact_name ?: $lead->company_name ?: 'Partner',
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company_name' => $lead->company_name,
                'is_active' => false, // Activate after invite accepted
                'kyc_status' => 'pending',
                'commission_rate' => config('affiliate.direct_rate', 0.20),
            ]);

            // Update local lead
            $lead->update([
                'partner_id' => $partner->id,
                'status' => OutreachLead::STATUS_INVITE_SENT,
            ]);

            DB::commit();

            $this->info("  Partner created (ID: {$partner->id})");

            // Send partner invite email
            $inviteSent = $this->sendPartnerInviteEmail($partner, $user);

            // Update HubSpot deal
            $this->updateDealWithPartner($dealId, $partner->id, $hubSpotService);

            // Move deal to "Invite Sent" stage
            $inviteSentStageId = $hubSpotService->getStageIdByLabel('Invite Sent');
            if ($inviteSentStageId) {
                $hubSpotService->updateDeal($dealId, [
                    'dealstage' => $inviteSentStageId,
                ]);
                $this->line("  Deal moved to 'Invite Sent' stage");
            }

            // Update local HubSpot mapping
            $mapping = HubSpotLeadMap::findByEmail($lead->email);
            if ($mapping) {
                $mapping->updateDealStage('invite_sent');
            }

            // Add note to contact
            if ($mapping && $mapping->hubspot_contact_id) {
                try {
                    $hubSpotService->createNote(
                        $mapping->hubspot_contact_id,
                        "Partner account created (ID: {$partner->id}). Invite email sent."
                    );
                } catch (\Exception $e) {
                    Log::debug('Failed to add HubSpot note', ['error' => $e->getMessage()]);
                }
            }

            Log::info('Partner created from HubSpot deal', [
                'partner_id' => $partner->id,
                'email' => $lead->email,
                'hubspot_deal_id' => $dealId,
            ]);

            return [
                'partners_created' => 1,
                'invites_sent' => $inviteSent ? 1 : 0,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update HubSpot deal with partner ID.
     *
     * @param string $dealId
     * @param int $partnerId
     * @param HubSpotService $hubSpotService
     * @return void
     */
    protected function updateDealWithPartner(
        string $dealId,
        int $partnerId,
        HubSpotService $hubSpotService
    ): void {
        try {
            $hubSpotService->updateDeal($dealId, [
                'facturino_partner_id' => (string) $partnerId,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to update HubSpot deal with partner ID', [
                'deal_id' => $dealId,
                'partner_id' => $partnerId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send partner invite email.
     *
     * @param Partner $partner
     * @param User $user
     * @return bool
     */
    protected function sendPartnerInviteEmail(Partner $partner, User $user): bool
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

            $this->line("  Invite email sent to: {$partner->email}");

            Log::info('Partner invite email sent', [
                'partner_id' => $partner->id,
                'email' => $partner->email,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->warn("  Failed to send invite email: " . $e->getMessage());

            Log::error('Failed to send partner invite email', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * List available pipeline stages.
     *
     * @param HubSpotService $hubSpotService
     * @return void
     */
    protected function listAvailableStages(HubSpotService $hubSpotService): void
    {
        try {
            $pipelines = $hubSpotService->getPipelines();

            foreach ($pipelines as $pipeline) {
                $this->line("  Pipeline: " . ($pipeline['label'] ?? 'Unknown'));

                $stages = $pipeline['stages'] ?? [];
                foreach ($stages as $stage) {
                    $this->line("    - " . ($stage['label'] ?? 'Unknown') . " (ID: " . ($stage['id'] ?? 'unknown') . ")");
                }
            }
        } catch (\Exception $e) {
            $this->warn("Could not fetch stages: " . $e->getMessage());
        }
    }

    /**
     * Display processing results.
     *
     * @param array $stats
     * @return void
     */
    protected function displayResults(array $stats): void
    {
        $this->newLine();
        $this->line('Results');
        $this->line('-------');
        $this->line("Deals processed: {$stats['processed']}");
        $this->line("Partners created: {$stats['partners_created']}");
        $this->line("Invites sent: {$stats['invites_sent']}");
        $this->line("Skipped (no lead ID): {$stats['skipped_no_lead']}");
        $this->line("Skipped (has partner): {$stats['skipped_has_partner']}");

        if (!empty($stats['errors'])) {
            $this->newLine();
            $this->warn('Errors (' . count($stats['errors']) . '):');
            foreach ($stats['errors'] as $error) {
                $this->line("  - {$error}");
            }
        }

        $this->newLine();
        $this->info('Polling completed.');
    }
}

// CLAUDE-CHECKPOINT
