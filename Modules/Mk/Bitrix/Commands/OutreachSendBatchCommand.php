<?php

namespace Modules\Mk\Bitrix\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Bitrix\Models\OutreachLead;
use Modules\Mk\Bitrix\Models\OutreachSend;
use Modules\Mk\Bitrix\Models\Suppression;
use Modules\Mk\Bitrix\Models\UnsubscribeToken;
use Modules\Mk\Bitrix\Services\HubSpotService;
use Modules\Mk\Bitrix\Services\OutreachService;
use Modules\Mk\Bitrix\Services\PostmarkOutreachService;

/**
 * OutreachSendBatchCommand
 *
 * Sends outreach emails in batches via Postmark, respecting rate limits.
 * Handles initial emails and follow-ups with appropriate jitter.
 * Syncs email engagement to HubSpot CRM.
 *
 * Usage:
 *   php artisan outreach:send-batch
 *   php artisan outreach:send-batch --limit=20
 *   php artisan outreach:send-batch --template=first_touch
 *   php artisan outreach:send-batch --dry-run
 */
class OutreachSendBatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outreach:send-batch
                            {--limit=20 : Maximum emails to send in this batch}
                            {--dry-run : Show what would be sent without sending}
                            {--template= : Force specific template (first_touch, followup_1, followup_2)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a batch of outreach emails via Postmark, respecting rate limits and logging to HubSpot';

    /**
     * Daily send limit.
     */
    protected int $dailyLimit = 100;

    /**
     * Hourly send limit.
     */
    protected int $hourlyLimit = 20;

    /**
     * Minimum jitter between sends in seconds.
     */
    protected int $minJitterSeconds = 30;

    /**
     * Maximum jitter between sends in seconds.
     */
    protected int $maxJitterSeconds = 60;

    /**
     * Follow-up schedule (template => days after initial).
     *
     * @var array<string, int>
     */
    protected array $followUpSchedule = [
        'followup_1' => 3,
        'followup_2' => 7,
    ];

    /**
     * Valid templates.
     *
     * @var array<string>
     */
    protected array $validTemplates = ['first_touch', 'followup_1', 'followup_2'];

    /**
     * HubSpot service instance.
     */
    protected ?HubSpotService $hubSpotService = null;

    /**
     * Execute the console command.
     *
     * @param OutreachService $outreachService
     * @param PostmarkOutreachService $postmarkService
     * @param HubSpotService $hubSpotService
     * @return int
     */
    public function handle(
        OutreachService $outreachService,
        PostmarkOutreachService $postmarkService,
        HubSpotService $hubSpotService
    ): int {
        $this->hubSpotService = $hubSpotService;
        $this->dailyLimit = config('bitrix.outreach.daily_limit', 100);
        $this->hourlyLimit = config('bitrix.outreach.hourly_limit', 20);

        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');
        $forcedTemplate = $this->option('template');

        $this->info('Outreach Batch Send');
        $this->line('===================');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No emails will be sent');
            $this->newLine();
        }

        // Validate forced template if provided
        if ($forcedTemplate && !in_array($forcedTemplate, $this->validTemplates)) {
            $this->error("Invalid template: {$forcedTemplate}");
            $this->line('Valid templates: ' . implode(', ', $this->validTemplates));
            return self::FAILURE;
        }

        // Step 1: Check throttle limits
        $dailyCount = OutreachSend::where('sent_at', '>=', now()->startOfDay())->count();
        $hourlyCount = OutreachSend::where('sent_at', '>=', now()->subHour())->count();

        $this->displayQuotaStatus($dailyCount, $hourlyCount);

        if ($dailyCount >= $this->dailyLimit) {
            $this->error("Daily limit ({$this->dailyLimit}) reached");
            return self::FAILURE;
        }

        if ($hourlyCount >= $this->hourlyLimit) {
            $this->error("Hourly limit ({$this->hourlyLimit}) reached");
            return self::FAILURE;
        }

        // Calculate effective limit
        $remainingDaily = $this->dailyLimit - $dailyCount;
        $remainingHourly = $this->hourlyLimit - $hourlyCount;
        $effectiveLimit = min($limit, $remainingDaily, $remainingHourly);

        if ($effectiveLimit <= 0) {
            $this->info('No capacity available for sending.');
            return self::SUCCESS;
        }

        $this->info("Effective batch size: {$effectiveLimit}");
        $this->newLine();

        // Step 2: Find eligible leads
        $leads = $this->findEligibleLeads($effectiveLimit, $forcedTemplate);

        if ($leads->isEmpty()) {
            $this->info('No eligible leads found for outreach.');
            return self::SUCCESS;
        }

        $this->info("Found {$leads->count()} eligible leads");
        $this->newLine();

        // Step 3 & 4: Process leads
        $stats = $this->processLeads(
            $leads,
            $outreachService,
            $postmarkService,
            $dryRun,
            $forcedTemplate
        );

        // Display results
        $this->displayResults($stats);

        return self::SUCCESS;
    }

    /**
     * Display quota status.
     *
     * @param int $dailyCount
     * @param int $hourlyCount
     * @return void
     */
    protected function displayQuotaStatus(int $dailyCount, int $hourlyCount): void
    {
        $dailyRemaining = max(0, $this->dailyLimit - $dailyCount);
        $hourlyRemaining = max(0, $this->hourlyLimit - $hourlyCount);

        $this->line('Quota Status:');
        $this->line("  Daily: {$dailyCount}/{$this->dailyLimit} used ({$dailyRemaining} remaining)");
        $this->line("  Hourly: {$hourlyCount}/{$this->hourlyLimit} used ({$hourlyRemaining} remaining)");
        $this->newLine();
    }

    /**
     * Find eligible leads for outreach.
     *
     * @param int $limit
     * @param string|null $forcedTemplate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function findEligibleLeads(int $limit, ?string $forcedTemplate)
    {
        $query = OutreachLead::query()
            // Has HubSpot mapping with deal in sendable stage
            ->whereHas('hubspotMapping', function ($q) {
                $q->whereIn('deal_stage', ['new_lead', 'new', 'emailed', 'followup_due', 'followup'])
                  ->whereNotNull('hubspot_deal_id');
            })
            // Not suppressed
            ->whereDoesntHave('suppression', function ($q) {
                // This requires a suppression relation - use email check instead
            })
            ->whereNotIn('email', function ($subQuery) {
                $subQuery->select('email')->from('suppressions');
            });

        if ($forcedTemplate) {
            // Apply template-specific filtering
            $query = $this->applyTemplateFilter($query, $forcedTemplate);
        } else {
            // Auto-select: either never contacted, or next_followup_at is due
            $query->where(function ($q) {
                $q->whereNull('last_contacted_at')
                  ->orWhere(function ($q2) {
                      // Has a next followup that is due
                      $q2->whereHas('sends')
                         ->whereIn('status', [
                             OutreachLead::STATUS_EMAILED,
                             OutreachLead::STATUS_FOLLOWUP,
                         ]);
                  });
            });
        }

        return $query
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Apply template-specific filtering to query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $template
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyTemplateFilter($query, string $template)
    {
        if ($template === 'first_touch') {
            // Never sent any email
            return $query->whereDoesntHave('sends')
                ->where('status', OutreachLead::STATUS_NEW);
        }

        if ($template === 'followup_1') {
            $daysAfter = $this->followUpSchedule['followup_1'];
            return $query
                ->whereHas('sends', function ($q) use ($daysAfter) {
                    $q->where('template_key', 'first_touch')
                      ->where('sent_at', '<=', now()->subDays($daysAfter));
                })
                ->whereDoesntHave('sends', function ($q) {
                    $q->where('template_key', 'followup_1');
                })
                ->whereIn('status', [OutreachLead::STATUS_EMAILED, OutreachLead::STATUS_FOLLOWUP]);
        }

        if ($template === 'followup_2') {
            $daysAfter = $this->followUpSchedule['followup_2'];
            return $query
                ->whereHas('sends', function ($q) use ($daysAfter) {
                    $q->where('template_key', 'first_touch')
                      ->where('sent_at', '<=', now()->subDays($daysAfter));
                })
                ->whereHas('sends', function ($q) {
                    $q->where('template_key', 'followup_1');
                })
                ->whereDoesntHave('sends', function ($q) {
                    $q->where('template_key', 'followup_2');
                })
                ->whereIn('status', [OutreachLead::STATUS_EMAILED, OutreachLead::STATUS_FOLLOWUP]);
        }

        return $query;
    }

    /**
     * Determine the template key for a lead based on send count.
     *
     * @param OutreachLead $lead
     * @return string|null Template key or null if max emails reached
     */
    protected function getTemplateKey(OutreachLead $lead): ?string
    {
        $sendCount = $lead->sends()->count();

        if ($sendCount === 0) {
            return 'first_touch';
        }

        // Check if first_touch was sent and enough time has passed for followup_1
        $firstTouchSend = $lead->sends()->where('template_key', 'first_touch')->first();
        if (!$firstTouchSend) {
            // Legacy: check for 'initial' template key
            $firstTouchSend = $lead->sends()->where('template_key', 'initial')->first();
        }

        if (!$firstTouchSend) {
            return 'first_touch';
        }

        $hasFollowup1 = $lead->sends()
            ->whereIn('template_key', ['followup_1'])
            ->exists();

        $hasFollowup2 = $lead->sends()
            ->whereIn('template_key', ['followup_2'])
            ->exists();

        if (!$hasFollowup1) {
            // Check if enough days have passed since first touch
            $daysSinceFirst = $firstTouchSend->sent_at->diffInDays(now());
            if ($daysSinceFirst >= $this->followUpSchedule['followup_1']) {
                return 'followup_1';
            }
            return null; // Not yet time for follow-up
        }

        if (!$hasFollowup2) {
            // Check if enough days have passed since first touch
            $daysSinceFirst = $firstTouchSend->sent_at->diffInDays(now());
            if ($daysSinceFirst >= $this->followUpSchedule['followup_2']) {
                return 'followup_2';
            }
            return null; // Not yet time for follow-up
        }

        // Max 3 emails reached
        return null;
    }

    /**
     * Process leads and send emails.
     *
     * @param \Illuminate\Database\Eloquent\Collection $leads
     * @param OutreachService $outreachService
     * @param PostmarkOutreachService $postmarkService
     * @param bool $dryRun
     * @param string|null $forcedTemplate
     * @return array
     */
    protected function processLeads(
        $leads,
        OutreachService $outreachService,
        PostmarkOutreachService $postmarkService,
        bool $dryRun,
        ?string $forcedTemplate
    ): array {
        $stats = ['sent' => 0, 'skipped' => 0, 'errors' => 0];

        $bar = $this->output->createProgressBar($leads->count());
        $bar->start();

        foreach ($leads as $index => $lead) {
            try {
                // Determine template (forced or auto-selected)
                $templateKey = $forcedTemplate ?? $this->getTemplateKey($lead);

                if (!$templateKey) {
                    $this->logSkipped($lead, 'Max emails reached or not due for follow-up');
                    $stats['skipped']++;
                    $bar->advance();
                    continue;
                }

                // Check if suppressed
                if (Suppression::isSuppressed($lead->email)) {
                    $this->logSkipped($lead, 'Email is suppressed');
                    $stats['skipped']++;
                    $bar->advance();
                    continue;
                }

                if ($dryRun) {
                    $this->logDryRun($lead, $templateKey);
                    $stats['sent']++;
                    $bar->advance();
                    continue;
                }

                // Add jitter (30-60 seconds between sends)
                if ($index > 0) {
                    $jitter = rand($this->minJitterSeconds, $this->maxJitterSeconds);
                    sleep($jitter);
                }

                // Generate unsubscribe token
                $unsubToken = UnsubscribeToken::getOrCreateForLead($lead->id);
                $unsubUrl = $unsubToken->getUnsubscribeUrl();

                // Map template key for PostmarkService (first_touch -> initial)
                $postmarkTemplateKey = $templateKey === 'first_touch' ? 'initial' : $templateKey;

                // Send via Postmark
                $messageId = $postmarkService->sendOutreachEmail(
                    $lead->email,
                    $postmarkTemplateKey,
                    [
                        'companyName' => $lead->company_name ?? 'there',
                        'demoUrl' => config('app.url') . '/demo',
                    ],
                    $unsubUrl
                );

                if (!$messageId) {
                    $this->error("  Failed to send to {$lead->email}");
                    $stats['errors']++;
                    $bar->advance();
                    continue;
                }

                // Record send
                $send = OutreachSend::create([
                    'email' => $lead->email,
                    'outreach_lead_id' => $lead->id,
                    'template_key' => $templateKey,
                    'postmark_message_id' => $messageId,
                    'status' => OutreachSend::STATUS_SENT,
                    'sent_at' => now(),
                ]);

                // Calculate next follow-up date
                $nextFollowup = $this->calculateNextFollowup($templateKey);

                // Update lead
                $lead->update([
                    'last_contacted_at' => now(),
                    'status' => OutreachLead::STATUS_EMAILED,
                ]);

                // Sync to HubSpot
                $this->syncToHubSpot($lead, $templateKey, $messageId, $nextFollowup);

                $this->info("  Sent {$templateKey} to {$lead->email}");
                $stats['sent']++;

            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Error sending outreach email', [
                    'lead_id' => $lead->id,
                    'email' => $lead->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("  Error for {$lead->email}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $stats;
    }

    /**
     * Calculate next follow-up date based on template.
     *
     * @param string $templateKey
     * @return Carbon
     */
    protected function calculateNextFollowup(string $templateKey): Carbon
    {
        if ($templateKey === 'first_touch') {
            return now()->addDays($this->followUpSchedule['followup_1']);
        }

        if ($templateKey === 'followup_1') {
            $daysUntilFollowup2 = $this->followUpSchedule['followup_2'] - $this->followUpSchedule['followup_1'];
            return now()->addDays($daysUntilFollowup2);
        }

        // After followup_2, no more follow-ups
        return now()->addDays(30);
    }

    /**
     * Sync email send to HubSpot.
     *
     * @param OutreachLead $lead
     * @param string $templateKey
     * @param string $messageId
     * @param Carbon $nextFollowup
     * @return void
     */
    protected function syncToHubSpot(
        OutreachLead $lead,
        string $templateKey,
        string $messageId,
        Carbon $nextFollowup
    ): void {
        if (!$this->hubSpotService || !$this->hubSpotService->isConfigured()) {
            Log::debug('HubSpot not configured, skipping sync');
            return;
        }

        try {
            $mapping = $lead->hubspotMapping;

            if (!$mapping || !$mapping->hubspot_deal_id) {
                Log::debug('No HubSpot deal mapping for lead', [
                    'lead_id' => $lead->id,
                    'email' => $lead->email,
                ]);
                return;
            }

            // Update deal stage to 'emailed' if first email
            if ($templateKey === 'first_touch') {
                $stageId = $this->hubSpotService->getStageIdByLabel('Emailed');
                if ($stageId) {
                    $this->hubSpotService->updateDeal($mapping->hubspot_deal_id, [
                        'dealstage' => $stageId,
                    ]);
                    $mapping->updateDealStage('emailed');
                }
            }

            // Update deal properties with dates
            $dealProperties = [
                'fct_last_touch_date' => now()->format('Y-m-d'),
                'fct_next_followup_date' => $nextFollowup->format('Y-m-d'),
                'fct_last_email_template' => $templateKey,
            ];

            try {
                $this->hubSpotService->updateDeal($mapping->hubspot_deal_id, $dealProperties);
            } catch (\Exception $e) {
                // Custom properties may not exist, log and continue
                Log::warning('Failed to update deal custom properties', [
                    'deal_id' => $mapping->hubspot_deal_id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Log note to contact timeline
            if ($mapping->hubspot_contact_id) {
                $noteBody = sprintf(
                    "Outreach email sent\nTemplate: %s\nPostmark ID: %s\nNext follow-up: %s",
                    $templateKey,
                    $messageId,
                    $nextFollowup->format('Y-m-d')
                );

                $this->hubSpotService->createNote($mapping->hubspot_contact_id, $noteBody);

                // Log email engagement
                $this->hubSpotService->logEmailEngagement(
                    $mapping->hubspot_contact_id,
                    $this->getEmailSubject($templateKey),
                    "Outreach email sent: {$templateKey}",
                    $messageId
                );
            }

            Log::info('HubSpot sync completed for outreach email', [
                'lead_id' => $lead->id,
                'template' => $templateKey,
                'deal_id' => $mapping->hubspot_deal_id,
                'contact_id' => $mapping->hubspot_contact_id,
            ]);

        } catch (\Exception $e) {
            Log::warning('Failed to sync email to HubSpot', [
                'lead_id' => $lead->id,
                'template' => $templateKey,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get email subject for a template.
     *
     * @param string $templateKey
     * @return string
     */
    protected function getEmailSubject(string $templateKey): string
    {
        return match ($templateKey) {
            'first_touch' => 'Facturino Partner Opportunity',
            'followup_1' => 'Following Up - Facturino Partnership',
            'followup_2' => 'Last Chance - Facturino Partnership',
            default => "Facturino Outreach: {$templateKey}",
        };
    }

    /**
     * Log dry run action.
     *
     * @param OutreachLead $lead
     * @param string $templateKey
     * @return void
     */
    protected function logDryRun(OutreachLead $lead, string $templateKey): void
    {
        $message = "  [DRY-RUN] Would send {$templateKey} to: {$lead->email}";

        if ($lead->company_name) {
            $message .= " ({$lead->company_name})";
        }

        $this->output->writeln($message);
    }

    /**
     * Log skipped lead.
     *
     * @param OutreachLead $lead
     * @param string $reason
     * @return void
     */
    protected function logSkipped(OutreachLead $lead, string $reason): void
    {
        Log::debug('Lead skipped for outreach', [
            'lead_id' => $lead->id,
            'email' => $lead->email,
            'reason' => $reason,
        ]);
    }

    /**
     * Display batch results.
     *
     * @param array $stats
     * @return void
     */
    protected function displayResults(array $stats): void
    {
        $this->newLine();
        $this->line('Batch Results');
        $this->line('-------------');
        $this->line("  Sent: {$stats['sent']}");
        $this->line("  Skipped: {$stats['skipped']}");
        $this->line("  Errors: {$stats['errors']}");
        $this->newLine();

        $total = $stats['sent'] + $stats['skipped'] + $stats['errors'];
        $successRate = $total > 0 ? round(($stats['sent'] / $total) * 100, 1) : 0;

        $this->info("Success rate: {$successRate}%");
    }
}

// CLAUDE-CHECKPOINT
