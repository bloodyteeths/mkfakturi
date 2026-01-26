<?php

namespace Modules\Mk\Bitrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use Modules\Mk\Bitrix\Models\BitrixLeadMap;
use Modules\Mk\Bitrix\Models\OutreachLead;
use Modules\Mk\Bitrix\Models\Suppression;
use Modules\Mk\Bitrix\Services\Bitrix24ApiClient;

/**
 * BitrixImportLeadsCommand
 *
 * Imports leads from CSV file into OutreachLead table and Bitrix24 CRM.
 *
 * Usage:
 *   php artisan bitrix:import-leads --csv=leads.csv
 *   php artisan bitrix:import-leads --csv=leads.csv --dry-run
 *   php artisan bitrix:import-leads --csv=leads.csv --skip-bitrix
 *
 * CSV columns: company_name, email, phone, city, website, source, source_url, tags
 */
class BitrixImportLeadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitrix:import-leads
                            {--csv= : Path to CSV file with leads}
                            {--dry-run : Show what would be imported without making changes}
                            {--skip-bitrix : Only import locally, do not push to Bitrix24}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import leads from CSV into OutreachLead table and Bitrix24 CRM';

    /**
     * Required CSV columns.
     */
    protected array $requiredColumns = ['company_name', 'email'];

    /**
     * Optional CSV columns.
     */
    protected array $optionalColumns = ['phone', 'city', 'website', 'source', 'source_url', 'tags', 'contact_name'];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Bitrix24ApiClient $bitrixClient): int
    {
        $csvPath = $this->option('csv');
        $dryRun = $this->option('dry-run');
        $skipBitrix = $this->option('skip-bitrix');

        if (empty($csvPath)) {
            $this->error('CSV file path is required. Use --csv=path/to/file.csv');
            return self::FAILURE;
        }

        if (!file_exists($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");
            return self::FAILURE;
        }

        $this->info('Bitrix24 Lead Import');
        $this->line('====================');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        if ($skipBitrix) {
            $this->warn('SKIP BITRIX MODE - Leads will only be created locally');
            $this->newLine();
        }

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
                $bitrixClient,
                $dryRun,
                $skipBitrix
            );

            // Display results
            $this->displayResults($stats);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to import leads: ' . $e->getMessage());
            Log::error('Bitrix lead import failed', [
                'csv' => $csvPath,
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
     * @param Bitrix24ApiClient $bitrixClient
     * @param bool $dryRun
     * @param bool $skipBitrix
     * @return array
     */
    protected function processRecords(
        iterable $records,
        Bitrix24ApiClient $bitrixClient,
        bool $dryRun,
        bool $skipBitrix
    ): array {
        $stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'suppressed' => 0,
            'bitrix_created' => 0,
            'bitrix_updated' => 0,
            'bitrix_failed' => 0,
            'errors' => [],
        ];

        foreach ($records as $index => $record) {
            $stats['total']++;
            $rowNum = $index + 2; // Account for header row

            try {
                $result = $this->processRecord(
                    $record,
                    $bitrixClient,
                    $dryRun,
                    $skipBitrix,
                    $rowNum
                );

                $stats[$result['action']]++;

                if (isset($result['bitrix_action'])) {
                    $stats['bitrix_' . $result['bitrix_action']]++;
                }

            } catch (\Exception $e) {
                $stats['errors'][] = "Row {$rowNum}: " . $e->getMessage();
                $this->warn("Row {$rowNum}: " . $e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Process a single CSV record.
     *
     * @param array $record
     * @param Bitrix24ApiClient $bitrixClient
     * @param bool $dryRun
     * @param bool $skipBitrix
     * @param int $rowNum
     * @return array
     */
    protected function processRecord(
        array $record,
        Bitrix24ApiClient $bitrixClient,
        bool $dryRun,
        bool $skipBitrix,
        int $rowNum
    ): array {
        // Normalize record keys to lowercase
        $record = array_change_key_case($record, CASE_LOWER);

        // Validate email
        $email = strtolower(trim($record['email'] ?? ''));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid email: {$email}");
        }

        // Check suppression list
        if (Suppression::isSuppressed($email)) {
            $this->line("  [SUPPRESSED] Row {$rowNum}: {$email}");
            return ['action' => 'suppressed', 'bitrix_action' => null];
        }

        // Check for existing lead by email (dedupe)
        $existingLead = OutreachLead::where('email', $email)->first();

        // Parse tags
        $tags = [];
        if (!empty($record['tags'])) {
            $tags = array_map('trim', explode(',', $record['tags']));
        }

        $leadData = [
            'email' => $email,
            'company_name' => trim($record['company_name'] ?? ''),
            'contact_name' => trim($record['contact_name'] ?? ''),
            'phone' => trim($record['phone'] ?? ''),
            'city' => trim($record['city'] ?? ''),
            'source' => trim($record['source'] ?? 'csv_import'),
            'source_url' => trim($record['source_url'] ?? $record['website'] ?? ''),
            'tags' => $tags,
            'status' => OutreachLead::STATUS_NEW,
        ];

        if ($dryRun) {
            if ($existingLead) {
                $this->line("  [DRY-RUN UPDATE] Row {$rowNum}: {$email} (existing ID: {$existingLead->id})");
                return ['action' => 'updated', 'bitrix_action' => null];
            } else {
                $this->line("  [DRY-RUN CREATE] Row {$rowNum}: {$email}");
                return ['action' => 'created', 'bitrix_action' => null];
            }
        }

        // Create or update OutreachLead
        if ($existingLead) {
            $existingLead->update($leadData);
            $lead = $existingLead;
            $action = 'updated';
            $this->line("  [UPDATED] Row {$rowNum}: {$email} (ID: {$lead->id})");
        } else {
            $lead = OutreachLead::create($leadData);
            $action = 'created';
            $this->info("  [CREATED] Row {$rowNum}: {$email} (ID: {$lead->id})");
        }

        // Push to Bitrix24
        $bitrixAction = null;

        if (!$skipBitrix) {
            $bitrixAction = $this->pushToBitrix($lead, $bitrixClient, $action);
        }

        return ['action' => $action, 'bitrix_action' => $bitrixAction];
    }

    /**
     * Push lead to Bitrix24 CRM.
     *
     * @param OutreachLead $lead
     * @param Bitrix24ApiClient $bitrixClient
     * @param string $localAction
     * @return string|null
     */
    protected function pushToBitrix(
        OutreachLead $lead,
        Bitrix24ApiClient $bitrixClient,
        string $localAction
    ): ?string {
        try {
            $bitrixData = [
                'TITLE' => $lead->company_name ?: 'Lead from CSV',
                'NAME' => $lead->contact_name ?: $lead->company_name,
                'EMAIL' => [
                    ['VALUE' => $lead->email, 'VALUE_TYPE' => 'WORK'],
                ],
                'STATUS_ID' => 'NEW',
                'SOURCE_ID' => 'WEBFORM', // Or configure custom source
            ];

            if ($lead->phone) {
                $bitrixData['PHONE'] = [
                    ['VALUE' => $lead->phone, 'VALUE_TYPE' => 'WORK'],
                ];
            }

            // Add custom fields
            if ($lead->source) {
                $bitrixData['UF_FCT_SOURCE'] = $lead->source;
            }
            if ($lead->source_url) {
                $bitrixData['UF_FCT_SOURCE_URL'] = $lead->source_url;
            }
            if ($lead->city) {
                $bitrixData['UF_FCT_CITY'] = $lead->city;
            }
            if (!empty($lead->tags)) {
                $bitrixData['UF_FCT_TAGS'] = implode(', ', $lead->tags);
            }

            // Check for existing Bitrix mapping
            $mapping = $lead->bitrixMapping;

            if ($mapping) {
                // Update existing lead
                $success = $bitrixClient->updateLead($mapping->bitrix_lead_id, $bitrixData);

                if ($success) {
                    $mapping->markSynced();
                    return 'updated';
                }

                return 'failed';
            }

            // Create new lead
            $bitrixLeadId = $bitrixClient->createLead($bitrixData);

            if ($bitrixLeadId) {
                // Create mapping
                BitrixLeadMap::syncMapping(
                    $lead->id,
                    $bitrixLeadId,
                    'NEW'
                );

                return 'created';
            }

            return 'failed';

        } catch (\Exception $e) {
            Log::warning('Failed to push lead to Bitrix24', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            return 'failed';
        }
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
        $this->line('Import Results');
        $this->line('--------------');
        $this->line("Total rows processed: {$stats['total']}");
        $this->line("  Created: {$stats['created']}");
        $this->line("  Updated: {$stats['updated']}");
        $this->line("  Skipped: {$stats['skipped']}");
        $this->line("  Suppressed: {$stats['suppressed']}");
        $this->newLine();

        if ($stats['bitrix_created'] > 0 || $stats['bitrix_updated'] > 0) {
            $this->line('Bitrix24 Sync');
            $this->line("  Created: {$stats['bitrix_created']}");
            $this->line("  Updated: {$stats['bitrix_updated']}");
            $this->line("  Failed: {$stats['bitrix_failed']}");
            $this->newLine();
        }

        if (!empty($stats['errors'])) {
            $this->warn('Errors (' . count($stats['errors']) . '):');
            foreach (array_slice($stats['errors'], 0, 10) as $error) {
                $this->line("  - {$error}");
            }
            if (count($stats['errors']) > 10) {
                $this->line('  ... and ' . (count($stats['errors']) - 10) . ' more errors');
            }
        }

        $this->info('Import completed.');
    }
}

// CLAUDE-CHECKPOINT
