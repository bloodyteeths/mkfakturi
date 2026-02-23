<?php

namespace Modules\Mk\Bitrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Bitrix\Models\OutreachLead;
use Modules\Mk\Bitrix\Models\Suppression;
use Modules\Mk\Bitrix\Services\SmtpVerifier;

/**
 * OutreachVerifySmtpCommand
 *
 * Performs SMTP-level RCPT TO verification on outreach leads to catch
 * dead mailboxes on valid MX domains. Runs after MX verification.
 *
 * Skips freemail providers (Gmail, Yahoo, Hotmail) since they block RCPT TO.
 * Detects catch-all servers to avoid false positives.
 * Auto-suppresses emails that fail verification.
 *
 * Usage:
 *   php artisan outreach:verify-smtp
 *   php artisan outreach:verify-smtp --limit=500 --type=company
 *   php artisan outreach:verify-smtp --dry-run
 */
class OutreachVerifySmtpCommand extends Command
{
    protected $signature = 'outreach:verify-smtp
                            {--limit=500 : Maximum leads to process}
                            {--type= : Filter by lead type (accountant, company)}
                            {--dry-run : Show results without updating database}';

    protected $description = 'Verify email addresses via SMTP RCPT TO to catch dead mailboxes';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $leadType = $this->option('type');
        $dryRun = $this->option('dry-run');

        $this->info('SMTP Email Verification (RCPT TO)');
        $this->line('==================================');

        if ($dryRun) {
            $this->warn('DRY RUN — no database changes will be made');
        }

        $this->newLine();

        $verifier = new SmtpVerifier();

        // Fetch MX-valid leads that haven't been SMTP-checked yet
        $query = OutreachLead::query()
            ->where('mx_valid', true)
            ->whereNull('smtp_checked_at')
            ->whereIn('status', [
                OutreachLead::STATUS_NEW,
                OutreachLead::STATUS_EMAILED,
                OutreachLead::STATUS_FOLLOWUP,
            ])
            // Skip already-suppressed leads
            ->whereNotIn('email', function ($q) {
                $q->select('email')->from('suppressions');
            });

        if ($leadType === OutreachLead::TYPE_ACCOUNTANT) {
            $query->accountants();
        } elseif ($leadType === OutreachLead::TYPE_COMPANY) {
            $query->companies();
        }

        $totalEligible = (clone $query)->count();
        $this->info("Eligible leads (MX-valid, SMTP-unchecked): {$totalEligible}");
        $this->info("Processing up to: {$limit}");
        $this->newLine();

        if ($totalEligible === 0) {
            $this->info('No leads to verify.');

            return self::SUCCESS;
        }

        $leads = $query->limit($limit)->get(['id', 'email']);

        // Group by domain for efficient verification
        $byDomain = [];
        foreach ($leads as $lead) {
            $domain = strtolower(substr($lead->email, strpos($lead->email, '@') + 1));
            $byDomain[$domain][] = $lead;
        }

        $this->info('Unique domains: ' . count($byDomain));
        $this->info('Leads to check: ' . $leads->count());
        $this->newLine();

        $stats = [
            'valid' => 0,
            'invalid' => 0,
            'inconclusive' => 0,
            'freemail_skipped' => 0,
            'catch_all' => 0,
            'suppressed' => 0,
        ];

        $bar = $this->output->createProgressBar(count($byDomain));
        $bar->start();

        foreach ($byDomain as $domain => $domainLeads) {
            // Skip freemail domains entirely
            if ($verifier->isFreemailDomain($domain)) {
                $stats['freemail_skipped'] += count($domainLeads);

                if (! $dryRun) {
                    $ids = array_map(fn ($l) => $l->id, $domainLeads);
                    DB::table('outreach_leads')
                        ->whereIn('id', $ids)
                        ->update(['smtp_checked_at' => now()]);
                }

                $bar->advance();

                continue;
            }

            // Rate limit: pause between domains
            usleep(500_000); // 0.5s between domains

            foreach ($domainLeads as $lead) {
                try {
                    $result = $verifier->verify($lead->email);

                    if ($result === true) {
                        $stats['valid']++;

                        if (! $dryRun) {
                            DB::table('outreach_leads')
                                ->where('id', $lead->id)
                                ->update([
                                    'smtp_valid' => true,
                                    'smtp_checked_at' => now(),
                                ]);
                        }
                    } elseif ($result === false) {
                        $stats['invalid']++;

                        if (! $dryRun) {
                            DB::table('outreach_leads')
                                ->where('id', $lead->id)
                                ->update([
                                    'smtp_valid' => false,
                                    'smtp_checked_at' => now(),
                                ]);

                            // Auto-suppress invalid mailboxes
                            Suppression::suppress(
                                $lead->email,
                                Suppression::TYPE_BOUNCE,
                                'SMTP RCPT TO rejected - mailbox does not exist',
                                Suppression::SOURCE_ADMIN,
                                ['verified_by' => 'smtp_rcpt_to']
                            );

                            // Mark lead as lost
                            DB::table('outreach_leads')
                                ->where('id', $lead->id)
                                ->update(['status' => OutreachLead::STATUS_LOST]);

                            $stats['suppressed']++;
                        }

                        $this->line("  <fg=red>INVALID:</> {$lead->email}");
                    } else {
                        $stats['inconclusive']++;

                        if (! $dryRun) {
                            DB::table('outreach_leads')
                                ->where('id', $lead->id)
                                ->update(['smtp_checked_at' => now()]);
                        }
                    }

                    // Rate limit: pause between emails on same domain
                    usleep(200_000); // 0.2s between emails

                } catch (\Throwable $e) {
                    $stats['inconclusive']++;
                    Log::warning('SMTP verification error', [
                        'email' => $lead->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Display results
        $this->line('Results');
        $this->line('-------');
        $this->line("  Valid mailboxes: {$stats['valid']}");
        $this->line("  <fg=red>Invalid mailboxes: {$stats['invalid']}</>");
        $this->line("  Inconclusive: {$stats['inconclusive']}");
        $this->line("  Freemail skipped: {$stats['freemail_skipped']}");
        $this->line("  Auto-suppressed: {$stats['suppressed']}");
        $this->newLine();

        $total = $stats['valid'] + $stats['invalid'] + $stats['inconclusive'];

        if ($total > 0) {
            $rejectRate = round(($stats['invalid'] / $total) * 100, 1);
            $this->info("Rejection rate (business domains): {$rejectRate}%");
        }

        if ($stats['suppressed'] > 0) {
            $this->warn("{$stats['suppressed']} emails suppressed — they will not receive outreach.");
        }

        return self::SUCCESS;
    }
}
