<?php

/**
 * SD-02: Sample Data Import Helper Script
 *
 * This script imports sample Macedonia invoices from /samples/invoices/ into the database
 * using the Universal Migration Wizard system. It works with the existing CSV files
 * created for SD-01 and processes them through the complete import workflow.
 *
 * Usage:
 *   php tools/sample_import.php [--company=1] [--user=1] [--force]
 *
 * Options:
 *   --company=ID   Specify company ID (default: 1)
 *   --user=ID      Specify user ID for import job (default: 1)
 *   --force        Skip confirmation prompts
 *
 * Features:
 * - Uses existing Universal Migration Wizard infrastructure
 * - Processes all 4 CSV files (customers, invoices, items, payments)
 * - Provides detailed progress tracking and error reporting
 * - Validates data integrity after import
 * - Supports rollback on failure
 */

require_once __DIR__.'/../vendor/autoload.php';

use App\Jobs\Migration\CommitImportJob;
use App\Jobs\Migration\DetectFileTypeJob;
use App\Jobs\Migration\ValidateDataJob;
use App\Models\Company;
use App\Models\Customer;
use App\Models\ImportJob;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// Boot Laravel application
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

class SampleImporter
{
    private int $companyId;

    private int $userId;

    private bool $force;

    private array $csvFiles;

    private array $importStats = [];

    public function __construct(array $options = [])
    {
        $this->companyId = $options['company'] ?? 1;
        $this->userId = $options['user'] ?? 1;
        $this->force = $options['force'] ?? false;

        $this->csvFiles = [
            'customers' => 'samples/invoices/macedonia_customers_sample.csv',
            'items' => 'samples/invoices/macedonia_items_sample.csv',
            'invoices' => 'samples/invoices/macedonia_invoices_sample.csv',
            'payments' => 'samples/invoices/macedonia_payments_sample.csv',
        ];
    }

    /**
     * Main import execution method
     */
    public function import(): bool
    {
        try {
            $this->printHeader();

            if (! $this->validatePrerequisites()) {
                return false;
            }

            if (! $this->confirmImport()) {
                $this->output('Import cancelled by user.');

                return false;
            }

            $this->output("Starting sample data import...\n");

            // Import data in dependency order
            $importOrder = ['customers', 'items', 'invoices', 'payments'];

            foreach ($importOrder as $type) {
                $this->output("Importing {$type}...");

                if (! $this->importCsvFile($type, $this->csvFiles[$type])) {
                    $this->output("ERROR: Failed to import {$type}. Rolling back...");
                    $this->rollbackImports();

                    return false;
                }

                $this->output("✓ {$type} imported successfully");
            }

            $this->validateImportResults();
            $this->printSummary();

            return true;

        } catch (Exception $e) {
            $this->output('ERROR: '.$e->getMessage());
            $this->output('Rolling back imports...');
            $this->rollbackImports();

            return false;
        }
    }

    /**
     * Import single CSV file using Universal Migration Wizard
     */
    private function importCsvFile(string $type, string $filePath): bool
    {
        try {
            // Verify file exists
            $fullPath = base_path($filePath);
            if (! file_exists($fullPath)) {
                throw new Exception("File not found: {$fullPath}");
            }

            // Create temporary file in storage for import job
            $tempFilename = "sample_import_{$type}_".now()->format('Y-m-d_H-i-s').'.csv';
            $storagePath = "imports/{$this->companyId}/{$tempFilename}";

            Storage::disk('private')->put($storagePath, file_get_contents($fullPath));

            // Create import job
            $importJob = ImportJob::create([
                'company_id' => $this->companyId,
                'creator_id' => $this->userId,
                'type' => $type === 'invoices' ? ImportJob::TYPE_COMPLETE : $type,
                'status' => ImportJob::STATUS_PENDING,
                'source_system' => 'sample_data',
                'file_path' => $storagePath,
                'file_info' => [
                    'original_name' => basename($filePath),
                    'filename' => $tempFilename,
                    'extension' => 'csv',
                    'size' => filesize($fullPath),
                    'mime_type' => 'text/csv',
                ],
            ]);

            // Process through migration pipeline
            $this->output("  - Processing file: {$filePath}");

            // Step 1: Detect file type
            DetectFileTypeJob::dispatchSync($importJob);
            $importJob->refresh();

            if ($importJob->status === ImportJob::STATUS_FAILED) {
                throw new Exception('File type detection failed: '.json_encode($importJob->error_details));
            }

            // Step 2: Validate data
            ValidateDataJob::dispatchSync($importJob);
            $importJob->refresh();

            if ($importJob->status === ImportJob::STATUS_FAILED) {
                throw new Exception('Data validation failed: '.json_encode($importJob->error_details));
            }

            // Step 3: Commit import
            CommitImportJob::dispatchSync($importJob);
            $importJob->refresh();

            if ($importJob->status === ImportJob::STATUS_FAILED) {
                throw new Exception('Import commit failed: '.json_encode($importJob->error_details));
            }

            // Store import stats
            $this->importStats[$type] = [
                'job_id' => $importJob->id,
                'total_records' => $importJob->total_records,
                'successful_records' => $importJob->successful_records,
                'failed_records' => $importJob->failed_records,
            ];

            // Clean up temp file
            Storage::disk('private')->delete($storagePath);

            return true;

        } catch (Exception $e) {
            $this->output('  ERROR: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Validate prerequisites for import
     */
    private function validatePrerequisites(): bool
    {
        $this->output('Validating prerequisites...');

        // Check if company exists
        $company = Company::find($this->companyId);
        if (! $company) {
            $this->output("ERROR: Company with ID {$this->companyId} not found.");

            return false;
        }

        // Check if user exists
        $user = User::find($this->userId);
        if (! $user) {
            $this->output("ERROR: User with ID {$this->userId} not found.");

            return false;
        }

        // Check if CSV files exist
        foreach ($this->csvFiles as $type => $filePath) {
            $fullPath = base_path($filePath);
            if (! file_exists($fullPath)) {
                $this->output("ERROR: Required file not found: {$fullPath}");

                return false;
            }
        }

        // Check database connection
        try {
            DB::connection()->getPdo();
        } catch (Exception $e) {
            $this->output('ERROR: Database connection failed: '.$e->getMessage());

            return false;
        }

        $this->output('✓ All prerequisites validated');

        return true;
    }

    /**
     * Confirm import with user
     */
    private function confirmImport(): bool
    {
        if ($this->force) {
            return true;
        }

        $this->output("\nImport Configuration:");
        $this->output("  Company ID: {$this->companyId}");
        $this->output("  User ID: {$this->userId}");
        $this->output('  Files to import: '.count($this->csvFiles));

        foreach ($this->csvFiles as $type => $filePath) {
            $fullPath = base_path($filePath);
            $lines = count(file($fullPath)) - 1; // Subtract header
            $this->output("    - {$type}: {$lines} records");
        }

        $this->output("\nThis will import sample Macedonia invoice data into the database.");
        $confirm = readline('Continue? (y/N): ');

        return strtolower(trim($confirm)) === 'y';
    }

    /**
     * Validate import results
     */
    private function validateImportResults(): void
    {
        $this->output("\nValidating import results...");

        $counts = [
            'customers' => Customer::where('company_id', $this->companyId)->count(),
            'items' => Item::where('company_id', $this->companyId)->count(),
            'invoices' => Invoice::where('company_id', $this->companyId)->count(),
            'payments' => Payment::where('company_id', $this->companyId)->count(),
        ];

        foreach ($counts as $type => $count) {
            $expected = $this->importStats[$type]['successful_records'] ?? 0;
            if ($count >= $expected) {
                $this->output("✓ {$type}: {$count} records in database");
            } else {
                $this->output("⚠ {$type}: Expected {$expected}, found {$count} records");
            }
        }

        // Validate specific sample data
        $macedonianBank = Customer::where('company_id', $this->companyId)
            ->where('name', 'LIKE', '%Македонска Банка%')
            ->first();

        if ($macedonianBank) {
            $this->output('✓ Sample customer data validated (Македонска Банка found)');
        } else {
            $this->output('⚠ Sample customer validation failed');
        }

        $sampleInvoice = Invoice::where('company_id', $this->companyId)
            ->where('invoice_number', 'МК-2024-001')
            ->first();

        if ($sampleInvoice) {
            $this->output('✓ Sample invoice data validated (МК-2024-001 found)');
        } else {
            $this->output('⚠ Sample invoice validation failed');
        }
    }

    /**
     * Rollback imports on failure
     */
    private function rollbackImports(): void
    {
        $this->output('Rolling back imported data...');

        try {
            DB::beginTransaction();

            // Delete imported data (reverse dependency order)
            Payment::where('company_id', $this->companyId)->delete();
            Invoice::where('company_id', $this->companyId)->delete();
            Item::where('company_id', $this->companyId)->delete();
            Customer::where('company_id', $this->companyId)->delete();

            // Delete import jobs
            foreach ($this->importStats as $stats) {
                ImportJob::where('id', $stats['job_id'])->delete();
            }

            DB::commit();
            $this->output('✓ Rollback completed');

        } catch (Exception $e) {
            DB::rollback();
            $this->output('ERROR: Rollback failed: '.$e->getMessage());
        }
    }

    /**
     * Print import summary
     */
    private function printSummary(): void
    {
        $this->output("\n".str_repeat('=', 60));
        $this->output('IMPORT SUMMARY');
        $this->output(str_repeat('=', 60));

        $totalRecords = 0;
        $totalSuccessful = 0;
        $totalFailed = 0;

        foreach ($this->importStats as $type => $stats) {
            $this->output(sprintf(
                '%-12s: %3d total, %3d successful, %3d failed',
                ucfirst($type),
                $stats['total_records'],
                $stats['successful_records'],
                $stats['failed_records']
            ));

            $totalRecords += $stats['total_records'];
            $totalSuccessful += $stats['successful_records'];
            $totalFailed += $stats['failed_records'];
        }

        $this->output(str_repeat('-', 60));
        $this->output(sprintf(
            '%-12s: %3d total, %3d successful, %3d failed',
            'TOTAL',
            $totalRecords,
            $totalSuccessful,
            $totalFailed
        ));

        $this->output("\n✓ Sample Macedonia invoice data imported successfully!");
        $this->output('  The Universal Migration Wizard has processed all sample data.');
        $this->output('  You can now view the imported invoices in the system.');
    }

    /**
     * Print script header
     */
    private function printHeader(): void
    {
        $this->output(str_repeat('=', 60));
        $this->output('FACTURINO - Sample Data Import (SD-02)');
        $this->output('Universal Migration Wizard - Macedonia Invoice Data');
        $this->output(str_repeat('=', 60));
    }

    /**
     * Output message to console
     */
    private function output(string $message): void
    {
        echo $message.PHP_EOL;
    }
}

// Parse command line arguments
$options = [];
$args = array_slice($argv, 1);

foreach ($args as $arg) {
    if (str_starts_with($arg, '--company=')) {
        $options['company'] = (int) substr($arg, 10);
    } elseif (str_starts_with($arg, '--user=')) {
        $options['user'] = (int) substr($arg, 7);
    } elseif ($arg === '--force') {
        $options['force'] = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Usage: php tools/sample_import.php [--company=1] [--user=1] [--force]\n";
        echo "\n";
        echo "Options:\n";
        echo "  --company=ID   Specify company ID (default: 1)\n";
        echo "  --user=ID      Specify user ID for import job (default: 1)\n";
        echo "  --force        Skip confirmation prompts\n";
        echo "  --help, -h     Show this help message\n";
        echo "\n";
        echo "This script imports sample Macedonia invoice data from /samples/invoices/\n";
        echo "using the Universal Migration Wizard system.\n";
        exit(0);
    }
}

// Execute import
$importer = new SampleImporter($options);
$success = $importer->import();

exit($success ? 0 : 1);
