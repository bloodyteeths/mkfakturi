<?php

namespace App\Jobs\Migration;

use App\Models\ImportJob;
use App\Models\ImportLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\QueueableActions\QueueableAction;

/**
 * DetectFileTypeJob - Detect file type and validate structure
 *
 * This job analyzes uploaded files to:
 * - Detect file format (CSV, Excel, XML)
 * - Validate file structure and readability
 * - Extract basic metadata (size, encoding, headers)
 * - Update ImportJob with file information
 * - Chain to next job (ParseFileJob) on success
 */
class DetectFileTypeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, QueueableAction, SerializesModels;

    public ImportJob $importJob;

    /**
     * Job timeout in seconds (5 minutes)
     */
    public int $timeout = 300;

    /**
     * Maximum number of retries
     */
    public int $tries = 3;

    /**
     * Backoff delays in seconds
     */
    public array $backoff = [10, 30, 60];

    /**
     * Queue name for import jobs
     */
    public string $queue = 'migration';

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
        try {
            // Mark job as started if not already
            if ($this->importJob->status === ImportJob::STATUS_PENDING) {
                $this->importJob->markAsStarted();
                ImportLog::logJobStarted($this->importJob);
            }

            // Update status to parsing
            $this->importJob->update(['status' => ImportJob::STATUS_PARSING]);

            Log::info('File type detection started', [
                'import_job_id' => $this->importJob->id,
                'company_id' => $this->importJob->company_id,
                'file_path' => $this->importJob->file_path,
            ]);

            // Validate file exists
            if (! Storage::exists($this->importJob->file_path)) {
                throw new \Exception("File not found: {$this->importJob->file_path}");
            }

            // Get file info
            $fileInfo = $this->analyzeFile();

            // Update import job with file information
            $this->importJob->update([
                'file_info' => $fileInfo,
                'total_records' => $fileInfo['estimated_rows'] ?? 0,
            ]);

            // Log successful detection
            ImportLog::create([
                'import_job_id' => $this->importJob->id,
                'log_type' => ImportLog::LOG_FILE_PARSED,
                'severity' => ImportLog::SEVERITY_INFO,
                'message' => "File type detected: {$fileInfo['type']}",
                'detailed_message' => "File analysis completed successfully. Format: {$fileInfo['type']}, Size: {$fileInfo['formatted_size']}, Estimated rows: {$fileInfo['estimated_rows']}",
                'process_stage' => 'parsing',
                'final_data' => $fileInfo,
            ]);

            Log::info('File type detection completed', [
                'import_job_id' => $this->importJob->id,
                'file_info' => $fileInfo,
            ]);

            // Chain to next job - ParseFileJob
            ParseFileJob::dispatch($this->importJob)
                ->onQueue('migration')
                ->delay(now()->addSeconds(2));

        } catch (\Exception $e) {
            $this->handleJobFailure($e);
            throw $e;
        }
    }

    /**
     * Analyze file to detect type and extract metadata
     */
    protected function analyzeFile(): array
    {
        $filePath = $this->importJob->file_path;
        $fileSize = Storage::size($filePath);
        $fileName = basename($filePath);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Read first chunk of file for analysis
        $chunkSize = min($fileSize, 8192); // Read up to 8KB
        $fileContent = Storage::get($filePath, 0, $chunkSize);

        $fileInfo = [
            'name' => $fileName,
            'size' => $fileSize,
            'formatted_size' => $this->formatBytes($fileSize),
            'extension' => $extension,
            'mime_type' => $this->detectMimeType($fileContent, $extension),
            'encoding' => $this->detectEncoding($fileContent),
            'type' => null,
            'structure' => [],
            'estimated_rows' => 0,
            'headers' => [],
            'sample_data' => [],
            'validation_errors' => [],
        ];

        // Detect file type based on extension and content
        $fileInfo['type'] = $this->detectFileType($extension, $fileContent);

        // Analyze structure based on file type
        switch ($fileInfo['type']) {
            case 'csv':
                $csvInfo = $this->analyzeCsvStructure($fileContent);
                $fileInfo = array_merge($fileInfo, $csvInfo);
                break;

            case 'excel':
                $excelInfo = $this->analyzeExcelStructure($filePath);
                $fileInfo = array_merge($fileInfo, $excelInfo);
                break;

            case 'xml':
                $xmlInfo = $this->analyzeXmlStructure($fileContent);
                $fileInfo = array_merge($fileInfo, $xmlInfo);
                break;

            default:
                $fileInfo['validation_errors'][] = "Unsupported file type: {$fileInfo['type']}";
        }

        // Validate file structure
        $this->validateFileStructure($fileInfo);

        return $fileInfo;
    }

    /**
     * Detect file type from extension and content
     */
    protected function detectFileType(string $extension, string $content): string
    {
        // Primary detection by extension
        switch ($extension) {
            case 'csv':
            case 'txt':
                return 'csv';
            case 'xlsx':
            case 'xls':
                return 'excel';
            case 'xml':
                return 'xml';
        }

        // Secondary detection by content analysis
        if (str_contains($content, '<?xml')) {
            return 'xml';
        }

        // Check for Excel file signatures
        if (str_starts_with($content, 'PK') || str_contains($content, 'xl/')) {
            return 'excel';
        }

        // Default to CSV for text-based content
        if (mb_check_encoding($content, 'UTF-8') || mb_check_encoding($content, 'ISO-8859-1')) {
            return 'csv';
        }

        return 'unknown';
    }

    /**
     * Analyze CSV file structure
     */
    protected function analyzeCsvStructure(string $content): array
    {
        $lines = explode("\n", $content);
        $lines = array_filter($lines, fn ($line) => trim($line) !== '');

        if (empty($lines)) {
            return [
                'estimated_rows' => 0,
                'validation_errors' => ['Empty CSV file'],
            ];
        }

        // Detect CSV delimiter
        $delimiter = $this->detectCsvDelimiter($lines[0]);

        // Parse first few rows
        $headers = [];
        $sampleData = [];
        $maxSampleRows = min(5, count($lines));

        for ($i = 0; $i < $maxSampleRows; $i++) {
            $row = str_getcsv($lines[$i], $delimiter);
            if ($i === 0) {
                $headers = $row;
            } else {
                $sampleData[] = $row;
            }
        }

        return [
            'structure' => [
                'delimiter' => $delimiter,
                'has_header' => $this->detectCsvHeaders($headers),
                'columns' => count($headers),
            ],
            'estimated_rows' => count($lines) - 1, // Subtract header row
            'headers' => $headers,
            'sample_data' => $sampleData,
        ];
    }

    /**
     * Analyze Excel file structure (basic implementation)
     */
    protected function analyzeExcelStructure(string $filePath): array
    {
        try {
            // This would require PhpSpreadsheet package
            // For now, return basic structure
            return [
                'structure' => [
                    'format' => 'excel',
                    'sheets' => 1, // Default assumption
                ],
                'estimated_rows' => 100, // Placeholder
                'headers' => [],
                'sample_data' => [],
                'validation_errors' => ['Excel parsing requires PhpSpreadsheet - will be implemented in ParseFileJob'],
            ];
        } catch (\Exception $e) {
            return [
                'estimated_rows' => 0,
                'validation_errors' => ["Excel analysis failed: {$e->getMessage()}"],
            ];
        }
    }

    /**
     * Analyze XML file structure
     */
    protected function analyzeXmlStructure(string $content): array
    {
        try {
            $xml = simplexml_load_string($content);
            if ($xml === false) {
                return [
                    'estimated_rows' => 0,
                    'validation_errors' => ['Invalid XML format'],
                ];
            }

            // Basic XML analysis
            $rootElement = $xml->getName();
            $children = $xml->children();

            return [
                'structure' => [
                    'root_element' => $rootElement,
                    'child_elements' => count($children),
                ],
                'estimated_rows' => count($children),
                'headers' => [], // Will be extracted in parsing stage
                'sample_data' => [],
            ];
        } catch (\Exception $e) {
            return [
                'estimated_rows' => 0,
                'validation_errors' => ["XML analysis failed: {$e->getMessage()}"],
            ];
        }
    }

    /**
     * Detect CSV delimiter
     */
    protected function detectCsvDelimiter(string $firstLine): string
    {
        $delimiters = [',', ';', '\t', '|'];
        $maxCount = 0;
        $bestDelimiter = ',';

        foreach ($delimiters as $delimiter) {
            $actualDelimiter = $delimiter === '\t' ? "\t" : $delimiter;
            $count = substr_count($firstLine, $actualDelimiter);
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $actualDelimiter;
            }
        }

        return $bestDelimiter;
    }

    /**
     * Detect if CSV has headers
     */
    protected function detectCsvHeaders(array $firstRow): bool
    {
        // Heuristic: if first row contains mostly strings and few numbers, likely headers
        $stringCount = 0;
        $numberCount = 0;

        foreach ($firstRow as $cell) {
            if (is_numeric($cell)) {
                $numberCount++;
            } else {
                $stringCount++;
            }
        }

        return $stringCount > $numberCount;
    }

    /**
     * Detect file encoding
     */
    protected function detectEncoding(string $content): string
    {
        $encodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'UTF-16'];

        foreach ($encodings as $encoding) {
            if (mb_check_encoding($content, $encoding)) {
                return $encoding;
            }
        }

        return 'unknown';
    }

    /**
     * Detect MIME type
     */
    protected function detectMimeType(string $content, string $extension): string
    {
        // Basic MIME type detection
        $mimeTypes = [
            'csv' => 'text/csv',
            'txt' => 'text/plain',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'xml' => 'application/xml',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Validate file structure
     */
    protected function validateFileStructure(array &$fileInfo): void
    {
        // Check for empty file
        if ($fileInfo['size'] === 0) {
            $fileInfo['validation_errors'][] = 'File is empty';

            return;
        }

        // Check file size limits (max 50MB)
        if ($fileInfo['size'] > 50 * 1024 * 1024) {
            $fileInfo['validation_errors'][] = 'File size exceeds 50MB limit';
        }

        // Check for minimum rows
        if ($fileInfo['estimated_rows'] === 0) {
            $fileInfo['validation_errors'][] = 'No data rows detected';
        }

        // Type-specific validations
        switch ($fileInfo['type']) {
            case 'csv':
                if (empty($fileInfo['headers'])) {
                    $fileInfo['validation_errors'][] = 'No CSV headers detected';
                }
                break;

            case 'unknown':
                $fileInfo['validation_errors'][] = 'Unknown or unsupported file format';
                break;
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }

    /**
     * Handle job failure
     */
    protected function handleJobFailure(\Exception $exception): void
    {
        Log::error('File type detection job failed', [
            'import_job_id' => $this->importJob->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mark import job as failed
        $this->importJob->markAsFailed(
            'File type detection failed: '.$exception->getMessage(),
            [
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]
        );

        // Log failure
        ImportLog::logJobFailed($this->importJob, $exception->getMessage(), [
            'stage' => 'file_detection',
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Handle job failure (Laravel queue method)
     */
    public function failed(\Throwable $exception): void
    {
        $this->handleJobFailure($exception);
    }
}
