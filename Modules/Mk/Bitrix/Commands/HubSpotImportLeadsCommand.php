<?php

namespace Modules\Mk\Bitrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use Modules\Mk\Bitrix\Models\HubSpotLeadMap;
use Modules\Mk\Bitrix\Models\OutreachLead;
use Modules\Mk\Bitrix\Models\Suppression;
use Modules\Mk\Bitrix\Services\HubSpotService;

/**
 * HubSpotImportLeadsCommand
 *
 * Imports leads from CSV file into OutreachLead table and HubSpot CRM.
 *
 * Usage:
 *   php artisan hubspot:import-leads --csv=leads.csv --source=isos
 *   php artisan hubspot:import-leads --csv=leads.csv --source=smetkovoditeli --dry-run
 *   php artisan hubspot:import-leads --csv=leads.csv --source=isos --skip-hubspot
 *   php artisan hubspot:import-leads --csv=leads.csv --source=isos --limit=100
 *
 * CSV columns: company_name, email, phone, city, website, source_url
 */
class HubSpotImportLeadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hubspot:import-leads
                            {--csv= : Path to CSV file with leads}
                            {--source= : Lead source (isos or smetkovoditeli)}
                            {--dry-run : Show what would be imported without making changes}
                            {--skip-hubspot : Only import locally, do not push to HubSpot}
                            {--limit= : Process only first N rows}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import leads from CSV into OutreachLead table and HubSpot CRM';

    /**
     * Valid source values.
     */
    protected array $validSources = ['isos', 'smetkovoditeli'];

    /**
     * Required CSV columns.
     */
    protected array $requiredColumns = ['company_name', 'email'];

    /**
     * Optional CSV columns.
     */
    protected array $optionalColumns = ['phone', 'city', 'website', 'source_url'];

    /**
     * Closed deal stages (won't create new deals if lead has deal in these stages).
     */
    protected array $closedStages = ['closedwon', 'closedlost'];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(HubSpotService $hubSpotService): int
    {
        $csvPath = $this->option('csv');
        $source = $this->option('source');
        $dryRun = $this->option('dry-run');
        $skipHubSpot = $this->option('skip-hubspot');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        // Validate CSV path
        if (empty($csvPath)) {
            $this->error('CSV file path is required. Use --csv=path/to/file.csv');
            return self::FAILURE;
        }

        if (!file_exists($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");
            return self::FAILURE;
        }

        if (!is_readable($csvPath)) {
            $this->error("CSV file is not readable: {$csvPath}");
            return self::FAILURE;
        }

        // Validate source
        if (empty($source)) {
            $this->error('Source is required. Use --source=isos or --source=smetkovoditeli');
            return self::FAILURE;
        }

        if (!in_array($source, $this->validSources)) {
            $this->error("Invalid source: {$source}. Valid sources: " . implode(', ', $this->validSources));
            return self::FAILURE;
        }

        $this->info('HubSpot Lead Import');
        $this->line('===================');
        $this->line("Source: {$source}");
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        if ($skipHubSpot) {
            $this->warn('SKIP HUBSPOT MODE - Leads will only be created locally');
            $this->newLine();
        }

        if ($limit) {
            $this->info("Processing limit: {$limit} rows");
            $this->newLine();
        }

        // Check HubSpot configuration if not skipping
        if (!$skipHubSpot && !$hubSpotService->isConfigured()) {
            $this->error('HubSpot is not configured. Set HUBSPOT_ACCESS_TOKEN in .env or use --skip-hubspot');
            return self::FAILURE;
        }

        $hasErrors = false;

        try {
            // Read CSV file
            $csv = Reader::createFromPath($csvPath, 'r');
            $csv->setHeaderOffset(0);
            $headers = $csv->getHeader();

            // Validate headers
            if (!$this->validateHeaders($headers)) {
                return self::FAILURE;
            }

            $records = $csv->getRecords();
            $totalRows = iterator_count($csv->getRecords());

            $this->info("Found {$totalRows} rows in CSV file.");
            $this->newLine();

            // Process records
            $stats = $this->processRecords(
                $csv->getRecords(),
                $hubSpotService,
                $source,
                $dryRun,
                $skipHubSpot,
                $limit
            );

            // Display results
            $this->displayResults($stats);

            $hasErrors = !empty($stats['errors']);

            return $hasErrors ? self::FAILURE : self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to import leads: ' . $e->getMessage());
            Log::error('HubSpot lead import failed', [
                'csv' => $csvPath,
                'source' => $source,
                'error' => $e->getMessage(),
            ]);
            return self::FAILURE;
        }
    }

    /**
     * Validate CSV headers.
     *
     * @param array $headers
     * @return bool
     */
    protected function validateHeaders(array $headers): bool
    {
        $headers = array_map('strtolower', $headers);
        $missing = [];

        foreach ($this->requiredColumns as $column) {
            if (!in_array($column, $headers)) {
                $missing[] = $column;
            }
        }

        if (!empty($missing)) {
            $this->error('Missing required CSV columns: ' . implode(', ', $missing));
            $this->line('Required columns: ' . implode(', ', $this->requiredColumns));
            $this->line('Optional columns: ' . implode(', ', $this->optionalColumns));
            return false;
        }

        $this->info('CSV headers validated successfully.');
        return true;
    }

    /**
     * Process CSV records.
     *
     * @param iterable $records
     * @param HubSpotService $hubSpotService
     * @param string $source Lead source (isos or smetkovoditeli)
     * @param bool $dryRun
     * @param bool $skipHubSpot
     * @param int|null $limit Max rows to process
     * @return array
     */
    protected function processRecords(
        iterable $records,
        HubSpotService $hubSpotService,
        string $source,
        bool $dryRun,
        bool $skipHubSpot,
        ?int $limit = null
    ): array {
        $stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'suppressed' => 0,
            'duplicates' => 0,
            'hubspot_contacts_created' => 0,
            'hubspot_contacts_updated' => 0,
            'hubspot_companies_created' => 0,
            'hubspot_companies_updated' => 0,
            'hubspot_deals_created' => 0,
            'hubspot_deals_skipped' => 0,
            'hubspot_failed' => 0,
            'errors' => [],
        ];

        $processedCount = 0;

        foreach ($records as $index => $record) {
            // Check limit
            if ($limit !== null && $processedCount >= $limit) {
                $this->info("Reached processing limit of {$limit} rows.");
                break;
            }

            $stats['total']++;
            $processedCount++;
            $rowNum = $index + 2; // Account for header row

            try {
                $result = $this->processRecord(
                    $record,
                    $hubSpotService,
                    $source,
                    $dryRun,
                    $skipHubSpot,
                    $rowNum
                );

                $stats[$result['action']]++;

                if (isset($result['hubspot_actions'])) {
                    foreach ($result['hubspot_actions'] as $action => $count) {
                        $key = 'hubspot_' . $action;
                        if (isset($stats[$key])) {
                            $stats[$key] += $count;
                        }
                    }
                }

            } catch (\Exception $e) {
                $stats['errors'][] = "Row {$rowNum}: " . $e->getMessage();
                $this->warn("Row {$rowNum}: " . $e->getMessage());
                Log::warning('HubSpot import row error', [
                    'row' => $rowNum,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Process a single CSV record.
     *
     * @param array $record
     * @param HubSpotService $hubSpotService
     * @param string $source Lead source (isos or smetkovoditeli)
     * @param bool $dryRun
     * @param bool $skipHubSpot
     * @param int $rowNum
     * @return array
     */
    protected function processRecord(
        array $record,
        HubSpotService $hubSpotService,
        string $source,
        bool $dryRun,
        bool $skipHubSpot,
        int $rowNum
    ): array {
        // Normalize record keys to lowercase
        $record = array_change_key_case($record, CASE_LOWER);

        // Normalize email
        $email = strtolower(trim($record['email'] ?? ''));
        $companyName = trim($record['company_name'] ?? '');

        // Validate required fields
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid email: {$email}");
        }

        if (empty($companyName)) {
            throw new \Exception("Missing company_name for email: {$email}");
        }

        // Check suppression list
        if (Suppression::isSuppressed($email)) {
            $this->line("  [SUPPRESSED] Row {$rowNum}: {$email}");
            return ['action' => 'suppressed', 'hubspot_actions' => []];
        }

        // Check for existing mapping (dedupe check)
        $existingMapping = HubSpotLeadMap::findByEmail($email);

        // Extract domain from website or email
        $website = trim($record['website'] ?? '');
        $domain = $this->extractDomain($website, $email);

        $leadData = [
            'email' => $email,
            'company_name' => $companyName,
            'phone' => trim($record['phone'] ?? '') ?: null,
            'city' => trim($record['city'] ?? '') ?: null,
            'website' => $website ?: null,
            'source' => $source,
            'source_url' => trim($record['source_url'] ?? '') ?: null,
            'status' => OutreachLead::STATUS_NEW,
        ];

        if ($dryRun) {
            $existingLead = OutreachLead::where('email', $email)->first();
            if ($existingLead) {
                $this->line("  [DRY-RUN UPDATE] Row {$rowNum}: {$email} (existing ID: {$existingLead->id})");
                return ['action' => 'updated', 'hubspot_actions' => []];
            } else {
                $this->line("  [DRY-RUN CREATE] Row {$rowNum}: {$email}");
                return ['action' => 'created', 'hubspot_actions' => []];
            }
        }

        // Create or update OutreachLead
        $lead = OutreachLead::updateOrCreate(
            ['email' => $email],
            $leadData
        );

        $wasRecentlyCreated = $lead->wasRecentlyCreated;
        $action = $wasRecentlyCreated ? 'created' : 'updated';

        if ($wasRecentlyCreated) {
            $this->info("  [CREATED] Row {$rowNum}: {$email} (ID: {$lead->id})");
        } else {
            $this->line("  [UPDATED] Row {$rowNum}: {$email} (ID: {$lead->id})");
        }

        // Push to HubSpot
        $hubspotActions = [];

        if (!$skipHubSpot) {
            $hubspotActions = $this->pushToHubSpot($lead, $record, $source, $domain, $hubSpotService);
        }

        return ['action' => $action, 'hubspot_actions' => $hubspotActions];
    }

    /**
     * Push lead to HubSpot CRM.
     *
     * Creates/updates Company, Contact, and Deal with proper associations.
     *
     * @param OutreachLead $lead
     * @param array $record Original CSV record
     * @param string $source Lead source (isos or smetkovoditeli)
     * @param string|null $domain Extracted domain
     * @param HubSpotService $hubSpotService
     * @return array Counts of actions taken
     */
    protected function pushToHubSpot(
        OutreachLead $lead,
        array $record,
        string $source,
        ?string $domain,
        HubSpotService $hubSpotService
    ): array {
        $actions = [
            'contacts_created' => 0,
            'contacts_updated' => 0,
            'companies_created' => 0,
            'companies_updated' => 0,
            'deals_created' => 0,
            'deals_skipped' => 0,
            'failed' => 0,
        ];

        try {
            // Check for existing HubSpot mapping
            $existingMapping = HubSpotLeadMap::findByEmail($lead->email);

            // 1. Upsert Company in HubSpot
            $companyId = null;

            if ($domain) {
                $companyProperties = [
                    'name' => $lead->company_name,
                    'fct_source' => $source,
                    'fct_source_url' => $lead->source_url ?? '',
                    'fct_city' => $lead->city ?? '',
                    'fct_phone' => $lead->phone ?? '',
                ];

                // Filter out empty values
                $companyProperties = array_filter($companyProperties, fn($v) => $v !== null && $v !== '');

                // Add domain to properties
                $companyProperties['domain'] = $domain;

                // Check if company exists first
                $existingCompany = $hubSpotService->findCompanyByDomain($domain);
                $companyId = $hubSpotService->upsertCompany($companyProperties);

                if ($companyId) {
                    if (!$existingCompany) {
                        $actions['companies_created'] = 1;
                    } else {
                        $actions['companies_updated'] = 1;
                    }
                }
            }

            // 2. Upsert Contact in HubSpot
            $contactProperties = [
                'firstname' => '', // Could parse from company_name if needed
                'lastname' => '',
                'company' => $lead->company_name,
                'phone' => $lead->phone ?? '',
                'city' => $lead->city ?? '',
                'facturino_lead_id' => (string) $lead->id,
                'facturino_source' => $source,
                'facturino_source_url' => $lead->source_url ?? '',
            ];

            // Filter out empty values
            $contactProperties = array_filter($contactProperties, fn($v) => $v !== null && $v !== '');

            // Check if contact exists first
            $existingContact = $hubSpotService->findContactByEmail($lead->email);
            $contactId = $hubSpotService->upsertContact($lead->email, $contactProperties);

            if (!$contactId) {
                throw new \RuntimeException('Failed to create/update HubSpot contact');
            }

            if (!$existingContact) {
                $actions['contacts_created'] = 1;
            } else {
                $actions['contacts_updated'] = 1;
            }

            // 3. Associate Contact with Company
            if ($companyId) {
                try {
                    $hubSpotService->associateContactToCompany($contactId, $companyId);
                } catch (\Exception $e) {
                    // Association might already exist, log and continue
                    Log::debug('HubSpot contact-company association failed (might already exist)', [
                        'contact_id' => $contactId,
                        'company_id' => $companyId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 4. Check if open deal exists for this company/contact
            $hubspotDealId = $existingMapping?->hubspot_deal_id;
            $hasOpenDeal = false;
            $dealStage = 'new_lead';

            if ($hubspotDealId) {
                // Check if existing deal is still open
                try {
                    $existingDeal = $hubSpotService->getDeal($hubspotDealId, ['dealstage']);
                    if ($existingDeal) {
                        $currentStage = $existingDeal['properties']['dealstage'] ?? '';
                        $hasOpenDeal = !in_array($currentStage, $this->closedStages);
                        if ($hasOpenDeal) {
                            $dealStage = $currentStage;
                        }
                    }
                } catch (\Exception $e) {
                    // Deal might have been deleted, we'll create a new one
                    Log::debug('Could not fetch existing HubSpot deal', [
                        'deal_id' => $hubspotDealId,
                        'error' => $e->getMessage(),
                    ]);
                    $hubspotDealId = null;
                }
            }

            // 5. Create Deal if no open deal exists
            if (!$hasOpenDeal) {
                $pipelineId = config('hubspot.pipeline', 'default');
                $newLeadStage = config('hubspot.deal_stages.new', 'appointmentscheduled');

                $dealProperties = [
                    'dealname' => "Partner: {$lead->company_name}",
                    'pipeline' => $pipelineId,
                    'dealstage' => $newLeadStage,
                    'facturino_lead_id' => (string) $lead->id,
                    'fct_last_touch_date' => null,
                    'fct_next_followup_date' => now()->format('Y-m-d'),
                    'fct_partner_id' => '',
                ];

                // Filter out null values but keep empty strings for clearing fields
                $dealProperties = array_filter($dealProperties, fn($v) => $v !== null);

                $deal = $hubSpotService->createDeal($dealProperties);
                $hubspotDealId = $deal['id'];
                $dealStage = 'new_lead';
                $actions['deals_created'] = 1;

                // Associate Deal with Contact
                try {
                    $hubSpotService->associateContactToDeal($contactId, $hubspotDealId);
                } catch (\Exception $e) {
                    Log::debug('HubSpot contact-deal association failed', [
                        'contact_id' => $contactId,
                        'deal_id' => $hubspotDealId,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Associate Deal with Company
                if ($companyId) {
                    try {
                        $hubSpotService->associateCompanyToDeal($companyId, $hubspotDealId);
                    } catch (\Exception $e) {
                        Log::debug('HubSpot company-deal association failed', [
                            'company_id' => $companyId,
                            'deal_id' => $hubspotDealId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } else {
                $actions['deals_skipped'] = 1;
                Log::info('Skipped deal creation - open deal already exists', [
                    'email' => $lead->email,
                    'deal_id' => $hubspotDealId,
                ]);
            }

            // 6. Create/update HubSpotLeadMap
            HubSpotLeadMap::syncMapping(
                $lead->email,
                $lead->id,
                $contactId,
                $companyId,
                $hubspotDealId,
                $dealStage
            );

        } catch (\Exception $e) {
            Log::warning('Failed to push lead to HubSpot', [
                'lead_id' => $lead->id,
                'email' => $lead->email,
                'error' => $e->getMessage(),
            ]);
            $actions['failed'] = 1;
        }

        return $actions;
    }

    /**
     * Extract domain from website URL or email address.
     *
     * @param string $website Website URL
     * @param string $email Email address (fallback)
     * @return string|null Domain or null if not extractable
     */
    protected function extractDomain(string $website, string $email): ?string
    {
        // Try extracting from website first
        $website = trim($website);
        if (!empty($website)) {
            // Add protocol if missing
            if (!preg_match('~^https?://~i', $website)) {
                $website = 'https://' . $website;
            }

            $parsed = parse_url($website);
            $host = $parsed['host'] ?? null;

            if ($host) {
                // Remove www prefix
                return preg_replace('/^www\./i', '', strtolower($host));
            }
        }

        // Fall back to email domain
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return null;
        }

        $domain = strtolower($parts[1]);

        // Exclude common personal email domains - don't create company for these
        $personalDomains = [
            'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com',
            'aol.com', 'icloud.com', 'mail.com', 'protonmail.com',
            'live.com', 'msn.com', 'yandex.com', 'zoho.com',
            'yahoo.co.uk', 'googlemail.com', 'me.com', 'mac.com',
            't-home.de', 'web.de', 'gmx.de', 'gmx.net',
        ];

        if (in_array($domain, $personalDomains)) {
            return null;
        }

        return $domain;
    }

    /**
     * Display import results.
     *
     * @param array $stats
     * @return void
     */
    protected function displayResults(array $stats): void
    {
        $this->newLine();
        $this->line('==========================================');
        $this->line('Import Results');
        $this->line('==========================================');
        $this->newLine();

        $this->line('Local Database:');
        $this->line("  Total rows processed: {$stats['total']}");
        $this->line("  New leads created:    {$stats['created']}");
        $this->line("  Leads updated:        {$stats['updated']}");
        $this->line("  Skipped (suppressed): {$stats['suppressed']}");
        $this->line("  Skipped (duplicates): {$stats['duplicates']}");
        $this->newLine();

        $hasHubSpotActivity = (
            $stats['hubspot_contacts_created'] > 0 ||
            $stats['hubspot_contacts_updated'] > 0 ||
            $stats['hubspot_companies_created'] > 0 ||
            $stats['hubspot_companies_updated'] > 0 ||
            $stats['hubspot_deals_created'] > 0 ||
            $stats['hubspot_deals_skipped'] > 0 ||
            $stats['hubspot_failed'] > 0
        );

        if ($hasHubSpotActivity) {
            $this->line('HubSpot Sync:');
            $this->line("  Companies created:    {$stats['hubspot_companies_created']}");
            $this->line("  Companies updated:    {$stats['hubspot_companies_updated']}");
            $this->line("  Contacts created:     {$stats['hubspot_contacts_created']}");
            $this->line("  Contacts updated:     {$stats['hubspot_contacts_updated']}");
            $this->line("  Deals created:        {$stats['hubspot_deals_created']}");
            $this->line("  Deals skipped:        {$stats['hubspot_deals_skipped']}");
            $this->line("  Failed:               {$stats['hubspot_failed']}");
            $this->newLine();
        }

        if (!empty($stats['errors'])) {
            $this->newLine();
            $this->warn('Errors (' . count($stats['errors']) . '):');
            foreach (array_slice($stats['errors'], 0, 10) as $error) {
                $this->line("  - {$error}");
            }
            if (count($stats['errors']) > 10) {
                $this->line('  ... and ' . (count($stats['errors']) - 10) . ' more errors');
            }
            $this->newLine();
        }

        $this->line('==========================================');
        if (empty($stats['errors'])) {
            $this->info('Import completed successfully.');
        } else {
            $this->warn('Import completed with errors.');
        }
    }
}

// CLAUDE-CHECKPOINT
