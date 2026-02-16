<?php

namespace Modules\Mk\Bitrix\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Bitrix\Models\OutreachLead;
use Modules\Mk\Bitrix\Models\OutreachSend;

/**
 * ImportPostmarkContactsCommand
 *
 * Imports existing outreach recipients from Postmark API into the
 * outreach_leads and outreach_sends tables. This is a one-time
 * backfill command to sync manually-sent campaigns with the app DB.
 *
 * Usage:
 *   php artisan outreach:import-postmark --dry-run
 *   php artisan outreach:import-postmark
 */
class ImportPostmarkContactsCommand extends Command
{
    protected $signature = 'outreach:import-postmark
                            {--dry-run : Show what would be imported without writing to DB}
                            {--limit=0 : Limit number of messages to process (0 = all)}';

    protected $description = 'Import existing outreach recipients from Postmark API into outreach_leads and outreach_sends tables';

    /**
     * Subject patterns that identify accountant outreach emails.
     */
    protected array $outreachSubjects = [
        'Партнерска покана',
        'Годишно затворање',
        'Дали знаете колку заработувате',
        'Рекурентни фактури',
    ];

    /**
     * Map Postmark subjects to template keys.
     */
    protected array $subjectToTemplate = [
        'Партнерска покана - Facturino е-фактура систем (УЈП одобрен)' => 'first_touch',
        'Дали знаете колку заработувате по проект?' => 'followup_1',
        'Рекурентни фактури, мулти-валута, е-фактура — на едно место' => 'followup_2',
        'Годишно затворање за 15 минути — автоматски' => 'followup_3',
        'Годишно затворање за 15 минути (рок: 28 февруари)' => 'followup_3',
        'Годишно затворање за 15 минути - последна можност пред рокот' => 'followup_4',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $token = config('services.postmark.token');

        if (empty($token)) {
            $this->error('POSTMARK_TOKEN not configured');
            return self::FAILURE;
        }

        $this->info('Importing Postmark outreach contacts');
        $this->line('====================================');
        if ($dryRun) {
            $this->warn('DRY RUN MODE');
        }
        $this->newLine();

        // Paginate through all outbound messages
        $allMessages = [];
        $offset = 0;
        $batchSize = 500;

        $this->info('Fetching messages from Postmark API...');

        while (true) {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'X-Postmark-Server-Token' => $token,
            ])->get('https://api.postmarkapp.com/messages/outbound', [
                'count' => $batchSize,
                'offset' => $offset,
            ]);

            if (!$response->successful()) {
                $this->error("API error at offset {$offset}: {$response->status()}");
                break;
            }

            $data = $response->json();
            $messages = $data['Messages'] ?? [];

            if (empty($messages)) {
                break;
            }

            // Filter for outreach-related subjects
            foreach ($messages as $msg) {
                $subject = $msg['Subject'] ?? '';
                foreach ($this->outreachSubjects as $pattern) {
                    if (str_contains($subject, $pattern)) {
                        $allMessages[] = $msg;
                        break;
                    }
                }
            }

            $this->line("  Scanned offset {$offset}: {$messages[0]['Subject']} ... ({$offset}+{$batchSize})");

            $offset += $batchSize;

            if ($limit > 0 && count($allMessages) >= $limit) {
                $allMessages = array_slice($allMessages, 0, $limit);
                break;
            }

            // Respect API rate limits
            usleep(200000); // 200ms between requests
        }

        $this->info("Found " . count($allMessages) . " outreach messages");
        $this->newLine();

        // Group by recipient email
        $recipients = [];
        foreach ($allMessages as $msg) {
            foreach ($msg['Recipients'] ?? [] as $email) {
                $email = strtolower(trim($email));
                if (!isset($recipients[$email])) {
                    $recipients[$email] = [];
                }
                $recipients[$email][] = $msg;
            }
        }

        $this->info("Unique recipients: " . count($recipients));
        $this->newLine();

        $stats = ['leads_created' => 0, 'leads_existing' => 0, 'sends_created' => 0, 'sends_existing' => 0];

        $bar = $this->output->createProgressBar(count($recipients));
        $bar->start();

        foreach ($recipients as $email => $messages) {
            // Create or find lead
            $existingLead = OutreachLead::where('email', $email)->first();

            if ($existingLead) {
                $stats['leads_existing']++;
                $lead = $existingLead;
            } else {
                if (!$dryRun) {
                    $lead = OutreachLead::create([
                        'email' => $email,
                        'company_name' => $this->extractCompanyFromEmail($email),
                        'source' => 'postmark_import',
                        'status' => OutreachLead::STATUS_EMAILED,
                        'last_contacted_at' => now(),
                    ]);
                }
                $stats['leads_created']++;
            }

            // Create send records for each message
            foreach ($messages as $msg) {
                $subject = $msg['Subject'] ?? '';
                $messageId = $msg['MessageID'] ?? null;
                $sentAt = $msg['SentAt'] ?? null;

                // Check if send already exists
                if ($messageId && OutreachSend::where('postmark_message_id', $messageId)->exists()) {
                    $stats['sends_existing']++;
                    continue;
                }

                $templateKey = $this->mapSubjectToTemplate($subject);

                if (!$dryRun) {
                    OutreachSend::create([
                        'email' => $email,
                        'outreach_lead_id' => $lead->id ?? null,
                        'template_key' => $templateKey,
                        'postmark_message_id' => $messageId ?? ('pm-import-' . uniqid()),
                        'status' => $msg['Status'] === 'Sent' ? OutreachSend::STATUS_SENT : OutreachSend::STATUS_DELIVERED,
                        'sent_at' => $sentAt ? Carbon::parse($sentAt) : now(),
                    ]);
                }
                $stats['sends_created']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->line('Results');
        $this->line('-------');
        $this->line("  Leads created: {$stats['leads_created']}");
        $this->line("  Leads already existed: {$stats['leads_existing']}");
        $this->line("  Send records created: {$stats['sends_created']}");
        $this->line("  Send records already existed: {$stats['sends_existing']}");

        return self::SUCCESS;
    }

    /**
     * Map a Postmark subject to a template key.
     */
    protected function mapSubjectToTemplate(string $subject): string
    {
        foreach ($this->subjectToTemplate as $subjectPattern => $template) {
            if ($subject === $subjectPattern) {
                return $template;
            }
        }

        // Fallback: match by partial
        foreach ($this->subjectToTemplate as $subjectPattern => $template) {
            if (str_contains($subject, substr($subjectPattern, 0, 20))) {
                return $template;
            }
        }

        return 'first_touch';
    }

    /**
     * Extract a rough company name from an email address.
     */
    protected function extractCompanyFromEmail(string $email): string
    {
        $parts = explode('@', $email);
        $domain = $parts[1] ?? '';

        // Common free email providers
        $freeProviders = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'live.com', 'mail.com', 'aol.com'];
        if (in_array($domain, $freeProviders)) {
            return $parts[0] ?? $email;
        }

        // Extract company name from domain
        $domainParts = explode('.', $domain);
        return ucfirst($domainParts[0] ?? $email);
    }
}
// CLAUDE-CHECKPOINT
