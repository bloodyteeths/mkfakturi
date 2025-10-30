<?php

namespace App\Jobs\Migration;

use App\Models\ImportJob;
use App\Models\ImportLog;
use App\Models\ImportTempCustomer;
use App\Models\ImportTempInvoice;
use App\Models\ImportTempItem;
use App\Models\ImportTempPayment;
use App\Models\ImportTempExpense;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Payment;
use App\Models\Expense;
use App\Services\Migration\Transformers\DateTransformer;
use App\Services\Migration\Transformers\DecimalTransformer;
use App\Services\Migration\Transformers\CurrencyTransformer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Spatie\QueueableActions\QueueableAction;
use Carbon\Carbon;

/**
 * ValidateDataJob - Validate mapped data before commit
 * 
 * This job validates mapped data against business rules and constraints:
 * - Field validation (required, format, length constraints)
 * - Business rule validation (dates, amounts, references)
 * - Duplicate detection and conflict resolution
 * - Data transformation and cleaning
 * - Referential integrity checks
 * - Macedonian-specific validation (tax IDs, phone formats)
 */
class ValidateDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, QueueableAction;

    public ImportJob $importJob;
    
    /**
     * Job timeout in seconds (20 minutes)
     */
    public int $timeout = 1200;

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
     * Batch size for validation processing
     */
    protected int $batchSize = 200;

    /**
     * Validation statistics
     */
    protected array $validationStats = [
        'total_records' => 0,
        'valid_records' => 0,
        'invalid_records' => 0,
        'duplicate_records' => 0,
        'transformed_records' => 0,
        'validation_errors' => [],
    ];

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

        try {
            // Update job status
            $this->importJob->update(['status' => ImportJob::STATUS_VALIDATING]);

            Log::info('Data validation started', [
                'import_job_id' => $this->importJob->id,
                'import_type' => $this->importJob->type,
            ]);

            // Get validation rules for import type
            $validationRules = $this->getValidationRules();

            // Validate data in batches
            $this->validateDataInBatches($validationRules);

            // Check validation results
            $this->processValidationResults();

            // Log validation results
            $this->logValidationResults(microtime(true) - $startTime);

            Log::info('Data validation completed', [
                'import_job_id' => $this->importJob->id,
                'stats' => $this->validationStats,
            ]);

            // Chain to next job - CommitImportJob (only if validation passed)
            if ($this->validationStats['valid_records'] > 0) {
                CommitImportJob::dispatch($this->importJob)
                    ->onQueue('migration')
                    ->delay(now()->addSeconds(5));
            } else {
                // All records failed validation
                $this->importJob->markAsFailed(
                    'All records failed validation',
                    $this->validationStats
                );
            }

        } catch (\Exception $e) {
            $this->handleJobFailure($e);
            throw $e;
        }
    }

    /**
     * Get validation rules for the import type
     */
    protected function getValidationRules(): array
    {
        return match ($this->importJob->type) {
            ImportJob::TYPE_CUSTOMERS => [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'tax_id' => 'nullable|string|max:50',
                'address_1' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'zip' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'website' => 'nullable|url|max:255',
            ],
            ImportJob::TYPE_INVOICES => [
                'invoice_number' => 'required|string|max:100',
                'customer_id' => 'nullable|integer',
                'invoice_date' => 'required|date',
                'due_date' => 'nullable|date|after_or_equal:invoice_date',
                'subtotal' => 'required|numeric|min:0',
                'tax_amount' => 'nullable|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'status' => 'nullable|in:draft,sent,viewed,overdue,paid',
            ],
            ImportJob::TYPE_ITEMS => [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'unit' => 'nullable|string|max:20',
                'sku' => 'nullable|string|max:100',
                'quantity' => 'nullable|numeric|min:0',
            ],
            ImportJob::TYPE_PAYMENTS => [
                'payment_date' => 'required|date',
                'amount' => 'required|numeric|min:0',
                'payment_method' => 'nullable|string|max:50',
                'reference_number' => 'nullable|string|max:100',
                'customer_id' => 'nullable|integer',
                'invoice_id' => 'nullable|integer',
            ],
            ImportJob::TYPE_EXPENSES => [
                'expense_date' => 'required|date',
                'amount' => 'required|numeric|min:0',
                'category' => 'nullable|string|max:100',
                'vendor' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'payment_method' => 'nullable|string|max:50',
            ],
            default => [],
        };
    }

    /**
     * Validate data in batches
     */
    protected function validateDataInBatches(array $validationRules): void
    {
        $tempModel = $this->getTempModelClass();
        
        $tempModel::where('import_job_id', $this->importJob->id)
            ->chunk($this->batchSize, function ($records) use ($validationRules) {
                $this->validateBatch($records, $validationRules);
                
                // Update progress
                $this->validationStats['total_records'] += count($records);
                $this->importJob->updateProgress($this->validationStats['total_records']);
            });
    }

    /**
     * Validate a batch of records
     */
    protected function validateBatch($records, array $validationRules): void
    {
        foreach ($records as $record) {
            $this->validateSingleRecord($record, $validationRules);
        }
    }

    /**
     * Validate a single record
     */
    protected function validateSingleRecord($record, array $validationRules): void
    {
        try {
            // Get mapped data or fall back to cleaned data
            $data = json_decode($record->mapped_data, true) ?: json_decode($record->cleaned_data, true);
            
            if (!is_array($data)) {
                $this->markRecordInvalid($record, ['Invalid data format']);
                return;
            }

            // Apply data transformations
            $transformedData = $this->applyDataTransformations($data);
            
            // Validate against rules
            $validator = Validator::make($transformedData, $validationRules);
            
            if ($validator->fails()) {
                $this->markRecordInvalid($record, $validator->errors()->all());
                return;
            }

            // Business rule validation
            $businessValidationErrors = $this->validateBusinessRules($transformedData);
            if (!empty($businessValidationErrors)) {
                $this->markRecordInvalid($record, $businessValidationErrors);
                return;
            }

            // Duplicate detection
            $duplicateInfo = $this->checkForDuplicates($transformedData);
            
            // Mark record as valid
            $this->markRecordValid($record, $transformedData, $duplicateInfo);

        } catch (\Exception $e) {
            $this->markRecordInvalid($record, ["Validation error: {$e->getMessage()}"]);
            
            Log::warning('Record validation failed', [
                'import_job_id' => $this->importJob->id,
                'record_id' => $record->id,
                'row_number' => $record->row_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Apply data transformations
     */
    protected function applyDataTransformations(array $data): array
    {
        $transformedData = $data;

        // Apply date transformations
        $dateFields = $this->getDateFields();
        foreach ($dateFields as $field) {
            if (isset($transformedData[$field]) && !empty($transformedData[$field])) {
                try {
                    $transformedData[$field] = DateTransformer::transform($transformedData[$field]);
                } catch (\Exception $e) {
                    // Keep original value if transformation fails
                    Log::debug("Date transformation failed for {$field}: {$e->getMessage()}");
                }
            }
        }

        // Apply decimal transformations
        $decimalFields = $this->getDecimalFields();
        foreach ($decimalFields as $field) {
            if (isset($transformedData[$field]) && !empty($transformedData[$field])) {
                try {
                    $transformedData[$field] = DecimalTransformer::transform($transformedData[$field]);
                } catch (\Exception $e) {
                    Log::debug("Decimal transformation failed for {$field}: {$e->getMessage()}");
                }
            }
        }

        // Apply currency transformations if needed
        $currencyFields = $this->getCurrencyFields();
        foreach ($currencyFields as $field) {
            if (isset($transformedData[$field]) && !empty($transformedData[$field])) {
                try {
                    $transformedData[$field] = CurrencyTransformer::transform($transformedData[$field], 'MKD', 'EUR');
                } catch (\Exception $e) {
                    Log::debug("Currency transformation failed for {$field}: {$e->getMessage()}");
                }
            }
        }

        return $transformedData;
    }

    /**
     * Validate business rules
     */
    protected function validateBusinessRules(array $data): array
    {
        $errors = [];

        switch ($this->importJob->type) {
            case ImportJob::TYPE_CUSTOMERS:
                $errors = array_merge($errors, $this->validateCustomerBusinessRules($data));
                break;
                
            case ImportJob::TYPE_INVOICES:
                $errors = array_merge($errors, $this->validateInvoiceBusinessRules($data));
                break;
                
            case ImportJob::TYPE_ITEMS:
                $errors = array_merge($errors, $this->validateItemBusinessRules($data));
                break;
                
            case ImportJob::TYPE_PAYMENTS:
                $errors = array_merge($errors, $this->validatePaymentBusinessRules($data));
                break;
                
            case ImportJob::TYPE_EXPENSES:
                $errors = array_merge($errors, $this->validateExpenseBusinessRules($data));
                break;
        }

        return $errors;
    }

    /**
     * Validate customer business rules
     */
    protected function validateCustomerBusinessRules(array $data): array
    {
        $errors = [];

        // Validate Macedonian tax ID format (EMBS)
        if (!empty($data['tax_id'])) {
            if (!$this->isValidMacedonianTaxId($data['tax_id'])) {
                $errors[] = 'Invalid Macedonian tax ID (EMBS) format';
            }
        }

        // Validate phone number format
        if (!empty($data['phone'])) {
            if (!$this->isValidMacedonianPhone($data['phone'])) {
                $errors[] = 'Invalid Macedonian phone number format';
            }
        }

        return $errors;
    }

    /**
     * Validate invoice business rules
     */
    protected function validateInvoiceBusinessRules(array $data): array
    {
        $errors = [];

        // Validate invoice date is not in future
        if (!empty($data['invoice_date'])) {
            $invoiceDate = Carbon::parse($data['invoice_date']);
            if ($invoiceDate->isFuture()) {
                $errors[] = 'Invoice date cannot be in the future';
            }
        }

        // Validate due date is after invoice date
        if (!empty($data['due_date']) && !empty($data['invoice_date'])) {
            $invoiceDate = Carbon::parse($data['invoice_date']);
            $dueDate = Carbon::parse($data['due_date']);
            if ($dueDate->lt($invoiceDate)) {
                $errors[] = 'Due date must be after invoice date';
            }
        }

        // Validate total amount calculation
        if (isset($data['subtotal']) && isset($data['tax_amount']) && isset($data['total'])) {
            $calculatedTotal = floatval($data['subtotal']) + floatval($data['tax_amount']);
            $providedTotal = floatval($data['total']);
            if (abs($calculatedTotal - $providedTotal) > 0.01) {
                $errors[] = 'Total amount does not match subtotal + tax amount';
            }
        }

        return $errors;
    }

    /**
     * Validate item business rules
     */
    protected function validateItemBusinessRules(array $data): array
    {
        $errors = [];

        // Validate price is reasonable
        if (isset($data['price'])) {
            $price = floatval($data['price']);
            if ($price > 1000000) { // 1M MKD
                $errors[] = 'Item price seems unreasonably high';
            }
        }

        return $errors;
    }

    /**
     * Validate payment business rules
     */
    protected function validatePaymentBusinessRules(array $data): array
    {
        $errors = [];

        // Validate payment date is not in future
        if (!empty($data['payment_date'])) {
            $paymentDate = Carbon::parse($data['payment_date']);
            if ($paymentDate->isFuture()) {
                $errors[] = 'Payment date cannot be in the future';
            }
        }

        return $errors;
    }

    /**
     * Validate expense business rules
     */
    protected function validateExpenseBusinessRules(array $data): array
    {
        $errors = [];

        // Validate expense date is not in future
        if (!empty($data['expense_date'])) {
            $expenseDate = Carbon::parse($data['expense_date']);
            if ($expenseDate->isFuture()) {
                $errors[] = 'Expense date cannot be in the future';
            }
        }

        return $errors;
    }

    /**
     * Check for duplicates
     */
    protected function checkForDuplicates(array $data): ?array
    {
        switch ($this->importJob->type) {
            case ImportJob::TYPE_CUSTOMERS:
                return $this->checkCustomerDuplicates($data);
            case ImportJob::TYPE_INVOICES:
                return $this->checkInvoiceDuplicates($data);
            case ImportJob::TYPE_ITEMS:
                return $this->checkItemDuplicates($data);
            default:
                return null;
        }
    }

    /**
     * Check customer duplicates
     */
    protected function checkCustomerDuplicates(array $data): ?array
    {
        $query = Customer::where('company_id', $this->importJob->company_id);

        // Check by email first
        if (!empty($data['email'])) {
            $existing = $query->where('email', $data['email'])->first();
            if ($existing) {
                return [
                    'exists' => true,
                    'match_field' => 'email',
                    'existing_id' => $existing->id,
                    'existing_name' => $existing->name,
                ];
            }
        }

        // Check by tax ID
        if (!empty($data['tax_id'])) {
            $existing = $query->where('tax_id', $data['tax_id'])->first();
            if ($existing) {
                return [
                    'exists' => true,
                    'match_field' => 'tax_id',
                    'existing_id' => $existing->id,
                    'existing_name' => $existing->name,
                ];
            }
        }

        // Check by name (fuzzy match)
        if (!empty($data['name'])) {
            $existing = $query->where('name', 'LIKE', '%' . $data['name'] . '%')->first();
            if ($existing) {
                return [
                    'exists' => true,
                    'match_field' => 'name',
                    'existing_id' => $existing->id,
                    'existing_name' => $existing->name,
                    'fuzzy_match' => true,
                ];
            }
        }

        return ['exists' => false];
    }

    /**
     * Check invoice duplicates
     */
    protected function checkInvoiceDuplicates(array $data): ?array
    {
        if (empty($data['invoice_number'])) {
            return ['exists' => false];
        }

        $existing = Invoice::where('company_id', $this->importJob->company_id)
            ->where('invoice_number', $data['invoice_number'])
            ->first();

        if ($existing) {
            return [
                'exists' => true,
                'match_field' => 'invoice_number',
                'existing_id' => $existing->id,
            ];
        }

        return ['exists' => false];
    }

    /**
     * Check item duplicates
     */
    protected function checkItemDuplicates(array $data): ?array
    {
        if (empty($data['name'])) {
            return ['exists' => false];
        }

        $query = Item::where('company_id', $this->importJob->company_id);

        // Check by SKU first
        if (!empty($data['sku'])) {
            $existing = $query->where('sku', $data['sku'])->first();
            if ($existing) {
                return [
                    'exists' => true,
                    'match_field' => 'sku',
                    'existing_id' => $existing->id,
                    'existing_name' => $existing->name,
                ];
            }
        }

        // Check by name
        $existing = $query->where('name', $data['name'])->first();
        if ($existing) {
            return [
                'exists' => true,
                'match_field' => 'name',
                'existing_id' => $existing->id,
                'existing_name' => $existing->name,
            ];
        }

        return ['exists' => false];
    }

    /**
     * Mark record as valid
     */
    protected function markRecordValid($record, array $transformedData, ?array $duplicateInfo): void
    {
        $record->update([
            'transformed_data' => json_encode($transformedData),
            'validation_status' => 'valid',
            'validation_errors' => null,
            'duplicate_info' => $duplicateInfo ? json_encode($duplicateInfo) : null,
        ]);

        $this->validationStats['valid_records']++;
        
        if ($duplicateInfo && $duplicateInfo['exists']) {
            $this->validationStats['duplicate_records']++;
            
            ImportLog::logDuplicateDetected(
                $this->importJob,
                $this->importJob->type,
                $record->id,
                $record->row_number,
                $duplicateInfo['match_field'],
                $duplicateInfo['existing_id'] ?? null
            );
        }
    }

    /**
     * Mark record as invalid
     */
    protected function markRecordInvalid($record, array $errors): void
    {
        $record->update([
            'validation_status' => 'invalid',
            'validation_errors' => json_encode($errors),
        ]);

        $this->validationStats['invalid_records']++;
        $this->validationStats['validation_errors'] = array_merge(
            $this->validationStats['validation_errors'],
            $errors
        );

        ImportLog::logValidationFailed(
            $this->importJob,
            $this->importJob->type,
            $record->id,
            $record->row_number,
            'multiple_fields',
            'validation_failed',
            $errors
        );
    }

    /**
     * Process validation results
     */
    protected function processValidationResults(): void
    {
        // Update import job with validation results
        $this->importJob->update([
            'successful_records' => $this->validationStats['valid_records'],
            'failed_records' => $this->validationStats['invalid_records'],
            'validation_rules' => [
                'validation_completed' => true,
                'validation_timestamp' => now()->toISOString(),
                'stats' => $this->validationStats,
            ],
        ]);
    }

    /**
     * Get date fields for the import type
     */
    protected function getDateFields(): array
    {
        return match ($this->importJob->type) {
            ImportJob::TYPE_INVOICES => ['invoice_date', 'due_date'],
            ImportJob::TYPE_PAYMENTS => ['payment_date'],
            ImportJob::TYPE_EXPENSES => ['expense_date'],
            default => [],
        };
    }

    /**
     * Get decimal fields for the import type
     */
    protected function getDecimalFields(): array
    {
        return match ($this->importJob->type) {
            ImportJob::TYPE_INVOICES => ['subtotal', 'tax_amount', 'total'],
            ImportJob::TYPE_ITEMS => ['price', 'quantity'],
            ImportJob::TYPE_PAYMENTS => ['amount'],
            ImportJob::TYPE_EXPENSES => ['amount', 'tax_amount'],
            default => [],
        };
    }

    /**
     * Get currency fields for the import type
     */
    protected function getCurrencyFields(): array
    {
        return match ($this->importJob->type) {
            ImportJob::TYPE_INVOICES => ['subtotal', 'tax_amount', 'total'],
            ImportJob::TYPE_ITEMS => ['price'],
            ImportJob::TYPE_PAYMENTS => ['amount'],
            ImportJob::TYPE_EXPENSES => ['amount'],
            default => [],
        };
    }

    /**
     * Validate Macedonian tax ID (EMBS)
     */
    protected function isValidMacedonianTaxId(string $taxId): bool
    {
        // Remove any non-numeric characters
        $cleanTaxId = preg_replace('/[^0-9]/', '', $taxId);
        
        // EMBS should be 13 digits
        return strlen($cleanTaxId) === 13 && is_numeric($cleanTaxId);
    }

    /**
     * Validate Macedonian phone number
     */
    protected function isValidMacedonianPhone(string $phone): bool
    {
        // Remove any non-numeric characters except +
        $cleanPhone = preg_replace('/[^+0-9]/', '', $phone);
        
        // Macedonian phone patterns
        $patterns = [
            '/^\+38970\d{6}$/',  // Mobile +38970XXXXXX
            '/^\+38971\d{6}$/',  // Mobile +38971XXXXXX
            '/^\+38972\d{6}$/',  // Mobile +38972XXXXXX
            '/^070\d{6}$/',      // Mobile 070XXXXXX
            '/^071\d{6}$/',      // Mobile 071XXXXXX
            '/^072\d{6}$/',      // Mobile 072XXXXXX
            '/^\+3892\d{7}$/',   // Landline +3892XXXXXXX
            '/^02\d{7}$/',       // Landline 02XXXXXXX
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $cleanPhone)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log validation results
     */
    protected function logValidationResults(float $processingTime): void
    {
        ImportLog::create([
            'import_job_id' => $this->importJob->id,
            'log_type' => $this->validationStats['valid_records'] > 0 ? ImportLog::LOG_VALIDATION_PASSED : ImportLog::LOG_VALIDATION_FAILED,
            'severity' => $this->validationStats['invalid_records'] > 0 ? ImportLog::SEVERITY_WARNING : ImportLog::SEVERITY_INFO,
            'message' => "Validation completed: {$this->validationStats['valid_records']} valid, {$this->validationStats['invalid_records']} invalid",
            'detailed_message' => "Data validation completed in " . round($processingTime, 2) . " seconds. {$this->validationStats['duplicate_records']} duplicates detected.",
            'process_stage' => 'validating',
            'processing_time' => $processingTime,
            'records_processed' => $this->validationStats['total_records'],
            'final_data' => $this->validationStats,
        ]);
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
            ImportJob::TYPE_COMPLETE => ImportTempCustomer::class,
            default => throw new \Exception("Unknown import type: {$this->importJob->type}"),
        };
    }

    /**
     * Handle job failure
     */
    protected function handleJobFailure(\Exception $exception): void
    {
        Log::error('Data validation job failed', [
            'import_job_id' => $this->importJob->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mark import job as failed
        $this->importJob->markAsFailed(
            'Data validation failed: ' . $exception->getMessage(),
            [
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'stage' => 'validation',
                'stats' => $this->validationStats,
            ]
        );

        // Log failure
        ImportLog::logJobFailed($this->importJob, $exception->getMessage(), [
            'stage' => 'data_validation',
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