<?php

namespace App\Imports;

use App\Models\Item;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Item Import Class
 *
 * Handles importing items/products from CSV/XLSX files with support for:
 * - Multiple column name mappings (Onivo, Megasoft presets)
 * - Chunk reading for large files (500 rows per chunk)
 * - Validation with error collection
 * - Unit lookup by name
 */
class ItemImport implements SkipsOnFailure, ToModel, WithChunkReading, WithHeadingRow, WithValidation
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

    private array $unitCache = [];

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
     * Convert row to Item model
     *
     * @return Item|null
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

        // Find unit
        $unitId = null;
        if (! empty($mappedRow['unit_name'])) {
            $unit = $this->findUnit($mappedRow['unit_name']);
            $unitId = $unit ? $unit->id : null;
        }

        $item = new Item([
            'company_id' => $this->companyId,
            'currency_id' => $this->currencyId,
            'creator_id' => $this->creatorId,
            'name' => $mappedRow['name'] ?? null,
            'description' => $mappedRow['description'] ?? null,
            'price' => $this->parseAmount($mappedRow['price'] ?? 0),
            'unit_id' => $unitId,
        ]);

        $this->successCount++;

        return $item;
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
     * Find unit by name
     */
    private function findUnit(string $name): ?Unit
    {
        if (isset($this->unitCache[$name])) {
            return $this->unitCache[$name];
        }

        $unit = Unit::where('company_id', $this->companyId)
            ->where('name', $name)
            ->first();

        if ($unit) {
            $this->unitCache[$name] = $unit;
        }

        return $unit;
    }

    /**
     * Parse amount (handle different formats: 1,000.00, 1.000,00, etc.)
     *
     * @param  mixed  $value
     * @return int Amount in cents
     */
    private function parseAmount($value): int
    {
        if (! $value) {
            return 0;
        }

        // Remove spaces
        $value = str_replace(' ', '', $value);

        // Detect format and normalize
        if (preg_match('/\d+,\d{2}$/', $value)) {
            // European format: 1.000,00
            $value = str_replace(['.', ','], ['', '.'], $value);
        } else {
            // US format: 1,000.00
            $value = str_replace(',', '', $value);
        }

        return (int) round((float) $value * 100);
    }

    /**
     * Define validation rules
     */
    public function rules(): array
    {
        return [
            $this->getColumnName('name') => 'required|string|max:255',
            $this->getColumnName('price') => 'required',
            $this->getColumnName('description') => 'nullable|string',
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
            'name.required' => 'Item name is required',
            'price.required' => 'Item price is required',
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
