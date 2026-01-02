<?php

namespace Modules\Mk\Bitrix\Services;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Mk\Bitrix\Models\HubSpotLeadMap;
use Modules\Mk\Bitrix\Models\OutreachLead;
use Modules\Mk\Bitrix\Models\OutreachSend;
use Modules\Mk\Bitrix\Models\Suppression;

/**
 * OutreachService
 *
 * Orchestrates outreach campaigns - sending emails, managing suppressions,
 * processing follow-ups, and converting leads to partners.
 * Coordinates between HubSpot CRM leads and Postmark email sending.
 */
class OutreachService
{
    /**
     * Postmark outreach service.
     */
    protected PostmarkOutreachService $postmarkService;

    /**
     * HubSpot API client.
     */
    protected HubSpotApiClient $hubspotClient;

    /**
     * Minimum days between contacts.
     */
    protected int $contactCooldownDays = 2;

    /**
     * Follow-up schedule (template => days after initial).
     */
    protected array $followUpSchedule = [
        'followup_1' => 3,
        'followup_2' => 7,
    ];

    /**
     * Create a new OutreachService instance.
     *
     * @param HubSpotApiClient $hubspotClient
     * @param PostmarkOutreachService $postmarkService
     */
    public function __construct(
        HubSpotApiClient $hubspotClient,
        PostmarkOutreachService $postmarkService
    ) {
        $this->hubspotClient = $hubspotClient;
        $this->postmarkService = $postmarkService;
    }

    /**
     * Check if we can send to a lead.
     *
     * @param OutreachLead $lead
     * @return bool
     */
    public function canSendToLead(OutreachLead $lead): bool
    {
        // Check suppression list
        if ($this->isEmailSuppressed($lead->email)) {
            return false;
        }

        // Check if recently emailed (cooldown period)
        if ($lead->last_contacted_at &&
            $lead->last_contacted_at->diffInDays(now()) < $this->contactCooldownDays) {
            return false;
        }

        // Check if lead is in a sendable status
        $sendableStatuses = [
            OutreachLead::STATUS_NEW,
            OutreachLead::STATUS_EMAILED,
            OutreachLead::STATUS_FOLLOWUP,
        ];

        if (!in_array($lead->status, $sendableStatuses)) {
            return false;
        }

        return true;
    }

    /**
     * Send initial outreach email to a lead.
     *
     * @param OutreachLead $lead
     * @return OutreachSend|null
     */
    public function sendInitialEmail(OutreachLead $lead): ?OutreachSend
    {
        if (!$this->canSendToLead($lead)) {
            return null;
        }

        $unsubscribeUrl = $this->generateUnsubscribeUrl($lead->email);

        $messageId = $this->postmarkService->sendOutreachEmail(
            $lead->email,
            'initial',
            [
                'companyName' => $lead->company_name ?? 'there',
                'demoUrl' => config('app.url') . '/demo',
            ],
            $unsubscribeUrl
        );

        if (!$messageId) {
            return null;
        }

        // Create send record
        $send = OutreachSend::create([
            'email' => $lead->email,
            'outreach_lead_id' => $lead->id,
            'template_key' => 'initial',
            'postmark_message_id' => $messageId,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Update lead status
        $lead->update([
            'status' => OutreachLead::STATUS_EMAILED,
            'last_contacted_at' => now(),
        ]);

        // Sync to HubSpot
        $this->syncLeadToHubSpot($lead, 'email_sent');

        // Log email engagement in HubSpot
        $this->logEmailSentToHubSpot($lead, $send);

        Log::info('Initial outreach email sent', [
            'lead_id' => $lead->id,
            'email' => $lead->email,
            'send_id' => $send->id,
        ]);

        return $send;
    }

    /**
     * Send a follow-up email to a lead.
     *
     * @param OutreachLead $lead
     * @param string $templateKey Template key (followup_1 or followup_2)
     * @return OutreachSend|null
     */
    public function sendFollowUp(OutreachLead $lead, string $templateKey): ?OutreachSend
    {
        if (!$this->canSendToLead($lead)) {
            return null;
        }

        // Verify this is a valid follow-up template
        if (!in_array($templateKey, ['followup_1', 'followup_2'])) {
            Log::warning('Invalid follow-up template', ['template' => $templateKey]);
            return null;
        }

        // Check if this follow-up was already sent
        $alreadySent = OutreachSend::where('outreach_lead_id', $lead->id)
            ->where('template_key', $templateKey)
            ->exists();

        if ($alreadySent) {
            Log::info('Follow-up already sent, skipping', [
                'lead_id' => $lead->id,
                'template' => $templateKey,
            ]);
            return null;
        }

        $unsubscribeUrl = $this->generateUnsubscribeUrl($lead->email);

        $messageId = $this->postmarkService->sendOutreachEmail(
            $lead->email,
            $templateKey,
            [
                'companyName' => $lead->company_name ?? 'there',
                'demoUrl' => config('app.url') . '/demo',
            ],
            $unsubscribeUrl
        );

        if (!$messageId) {
            return null;
        }

        // Create send record
        $send = OutreachSend::create([
            'email' => $lead->email,
            'outreach_lead_id' => $lead->id,
            'template_key' => $templateKey,
            'postmark_message_id' => $messageId,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Update lead
        $lead->update([
            'status' => OutreachLead::STATUS_FOLLOWUP,
            'last_contacted_at' => now(),
        ]);

        // Sync to HubSpot
        $this->syncLeadToHubSpot($lead, 'followup_sent');

        // Log email engagement in HubSpot
        $this->logEmailSentToHubSpot($lead, $send);

        Log::info('Follow-up email sent', [
            'lead_id' => $lead->id,
            'email' => $lead->email,
            'template' => $templateKey,
            'send_id' => $send->id,
        ]);

        return $send;
    }

    /**
     * Process leads due for follow-up emails.
     *
     * @return array{sent: int, skipped: int, errors: int}
     */
    public function processFollowUps(): array
    {
        $stats = ['sent' => 0, 'skipped' => 0, 'errors' => 0];

        foreach ($this->followUpSchedule as $templateKey => $daysAfter) {
            // Find leads that:
            // 1. Have been emailed but not with this follow-up
            // 2. Initial email was sent X days ago
            // 3. Are not suppressed
            // 4. Are in EMAILED or FOLLOWUP status
            $eligibleLeads = OutreachLead::whereIn('status', [
                    OutreachLead::STATUS_EMAILED,
                    OutreachLead::STATUS_FOLLOWUP,
                ])
                ->whereHas('sends', function ($query) use ($daysAfter) {
                    $query->where('template_key', 'initial')
                        ->where('sent_at', '<=', now()->subDays($daysAfter));
                })
                ->whereDoesntHave('sends', function ($query) use ($templateKey) {
                    $query->where('template_key', $templateKey);
                })
                ->limit(20) // Process in batches
                ->get();

            foreach ($eligibleLeads as $lead) {
                if (!$this->postmarkService->isWithinHourlyLimit()) {
                    Log::info('Hourly limit reached, stopping follow-up processing');
                    break 2;
                }

                try {
                    $send = $this->sendFollowUp($lead, $templateKey);

                    if ($send) {
                        $stats['sent']++;
                    } else {
                        $stats['skipped']++;
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error('Error sending follow-up', [
                        'lead_id' => $lead->id,
                        'template' => $templateKey,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Add jitter between sends (30-60 seconds for follow-ups)
                if ($this->postmarkService->isWithinHourlyLimit()) {
                    usleep(rand(30000000, 60000000));
                }
            }
        }

        return $stats;
    }

    /**
     * Add an email to the suppression list.
     *
     * @param string $email
     * @param string $type Suppression type/reason
     * @param array|null $meta Optional metadata
     * @return Suppression
     */
    public function suppressEmail(string $email, string $type, ?array $meta = null): Suppression
    {
        Log::info('Suppressing email', [
            'email' => $email,
            'type' => $type,
        ]);

        return Suppression::suppress($email, $type, Suppression::SOURCE_SYSTEM, $meta);
    }

    /**
     * Check if an email is suppressed.
     *
     * @param string $email
     * @return bool
     */
    public function isEmailSuppressed(string $email): bool
    {
        return Suppression::isSuppressed($email);
    }

    /**
     * Generate an unsubscribe token for an email.
     *
     * @param string $email
     * @return string
     */
    public function generateUnsubscribeToken(string $email): string
    {
        // Use HMAC to create a verifiable token
        $secret = config('app.key');
        $timestamp = now()->timestamp;

        return base64_encode(
            hash_hmac('sha256', $email . '|' . $timestamp, $secret) . '|' . $timestamp . '|' . $email
        );
    }

    /**
     * Generate full unsubscribe URL for an email.
     *
     * @param string $email
     * @return string
     */
    protected function generateUnsubscribeUrl(string $email): string
    {
        $token = $this->generateUnsubscribeToken($email);

        return config('app.url') . '/unsubscribe?token=' . urlencode($token);
    }

    /**
     * Process an unsubscribe request.
     *
     * @param string $token
     * @return bool
     */
    public function processUnsubscribe(string $token): bool
    {
        try {
            $decoded = base64_decode($token);
            $parts = explode('|', $decoded);

            if (count($parts) !== 3) {
                Log::warning('Invalid unsubscribe token format');
                return false;
            }

            [$hash, $timestamp, $email] = $parts;

            // Verify token is not too old (7 days max)
            if ((now()->timestamp - (int) $timestamp) > 604800) {
                Log::warning('Unsubscribe token expired', ['email' => $email]);
                return false;
            }

            // Verify hash
            $secret = config('app.key');
            $expectedHash = hash_hmac('sha256', $email . '|' . $timestamp, $secret);

            if (!hash_equals($expectedHash, $hash)) {
                Log::warning('Invalid unsubscribe token hash');
                return false;
            }

            // Add to suppression list
            $this->suppressEmail($email, Suppression::REASON_UNSUBSCRIBE, [
                'source' => 'one_click',
                'timestamp' => now()->toISOString(),
            ]);

            // Update any leads with this email
            OutreachLead::where('email', strtolower($email))
                ->update(['status' => OutreachLead::STATUS_LOST]);

            // Update HubSpot deal stage to lost
            $lead = OutreachLead::where('email', strtolower($email))->first();
            if ($lead) {
                $this->updateHubSpotDealStage($lead, 'lost');
            }

            Log::info('Unsubscribe processed', ['email' => $email]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error processing unsubscribe', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Convert an outreach lead to a partner account.
     *
     * @param OutreachLead $lead
     * @return Partner|null
     */
    public function convertLeadToPartner(OutreachLead $lead): ?Partner
    {
        try {
            return DB::transaction(function () use ($lead) {
                // Check if partner already exists with this email
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

                // Create user account first
                $password = Str::random(16);
                $user = User::create([
                    'name' => $lead->contact_name ?? $lead->company_name ?? 'Partner',
                    'email' => $lead->email,
                    'password' => Hash::make($password),
                    'role' => 'partner',
                ]);

                // Create partner record
                $partner = Partner::create([
                    'name' => $lead->contact_name ?? $lead->company_name ?? 'Partner',
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'company_name' => $lead->company_name,
                    'commission_rate' => config('affiliate.direct_rate', 0.20),
                    'is_active' => true,
                    'kyc_status' => 'pending',
                    'user_id' => $user->id,
                    'notes' => 'Converted from outreach lead #' . $lead->id,
                ]);

                // Update lead status
                $lead->update([
                    'status' => OutreachLead::STATUS_PARTNER_CREATED,
                    'partner_id' => $partner->id,
                ]);

                // Sync to HubSpot
                $this->syncLeadToHubSpot($lead, 'converted');

                // Update HubSpot deal to won stage
                $this->updateHubSpotDealStage($lead, 'won');

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
     * Sync lead status and data to HubSpot.
     *
     * @param OutreachLead $lead
     * @param string $action Action type (update, email_sent, followup_sent, converted)
     * @return bool
     */
    public function syncLeadToHubSpot(OutreachLead $lead, string $action = 'update'): bool
    {
        try {
            // Get or create HubSpot mapping
            $mapping = $lead->hubspotMapping;
            if (!$mapping) {
                $mapping = HubSpotLeadMap::syncMapping(
                    $lead->email,
                    $lead->id
                );
            }

            // Upsert contact in HubSpot
            $contactProperties = [
                'firstname' => $lead->contact_name ?? '',
                'company' => $lead->company_name ?? '',
                'phone' => $lead->phone ?? '',
                'city' => $lead->city ?? '',
                'facturino_lead_status' => $lead->status,
                'facturino_source' => $lead->source ?? '',
            ];

            $contact = $this->hubspotClient->upsertContact($lead->email, $contactProperties);

            if (!$contact) {
                Log::warning('Failed to upsert contact in HubSpot', ['lead_id' => $lead->id]);
                return false;
            }

            $contactId = $contact['id'];
            $companyId = null;
            $dealId = $mapping->hubspot_deal_id;

            // Upsert company if domain available
            $domain = $this->hubspotClient->extractDomainFromEmail($lead->email);
            if ($domain) {
                $companyProperties = [
                    'name' => $lead->company_name ?? $domain,
                    'city' => $lead->city ?? '',
                ];

                $company = $this->hubspotClient->upsertCompany($domain, $companyProperties);

                if ($company) {
                    $companyId = $company['id'];

                    // Associate contact with company
                    $this->hubspotClient->associateContactToCompany($contactId, $companyId);
                }
            }

            // Map local status to HubSpot deal stage
            $stageMap = [
                OutreachLead::STATUS_NEW => 'new',
                OutreachLead::STATUS_EMAILED => 'emailed',
                OutreachLead::STATUS_FOLLOWUP => 'followup',
                OutreachLead::STATUS_INTERESTED => 'interested',
                OutreachLead::STATUS_INVITE_SENT => 'invite_sent',
                OutreachLead::STATUS_PARTNER_CREATED => 'won',
                OutreachLead::STATUS_ACTIVE => 'won',
                OutreachLead::STATUS_LOST => 'lost',
            ];

            $stageName = $stageMap[$lead->status] ?? 'new';
            $stageId = $this->hubspotClient->getStageId($stageName) ?? $stageName;

            // Create or update deal
            $dealProperties = [
                'dealname' => 'Outreach: ' . ($lead->company_name ?? $lead->email),
                'dealstage' => $stageId,
                'facturino_lead_id' => (string) $lead->id,
            ];

            if ($lead->partner_id) {
                $dealProperties['facturino_partner_id'] = (string) $lead->partner_id;
            }

            $deal = $this->hubspotClient->upsertDeal($dealId, $dealProperties);

            if ($deal) {
                $dealId = $deal['id'];

                // Associate deal with contact
                $this->hubspotClient->associateDealToContact($dealId, $contactId);

                // Associate deal with company if available
                if ($companyId) {
                    $this->hubspotClient->associateDealToCompany($dealId, $companyId);
                }
            }

            // Update mapping with HubSpot IDs
            $mapping->updateHubSpotIds($contactId, $companyId, $dealId);
            $mapping->updateDealStage($stageName);

            // Add a note based on action
            $notes = [
                'email_sent' => 'Initial outreach email sent via Facturino',
                'followup_sent' => 'Follow-up email sent via Facturino',
                'converted' => 'Lead converted to partner in Facturino (Partner ID: ' . $lead->partner_id . ')',
                'update' => 'Lead updated in Facturino',
            ];

            $noteBody = $notes[$action] ?? 'Lead activity in Facturino';
            $this->hubspotClient->createNote($contactId, $noteBody);

            Log::info('Lead synced to HubSpot', [
                'lead_id' => $lead->id,
                'contact_id' => $contactId,
                'company_id' => $companyId,
                'deal_id' => $dealId,
                'action' => $action,
                'stage' => $stageName,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to sync lead to HubSpot', [
                'lead_id' => $lead->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Log email sent to HubSpot as an email engagement.
     *
     * @param OutreachLead $lead
     * @param OutreachSend $send
     * @return void
     */
    public function logEmailSentToHubSpot(OutreachLead $lead, OutreachSend $send): void
    {
        try {
            $mapping = $lead->hubspotMapping;

            if (!$mapping || !$mapping->hubspot_contact_id) {
                Log::debug('No HubSpot contact ID for lead, skipping email log', [
                    'lead_id' => $lead->id,
                ]);
                return;
            }

            // Determine email subject based on template
            $subjects = [
                'initial' => 'Facturino Partner Opportunity',
                'followup_1' => 'Following Up - Facturino Partnership',
                'followup_2' => 'Last Chance - Facturino Partnership',
            ];

            $subject = $subjects[$send->template_key] ?? 'Facturino Outreach';
            $body = "Outreach email sent via Postmark.\n\nTemplate: {$send->template_key}\nMessage ID: {$send->postmark_message_id}";

            $this->hubspotClient->createEmailEngagement(
                $mapping->hubspot_contact_id,
                $subject,
                $body,
                config('mail.from.address'),
                $lead->email
            );

            Log::info('Email engagement logged to HubSpot', [
                'lead_id' => $lead->id,
                'contact_id' => $mapping->hubspot_contact_id,
                'template' => $send->template_key,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to log email to HubSpot', [
                'lead_id' => $lead->id,
                'send_id' => $send->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log email opened event to HubSpot.
     *
     * @param OutreachLead $lead
     * @return void
     */
    public function logEmailOpenedToHubSpot(OutreachLead $lead): void
    {
        try {
            $mapping = $lead->hubspotMapping;

            if (!$mapping || !$mapping->hubspot_contact_id) {
                Log::debug('No HubSpot contact ID for lead, skipping open log', [
                    'lead_id' => $lead->id,
                ]);
                return;
            }

            $noteBody = "Email opened at " . now()->toDateTimeString();

            $this->hubspotClient->createNote($mapping->hubspot_contact_id, $noteBody);

            // Update deal stage to indicate engagement
            if ($mapping->hubspot_deal_id) {
                $openedStageId = $this->hubspotClient->getStageId('opened');
                if ($openedStageId) {
                    $this->hubspotClient->updateDealStage($mapping->hubspot_deal_id, $openedStageId);
                    $mapping->updateDealStage('opened');
                }
            }

            Log::info('Email open logged to HubSpot', [
                'lead_id' => $lead->id,
                'contact_id' => $mapping->hubspot_contact_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to log email open to HubSpot', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log email clicked event to HubSpot.
     *
     * @param OutreachLead $lead
     * @return void
     */
    public function logEmailClickedToHubSpot(OutreachLead $lead): void
    {
        try {
            $mapping = $lead->hubspotMapping;

            if (!$mapping || !$mapping->hubspot_contact_id) {
                Log::debug('No HubSpot contact ID for lead, skipping click log', [
                    'lead_id' => $lead->id,
                ]);
                return;
            }

            $noteBody = "Email link clicked at " . now()->toDateTimeString();

            $this->hubspotClient->createNote($mapping->hubspot_contact_id, $noteBody);

            // Update deal stage to indicate strong interest
            if ($mapping->hubspot_deal_id) {
                $clickedStageId = $this->hubspotClient->getStageId('clicked');
                if ($clickedStageId) {
                    $this->hubspotClient->updateDealStage($mapping->hubspot_deal_id, $clickedStageId);
                    $mapping->updateDealStage('clicked');
                }
            }

            Log::info('Email click logged to HubSpot', [
                'lead_id' => $lead->id,
                'contact_id' => $mapping->hubspot_contact_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to log email click to HubSpot', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update HubSpot deal stage.
     *
     * @param OutreachLead $lead
     * @param string $stage Stage name (new, emailed, interested, won, lost)
     * @return bool
     */
    public function updateHubSpotDealStage(OutreachLead $lead, string $stage): bool
    {
        try {
            $mapping = $lead->hubspotMapping;

            if (!$mapping || !$mapping->hubspot_deal_id) {
                Log::debug('No HubSpot deal ID for lead, skipping stage update', [
                    'lead_id' => $lead->id,
                ]);
                return false;
            }

            $stageId = $this->hubspotClient->getStageId($stage) ?? $stage;

            $success = $this->hubspotClient->updateDealStage($mapping->hubspot_deal_id, $stageId);

            if ($success) {
                $mapping->updateDealStage($stage);

                Log::info('HubSpot deal stage updated', [
                    'lead_id' => $lead->id,
                    'deal_id' => $mapping->hubspot_deal_id,
                    'stage' => $stage,
                ]);
            }

            return $success;

        } catch (\Exception $e) {
            Log::error('Failed to update HubSpot deal stage', [
                'lead_id' => $lead->id,
                'stage' => $stage,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get count of emails sent today.
     *
     * @return int
     */
    public function getSentCountToday(): int
    {
        return OutreachSend::today()->count();
    }

    /**
     * Get count of emails sent this hour.
     *
     * @return int
     */
    public function getSentCountThisHour(): int
    {
        return OutreachSend::lastHour()->count();
    }

    /**
     * Get pending leads for initial outreach.
     *
     * @param int $limit
     * @return Collection
     */
    public function getPendingLeads(int $limit): Collection
    {
        return OutreachLead::where('status', OutreachLead::STATUS_NEW)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get outreach statistics.
     *
     * @param string|null $period Period filter (today, week, month, all)
     * @return array
     */
    public function getStats(?string $period = 'today'): array
    {
        $query = OutreachSend::query();

        switch ($period) {
            case 'today':
                $query->whereDate('sent_at', today());
                break;
            case 'week':
                $query->where('sent_at', '>=', now()->subWeek());
                break;
            case 'month':
                $query->where('sent_at', '>=', now()->subMonth());
                break;
            case 'all':
            default:
                // No filter
                break;
        }

        $total = $query->count();
        $delivered = (clone $query)->where('status', 'delivered')->count();
        $opened = (clone $query)->whereNotNull('opened_at')->count();
        $clicked = (clone $query)->whereNotNull('clicked_at')->count();
        $bounced = (clone $query)->where('status', 'bounced')->count();

        return [
            'total_sent' => $total,
            'delivered' => $delivered,
            'opened' => $opened,
            'clicked' => $clicked,
            'bounced' => $bounced,
            'open_rate' => $total > 0 ? round(($opened / $total) * 100, 2) : 0,
            'click_rate' => $total > 0 ? round(($clicked / $total) * 100, 2) : 0,
            'bounce_rate' => $total > 0 ? round(($bounced / $total) * 100, 2) : 0,
            'quota' => $this->postmarkService->getRemainingQuota(),
        ];
    }
}

// CLAUDE-CHECKPOINT
