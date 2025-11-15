<?php

namespace App\Jobs;

use App\Imports\CustomerImport;
use App\Imports\InvoiceImport;
use App\Imports\BillImport;
use App\Imports\ItemImport;
use App\Models\ImportJob;
use App\Services\Migration\ImportPresetService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Writer;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Process Import Job
 *
 * Handles background processing of CSV/XLSX imports:
 * - Runs on 'migration' queue
 * - Supports dry-run mode (validation only)
 * - Generates error CSV for failed rows
 * - Updates ImportJob status in real-time
 *
 * @package App\Jobs
 */
class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    private ImportJob $importJob;
    private bool $isDryRun;

    /**
     * Create a new job instance.
     *
     * @param ImportJob $importJob
     * @param bool $isDryRun
     */
    public function __construct(ImportJob $importJob, bool $isDryRun = false)
    {
        $this->importJob = $importJob;
        $this->isDryRun = $isDryRun;
        $this->queue = 'migration';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ImportPresetService $presetService)
    {
        try {
            // Mark job as started
            $this->importJob->markAsStarted();

            // Get file info
            $fileInfo = $this->importJob->file_info;
            $filePath = $fileInfo['path'] ?? null;

            if (!$filePath || !Storage::exists($filePath)) {
                throw new \Exception('Import file not found');
            }

            // Update status to parsing
            $this->importJob->update(['status' => ImportJob::STATUS_PARSING]);

            // Detect file encoding and convert if needed
            $content = Storage::get($filePath);
            $encoding = $presetService->detectEncoding($content);

            if ($encoding !== 'UTF-8') {
                $content = $presetService->convertToUtf8($content, $encoding);
                // Save converted file
                $convertedPath = str_replace('.csv', '_utf8.csv', $filePath);
                Storage::put($convertedPath, $content);
                $filePath = $convertedPath;
            }

            // Update status to mapping
            $this->importJob->update(['status' => ImportJob::STATUS_MAPPING]);

            // Get column mapping
            $mapping = $this->importJob->mapping_config ?? [];

            // Update status to validating
            $this->importJob->update(['status' => ImportJob::STATUS_VALIDATING]);

            // Process import based on type
            $importer = $this->createImporter($mapping);

            // Import the file
            Excel::import($importer, $filePath);

            // Get results
            $successCount = $importer->getSuccessCount();
            $failureCount = $importer->getFailureCount();
            $failures = $importer->getFailures();

            // Update job progress
            $this->importJob->updateProgress(
                $successCount + $failureCount,
                $successCount,
                $failureCount
            );

            // Generate error CSV if there are failures
            if ($failureCount > 0) {
                $this->generateErrorCsv($failures);
            }

            // Mark as completed
            $summary = [
                'total_rows' => $successCount + $failureCount,
                'successful_rows' => $successCount,
                'failed_rows' => $failureCount,
                'is_dry_run' => $this->isDryRun,
                'encoding' => $encoding,
            ];

            $this->importJob->markAsCompleted($summary);

        } catch (\Exception $e) {
            Log::error('Import job failed', [
                'job_id' => $this->importJob->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->importJob->markAsFailed($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    /**
     * Create importer instance based on job type
     *
     * @param array $mapping
     * @return CustomerImport|InvoiceImport|ItemImport|BillImport
     * @throws \Exception
     */
    private function createImporter(array $mapping)
    {
        $companyId = $this->importJob->company_id;
        $creatorId = $this->importJob->creator_id;

        return match ($this->importJob->type) {
            ImportJob::TYPE_CUSTOMERS => new CustomerImport(
                $companyId,
                $this->importJob->file_info['currency_id'] ?? 1,
                $creatorId,
                $mapping,
                $this->isDryRun
            ),
            ImportJob::TYPE_ITEMS => new ItemImport(
                $companyId,
                $this->importJob->file_info['currency_id'] ?? 1,
                $creatorId,
                $mapping,
                $this->isDryRun
            ),
            ImportJob::TYPE_INVOICES => new InvoiceImport(
                $companyId,
                $creatorId,
                $mapping,
                $this->isDryRun
            ),
            ImportJob::TYPE_BILLS => new BillImport(
                $companyId,
                $creatorId,
                $mapping,
                $this->isDryRun
            ),
            default => throw new \Exception('Unsupported import type: ' . $this->importJob->type),
        };
    }

    /**
     * Generate error CSV file for failed rows
     *
     * @param array $failures
     * @return void
     */
    private function generateErrorCsv(array $failures): void
    {
        try {
            // Create CSV writer
            $csv = Writer::createFromString('');

            // Add header
            $headers = ['Row', 'Field', 'Error', 'Value'];
            $csv->insertOne($headers);

            // Add failure rows
            foreach ($failures as $failure) {
                $csv->insertOne([
                    $failure['row'] ?? 'N/A',
                    $failure['attribute'] ?? 'N/A',
                    implode(', ', $failure['errors'] ?? []),
                    json_encode($failure['values'] ?? []),
                ]);
            }

            // Save error CSV
            $errorPath = 'imports/errors/import_' . $this->importJob->id . '_errors.csv';
            Storage::put($errorPath, $csv->toString());

            // Update import job with error file path
            $this->importJob->update([
                'error_details' => array_merge(
                    $this->importJob->error_details ?? [],
                    ['error_csv_path' => $errorPath]
                ),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate error CSV', [
                'job_id' => $this->importJob->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Import job failed permanently', [
            'job_id' => $this->importJob->id,
            'error' => $exception->getMessage(),
        ]);

        $this->importJob->markAsFailed($exception->getMessage(), [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
        ]);
    }
}

// CLAUDE-CHECKPOINT
