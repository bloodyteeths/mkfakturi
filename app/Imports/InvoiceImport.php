<?php

namespace App\Imports;

use App\Models\Invoice;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Invoice Import Class
 *
 * Handles importing invoices from CSV/XLSX files with support for:
 * - Multiple column name mappings (Onivo, Megasoft presets)
 * - Chunk reading for large files (500 rows per chunk)
 * - Validation with error collection
 * - Customer lookup by name or email
 * - Date parsing
 *
 * @package App\Imports
 */
class InvoiceImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    WithChunkReading,
    SkipsOnFailure
{
    use Importable;

    private int $companyId;
    private int $creatorId;
    private array $columnMapping;
    private bool $isDryRun;
    private array $failures = [];
    private int $successCount = 0;
    private int $failureCount = 0;
    private array $customerCache = [];

    /**
     * Constructor
     *
     * @param int $companyId
     * @param int $creatorId
     * @param array $columnMapping Map of our field names to CSV column names
     * @param bool $isDryRun If true, validation only without creating records
     */
    public function __construct(
        int $companyId,
        int $creatorId,
        array $columnMapping = [],
        bool $isDryRun = false
    ) {
        $this->companyId = $companyId;
        $this->creatorId = $creatorId;
        $this->columnMapping = $columnMapping;
        $this->isDryRun = $isDryRun;
    }

    /**
     * Convert row to Invoice model
     *
     * @param array $row
     * @return Invoice|null
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

        // Find or get customer
        $customer = $this->findCustomer($mappedRow['customer_name'] ?? null, $mappedRow['customer_email'] ?? null);

        if (!$customer) {
            $this->failures[] = [
                'row' => null,
                'attribute' => 'customer',
                'errors' => ['Customer not found'],
                'values' => $mappedRow,
            ];
            $this->failureCount++;
            return null;
        }

        $invoice = new Invoice([
            'company_id' => $this->companyId,
            'creator_id' => $this->creatorId,
            'customer_id' => $customer->id,
            'invoice_number' => $mappedRow['invoice_number'] ?? null,
            'invoice_date' => $this->parseDate($mappedRow['invoice_date'] ?? null),
            'due_date' => $this->parseDate($mappedRow['due_date'] ?? null),
            'sub_total' => $this->parseAmount($mappedRow['sub_total'] ?? 0),
            'tax' => $this->parseAmount($mappedRow['tax'] ?? 0),
            'total' => $this->parseAmount($mappedRow['total'] ?? 0),
            'discount' => $mappedRow['discount'] ?? 0,
            'discount_type' => $mappedRow['discount_type'] ?? 'fixed',
            'discount_val' => $this->parseAmount($mappedRow['discount_val'] ?? 0),
            'notes' => $mappedRow['notes'] ?? null,
            'status' => $mappedRow['status'] ?? Invoice::STATUS_DRAFT,
        ]);

        $this->successCount++;

        return $invoice;
    }

    /**
     * Map CSV columns to our field names using column mapping
     *
     * @param array $row
     * @return array
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
     * Find customer by name or email
     *
     * @param string|null $name
     * @param string|null $email
     * @return Customer|null
     */
    private function findCustomer(?string $name, ?string $email): ?Customer
    {
        $cacheKey = $name ?? $email;

        if (isset($this->customerCache[$cacheKey])) {
            return $this->customerCache[$cacheKey];
        }

        $customer = Customer::where('company_id', $this->companyId)
            ->where(function ($query) use ($name, $email) {
                if ($name) {
                    $query->where('name', $name);
                }
                if ($email) {
                    $query->orWhere('email', $email);
                }
            })
            ->first();

        if ($customer) {
            $this->customerCache[$cacheKey] = $customer;
        }

        return $customer;
    }

    /**
     * Parse date from various formats
     *
     * @param mixed $value
     * @return Carbon|null
     */
    private function parseDate($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                // Excel date serial number
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            }

            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse amount (handle different formats: 1,000.00, 1.000,00, etc.)
     *
     * @param mixed $value
     * @return int Amount in cents
     */
    private function parseAmount($value): int
    {
        if (!$value) {
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
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            $this->getColumnName('invoice_number') => 'required|string|max:255',
            $this->getColumnName('invoice_date') => 'required',
            $this->getColumnName('total') => 'required',
        ];
    }

    /**
     * Get CSV column name for our field
     *
     * @param string $ourField
     * @return string
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
     *
     * @return array
     */
    public function customValidationMessages(): array
    {
        return [
            'invoice_number.required' => 'Invoice number is required',
            'invoice_date.required' => 'Invoice date is required',
            'total.required' => 'Total amount is required',
        ];
    }

    /**
     * Chunk size for reading
     *
     * @return int
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Handle validation failures
     *
     * @param Failure[] $failures
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
     *
     * @return array
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    /**
     * Get success count
     *
     * @return int
     */
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    /**
     * Get failure count
     *
     * @return int
     */
    public function getFailureCount(): int
    {
        return $this->failureCount;
    }
}

// CLAUDE-CHECKPOINT
