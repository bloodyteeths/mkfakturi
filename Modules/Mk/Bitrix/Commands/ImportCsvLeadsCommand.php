<?php

namespace Modules\Mk\Bitrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use Modules\Mk\Bitrix\Models\OutreachLead;

/**
 * ImportCsvLeadsCommand
 *
 * Imports company leads from a CSV file into the outreach_leads table.
 * Expects columns: email, company_name, sector (optional), city (optional), website (optional).
 *
 * Usage:
 *   php artisan outreach:import-csv /path/to/companies.csv --dry-run
 *   php artisan outreach:import-csv /path/to/companies.csv
 *   php artisan outreach:import-csv /path/to/companies.csv --type=accountant
 */
class ImportCsvLeadsCommand extends Command
{
    protected $signature = 'outreach:import-csv
                            {file : Path to CSV file}
                            {--dry-run : Show what would be imported without writing to DB}
                            {--type=company : Lead type (company or accountant)}
                            {--sector= : Default sector for all imported leads}
                            {--delimiter=, : CSV delimiter character}';

    protected $description = 'Import leads from a CSV file into outreach_leads table';

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $dryRun = $this->option('dry-run');
        $leadType = $this->option('type');
        $defaultSector = $this->option('sector');
        $delimiter = $this->option('delimiter');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        if (!in_array($leadType, [OutreachLead::TYPE_COMPANY, OutreachLead::TYPE_ACCOUNTANT])) {
            $this->error("Invalid lead type: {$leadType}. Use 'company' or 'accountant'.");
            return self::FAILURE;
        }

        $this->info("Importing {$leadType} leads from: {$filePath}");
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No records will be created');
        }
        $this->newLine();

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setDelimiter($delimiter);
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();
        $stats = ['created' => 0, 'skipped_duplicate' => 0, 'skipped_invalid' => 0];

        foreach ($records as $record) {
            $email = trim($record['email'] ?? '');

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->line("  <fg=yellow>SKIP</> Invalid email: {$email}");
                $stats['skipped_invalid']++;
                continue;
            }

            $email = strtolower($email);

            // Check if lead already exists
            if (OutreachLead::where('email', $email)->exists()) {
                $this->line("  <fg=yellow>SKIP</> Already exists: {$email}");
                $stats['skipped_duplicate']++;
                continue;
            }

            $companyName = trim($record['company_name'] ?? $record['company'] ?? '');
            $sector = trim($record['sector'] ?? $defaultSector ?? '');
            $city = trim($record['city'] ?? '');
            $website = trim($record['website'] ?? '');
            $contactName = trim($record['contact_name'] ?? $record['name'] ?? '');
            $phone = trim($record['phone'] ?? '');

            // Derive company name from email domain if not provided
            if (empty($companyName)) {
                $domain = explode('@', $email)[1] ?? '';
                $companyName = ucfirst(explode('.', $domain)[0] ?? '');
            }

            if ($dryRun) {
                $this->line("  <fg=green>IMPORT</> {$email} ({$companyName}) [{$leadType}]");
                $stats['created']++;
                continue;
            }

            OutreachLead::create([
                'email' => $email,
                'company_name' => $companyName ?: null,
                'contact_name' => $contactName ?: null,
                'phone' => $phone ?: null,
                'city' => $city ?: null,
                'website' => $website ?: null,
                'source' => OutreachLead::SOURCE_SCRAPE,
                'lead_type' => $leadType,
                'sector' => $sector ?: null,
                'status' => OutreachLead::STATUS_NEW,
            ]);

            $this->line("  <fg=green>CREATED</> {$email} ({$companyName})");
            $stats['created']++;
        }

        $this->newLine();
        $this->info('Import Results:');
        $this->line("  Created: {$stats['created']}");
        $this->line("  Skipped (duplicate): {$stats['skipped_duplicate']}");
        $this->line("  Skipped (invalid): {$stats['skipped_invalid']}");

        Log::info('CSV lead import completed', $stats);

        return self::SUCCESS;
    }
}

// CLAUDE-CHECKPOINT
