<?php

namespace Modules\Mk\Bitrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Bitrix\Models\OutreachLead;

/**
 * OutreachVerifyMxCommand
 *
 * Verifies MX records for outreach leads to prevent sending to dead mailboxes.
 * Groups leads by domain and caches DNS lookups for efficiency.
 *
 * Usage:
 *   php artisan outreach:verify-mx
 *   php artisan outreach:verify-mx --limit=1000 --type=company
 *   php artisan outreach:verify-mx --recheck
 *   php artisan outreach:verify-mx --domain=t.mk
 */
class OutreachVerifyMxCommand extends Command
{
    protected $signature = 'outreach:verify-mx
                            {--limit=500 : Maximum leads to process}
                            {--type= : Filter by lead type (accountant, company)}
                            {--recheck : Re-verify all leads, not just unchecked}
                            {--domain= : Check a single domain only}';

    protected $description = 'Verify MX DNS records for outreach leads to improve email deliverability';

    /**
     * Domain-level MX cache to avoid duplicate DNS lookups.
     *
     * @var array<string, bool>
     */
    protected array $domainCache = [];

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $leadType = $this->option('type');
        $recheck = $this->option('recheck');
        $singleDomain = $this->option('domain');

        $this->info('MX Record Verification');
        $this->line('======================');
        $this->newLine();

        // Validate lead type
        if ($leadType && ! in_array($leadType, [OutreachLead::TYPE_ACCOUNTANT, OutreachLead::TYPE_COMPANY])) {
            $this->error("Invalid lead type: {$leadType}");

            return self::FAILURE;
        }

        // Single domain mode
        if ($singleDomain) {
            return $this->verifySingleDomain($singleDomain, $leadType);
        }

        // Build query
        $query = OutreachLead::query();

        if (! $recheck) {
            $query->mxUnchecked();
        }

        if ($leadType === OutreachLead::TYPE_ACCOUNTANT) {
            $query->accountants();
        } elseif ($leadType === OutreachLead::TYPE_COMPANY) {
            $query->companies();
        }

        $totalEligible = (clone $query)->count();
        $this->info("Eligible leads: {$totalEligible}");
        $this->info("Processing up to: {$limit}");
        $this->newLine();

        if ($totalEligible === 0) {
            $this->info('No leads to verify.');

            return self::SUCCESS;
        }

        // Fetch leads, grouped by domain for efficiency
        $leads = $query->limit($limit)->get(['id', 'email', 'mx_valid']);

        // Group by domain
        $byDomain = [];
        foreach ($leads as $lead) {
            $domain = strtolower(substr($lead->email, strpos($lead->email, '@') + 1));
            $byDomain[$domain][] = $lead->id;
        }

        $this->info('Unique domains to check: ' . count($byDomain));
        $this->newLine();

        $bar = $this->output->createProgressBar(count($byDomain));
        $bar->start();

        $stats = ['valid' => 0, 'invalid' => 0, 'errors' => 0, 'leads_updated' => 0];

        foreach ($byDomain as $domain => $leadIds) {
            try {
                $isValid = $this->checkDomain($domain);

                // Batch update all leads for this domain
                DB::table('outreach_leads')
                    ->whereIn('id', $leadIds)
                    ->update([
                        'mx_valid' => $isValid,
                        'mx_checked_at' => now(),
                    ]);

                $stats['leads_updated'] += count($leadIds);

                if ($isValid) {
                    $stats['valid']++;
                } else {
                    $stats['invalid']++;
                }
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::warning('MX verification error', [
                    'domain' => $domain,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Display results
        $this->displayResults($stats, count($byDomain));

        return self::SUCCESS;
    }

    /**
     * Check if a domain has valid MX (or A record fallback).
     */
    protected function checkDomain(string $domain): bool
    {
        if (isset($this->domainCache[$domain])) {
            return $this->domainCache[$domain];
        }

        // Primary: check MX record
        $hasMx = @checkdnsrr($domain, 'MX');

        if ($hasMx) {
            $this->domainCache[$domain] = true;

            return true;
        }

        // Fallback: some domains serve mail on A record (no dedicated MX)
        $hasA = @checkdnsrr($domain, 'A');

        $this->domainCache[$domain] = $hasA;

        return $hasA;
    }

    /**
     * Verify a single domain and update all matching leads.
     */
    protected function verifySingleDomain(string $domain, ?string $leadType): int
    {
        $domain = strtolower($domain);
        $isValid = $this->checkDomain($domain);

        $this->info("Domain: {$domain}");
        $this->info('MX Valid: ' . ($isValid ? 'YES' : 'NO'));
        $this->newLine();

        $query = OutreachLead::where('email', 'LIKE', "%@{$domain}");
        if ($leadType) {
            $query->where('lead_type', $leadType);
        }

        $count = $query->count();
        $query->update([
            'mx_valid' => $isValid,
            'mx_checked_at' => now(),
        ]);

        $this->info("Updated {$count} leads with domain @{$domain}");

        return self::SUCCESS;
    }

    /**
     * Display verification results.
     */
    protected function displayResults(array $stats, int $domainCount): void
    {
        $this->line('Results');
        $this->line('-------');
        $this->line("  Domains checked: {$domainCount}");
        $this->line("  Valid domains: {$stats['valid']}");
        $this->line("  Invalid domains: {$stats['invalid']}");
        $this->line("  Errors: {$stats['errors']}");
        $this->line("  Leads updated: {$stats['leads_updated']}");
        $this->newLine();

        if ($domainCount > 0) {
            $validRate = round(($stats['valid'] / $domainCount) * 100, 1);
            $this->info("Domain validity rate: {$validRate}%");
        }

        if ($stats['invalid'] > 0) {
            $this->warn("Invalid domains will be excluded from future outreach sends.");
        }
    }
}
