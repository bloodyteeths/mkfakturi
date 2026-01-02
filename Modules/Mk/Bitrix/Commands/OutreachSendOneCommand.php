<?php

namespace Modules\Mk\Bitrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Mk\Bitrix\Services\HubSpotService;
use Modules\Mk\Bitrix\Services\OutreachService;
use Modules\Mk\Bitrix\Models\OutreachLead;
use Modules\Mk\Bitrix\Models\OutreachSend;
use Modules\Mk\Bitrix\Models\HubSpotLeadMap;

/**
 * Send a single personalized outreach email to a specific HubSpot deal/contact.
 *
 * Requires fct_email_verified=true on the contact.
 */
class OutreachSendOneCommand extends Command
{
    protected $signature = 'outreach:send-one 
                            {--deal= : HubSpot deal ID (required)}
                            {--template=partner_intro : Email template to use}
                            {--force : Skip email verification check}';

    protected $description = 'Send a single personalized email to a HubSpot deal contact';

    protected array $templates = [
        'partner_intro' => [
            'subject' => 'Facturino - Партнерска програма за сметководители',
            'view' => 'emails.outreach.partner_intro',
        ],
        'followup_1' => [
            'subject' => 'Facturino - Дали ја добивте нашата порака?',
            'view' => 'emails.outreach.followup_1',
        ],
    ];

    public function handle(): int
    {
        $dealId = $this->option('deal');
        $templateName = $this->option('template');
        $force = $this->option('force');

        if (!$dealId) {
            $this->error('--deal=<id> is required');
            return Command::FAILURE;
        }

        if (!isset($this->templates[$templateName])) {
            $this->error("Unknown template: {$templateName}");
            $this->line('Available templates: ' . implode(', ', array_keys($this->templates)));
            return Command::FAILURE;
        }

        $hubSpotService = new HubSpotService();

        // 1. Get deal from HubSpot
        $this->line("Fetching deal {$dealId}...");
        $deal = $hubSpotService->getDeal($dealId, ['dealname', 'dealstage', 'fct_partner_id']);

        if (!$deal) {
            $this->error("Deal not found: {$dealId}");
            return Command::FAILURE;
        }

        $dealName = $deal['properties']['dealname'] ?? 'Unknown';
        $this->info("Deal: {$dealName}");

        // 2. Get associated contact
        $this->line("Fetching associated contact...");
        $contactId = $this->getAssociatedContactId($hubSpotService, $dealId);

        if (!$contactId) {
            $this->error("No contact associated with deal {$dealId}");
            return Command::FAILURE;
        }

        $contact = $hubSpotService->getContact($contactId, [
            'email', 'firstname', 'lastname', 'company', 'phone',
            'fct_email_verified', 'fct_contact_person_name'
        ]);

        if (!$contact) {
            $this->error("Contact not found: {$contactId}");
            return Command::FAILURE;
        }

        $email = $contact['properties']['email'] ?? null;
        $companyName = $contact['properties']['company'] ?? $dealName;
        $contactPerson = $contact['properties']['fct_contact_person_name'] ?? '';
        $emailVerified = $contact['properties']['fct_email_verified'] ?? 'false';

        $this->line("Email: {$email}");
        $this->line("Company: {$companyName}");
        $this->line("Contact Person: " . ($contactPerson ?: '(not set)'));
        $this->line("Email Verified: {$emailVerified}");

        if (!$email) {
            $this->error("Contact has no email address");
            return Command::FAILURE;
        }

        // 3. Check email verification
        if (!$force && $emailVerified !== 'true') {
            $this->error("Email not verified! Set fct_email_verified=true on the contact first.");
            $this->line("Or use --force to skip this check.");
            return Command::FAILURE;
        }

        // 4. Check suppression
        $outreachService = new OutreachService();
        if ($outreachService->isSuppressed($email)) {
            $this->error("Email is suppressed (bounced/unsubscribed): {$email}");
            return Command::FAILURE;
        }

        // 5. Send email via Postmark
        $template = $this->templates[$templateName];
        $this->line("Sending email with template: {$templateName}");

        try {
            $unsubscribeToken = $outreachService->generateUnsubscribeToken($email);
            $unsubscribeUrl = route('outreach.unsubscribe', ['token' => $unsubscribeToken]);

            // Prepare email data
            $emailData = [
                'company_name' => $companyName,
                'contact_name' => $contactPerson ?: 'Почитувани',
                'unsubscribe_url' => $unsubscribeUrl,
            ];

            // Send via Postmark
            Mail::send($template['view'], $emailData, function ($message) use ($email, $template, $companyName) {
                $message->to($email)
                    ->subject($template['subject'])
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            $this->info("Email sent successfully to {$email}");

            // 6. Log send to database
            $mapping = HubSpotLeadMap::findByEmail($email);
            $outreachLeadId = $mapping?->outreach_lead_id;

            OutreachSend::create([
                'outreach_lead_id' => $outreachLeadId,
                'email' => $email,
                'template' => $templateName,
                'postmark_message_id' => null, // Postmark returns this in response
                'sent_at' => now(),
            ]);

            // 7. Log to HubSpot timeline
            $this->line("Logging to HubSpot timeline...");
            $hubSpotService->createNote(
                $contactId,
                "Email sent: {$template['subject']}\nTemplate: {$templateName}\nSent at: " . now()->toDateTimeString()
            );

            // 8. Move deal to "Emailed" stage
            $this->line("Moving deal to 'Emailed' stage...");
            $emailedStageId = config('hubspot.stages.emailed');
            
            if ($emailedStageId) {
                $hubSpotService->updateDeal($dealId, [
                    'dealstage' => $emailedStageId,
                    'fct_last_touch_date' => now()->format('Y-m-d'),
                ]);
                $this->info("Deal moved to 'Emailed' stage");
            }

            // Update mapping
            if ($mapping) {
                $mapping->update([
                    'deal_stage' => 'emailed',
                    'last_synced_at' => now(),
                ]);
            }

            $this->newLine();
            $this->info("Success! Email sent to {$email}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to send email: " . $e->getMessage());
            Log::error('OutreachSendOne failed', [
                'deal_id' => $dealId,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Get the contact ID associated with a deal.
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
            Log::error('Failed to get deal associations', [
                'deal_id' => $dealId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

// CLAUDE-CHECKPOINT
