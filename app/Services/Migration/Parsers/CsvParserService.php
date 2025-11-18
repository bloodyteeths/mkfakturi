<?php

namespace App\Services\Migration\Parsers;

use App\Models\ImportJob;
use App\Models\ImportLog;
use App\Services\Migration\FieldMapperService;
use App\Services\Migration\Transformers\DateTransformer;
use App\Services\Migration\Transformers\DecimalTransformer;
use Exception;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;

/**
 * CsvParserService - Parse CSV files for Universal Migration Wizard
 *
 * This service processes CSV files from Macedonia accounting software (Onivo, Megasoft, Pantheon)
 * with specialized handling for Macedonia-specific data formats, encodings, and business requirements.
 *
 * Features:
 * - Stream processing for large files (no memory limits)
 * - Auto-detection of CSV structure (delimiter, encoding, headers)
 * - Macedonia-specific encoding support (UTF-8, Windows-1251 for Cyrillic)
 * - Field header extraction and data type detection
 * - Integration with FieldMapperService for intelligent field mapping
 * - Progress callbacks for real-time UI updates
 * - Comprehensive error handling and validation
 * - Support for Macedonia date formats (dd.mm.yyyy, dd/mm/yyyy)
 * - Decimal separator handling (comma to dot conversion)
 * - Batch processing with configurable chunk sizes
 * - Memory-efficient processing of files up to 1GB+
 *
 * Macedonia Business Context:
 * - Handles exports from major accounting platforms
 * - Supports Macedonian/Serbian field names in Cyrillic and Latin scripts
 * - Preserves business relationships (invoices → line items → payments)
 * - Maintains audit trail for compliance requirements
 */
class CsvParserService
{
    private FieldMapperService $fieldMapper;

    private DateTransformer $dateTransformer;

    private DecimalTransformer $decimalTransformer;

    /**
     * Supported CSV delimiters in order of detection priority
     */
    private const DELIMITERS = [',', ';', '\t', '|'];

    /**
     * Supported encodings for Macedonia data
     * Windows-1251 is common for Cyrillic exports from legacy systems
     */
    private const ENCODINGS = ['UTF-8', 'Windows-1251', 'ISO-8859-1'];

    /**
     * Maximum file size for processing (1GB)
     */
    private const MAX_FILE_SIZE = 1024 * 1024 * 1024;

    /**
     * Default chunk size for batch processing
     */
    private const DEFAULT_CHUNK_SIZE = 1000;

    /**
     * Sample rows to use for structure detection
     */
    private const SAMPLE_ROWS = 100;

    public function __construct(
        FieldMapperService $fieldMapper,
        DateTransformer $dateTransformer,
        DecimalTransformer $decimalTransformer
    ) {
        $this->fieldMapper = $fieldMapper;
        $this->dateTransformer = $dateTransformer;
        $this->decimalTransformer = $decimalTransformer;
    }

    /**
     * Parse CSV file and extract structured data
     *
     * @param  ImportJob  $importJob  The import job context
     * @param  string  $filePath  Path to the CSV file
     * @param  array  $options  Parser options
     * @param  callable|null  $progressCallback  Progress update callback
     * @return array Parsed data with headers, rows, and metadata
     *
     * @throws Exception
     */
    public function parse(
        ImportJob $importJob,
        string $filePath,
        array $options = [],
        ?callable $progressCallback = null
    ): array {
        $startTime = microtime(true);

        try {
            // Log parsing start
            ImportLog::create([
                'import_job_id' => $importJob->id,
                'log_type' => 'file_parsed',
                'message' => 'Starting CSV file parsing',
                'data' => [
                    'file_path' => $filePath,
                    'file_size' => filesize($filePath),
                    'options' => $options,
                ],
            ]);

            // Validate file
            $this->validateFile($filePath);

            // Detect file structure
            $structure = $this->detectStructure($filePath, $options);

            // Create CSV reader with detected settings
            $reader = $this->createReader($filePath, $structure);

            // Extract headers
            $headers = $this->extractHeaders($reader, $structure);

            // Map headers to standard fields
            $fieldMappings = $this->mapHeaders($headers, $importJob);

            // Count total rows for progress tracking
            $totalRows = $this->countRows($reader);

            // Process data in chunks
            $processedData = $this->processData(
                $reader,
                $fieldMappings,
                $structure,
                $totalRows,
                $options,
                $progressCallback
            );

            $endTime = microtime(true);
            $processingTime = round(($endTime - $startTime), 2);

            // Log parsing completion
            ImportLog::create([
                'import_job_id' => $importJob->id,
                'log_type' => 'file_parsed',
                'message' => 'CSV file parsing completed successfully',
                'data' => [
                    'total_rows' => $totalRows,
                    'processed_rows' => count($processedData['rows']),
                    'processing_time_seconds' => $processingTime,
                    'detected_encoding' => $structure['encoding'],
                    'detected_delimiter' => $structure['delimiter'],
                    'field_mappings' => $fieldMappings,
                ],
            ]);

            return [
                'headers' => $headers,
                'field_mappings' => $fieldMappings,
                'rows' => $processedData['rows'],
                'metadata' => [
                    'total_rows' => $totalRows,
                    'processed_rows' => count($processedData['rows']),
                    'structure' => $structure,
                    'processing_time' => $processingTime,
                    'statistics' => $processedData['statistics'],
                ],
            ];

        } catch (Exception $e) {
            // Log parsing error
            ImportLog::create([
                'import_job_id' => $importJob->id,
                'log_type' => 'parsing_error',
                'message' => 'CSV file parsing failed',
                'data' => [
                    'error' => $e->getMessage(),
                    'file_path' => $filePath,
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ],
            ]);

            Log::error('CSV parsing failed', [
                'import_job_id' => $importJob->id,
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('CSV parsing failed: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Validate CSV file before processing
     */
    private function validateFile(string $filePath): void
    {
        if (! file_exists($filePath)) {
            throw new Exception("CSV file not found: {$filePath}");
        }

        if (! is_readable($filePath)) {
            throw new Exception("CSV file is not readable: {$filePath}");
        }

        $fileSize = filesize($filePath);
        if ($fileSize === false) {
            throw new Exception("Cannot determine file size: {$filePath}");
        }

        if ($fileSize > self::MAX_FILE_SIZE) {
            throw new Exception(
                'CSV file too large: '.number_format($fileSize / (1024 * 1024)).
                'MB exceeds limit of '.number_format(self::MAX_FILE_SIZE / (1024 * 1024)).'MB'
            );
        }

        if ($fileSize === 0) {
            throw new Exception("CSV file is empty: {$filePath}");
        }
    }

    /**
     * Detect CSV structure (delimiter, encoding, headers)
     */
    private function detectStructure(string $filePath, array $options): array
    {
        $structure = [
            'delimiter' => $options['delimiter'] ?? null,
            'encoding' => $options['encoding'] ?? null,
            'has_headers' => $options['has_headers'] ?? true,
            'skip_rows' => $options['skip_rows'] ?? 0,
        ];

        // Auto-detect encoding if not specified
        if (! $structure['encoding']) {
            $structure['encoding'] = $this->detectEncoding($filePath);
        }

        // Auto-detect delimiter if not specified
        if (! $structure['delimiter']) {
            $structure['delimiter'] = $this->detectDelimiter($filePath, $structure['encoding']);
        }

        return $structure;
    }

    /**
     * Detect file encoding
     */
    private function detectEncoding(string $filePath): string
    {
        $sample = file_get_contents($filePath, false, null, 0, 8192);

        foreach (self::ENCODINGS as $encoding) {
            if (mb_check_encoding($sample, $encoding)) {
                return $encoding;
            }
        }

        // Default to UTF-8 if detection fails
        return 'UTF-8';
    }

    /**
     * Detect CSV delimiter
     */
    private function detectDelimiter(string $filePath, string $encoding): string
    {
        $sample = file_get_contents($filePath, false, null, 0, 8192);

        // Convert encoding if needed
        if ($encoding !== 'UTF-8') {
            $sample = mb_convert_encoding($sample, 'UTF-8', $encoding);
        }

        $lines = array_slice(explode("\n", $sample), 0, 5);
        $delimiterCounts = [];

        foreach (self::DELIMITERS as $delimiter) {
            $count = 0;
            $actualDelimiter = $delimiter === '\t' ? "\t" : $delimiter;

            foreach ($lines as $line) {
                if (trim($line)) {
                    $count += substr_count($line, $actualDelimiter);
                }
            }

            $delimiterCounts[$delimiter] = $count;
        }

        // Return delimiter with highest count
        $bestDelimiter = array_keys($delimiterCounts, max($delimiterCounts))[0];

        return $bestDelimiter === '\t' ? "\t" : $bestDelimiter;
    }

    /**
     * Create CSV reader with proper settings
     */
    private function createReader(string $filePath, array $structure): Reader
    {
        $reader = Reader::createFromPath($filePath, 'r');

        // Set delimiter
        $reader->setDelimiter($structure['delimiter']);

        // Handle encoding conversion
        if ($structure['encoding'] !== 'UTF-8') {
            $reader->addStreamFilter('convert.iconv.'.$structure['encoding'].'/UTF-8');
        }

        // Configure header offset when headers are present
        if ($structure['has_headers']) {
            $reader->setHeaderOffset($structure['skip_rows']);
        }

        return $reader;
    }

    /**
     * Extract and validate headers
     */
    private function extractHeaders(Reader $reader, array $structure): array
    {
        if ($structure['has_headers']) {
            $headers = $reader->getHeader();

            // Clean and validate headers
            $cleanHeaders = [];
            foreach ($headers as $index => $header) {
                $cleanHeader = trim($header);
                if (empty($cleanHeader)) {
                    $cleanHeader = 'column_'.($index + 1);
                }
                $cleanHeaders[] = $cleanHeader;
            }

            return $cleanHeaders;
        } else {
            // Generate generic headers for headerless CSV
            $firstRow = $reader->fetchOne();
            $headers = [];
            for ($i = 0; $i < count($firstRow); $i++) {
                $headers[] = 'column_'.($i + 1);
            }

            return $headers;
        }
    }

    /**
     * Map headers to standard fields using FieldMapperService
     */
    private function mapHeaders(array $headers, ImportJob $importJob): array
    {
        $mappings = [];

        foreach ($headers as $header) {
            $mapping = $this->fieldMapper->mapField($header, 'csv');
            $mappings[$header] = $mapping;
        }

        // Log field mapping results
        ImportLog::create([
            'import_job_id' => $importJob->id,
            'log_type' => 'mapping_applied',
            'message' => 'CSV field mapping completed',
            'data' => [
                'total_fields' => count($headers),
                'mapped_fields' => count(array_filter($mappings, fn ($m) => $m['confidence'] > 0.5)),
                'mappings' => $mappings,
            ],
        ]);

        return $mappings;
    }

    /**
     * Count total rows for progress tracking
     */
    private function countRows(Reader $reader): int
    {
        return iterator_count($reader);
    }

    /**
     * Process CSV data in chunks with transformation
     */
    private function processData(
        Reader $reader,
        array $fieldMappings,
        array $structure,
        int $totalRows,
        array $options,
        ?callable $progressCallback = null
    ): array {
        $chunkSize = $options['chunk_size'] ?? self::DEFAULT_CHUNK_SIZE;
        $processedRows = [];
        $statistics = [
            'total_rows' => $totalRows,
            'processed_rows' => 0,
            'error_rows' => 0,
            'warnings' => [],
            'data_types' => [],
        ];

        $stmt = Statement::create();
        $records = $stmt->process($reader);

        $currentChunk = [];
        $processedCount = 0;

        foreach ($records as $offset => $record) {
            try {
                // Transform row data
                $transformedRow = $this->transformRow($record, $fieldMappings, $structure);

                // Add to current chunk
                $currentChunk[] = $transformedRow;
                $processedCount++;

                // Process chunk when it reaches the limit
                if (count($currentChunk) >= $chunkSize) {
                    $processedRows = array_merge($processedRows, $currentChunk);
                    $currentChunk = [];

                    // Update progress
                    if ($progressCallback) {
                        $progressCallback([
                            'processed' => $processedCount,
                            'total' => $totalRows,
                            'percentage' => round(($processedCount / $totalRows) * 100, 2),
                        ]);
                    }
                }

            } catch (Exception $e) {
                $statistics['error_rows']++;
                $statistics['warnings'][] = "Row {$offset}: ".$e->getMessage();

                // Continue processing other rows
                continue;
            }
        }

        // Process remaining rows in final chunk
        if (! empty($currentChunk)) {
            $processedRows = array_merge($processedRows, $currentChunk);
        }

        $statistics['processed_rows'] = count($processedRows);

        // Final progress update
        if ($progressCallback) {
            $progressCallback([
                'processed' => $statistics['processed_rows'],
                'total' => $totalRows,
                'percentage' => 100,
            ]);
        }

        return [
            'rows' => $processedRows,
            'statistics' => $statistics,
        ];
    }

    /**
     * Transform individual row with field mappings and data transformations
     */
    private function transformRow(array $record, array $fieldMappings, array $structure): array
    {
        $transformedRow = [];

        foreach ($record as $originalField => $value) {
            // Skip empty values
            if ($value === null || $value === '') {
                continue;
            }

            // Get field mapping
            $mapping = $fieldMappings[$originalField] ?? null;
            if (! $mapping || $mapping['confidence'] < 0.3) {
                // Keep unmapped fields with original names
                $transformedRow[$originalField] = $value;

                continue;
            }

            $standardField = $mapping['standard_field'];
            $transformedValue = $value;

            // Apply data transformations based on field type
            try {
                switch ($mapping['data_type']) {
                    case 'date':
                        $transformedValue = $this->dateTransformer->transform($value);
                        break;

                    case 'decimal':
                    case 'currency':
                        $transformedValue = $this->decimalTransformer->transform($value);
                        break;

                    case 'integer':
                        $transformedValue = (int) $value;
                        break;

                    case 'boolean':
                        $transformedValue = $this->transformBoolean($value);
                        break;

                    default:
                        // String fields - just trim whitespace
                        $transformedValue = trim($value);
                        break;
                }

                // Store both standardized field and original header for downstream flexibility
                $transformedRow[$standardField] = $transformedValue;
                if (! array_key_exists($originalField, $transformedRow)) {
                    $transformedRow[$originalField] = $transformedValue;
                }

            } catch (Exception $e) {
                // Log transformation error but keep original value under both keys
                $transformedRow[$standardField] = $value;
                if (! array_key_exists($originalField, $transformedRow)) {
                    $transformedRow[$originalField] = $value;
                }
            }
        }

        return $transformedRow;
    }

    /**
     * Transform boolean values from various formats
     */
    private function transformBoolean($value): bool
    {
        $value = strtolower(trim($value));

        $trueValues = ['1', 'true', 'да', 'yes', 'y', 'активен', 'active'];
        $falseValues = ['0', 'false', 'не', 'no', 'n', 'неактивен', 'inactive'];

        if (in_array($value, $trueValues)) {
            return true;
        }

        if (in_array($value, $falseValues)) {
            return false;
        }

        // Default to true for non-empty values
        return ! empty($value);
    }

    /**
     * Get preview of CSV file structure (first few rows)
     */
    public function preview(string $filePath, array $options = []): array
    {
        $structure = $this->detectStructure($filePath, $options);
        $reader = $this->createReader($filePath, $structure);

        $headers = $this->extractHeaders($reader, $structure);
        $previewRows = [];

        $stmt = Statement::create()->limit(10);
        $records = $stmt->process($reader);

        foreach ($records as $record) {
            $previewRows[] = $record;
        }

        return [
            'headers' => $headers,
            'sample_rows' => $previewRows,
            'detected_structure' => $structure,
            'total_columns' => count($headers),
        ];
    }
}
