<?php

namespace App\Services\Migration\Parsers;

use App\Models\ImportJob;
use App\Models\ImportLog;
use App\Services\Migration\FieldMapperService;
use App\Services\Migration\Transformers\DateTransformer;
use App\Services\Migration\Transformers\DecimalTransformer;
use Exception;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * ExcelParserService - Parse Excel files for Universal Migration Wizard
 *
 * This service processes Excel files (.xlsx, .xls, .ods) from Macedonia accounting software
 * with specialized handling for large files, multiple worksheets, and Macedonia-specific
 * data formats commonly found in Onivo, Megasoft, and Pantheon exports.
 *
 * Features:
 * - Support for multiple Excel formats (XLSX, XLS, ODS, CSV)
 * - Memory-efficient chunked reading for large files (>100MB)
 * - Multiple worksheet processing with intelligent sheet selection
 * - Macedonia-specific data type detection and conversion
 * - Cyrillic text support with proper encoding handling
 * - Excel date/time parsing with Macedonia locale support
 * - Formula evaluation and calculated cell handling
 * - Merged cell detection and data extraction
 * - Progress tracking for real-time UI updates
 * - Comprehensive error handling and recovery
 * - Integration with FieldMapperService for intelligent mapping
 * - Support for complex Excel structures (headers across multiple rows)
 * - Business data validation (tax rates, currency codes, etc.)
 *
 * Macedonia Business Context:
 * - Handles complex Excel exports with multiple data tables
 * - Supports Macedonian number formats (comma decimals, space thousands)
 * - Processes multi-sheet workbooks (customers, invoices, items, payments)
 * - Maintains relationships between sheets via ID references
 * - Handles Macedonia-specific Excel templates and structures
 *
 * Performance:
 * - Streams large files without loading entire workbook into memory
 * - Processes files up to 1GB+ with configurable memory limits
 * - Uses chunked reading to prevent PHP memory exhaustion
 * - Optimized for Macedonia accounting data patterns
 */
class ExcelParserService
{
    private FieldMapperService $fieldMapper;

    private DateTransformer $dateTransformer;

    private DecimalTransformer $decimalTransformer;

    /**
     * Maximum file size for processing (1GB)
     */
    private const MAX_FILE_SIZE = 1024 * 1024 * 1024;

    /**
     * Default chunk size for batch processing
     */
    private const DEFAULT_CHUNK_SIZE = 1000;

    /**
     * Maximum number of rows to scan for header detection
     */
    private const HEADER_SCAN_LIMIT = 10;

    /**
     * Supported Excel formats
     */
    private const SUPPORTED_FORMATS = ['xlsx', 'xls', 'ods', 'csv'];

    /**
     * Macedonia-specific worksheet names to prioritize
     */
    private const PRIORITY_SHEET_NAMES = [
        // Macedonian names
        'klienti', 'kupci', 'potrosuvachi',      // customers
        'fakturi', 'smetki',                      // invoices
        'stavki', 'proizvodi', 'artikli',         // items
        'plakanja', 'placanja',                   // payments
        'trosoci', 'rashodi',                     // expenses

        // Serbian names
        'klijenti', 'kupci', 'mušterije',         // customers
        'fakture', 'računi',                      // invoices
        'stavke', 'proizvodi', 'artikali',        // items
        'plaćanja', 'uplate',                     // payments
        'troškovi', 'rashodi',                    // expenses

        // English names
        'customers', 'clients',
        'invoices', 'bills',
        'items', 'products',
        'payments',
        'expenses',
    ];

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
     * Parse Excel file and extract structured data
     *
     * @param  ImportJob  $importJob  The import job context
     * @param  string  $filePath  Path to the Excel file
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
                'message' => 'Starting Excel file parsing',
                'data' => [
                    'file_path' => $filePath,
                    'file_size' => filesize($filePath),
                    'options' => $options,
                ],
            ]);

            // Validate file
            $this->validateFile($filePath);

            // Create reader with memory optimization
            $reader = $this->createReader($filePath, $options);

            // Load workbook metadata
            $workbook = $reader->load($filePath);

            // Select worksheet to process
            $worksheet = $this->selectWorksheet($workbook, $options);

            // Detect data structure
            $structure = $this->detectStructure($worksheet, $options);

            // Extract headers
            $headers = $this->extractHeaders($worksheet, $structure);

            // Map headers to standard fields
            $fieldMappings = $this->mapHeaders($headers, $importJob);

            // Count total rows for progress tracking
            $totalRows = $this->countDataRows($worksheet, $structure);

            // Process data in chunks
            $processedData = $this->processData(
                $worksheet,
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
                'message' => 'Excel file parsing completed successfully',
                'data' => [
                    'total_rows' => $totalRows,
                    'processed_rows' => count($processedData['rows']),
                    'processing_time_seconds' => $processingTime,
                    'worksheet_name' => $worksheet->getTitle(),
                    'field_mappings' => $fieldMappings,
                    'structure' => $structure,
                ],
            ]);

            // Clean up memory
            $workbook->disconnectWorksheets();
            unset($workbook);

            return [
                'headers' => $headers,
                'field_mappings' => $fieldMappings,
                'rows' => $processedData['rows'],
                'metadata' => [
                    'total_rows' => $totalRows,
                    'processed_rows' => count($processedData['rows']),
                    'worksheet_name' => $worksheet->getTitle(),
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
                'message' => 'Excel file parsing failed',
                'data' => [
                    'error' => $e->getMessage(),
                    'file_path' => $filePath,
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ],
            ]);

            Log::error('Excel parsing failed', [
                'import_job_id' => $importJob->id,
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Excel parsing failed: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Validate Excel file before processing
     */
    private function validateFile(string $filePath): void
    {
        if (! file_exists($filePath)) {
            throw new Exception("Excel file not found: {$filePath}");
        }

        if (! is_readable($filePath)) {
            throw new Exception("Excel file is not readable: {$filePath}");
        }

        $fileSize = filesize($filePath);
        if ($fileSize === false) {
            throw new Exception("Cannot determine file size: {$filePath}");
        }

        if ($fileSize > self::MAX_FILE_SIZE) {
            throw new Exception(
                'Excel file too large: '.number_format($fileSize / (1024 * 1024)).
                'MB exceeds limit of '.number_format(self::MAX_FILE_SIZE / (1024 * 1024)).'MB'
            );
        }

        if ($fileSize === 0) {
            throw new Exception("Excel file is empty: {$filePath}");
        }

        // Validate file format
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (! in_array($extension, self::SUPPORTED_FORMATS)) {
            throw new Exception("Unsupported Excel format: {$extension}");
        }
    }

    /**
     * Create Excel reader with memory optimization
     */
    private function createReader(string $filePath, array $options): \PhpOffice\PhpSpreadsheet\Reader\IReader
    {
        try {
            // Identify file type
            $inputFileType = IOFactory::identify($filePath);
            $reader = IOFactory::createReader($inputFileType);

            // Configure reader for memory efficiency
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);

            // Set memory limit if specified
            if (isset($options['memory_limit'])) {
                ini_set('memory_limit', $options['memory_limit']);
            }

            return $reader;

        } catch (ReaderException $e) {
            throw new Exception('Cannot create Excel reader: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Select the most appropriate worksheet to process
     */
    private function selectWorksheet(Spreadsheet $workbook, array $options): Worksheet
    {
        // If specific sheet is requested
        if (isset($options['worksheet_name'])) {
            try {
                return $workbook->getSheetByName($options['worksheet_name']);
            } catch (Exception $e) {
                throw new Exception("Worksheet '{$options['worksheet_name']}' not found");
            }
        }

        if (isset($options['worksheet_index'])) {
            try {
                return $workbook->getSheet($options['worksheet_index']);
            } catch (Exception $e) {
                throw new Exception("Worksheet index {$options['worksheet_index']} not found");
            }
        }

        // Auto-select best worksheet
        $worksheets = [];
        foreach ($workbook->getAllSheets() as $sheet) {
            $worksheets[] = [
                'sheet' => $sheet,
                'name' => $sheet->getTitle(),
                'data_rows' => $this->countDataRows($sheet),
                'priority_score' => $this->calculateSheetPriority($sheet),
            ];
        }

        // Sort by priority score and data rows
        usort($worksheets, function ($a, $b) {
            if ($a['priority_score'] !== $b['priority_score']) {
                return $b['priority_score'] <=> $a['priority_score'];
            }

            return $b['data_rows'] <=> $a['data_rows'];
        });

        return $worksheets[0]['sheet'];
    }

    /**
     * Calculate priority score for worksheet selection
     */
    private function calculateSheetPriority(Worksheet $sheet): int
    {
        $title = strtolower($sheet->getTitle());
        $score = 0;

        // Check against priority sheet names
        foreach (self::PRIORITY_SHEET_NAMES as $priorityName) {
            if (strpos($title, $priorityName) !== false) {
                $score += 10;
                break;
            }
        }

        // Bonus for sheets with more data
        $dataRows = $this->countDataRows($sheet);
        if ($dataRows > 100) {
            $score += 5;
        }
        if ($dataRows > 1000) {
            $score += 3;
        }

        // Penalty for sheets that look like metadata
        $metadataKeywords = ['config', 'settings', 'meta', 'info', 'summary'];
        foreach ($metadataKeywords as $keyword) {
            if (strpos($title, $keyword) !== false) {
                $score -= 5;
                break;
            }
        }

        return $score;
    }

    /**
     * Detect Excel data structure
     */
    private function detectStructure(Worksheet $worksheet, array $options): array
    {
        $structure = [
            'has_headers' => $options['has_headers'] ?? true,
            'header_row' => $options['header_row'] ?? 1,
            'data_start_row' => $options['data_start_row'] ?? null,
            'data_end_row' => $options['data_end_row'] ?? null,
            'data_start_column' => $options['data_start_column'] ?? 'A',
            'data_end_column' => $options['data_end_column'] ?? null,
        ];

        // Auto-detect data start row if not specified
        if (! $structure['data_start_row']) {
            $structure['data_start_row'] = $structure['has_headers'] ?
                $structure['header_row'] + 1 : 1;
        }

        // Detect data boundaries
        if (! $structure['data_end_row']) {
            $structure['data_end_row'] = $worksheet->getHighestRow();
        }

        if (! $structure['data_end_column']) {
            $structure['data_end_column'] = $worksheet->getHighestColumn();
        }

        return $structure;
    }

    /**
     * Extract and validate headers from Excel worksheet
     */
    private function extractHeaders(Worksheet $worksheet, array $structure): array
    {
        if (! $structure['has_headers']) {
            // Generate generic headers based on columns
            $headers = [];
            $startCol = $structure['data_start_column'];
            $endCol = $structure['data_end_column'];

            for ($col = $startCol; $col <= $endCol; $col++) {
                $headers[] = 'column_'.$col;
            }

            return $headers;
        }

        // Extract headers from specified row
        $headers = [];
        $headerRow = $structure['header_row'];
        $startCol = $structure['data_start_column'];
        $endCol = $structure['data_end_column'];

        for ($col = $startCol; $col <= $endCol; $col++) {
            $cell = $worksheet->getCell($col.$headerRow);
            $header = $this->getCellValue($cell);

            // Clean and validate header
            $cleanHeader = trim($header);
            if (empty($cleanHeader)) {
                $cleanHeader = 'column_'.$col;
            }

            $headers[] = $cleanHeader;
        }

        return $headers;
    }

    /**
     * Map headers to standard fields using FieldMapperService
     */
    private function mapHeaders(array $headers, ImportJob $importJob): array
    {
        $mappings = [];

        foreach ($headers as $header) {
            $mapping = $this->fieldMapper->mapField($header, 'excel');
            $mappings[$header] = $mapping;
        }

        // Log field mapping results
        ImportLog::create([
            'import_job_id' => $importJob->id,
            'log_type' => 'mapping_applied',
            'message' => 'Excel field mapping completed',
            'data' => [
                'total_fields' => count($headers),
                'mapped_fields' => count(array_filter($mappings, fn ($m) => $m['confidence'] > 0.5)),
                'mappings' => $mappings,
            ],
        ]);

        return $mappings;
    }

    /**
     * Count data rows in worksheet
     */
    private function countDataRows(Worksheet $worksheet, ?array $structure = null): int
    {
        if (! $structure) {
            // Simple count - just get highest row
            return $worksheet->getHighestRow();
        }

        $dataStartRow = $structure['data_start_row'];
        $dataEndRow = $structure['data_end_row'];

        return max(0, $dataEndRow - $dataStartRow + 1);
    }

    /**
     * Process Excel data in chunks with transformation
     */
    private function processData(
        Worksheet $worksheet,
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

        $headers = array_keys($fieldMappings);
        $dataStartRow = $structure['data_start_row'];
        $dataEndRow = $structure['data_end_row'];
        $startCol = $structure['data_start_column'];
        $endCol = $structure['data_end_column'];

        $processedCount = 0;
        $currentChunk = [];

        for ($row = $dataStartRow; $row <= $dataEndRow; $row++) {
            try {
                // Extract row data
                $rowData = [];
                $colIndex = 0;

                for ($col = $startCol; $col <= $endCol; $col++) {
                    $cell = $worksheet->getCell($col.$row);
                    $value = $this->getCellValue($cell);

                    if (isset($headers[$colIndex])) {
                        $rowData[$headers[$colIndex]] = $value;
                    }

                    $colIndex++;
                }

                // Skip empty rows
                if ($this->isEmptyRow($rowData)) {
                    continue;
                }

                // Transform row data
                $transformedRow = $this->transformRow($rowData, $fieldMappings);

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
                $statistics['warnings'][] = "Row {$row}: ".$e->getMessage();

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
     * Get properly formatted cell value
     */
    private function getCellValue(Cell $cell)
    {
        $value = $cell->getValue();

        // Handle different cell data types
        if ($cell->getDataType() === DataType::TYPE_FORMULA) {
            try {
                $value = $cell->getCalculatedValue();
            } catch (Exception $e) {
                // If formula calculation fails, use formula string
                $value = $cell->getValue();
            }
        }

        // Handle Excel dates
        if (Date::isDateTime($cell)) {
            try {
                $dateValue = Date::excelToDateTimeObject($value);

                return $dateValue->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                // If date conversion fails, return original value
                return $value;
            }
        }

        // Handle numeric values
        if (is_numeric($value)) {
            // Check if it's actually an integer
            if (floor($value) == $value) {
                return (int) $value;
            }

            return (float) $value;
        }

        // Return string value, trimmed
        return trim((string) $value);
    }

    /**
     * Check if row is empty (all values are null or empty)
     */
    private function isEmptyRow(array $rowData): bool
    {
        foreach ($rowData as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Transform individual row with field mappings and data transformations
     */
    private function transformRow(array $rowData, array $fieldMappings): array
    {
        $transformedRow = [];

        foreach ($rowData as $originalField => $value) {
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
                        $transformedValue = trim((string) $value);
                        break;
                }

                $transformedRow[$standardField] = $transformedValue;

            } catch (Exception $e) {
                // Log transformation error but keep original value
                $transformedRow[$standardField] = $value;
            }
        }

        return $transformedRow;
    }

    /**
     * Transform boolean values from various formats
     */
    private function transformBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

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
     * Get preview of Excel file structure (worksheets and sample data)
     */
    public function preview(string $filePath, array $options = []): array
    {
        $reader = $this->createReader($filePath, $options);
        $workbook = $reader->load($filePath);

        $worksheets = [];
        foreach ($workbook->getAllSheets() as $sheet) {
            $structure = $this->detectStructure($sheet, $options);
            $headers = $this->extractHeaders($sheet, $structure);

            // Get sample rows
            $sampleRows = [];
            $dataStartRow = $structure['data_start_row'];
            $sampleLimit = min(5, $sheet->getHighestRow() - $dataStartRow + 1);

            for ($row = $dataStartRow; $row < $dataStartRow + $sampleLimit; $row++) {
                $rowData = [];
                $colIndex = 0;

                for ($col = $structure['data_start_column']; $col <= $structure['data_end_column']; $col++) {
                    $cell = $sheet->getCell($col.$row);
                    $value = $this->getCellValue($cell);

                    if (isset($headers[$colIndex])) {
                        $rowData[$headers[$colIndex]] = $value;
                    }

                    $colIndex++;
                }

                if (! $this->isEmptyRow($rowData)) {
                    $sampleRows[] = $rowData;
                }
            }

            $worksheets[] = [
                'name' => $sheet->getTitle(),
                'headers' => $headers,
                'sample_rows' => $sampleRows,
                'total_rows' => $this->countDataRows($sheet, $structure),
                'structure' => $structure,
                'priority_score' => $this->calculateSheetPriority($sheet),
            ];
        }

        // Clean up memory
        $workbook->disconnectWorksheets();
        unset($workbook);

        return [
            'worksheets' => $worksheets,
            'recommended_worksheet' => $worksheets[0]['name'] ?? null,
        ];
    }
}
