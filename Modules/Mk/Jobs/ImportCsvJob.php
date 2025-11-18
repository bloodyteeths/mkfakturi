<?php

namespace Modules\Mk\Jobs;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\TaxType;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * CSV Import Job for processing CSV file imports
 *
 * Handles importing various data types from CSV files:
 * - Customers
 * - Items
 * - Invoices
 * - Expenses
 */
class ImportCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $filePath;

    protected array $options;

    protected string $importType;

    protected array $columnMapping;

    protected array $config;

    protected int $companyId;

    /**
     * Job timeout in seconds (10 minutes)
     */
    public int $timeout = 600;

    /**
     * Maximum number of retries
     */
    public int $tries = 3;

    /**
     * Create a new job instance
     */
    public function __construct(
        string $filePath,
        array $options,
        string $importType,
        array $columnMapping,
        array $config,
        int $companyId
    ) {
        $this->filePath = $filePath;
        $this->options = $options;
        $this->importType = $importType;
        $this->columnMapping = $columnMapping;
        $this->config = $config;
        $this->companyId = $companyId;
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        try {
            Log::info('CSV import job started', [
                'file_path' => $this->filePath,
                'import_type' => $this->importType,
                'company_id' => $this->companyId,
                'options' => $this->options,
                'config' => $this->config,
            ]);

            // Validate file exists
            if (! Storage::exists($this->filePath)) {
                throw new \Exception("CSV file not found: {$this->filePath}");
            }

            // Read and parse CSV
            $csvData = $this->parseCSV();

            if (empty($csvData)) {
                throw new \Exception('No data found in CSV file');
            }

            // Process import based on type
            $results = match ($this->importType) {
                'customers' => $this->importCustomers($csvData),
                'items' => $this->importItems($csvData),
                'invoices' => $this->importInvoices($csvData),
                'expenses' => $this->importExpenses($csvData),
                default => throw new \Exception("Unsupported import type: {$this->importType}")
            };

            // Clean up temporary file
            Storage::delete($this->filePath);

            Log::info('CSV import job completed successfully', [
                'import_type' => $this->importType,
                'company_id' => $this->companyId,
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('CSV import job failed', [
                'file_path' => $this->filePath,
                'import_type' => $this->importType,
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Clean up temporary file on error
            if (Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
            }

            throw $e;
        }
    }

    /**
     * Parse CSV file into array
     */
    protected function parseCSV(): array
    {
        $csvContent = Storage::get($this->filePath);
        $delimiter = $this->options['delimiter'] ?? ',';
        $encoding = $this->options['encoding'] ?? 'UTF-8';
        $hasHeader = $this->options['hasHeader'] ?? true;

        // Convert encoding if needed
        if ($encoding !== 'UTF-8') {
            $csvContent = mb_convert_encoding($csvContent, 'UTF-8', $encoding);
        }

        // Parse CSV lines
        $lines = explode("\n", $csvContent);
        $lines = array_filter($lines, fn ($line) => trim($line) !== '');

        $data = [];
        $isFirstRow = true;

        foreach ($lines as $line) {
            $row = str_getcsv($line, $delimiter);

            // Skip header row if configured
            if ($hasHeader && $isFirstRow) {
                $isFirstRow = false;

                continue;
            }

            $data[] = $row;
            $isFirstRow = false;
        }

        return $data;
    }

    /**
     * Import customers from CSV data
     */
    protected function importCustomers(array $csvData): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        // Get default currency
        $defaultCurrency = Currency::where('code', 'MKD')->first()
            ?? Currency::first();

        if (! $defaultCurrency) {
            throw new \Exception('No currency found. Please create at least one currency.');
        }

        foreach ($csvData as $rowIndex => $row) {
            try {
                $customerData = $this->mapRowData($row, [
                    'name' => 'required|string|max:255',
                    'email' => 'nullable|email|max:255',
                    'phone' => 'nullable|string|max:20',
                    'address' => 'nullable|string',
                    'city' => 'nullable|string|max:100',
                    'country' => 'nullable|string|max:100',
                    'tax_number' => 'nullable|string|max:50',
                    'website' => 'nullable|url',
                ]);

                if (empty($customerData['name'])) {
                    $errors[] = "Row {$rowIndex}: Customer name is required";
                    $skipped++;

                    continue;
                }

                // Check for duplicates
                $existingCustomer = null;
                if (! empty($customerData['email'])) {
                    $existingCustomer = Customer::where('company_id', $this->companyId)
                        ->where('email', $customerData['email'])
                        ->first();
                }

                if (! $existingCustomer && ! empty($customerData['name'])) {
                    $existingCustomer = Customer::where('company_id', $this->companyId)
                        ->where('name', $customerData['name'])
                        ->first();
                }

                if ($existingCustomer) {
                    if ($this->config['skipDuplicates']) {
                        $skipped++;

                        continue;
                    } elseif ($this->config['updateExisting']) {
                        $existingCustomer->update($customerData);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    // Create new customer
                    if (! $this->config['dryRun']) {
                        Customer::create(array_merge($customerData, [
                            'company_id' => $this->companyId,
                            'currency_id' => $defaultCurrency->id,
                            'creator_id' => auth()->id() ?? 1,
                        ]));
                    }
                    $created++;
                }

            } catch (\Exception $e) {
                $errors[] = "Row {$rowIndex}: ".$e->getMessage();
                $skipped++;
            }
        }

        return [
            'type' => 'customers',
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_processed' => count($csvData),
        ];
    }

    /**
     * Import items from CSV data
     */
    protected function importItems(array $csvData): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($csvData as $rowIndex => $row) {
            try {
                $itemData = $this->mapRowData($row, [
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'price' => 'required|numeric|min:0',
                    'unit' => 'nullable|string|max:20',
                    'tax_rate' => 'nullable|numeric|min:0|max:100',
                ]);

                if (empty($itemData['name']) || ! isset($itemData['price'])) {
                    $errors[] = "Row {$rowIndex}: Item name and price are required";
                    $skipped++;

                    continue;
                }

                // Convert price to smallest currency unit (denars to cent equivalent)
                $itemData['price'] = (int) round(floatval($itemData['price']) * 100);

                // Handle tax rate
                if (isset($itemData['tax_rate'])) {
                    $taxRate = floatval($itemData['tax_rate']);
                    if ($taxRate > 0) {
                        $taxType = TaxType::where('company_id', $this->companyId)
                            ->where('percent', $taxRate)
                            ->first();

                        if ($taxType) {
                            $itemData['tax_type_id'] = $taxType->id;
                        }
                    }
                    unset($itemData['tax_rate']);
                }

                // Check for duplicates
                $existingItem = Item::where('company_id', $this->companyId)
                    ->where('name', $itemData['name'])
                    ->first();

                if ($existingItem) {
                    if ($this->config['skipDuplicates']) {
                        $skipped++;

                        continue;
                    } elseif ($this->config['updateExisting']) {
                        $existingItem->update($itemData);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    // Create new item
                    if (! $this->config['dryRun']) {
                        Item::create(array_merge($itemData, [
                            'company_id' => $this->companyId,
                            'creator_id' => auth()->id() ?? 1,
                        ]));
                    }
                    $created++;
                }

            } catch (\Exception $e) {
                $errors[] = "Row {$rowIndex}: ".$e->getMessage();
                $skipped++;
            }
        }

        return [
            'type' => 'items',
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_processed' => count($csvData),
        ];
    }

    /**
     * Import invoices from CSV data (basic implementation)
     */
    protected function importInvoices(array $csvData): array
    {
        // Note: Invoice import is complex and would typically require
        // separate line items, tax calculations, etc.
        // This is a basic implementation for demonstration

        $created = 0;
        $errors = [];

        foreach ($csvData as $rowIndex => $row) {
            $errors[] = "Row {$rowIndex}: Invoice import not fully implemented yet";
        }

        return [
            'type' => 'invoices',
            'created' => $created,
            'updated' => 0,
            'skipped' => count($csvData),
            'errors' => $errors,
            'total_processed' => count($csvData),
        ];
    }

    /**
     * Import expenses from CSV data
     */
    protected function importExpenses(array $csvData): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($csvData as $rowIndex => $row) {
            try {
                $expenseData = $this->mapRowData($row, [
                    'expense_date' => 'required|date',
                    'amount' => 'required|numeric|min:0',
                    'notes' => 'nullable|string',
                    'category' => 'nullable|string|max:100',
                ]);

                if (! isset($expenseData['amount']) || ! isset($expenseData['expense_date'])) {
                    $errors[] = "Row {$rowIndex}: Amount and date are required";
                    $skipped++;

                    continue;
                }

                // Convert amount to smallest currency unit
                $expenseData['amount'] = (int) round(floatval($expenseData['amount']) * 100);

                // Parse date
                $expenseData['expense_date'] = Carbon::parse($expenseData['expense_date'])->format('Y-m-d');

                if (! $this->config['dryRun']) {
                    Expense::create(array_merge($expenseData, [
                        'company_id' => $this->companyId,
                        'creator_id' => auth()->id() ?? 1,
                        'currency_id' => Currency::where('code', 'MKD')->first()?->id ?? 1,
                    ]));
                }
                $created++;

            } catch (\Exception $e) {
                $errors[] = "Row {$rowIndex}: ".$e->getMessage();
                $skipped++;
            }
        }

        return [
            'type' => 'expenses',
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_processed' => count($csvData),
        ];
    }

    /**
     * Map CSV row data to model fields
     */
    protected function mapRowData(array $row, array $rules): array
    {
        $data = [];

        foreach ($this->columnMapping as $columnIndex => $fieldName) {
            if (empty($fieldName) || ! isset($row[$columnIndex])) {
                continue;
            }

            $value = trim($row[$columnIndex]);

            // Skip empty values
            if ($value === '') {
                continue;
            }

            // Basic data type conversion
            if (isset($rules[$fieldName])) {
                $rule = $rules[$fieldName];

                if (str_contains($rule, 'numeric')) {
                    $value = is_numeric($value) ? $value : 0;
                } elseif (str_contains($rule, 'date')) {
                    try {
                        $value = Carbon::parse($value)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $value = null;
                    }
                } elseif (str_contains($rule, 'email')) {
                    $value = filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
                } elseif (str_contains($rule, 'url')) {
                    $value = filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
                }
            }

            if ($value !== null) {
                $data[$fieldName] = $value;
            }
        }

        return $data;
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CSV import job failed permanently', [
            'file_path' => $this->filePath,
            'import_type' => $this->importType,
            'company_id' => $this->companyId,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Clean up temporary file
        if (Storage::exists($this->filePath)) {
            Storage::delete($this->filePath);
        }

        // TODO: Notify user of failed import
        // This could be done via email, notification system, etc.
    }
}
