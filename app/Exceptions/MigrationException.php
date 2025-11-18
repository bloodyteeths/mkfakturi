<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class MigrationException extends Exception
{
    /**
     * The migration step where the error occurred
     */
    protected string $step;

    /**
     * The import job ID associated with this error
     */
    protected ?string $importJobId;

    /**
     * Recovery suggestions for the user
     */
    protected array $recoverySuggestions = [];

    /**
     * Whether this is a critical error that prevents continuation
     */
    protected bool $critical = false;

    /**
     * Error code for API responses
     */
    protected string $errorCode;

    /**
     * HTTP status code
     */
    protected int $statusCode = 422;

    /**
     * Create a new migration exception instance.
     */
    public function __construct(
        string $message,
        string $step,
        ?string $importJobId = null,
        array $recoverySuggestions = [],
        bool $critical = false,
        string $errorCode = 'MIGRATION_ERROR',
        int $statusCode = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->step = $step;
        $this->importJobId = $importJobId;
        $this->recoverySuggestions = $recoverySuggestions;
        $this->critical = $critical;
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
    }

    /**
     * Get the migration step where the error occurred
     */
    public function getStep(): string
    {
        return $this->step;
    }

    /**
     * Get the import job ID
     */
    public function getImportJobId(): ?string
    {
        return $this->importJobId;
    }

    /**
     * Get recovery suggestions
     */
    public function getRecoverySuggestions(): array
    {
        return $this->recoverySuggestions;
    }

    /**
     * Check if this is a critical error
     */
    public function isCritical(): bool
    {
        return $this->critical;
    }

    /**
     * Get the error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Create a file upload error
     */
    public static function fileUploadError(string $reason, ?string $importJobId = null): self
    {
        return new self(
            message: "File upload failed: {$reason}",
            step: 'upload',
            importJobId: $importJobId,
            recoverySuggestions: [
                'Check that the file is not corrupted',
                'Ensure the file size is under the maximum limit',
                'Verify the file format is supported (CSV, Excel, XML)',
                'Try uploading the file again',
            ],
            errorCode: 'FILE_UPLOAD_ERROR'
        );
    }

    /**
     * Create a file parsing error
     */
    public static function fileParsingError(string $reason, string $importJobId, array $additionalSuggestions = []): self
    {
        $suggestions = array_merge([
            'Check that the file format matches the detected type',
            'Ensure the file is not password protected',
            'Verify the file encoding is UTF-8 or compatible',
            'Try exporting the data in a different format',
        ], $additionalSuggestions);

        return new self(
            message: "File parsing failed: {$reason}",
            step: 'parsing',
            importJobId: $importJobId,
            recoverySuggestions: $suggestions,
            errorCode: 'FILE_PARSING_ERROR'
        );
    }

    /**
     * Create a field mapping error
     */
    public static function fieldMappingError(string $reason, string $importJobId, array $unmappedFields = []): self
    {
        $suggestions = [
            'Review the field mapping suggestions',
            'Manually map any unrecognized fields',
            'Check if field names in your file match expected formats',
        ];

        if (! empty($unmappedFields)) {
            $fieldList = implode(', ', array_slice($unmappedFields, 0, 5));
            $suggestions[] = "Unmapped fields: {$fieldList}".(count($unmappedFields) > 5 ? ' and '.(count($unmappedFields) - 5).' more' : '');
        }

        return new self(
            message: "Field mapping failed: {$reason}",
            step: 'mapping',
            importJobId: $importJobId,
            recoverySuggestions: $suggestions,
            errorCode: 'FIELD_MAPPING_ERROR'
        );
    }

    /**
     * Create a data validation error
     */
    public static function dataValidationError(string $reason, string $importJobId, array $validationErrors = []): self
    {
        $suggestions = [
            'Review the validation errors and fix data issues',
            'Ensure required fields are not empty',
            'Check date formats match expected patterns',
            'Verify numeric values are properly formatted',
        ];

        if (! empty($validationErrors)) {
            $suggestions[] = 'First few errors: '.implode('; ', array_slice($validationErrors, 0, 3));
        }

        return new self(
            message: "Data validation failed: {$reason}",
            step: 'validation',
            importJobId: $importJobId,
            recoverySuggestions: $suggestions,
            errorCode: 'DATA_VALIDATION_ERROR'
        );
    }

    /**
     * Create a data transformation error
     */
    public static function dataTransformationError(string $reason, string $importJobId, ?string $fieldName = null): self
    {
        $suggestions = [
            'Check the data format in the source file',
            'Ensure data types match expected formats',
            'Verify that special characters are properly encoded',
        ];

        if ($fieldName) {
            $suggestions[] = "Problem with field: {$fieldName}";
        }

        return new self(
            message: "Data transformation failed: {$reason}",
            step: 'transformation',
            importJobId: $importJobId,
            recoverySuggestions: $suggestions,
            errorCode: 'DATA_TRANSFORMATION_ERROR'
        );
    }

    /**
     * Create a database commit error
     */
    public static function commitError(string $reason, string $importJobId, bool $rollbackSuccessful = true): self
    {
        $suggestions = [
            'Try the import process again',
            'Check for duplicate data that might cause conflicts',
            'Ensure database connection is stable',
        ];

        if (! $rollbackSuccessful) {
            $suggestions[] = 'CRITICAL: Data rollback failed - contact support immediately';
        } else {
            $suggestions[] = 'No data was imported due to the error';
        }

        return new self(
            message: "Data commit failed: {$reason}",
            step: 'commit',
            importJobId: $importJobId,
            recoverySuggestions: $suggestions,
            critical: ! $rollbackSuccessful,
            errorCode: 'COMMIT_ERROR',
            statusCode: $rollbackSuccessful ? 422 : 500
        );
    }

    /**
     * Create a memory limit error
     */
    public static function memoryLimitError(string $importJobId, ?int $fileSize = null): self
    {
        $suggestions = [
            'Try importing a smaller file',
            'Split your data into multiple smaller files',
            'Remove any unnecessary columns from your file',
            'Contact support for large file import assistance',
        ];

        if ($fileSize) {
            $suggestions[] = 'File size: '.number_format($fileSize / 1024 / 1024, 2).' MB';
        }

        return new self(
            message: 'Import failed due to memory limitations',
            step: 'processing',
            importJobId: $importJobId,
            recoverySuggestions: $suggestions,
            errorCode: 'MEMORY_LIMIT_ERROR',
            statusCode: 413
        );
    }

    /**
     * Create a timeout error
     */
    public static function timeoutError(string $importJobId, string $step): self
    {
        return new self(
            message: "Import process timed out during {$step}",
            step: $step,
            importJobId: $importJobId,
            recoverySuggestions: [
                'Try importing a smaller file',
                'Split your data into multiple smaller files',
                'Retry the import process',
                'Contact support if the problem persists',
            ],
            errorCode: 'TIMEOUT_ERROR',
            statusCode: 408
        );
    }

    /**
     * Create an unsupported file format error
     */
    public static function unsupportedFormatError(string $detectedFormat, ?string $importJobId = null): self
    {
        return new self(
            message: "Unsupported file format: {$detectedFormat}",
            step: 'upload',
            importJobId: $importJobId,
            recoverySuggestions: [
                'Supported formats: CSV, Excel (.xlsx, .xls), XML',
                'Convert your file to one of the supported formats',
                'Check that the file extension matches the actual format',
                'Ensure the file is not corrupted',
            ],
            errorCode: 'UNSUPPORTED_FORMAT_ERROR',
            statusCode: 415
        );
    }

    /**
     * Create a competitor integration error
     */
    public static function competitorIntegrationError(string $competitor, string $reason, ?string $importJobId = null): self
    {
        return new self(
            message: "Failed to fetch data from {$competitor}: {$reason}",
            step: 'competitor_fetch',
            importJobId: $importJobId,
            recoverySuggestions: [
                'Check your login credentials for the competitor system',
                'Ensure the competitor system is accessible',
                'Try exporting data manually and uploading the file',
                'Contact support for assistance with competitor integration',
            ],
            errorCode: 'COMPETITOR_INTEGRATION_ERROR'
        );
    }

    /**
     * Create a quota exceeded error
     */
    public static function quotaExceededError(string $quotaType, int $limit, int $current, ?string $importJobId = null): self
    {
        return new self(
            message: "Import quota exceeded: {$quotaType} limit is {$limit}, trying to import {$current}",
            step: 'validation',
            importJobId: $importJobId,
            recoverySuggestions: [
                "Current {$quotaType} limit: {$limit}",
                "Trying to import: {$current}",
                'Consider upgrading your plan for higher limits',
                'Remove some existing data before importing',
                'Split the import into multiple smaller batches',
            ],
            errorCode: 'QUOTA_EXCEEDED_ERROR',
            statusCode: 429
        );
    }
}
