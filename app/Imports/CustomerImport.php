<?php

namespace App\Imports;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Customer Import Class
 *
 * Handles importing customers from CSV/XLSX files with support for:
 * - Multiple column name mappings (Onivo, Megasoft presets)
 * - Chunk reading for large files (500 rows per chunk)
 * - Validation with error collection
 * - Encoding detection (Windows-1251, UTF-8)
 */
class CustomerImport implements SkipsOnFailure, ToModel, WithChunkReading, WithHeadingRow, WithValidation
{
    use Importable;

    private int $companyId;

    private int $currencyId;

    private int $creatorId;

    private array $columnMapping;

    private bool $isDryRun;

    private array $failures = [];

    private int $successCount = 0;

    private int $failureCount = 0;

    /**
     * Constructor
     *
     * @param  array  $columnMapping  Map of our field names to CSV column names
     * @param  bool  $isDryRun  If true, validation only without creating records
     */
    public function __construct(
        int $companyId,
        int $currencyId,
        int $creatorId,
        array $columnMapping = [],
        bool $isDryRun = false
    ) {
        $this->companyId = $companyId;
        $this->currencyId = $currencyId;
        $this->creatorId = $creatorId;
        $this->columnMapping = $columnMapping;
        $this->isDryRun = $isDryRun;
    }

    /**
     * Convert row to Customer model
     *
     * @return Customer|null
     */
    public function model(array $row)
    {
        // Skip if dry run
        if ($this->isDryRun) {
            $this->successCount++;

            return null;
        }

        // Map columns based on preset
        $mappedRow = $this->mapColumns($row);

        $customer = new Customer([
            'company_id' => $this->companyId,
            'currency_id' => $this->currencyId,
            'creator_id' => $this->creatorId,
            'name' => $mappedRow['name'] ?? null,
            'email' => $mappedRow['email'] ?? null,
            'phone' => $mappedRow['phone'] ?? null,
            'contact_name' => $mappedRow['contact_name'] ?? null,
            'website' => $mappedRow['website'] ?? null,
            'vat_number' => $mappedRow['vat_number'] ?? null,
            'prefix' => $mappedRow['prefix'] ?? null,
            'enable_portal' => $mappedRow['enable_portal'] ?? false,
        ]);

        $this->successCount++;

        return $customer;
    }

    /**
     * Map CSV columns to our field names using column mapping
     */
    private function mapColumns(array $row): array
    {
        if (empty($this->columnMapping)) {
            return $row;
        }

        $mapped = [];
        foreach ($this->columnMapping as $ourField => $csvColumn) {
            // Normalize column name (lowercase, trim)
            $normalizedColumn = strtolower(trim($csvColumn));

            // Try to find the value in the row
            $value = null;
            foreach ($row as $key => $val) {
                if (strtolower(trim($key)) === $normalizedColumn) {
                    $value = $val;
                    break;
                }
            }

            $mapped[$ourField] = $value;
        }

        return $mapped;
    }

    /**
     * Define validation rules
     */
    public function rules(): array
    {
        $nameField = $this->getColumnName('name');

        return [
            $nameField => 'required|string|max:255',
            $this->getColumnName('email') => 'nullable|email|max:255',
            $this->getColumnName('phone') => 'nullable|string|max:50',
            $this->getColumnName('contact_name') => 'nullable|string|max:255',
            $this->getColumnName('website') => 'nullable|url|max:255',
            $this->getColumnName('vat_number') => 'nullable|string|max:50',
        ];
    }

    /**
     * Get CSV column name for our field
     */
    private function getColumnName(string $ourField): string
    {
        if (empty($this->columnMapping)) {
            return $ourField;
        }

        return $this->columnMapping[$ourField] ?? $ourField;
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages(): array
    {
        return [
            'name.required' => 'Customer name is required',
            'email.email' => 'Email must be a valid email address',
            'website.url' => 'Website must be a valid URL',
        ];
    }

    /**
     * Chunk size for reading
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Handle validation failures
     *
     * @param  Failure[]  $failures
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failures[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
            $this->failureCount++;
        }
    }

    /**
     * Get validation failures
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    /**
     * Get success count
     */
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    /**
     * Get failure count
     */
    public function getFailureCount(): int
    {
        return $this->failureCount;
    }
}

// CLAUDE-CHECKPOINT
