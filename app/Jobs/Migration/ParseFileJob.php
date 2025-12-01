<?php

namespace App\Jobs\Migration;

use App\Models\ImportJob;
use App\Models\ImportLog;
use App\Models\ImportTempCustomer;
use App\Models\ImportTempExpense;
use App\Models\ImportTempInvoice;
use App\Models\ImportTempItem;
use App\Models\ImportTempPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * ParseFileJob - Parse CSV/Excel/XML files into temp tables
 *
 * This job parses uploaded files and loads raw data into temporary staging tables:
 * - Handles CSV, Excel, and XML formats
 * - Applies basic data cleaning and transformation
 * - Loads data into appropriate temp tables based on import type
 * - Maintains raw data for audit trail
 * - Chains to AutoMapFieldsJob on success
 */
class ParseFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ImportJob $importJob;

    /**
     * Job timeout in seconds (30 minutes for large files)
     */
    public int $timeout = 1800;

    /**
     * Maximum number of retries
     */
    public int $tries = 2;

    /**
     * Backoff delays in seconds
     */
    public array $backoff = [60, 300];

    /**
     * Queue name for import jobs
     */
    public string $queue = 'migration';

    /**
     * Batch size for database inserts
     */
    protected int $batchSize = 500;

    /**
     * Memory threshold for performance warnings (MB)
     */
    protected int $memoryThreshold = 256;

    /**
     * Create a new job instance
     */
    public function __construct(ImportJob $importJob)
    {
        $this->importJob = $importJob;
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            // Update job status
            $this->importJob->update(['status' => ImportJob::STATUS_PARSING]);

            Log::info('File parsing started', [
                'import_job_id' => $this->importJob->id,
                'file_type' => $this->importJob->file_info['type'] ?? 'unknown',
                'estimated_rows' => $this->importJob->total_records,
            ]);

            // Parse file based on detected type
            $fileInfo = $this->importJob->file_info;
            $parsedData = $this->parseFileByType($fileInfo);

            // Load parsed data into temp tables
            $this->loadDataToTempTables($parsedData, $fileInfo);

            // Update job progress
            $this->importJob->update([
                'processed_records' => count($parsedData),
                'successful_records' => count($parsedData),
            ]);

            // Performance monitoring
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            $processingTime = $endTime - $startTime;
            $memoryUsed = $endMemory - $startMemory;

            if ($memoryUsed > $this->memoryThreshold * 1024 * 1024) {
                ImportLog::logPerformanceWarning(
                    $this->importJob,
                    'High memory usage during file parsing',
                    $processingTime,
                    $memoryUsed,
                    count($parsedData)
                );
            }

            // Log successful parsing
            ImportLog::create([
                'import_job_id' => $this->importJob->id,
                'log_type' => ImportLog::LOG_FILE_PARSED,
                'severity' => ImportLog::SEVERITY_INFO,
                'message' => 'File parsed successfully',
                'detailed_message' => "Parsed {$this->importJob->processed_records} records in ".round($processingTime, 2).' seconds',
                'process_stage' => 'parsing',
                'processing_time' => $processingTime,
                'memory_usage' => $memoryUsed,
                'records_processed' => count($parsedData),
            ]);

            Log::info('File parsing completed', [
                'import_job_id' => $this->importJob->id,
                'records_parsed' => count($parsedData),
                'processing_time' => $processingTime,
            ]);

            // Chain to next job - AutoMapFieldsJob
            AutoMapFieldsJob::dispatch($this->importJob)
                ->onQueue('imports')
                ->delay(now()->addSeconds(5));

        } catch (\Exception $e) {
            $this->handleJobFailure($e);
            throw $e;
        }
    }

    /**
     * Parse file based on detected type
     */
    protected function parseFileByType(array $fileInfo): array
    {
        $filePath = $this->importJob->file_path;

        switch ($fileInfo['type']) {
            case 'csv':
                return $this->parseCsvFile($filePath, $fileInfo);

            case 'excel':
                return $this->parseExcelFile($filePath, $fileInfo);

            case 'xml':
                return $this->parseXmlFile($filePath, $fileInfo);

            default:
                throw new \Exception("Unsupported file type: {$fileInfo['type']}");
        }
    }

    /**
     * Parse CSV file
     */
    protected function parseCsvFile(string $filePath, array $fileInfo): array
    {
        $csvContent = Storage::get($filePath);

        // Handle encoding conversion
        $encoding = $fileInfo['encoding'] ?? 'UTF-8';
        if ($encoding !== 'UTF-8') {
            $csvContent = mb_convert_encoding($csvContent, 'UTF-8', $encoding);
        }

        // Create CSV reader
        $csv = Reader::createFromString($csvContent);
        $delimiter = $fileInfo['structure']['delimiter'] ?? ',';
        $csv->setDelimiter($delimiter);

        // Set header offset if file has headers
        if ($fileInfo['structure']['has_header'] ?? true) {
            $csv->setHeaderOffset(0);
        }

        $records = [];
        $rowNumber = 1;

        foreach ($csv as $record) {
            // Skip empty rows
            if (empty(array_filter($record, fn ($cell) => trim($cell) !== ''))) {
                continue;
            }

            // Apply basic data cleaning
            $cleanedRecord = array_map([$this, 'cleanCellValue'], $record);

            $records[] = [
                'row_number' => $rowNumber,
                'raw_data' => $record,
                'cleaned_data' => $cleanedRecord,
            ];

            $rowNumber++;

            // Memory management for large files
            if ($rowNumber % 1000 === 0) {
                $this->checkMemoryUsage($rowNumber);
            }
        }

        return $records;
    }

    /**
     * Parse Excel file
     */
    protected function parseExcelFile(string $filePath, array $fileInfo): array
    {
        $fullPath = Storage::path($filePath);

        try {
            $spreadsheet = IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $records = [];
            $rowNumber = 1;
            $hasHeader = true; // Assume Excel files have headers

            foreach ($rows as $row) {
                // Skip header row
                if ($hasHeader && $rowNumber === 1) {
                    $rowNumber++;

                    continue;
                }

                // Skip empty rows
                if (empty(array_filter($row, fn ($cell) => $cell !== null && trim($cell) !== ''))) {
                    continue;
                }

                // Convert null values to empty strings and clean data
                $cleanedRow = array_map(function ($cell) {
                    if ($cell === null) {
                        return '';
                    }

                    return $this->cleanCellValue($cell);
                }, $row);

                $records[] = [
                    'row_number' => $rowNumber,
                    'raw_data' => $row,
                    'cleaned_data' => $cleanedRow,
                ];

                $rowNumber++;

                // Memory management
                if ($rowNumber % 500 === 0) {
                    $this->checkMemoryUsage($rowNumber);
                }
            }

            return $records;

        } catch (\Exception $e) {
            throw new \Exception("Excel parsing failed: {$e->getMessage()}");
        }
    }

    /**
     * Parse XML file
     */
    protected function parseXmlFile(string $filePath, array $fileInfo): array
    {
        $xmlContent = Storage::get($filePath);

        try {
            $xml = simplexml_load_string($xmlContent);
            if ($xml === false) {
                throw new \Exception('Invalid XML format');
            }

            $records = [];
            $rowNumber = 1;

            // Convert XML to array-like structure
            // This is a simplified implementation - real XML parsing would be more sophisticated
            foreach ($xml->children() as $element) {
                $record = [];

                // Extract attributes
                foreach ($element->attributes() as $key => $value) {
                    $record["@{$key}"] = (string) $value;
                }

                // Extract child elements
                foreach ($element->children() as $key => $value) {
                    $record[$key] = $this->cleanCellValue((string) $value);
                }

                $records[] = [
                    'row_number' => $rowNumber,
                    'raw_data' => $record,
                    'cleaned_data' => $record,
                ];

                $rowNumber++;
            }

            return $records;

        } catch (\Exception $e) {
            throw new \Exception("XML parsing failed: {$e->getMessage()}");
        }
    }

    /**
     * Load parsed data into temporary tables
     */
    protected function loadDataToTempTables(array $parsedData, array $fileInfo): void
    {
        if (empty($parsedData)) {
            return;
        }

        // Determine target temp table based on import type
        $tempModel = $this->getTempModelClass();

        // Process data in batches for better performance
        $batches = array_chunk($parsedData, $this->batchSize);
        $totalBatches = count($batches);

        foreach ($batches as $batchIndex => $batch) {
            $this->processBatch($batch, $tempModel, $fileInfo);

            // Update progress
            $processed = ($batchIndex + 1) * $this->batchSize;
            $this->importJob->updateProgress(min($processed, count($parsedData)));

            Log::debug('Processed batch '.($batchIndex + 1)."/{$totalBatches}", [
                'import_job_id' => $this->importJob->id,
                'records_in_batch' => count($batch),
            ]);
        }
    }

    /**
     * Process a batch of records
     */
    protected function processBatch(array $batch, string $tempModel, array $fileInfo): void
    {
        $insertData = [];

        foreach ($batch as $record) {
            $insertData[] = [
                'import_job_id' => $this->importJob->id,
                'row_number' => $record['row_number'],
                'raw_data' => json_encode($record['raw_data']),
                'cleaned_data' => json_encode($record['cleaned_data']),
                'validation_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Bulk insert for performance
        DB::table((new $tempModel)->getTable())->insert($insertData);
    }

    /**
     * Get appropriate temp model class based on import type
     */
    protected function getTempModelClass(): string
    {
        return match ($this->importJob->type) {
            ImportJob::TYPE_CUSTOMERS => ImportTempCustomer::class,
            ImportJob::TYPE_INVOICES => ImportTempInvoice::class,
            ImportJob::TYPE_ITEMS => ImportTempItem::class,
            ImportJob::TYPE_PAYMENTS => ImportTempPayment::class,
            ImportJob::TYPE_EXPENSES => ImportTempExpense::class,
            ImportJob::TYPE_COMPLETE => ImportTempCustomer::class, // Start with customers for complete import
            default => throw new \Exception("Unknown import type: {$this->importJob->type}"),
        };
    }

    /**
     * Clean individual cell values
     */
    protected function cleanCellValue($value): string
    {
        if ($value === null) {
            return '';
        }

        $value = (string) $value;

        // Trim whitespace
        $value = trim($value);

        // Remove BOM if present
        $value = str_replace("\xEF\xBB\xBF", '', $value);

        // Normalize line endings
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        // Remove excessive whitespace
        $value = preg_replace('/\s+/', ' ', $value);

        return $value;
    }

    /**
     * Check memory usage and warn if threshold exceeded
     */
    protected function checkMemoryUsage(int $recordsProcessed): void
    {
        $memoryUsage = memory_get_usage(true);
        $memoryUsageMB = $memoryUsage / (1024 * 1024);

        if ($memoryUsageMB > $this->memoryThreshold) {
            Log::warning('High memory usage during parsing', [
                'import_job_id' => $this->importJob->id,
                'memory_usage_mb' => round($memoryUsageMB, 2),
                'records_processed' => $recordsProcessed,
            ]);

            // Force garbage collection
            gc_collect_cycles();
        }
    }

    /**
     * Handle job failure
     */
    protected function handleJobFailure(\Exception $exception): void
    {
        Log::error('File parsing job failed', [
            'import_job_id' => $this->importJob->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mark import job as failed
        $this->importJob->markAsFailed(
            'File parsing failed: '.$exception->getMessage(),
            [
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'stage' => 'parsing',
            ]
        );

        // Log failure
        ImportLog::logJobFailed($this->importJob, $exception->getMessage(), [
            'stage' => 'file_parsing',
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Cleanup temp data if any was created
        $this->cleanupTempData();
    }

    /**
     * Cleanup temporary data on failure
     */
    protected function cleanupTempData(): void
    {
        try {
            $tempModel = $this->getTempModelClass();
            DB::table((new $tempModel)->getTable())
                ->where('import_job_id', $this->importJob->id)
                ->delete();
        } catch (\Exception $e) {
            Log::warning('Failed to cleanup temp data', [
                'import_job_id' => $this->importJob->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure (Laravel queue method)
     */
    public function failed(\Throwable $exception): void
    {
        $this->handleJobFailure($exception);
    }
}
