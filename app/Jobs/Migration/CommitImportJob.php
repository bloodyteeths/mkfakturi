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
use App\Models\Currency;
use App\Models\TaxType;
use App\Models\PaymentMethod;
use App\Models\ExpenseCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Spatie\QueueableActions\QueueableAction;

/**
 * CommitImportJob - Commit validated data to production tables
 * 
 * This job commits validated temporary data to production tables:
 * - Creates records in production tables from validated temp data
 * - Handles duplicate resolution strategies
 * - Maintains referential integrity
 * - Provides rollback capability on failure
 * - Generates comprehensive audit trail
 * - Updates import job with final results
 */
class CommitImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, QueueableAction;

    public ImportJob $importJob;
    
    /**
     * Job timeout in seconds (45 minutes for large imports)
     */
    public int $timeout = 2700;

    /**
     * Maximum number of retries
     */
    public int $tries = 1; // No retries - rollback on failure

    /**
     * Queue name for import jobs
     */
    public string $queue = 'migration';

    /**
     * Batch size for commit processing
     */
    protected int $batchSize = 100;

    /**
     * Commit statistics
     */
    protected array $commitStats = [
        'total_processed' => 0,
        'successfully_committed' => 0,
        'failed_commits' => 0,
        'duplicates_skipped' => 0,
        'duplicates_updated' => 0,
        'records_created' => [],
        'commit_errors' => [],
    ];

    /**
     * Transaction checkpoint for rollback
     */
    protected array $transactionCheckpoint = [];

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
            $this->importJob->update(['status' => ImportJob::STATUS_COMMITTING]);

            Log::info('Import commit started', [
                'import_job_id' => $this->importJob->id,
                'import_type' => $this->importJob->type,
            ]);

            // Begin transaction for rollback capability
            DB::beginTransaction();
            $this->transactionCheckpoint = $this->createCheckpoint();

            // Commit data based on import type
            $this->commitValidatedData();

            // If we get here, commit was successful
            DB::commit();

            // Mark job as completed
            $this->importJob->markAsCompleted([
                'commit_completed' => true,
                'commit_timestamp' => now()->toISOString(),
                'processing_time' => microtime(true) - $startTime,
                'stats' => $this->commitStats,
            ]);

            // Log successful completion
            $this->logCommitResults(microtime(true) - $startTime);

            Log::info('Import commit completed successfully', [
                'import_job_id' => $this->importJob->id,
                'stats' => $this->commitStats,
            ]);

            // Clean up temporary data
            $this->cleanupTempData();

        } catch (\Exception $e) {
            // Rollback on any error
            DB::rollBack();
            $this->handleJobFailure($e);
            throw $e;
        }
    }

    /**
     * Create transaction checkpoint for rollback
     */
    protected function createCheckpoint(): array
    {
        return [
            'timestamp' => now(),
            'memory_usage' => memory_get_usage(true),
            'customer_count' => Customer::where('company_id', $this->importJob->company_id)->count(),
            'invoice_count' => Invoice::where('company_id', $this->importJob->company_id)->count(),
            'item_count' => Item::where('company_id', $this->importJob->company_id)->count(),
            'payment_count' => Payment::where('company_id', $this->importJob->company_id)->count(),
            'expense_count' => Expense::where('company_id', $this->importJob->company_id)->count(),
        ];
    }

    /**
     * Commit validated data based on import type
     */
    protected function commitValidatedData(): void
    {
        switch ($this->importJob->type) {
            case ImportJob::TYPE_CUSTOMERS:
                $this->commitCustomers();
                break;
                
            case ImportJob::TYPE_INVOICES:
                $this->commitInvoices();
                break;
                
            case ImportJob::TYPE_ITEMS:
                $this->commitItems();
                break;
                
            case ImportJob::TYPE_PAYMENTS:
                $this->commitPayments();
                break;
                
            case ImportJob::TYPE_EXPENSES:
                $this->commitExpenses();
                break;
                
            case ImportJob::TYPE_COMPLETE:
                // For complete business import, commit in order of dependencies
                $this->commitCustomers();
                $this->commitItems();
                $this->commitInvoices();
                $this->commitPayments();
                $this->commitExpenses();
                break;
                
            default:
                throw new \Exception("Unknown import type: {$this->importJob->type}");
        }
    }

    /**
     * Commit customers
     */
    protected function commitCustomers(): void
    {
        $validRecords = ImportTempCustomer::where('import_job_id', $this->importJob->id)
            ->where('validation_status', 'valid')
            ->get();

        if ($validRecords->isEmpty()) {
            return;
        }

        $defaultCurrency = $this->getDefaultCurrency();

        foreach ($validRecords as $tempRecord) {
            try {
                $data = json_decode($tempRecord->transformed_data, true);
                $duplicateInfo = json_decode($tempRecord->duplicate_info, true);

                // Handle duplicates based on strategy
                if ($duplicateInfo && $duplicateInfo['exists']) {
                    $this->handleCustomerDuplicate($tempRecord, $data, $duplicateInfo);
                } else {
                    $this->createCustomer($tempRecord, $data, $defaultCurrency);
                }

                $this->commitStats['total_processed']++;

            } catch (\Exception $e) {
                $this->handleRecordCommitFailure($tempRecord, $e);
            }
        }
    }

    /**
     * Handle customer duplicate
     */
    protected function handleCustomerDuplicate($tempRecord, array $data, array $duplicateInfo): void
    {
        $strategy = $this->getDuplicateStrategy();

        switch ($strategy) {
            case 'skip':
                $this->commitStats['duplicates_skipped']++;
                ImportLog::create([
                    'import_job_id' => $this->importJob->id,
                    'log_type' => ImportLog::LOG_DUPLICATE_RESOLVED,
                    'severity' => ImportLog::SEVERITY_INFO,
                    'message' => "Duplicate customer skipped (row {$tempRecord->row_number})",
                    'detailed_message' => "Skipped duplicate customer based on {$duplicateInfo['match_field']}",
                    'entity_type' => ImportLog::ENTITY_CUSTOMER,
                    'row_number' => $tempRecord->row_number,
                    'process_stage' => 'committing',
                ]);
                break;

            case 'update':
                $existingCustomer = Customer::find($duplicateInfo['existing_id']);
                if ($existingCustomer) {
                    $existingCustomer->update($this->prepareCustomerData($data));
                    $this->commitStats['duplicates_updated']++;
                    $this->commitStats['records_created']['customers'][] = $existingCustomer->id;
                }
                break;

            case 'create_new':
            default:
                $this->createCustomer($tempRecord, $data, $this->getDefaultCurrency());
                break;
        }
    }

    /**
     * Create new customer
     */
    protected function createCustomer($tempRecord, array $data, $defaultCurrency): void
    {
        $customerData = $this->prepareCustomerData($data);
        $customerData['company_id'] = $this->importJob->company_id;
        $customerData['creator_id'] = $this->importJob->creator_id;
        $customerData['currency_id'] = $defaultCurrency->id;

        $customer = Customer::create($customerData);
        
        $this->commitStats['successfully_committed']++;
        $this->commitStats['records_created']['customers'][] = $customer->id;

        ImportLog::create([
            'import_job_id' => $this->importJob->id,
            'log_type' => ImportLog::LOG_RECORD_COMMITTED,
            'severity' => ImportLog::SEVERITY_INFO,
            'message' => "Customer created: {$customer->name}",
            'detailed_message' => "Successfully created customer from row {$tempRecord->row_number}",
            'entity_type' => ImportLog::ENTITY_CUSTOMER,
            'entity_id' => $customer->id,
            'row_number' => $tempRecord->row_number,
            'process_stage' => 'committing',
        ]);
    }

    /**
     * Prepare customer data for creation/update
     */
    protected function prepareCustomerData(array $data): array
    {
        return [
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'website' => $data['website'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'enable_portal' => true,
        ];
    }

    /**
     * Commit invoices
     */
    protected function commitInvoices(): void
    {
        $validRecords = ImportTempInvoice::where('import_job_id', $this->importJob->id)
            ->where('validation_status', 'valid')
            ->get();

        if ($validRecords->isEmpty()) {
            return;
        }

        $defaultCurrency = $this->getDefaultCurrency();

        foreach ($validRecords as $tempRecord) {
            try {
                $data = json_decode($tempRecord->transformed_data, true);
                $duplicateInfo = json_decode($tempRecord->duplicate_info, true);

                if ($duplicateInfo && $duplicateInfo['exists']) {
                    $this->handleInvoiceDuplicate($tempRecord, $data, $duplicateInfo);
                } else {
                    $this->createInvoice($tempRecord, $data, $defaultCurrency);
                }

                $this->commitStats['total_processed']++;

            } catch (\Exception $e) {
                $this->handleRecordCommitFailure($tempRecord, $e);
            }
        }
    }

    /**
     * Create new invoice
     */
    protected function createInvoice($tempRecord, array $data, $defaultCurrency): void
    {
        $invoiceData = $this->prepareInvoiceData($data);
        $invoiceData['company_id'] = $this->importJob->company_id;
        $invoiceData['creator_id'] = $this->importJob->creator_id;
        $invoiceData['currency_id'] = $defaultCurrency->id;

        $invoice = Invoice::create($invoiceData);
        
        $this->commitStats['successfully_committed']++;
        $this->commitStats['records_created']['invoices'][] = $invoice->id;

        ImportLog::create([
            'import_job_id' => $this->importJob->id,
            'log_type' => ImportLog::LOG_RECORD_COMMITTED,
            'severity' => ImportLog::SEVERITY_INFO,
            'message' => "Invoice created: {$invoice->invoice_number}",
            'detailed_message' => "Successfully created invoice from row {$tempRecord->row_number}",
            'entity_type' => ImportLog::ENTITY_INVOICE,
            'entity_id' => $invoice->id,
            'row_number' => $tempRecord->row_number,
            'process_stage' => 'committing',
        ]);
    }

    /**
     * Handle invoice duplicate
     */
    protected function handleInvoiceDuplicate($tempRecord, array $data, array $duplicateInfo): void
    {
        $strategy = $this->getDuplicateStrategy();

        switch ($strategy) {
            case 'skip':
                $this->commitStats['duplicates_skipped']++;
                break;
            case 'update':
                $existingInvoice = Invoice::find($duplicateInfo['existing_id']);
                if ($existingInvoice) {
                    $existingInvoice->update($this->prepareInvoiceData($data));
                    $this->commitStats['duplicates_updated']++;
                }
                break;
            default:
                // Create with modified invoice number
                $data['invoice_number'] = $data['invoice_number'] . '_' . uniqid();
                $this->createInvoice($tempRecord, $data, $this->getDefaultCurrency());
                break;
        }
    }

    /**
     * Prepare invoice data for creation/update
     */
    protected function prepareInvoiceData(array $data): array
    {
        return [
            'invoice_number' => $data['invoice_number'] ?? uniqid('INV-'),
            'invoice_date' => $data['invoice_date'] ?? now()->format('Y-m-d'),
            'due_date' => $data['due_date'] ?? now()->addDays(30)->format('Y-m-d'),
            'sub_total' => isset($data['subtotal']) ? (int) round(floatval($data['subtotal']) * 100) : 0,
            'tax' => isset($data['tax_amount']) ? (int) round(floatval($data['tax_amount']) * 100) : 0,
            'total' => isset($data['total']) ? (int) round(floatval($data['total']) * 100) : 0,
            'status' => $data['status'] ?? 'draft',
            'notes' => $data['notes'] ?? null,
            'paid_status' => Payment::STATUS_UNPAID,
            'base_sub_total' => isset($data['subtotal']) ? (int) round(floatval($data['subtotal']) * 100) : 0,
            'base_tax' => isset($data['tax_amount']) ? (int) round(floatval($data['tax_amount']) * 100) : 0,
            'base_total' => isset($data['total']) ? (int) round(floatval($data['total']) * 100) : 0,
        ];
    }

    /**
     * Commit items
     */
    protected function commitItems(): void
    {
        $validRecords = ImportTempItem::where('import_job_id', $this->importJob->id)
            ->where('validation_status', 'valid')
            ->get();

        if ($validRecords->isEmpty()) {
            return;
        }

        $defaultCurrency = $this->getDefaultCurrency();

        foreach ($validRecords as $tempRecord) {
            try {
                $data = json_decode($tempRecord->transformed_data, true);
                $duplicateInfo = json_decode($tempRecord->duplicate_info, true);

                if ($duplicateInfo && $duplicateInfo['exists']) {
                    $this->handleItemDuplicate($tempRecord, $data, $duplicateInfo);
                } else {
                    $this->createItem($tempRecord, $data, $defaultCurrency);
                }

                $this->commitStats['total_processed']++;

            } catch (\Exception $e) {
                $this->handleRecordCommitFailure($tempRecord, $e);
            }
        }
    }

    /**
     * Create new item
     */
    protected function createItem($tempRecord, array $data, $defaultCurrency): void
    {
        $itemData = $this->prepareItemData($data);
        $itemData['company_id'] = $this->importJob->company_id;
        $itemData['creator_id'] = $this->importJob->creator_id;
        $itemData['currency_id'] = $defaultCurrency->id;

        $item = Item::create($itemData);
        
        $this->commitStats['successfully_committed']++;
        $this->commitStats['records_created']['items'][] = $item->id;

        ImportLog::create([
            'import_job_id' => $this->importJob->id,
            'log_type' => ImportLog::LOG_RECORD_COMMITTED,
            'severity' => ImportLog::SEVERITY_INFO,
            'message' => "Item created: {$item->name}",
            'detailed_message' => "Successfully created item from row {$tempRecord->row_number}",
            'entity_type' => ImportLog::ENTITY_ITEM,
            'entity_id' => $item->id,
            'row_number' => $tempRecord->row_number,
            'process_stage' => 'committing',
        ]);
    }

    /**
     * Handle item duplicate
     */
    protected function handleItemDuplicate($tempRecord, array $data, array $duplicateInfo): void
    {
        $strategy = $this->getDuplicateStrategy();

        switch ($strategy) {
            case 'skip':
                $this->commitStats['duplicates_skipped']++;
                break;
            case 'update':
                $existingItem = Item::find($duplicateInfo['existing_id']);
                if ($existingItem) {
                    $existingItem->update($this->prepareItemData($data));
                    $this->commitStats['duplicates_updated']++;
                }
                break;
            default:
                $this->createItem($tempRecord, $data, $this->getDefaultCurrency());
                break;
        }
    }

    /**
     * Prepare item data for creation/update
     */
    protected function prepareItemData(array $data): array
    {
        return [
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? null,
            'price' => isset($data['price']) ? (int) round(floatval($data['price']) * 100) : 0,
            'unit_name' => $data['unit'] ?? null,
            'sku' => $data['sku'] ?? null,
            'tax_per_item' => false,
            'base_price' => isset($data['price']) ? (int) round(floatval($data['price']) * 100) : 0,
        ];
    }

    /**
     * Commit payments
     */
    protected function commitPayments(): void
    {
        $validRecords = ImportTempPayment::where('import_job_id', $this->importJob->id)
            ->where('validation_status', 'valid')
            ->get();

        if ($validRecords->isEmpty()) {
            return;
        }

        $defaultCurrency = $this->getDefaultCurrency();
        $defaultPaymentMethod = $this->getDefaultPaymentMethod();

        foreach ($validRecords as $tempRecord) {
            try {
                $data = json_decode($tempRecord->transformed_data, true);
                $this->createPayment($tempRecord, $data, $defaultCurrency, $defaultPaymentMethod);
                $this->commitStats['total_processed']++;

            } catch (\Exception $e) {
                $this->handleRecordCommitFailure($tempRecord, $e);
            }
        }
    }

    /**
     * Create new payment
     */
    protected function createPayment($tempRecord, array $data, $defaultCurrency, $defaultPaymentMethod): void
    {
        $paymentData = $this->preparePaymentData($data, $defaultPaymentMethod);
        $paymentData['company_id'] = $this->importJob->company_id;
        $paymentData['creator_id'] = $this->importJob->creator_id;
        $paymentData['currency_id'] = $defaultCurrency->id;

        $payment = Payment::create($paymentData);
        
        $this->commitStats['successfully_committed']++;
        $this->commitStats['records_created']['payments'][] = $payment->id;

        ImportLog::create([
            'import_job_id' => $this->importJob->id,
            'log_type' => ImportLog::LOG_RECORD_COMMITTED,
            'severity' => ImportLog::SEVERITY_INFO,
            'message' => "Payment created: {$payment->payment_number}",
            'detailed_message' => "Successfully created payment from row {$tempRecord->row_number}",
            'entity_type' => ImportLog::ENTITY_PAYMENT,
            'entity_id' => $payment->id,
            'row_number' => $tempRecord->row_number,
            'process_stage' => 'committing',
        ]);
    }

    /**
     * Prepare payment data for creation
     */
    protected function preparePaymentData(array $data, $defaultPaymentMethod): array
    {
        return [
            'payment_number' => 'PAY-' . uniqid(),
            'payment_date' => $data['payment_date'] ?? now()->format('Y-m-d'),
            'amount' => isset($data['amount']) ? (int) round(floatval($data['amount']) * 100) : 0,
            'payment_method_id' => $defaultPaymentMethod->id,
            'reference_number' => $data['reference_number'] ?? null,
            'notes' => $data['notes'] ?? null,
            'base_amount' => isset($data['amount']) ? (int) round(floatval($data['amount']) * 100) : 0,
        ];
    }

    /**
     * Commit expenses
     */
    protected function commitExpenses(): void
    {
        $validRecords = ImportTempExpense::where('import_job_id', $this->importJob->id)
            ->where('validation_status', 'valid')
            ->get();

        if ($validRecords->isEmpty()) {
            return;
        }

        $defaultCurrency = $this->getDefaultCurrency();
        $defaultCategory = $this->getDefaultExpenseCategory();

        foreach ($validRecords as $tempRecord) {
            try {
                $data = json_decode($tempRecord->transformed_data, true);
                $this->createExpense($tempRecord, $data, $defaultCurrency, $defaultCategory);
                $this->commitStats['total_processed']++;

            } catch (\Exception $e) {
                $this->handleRecordCommitFailure($tempRecord, $e);
            }
        }
    }

    /**
     * Create new expense
     */
    protected function createExpense($tempRecord, array $data, $defaultCurrency, $defaultCategory): void
    {
        $expenseData = $this->prepareExpenseData($data, $defaultCategory);
        $expenseData['company_id'] = $this->importJob->company_id;
        $expenseData['creator_id'] = $this->importJob->creator_id;
        $expenseData['currency_id'] = $defaultCurrency->id;

        $expense = Expense::create($expenseData);
        
        $this->commitStats['successfully_committed']++;
        $this->commitStats['records_created']['expenses'][] = $expense->id;

        ImportLog::create([
            'import_job_id' => $this->importJob->id,
            'log_type' => ImportLog::LOG_RECORD_COMMITTED,
            'severity' => ImportLog::SEVERITY_INFO,
            'message' => "Expense created: {$expense->expense_number}",
            'detailed_message' => "Successfully created expense from row {$tempRecord->row_number}",
            'entity_type' => ImportLog::ENTITY_EXPENSE,
            'entity_id' => $expense->id,
            'row_number' => $tempRecord->row_number,
            'process_stage' => 'committing',
        ]);
    }

    /**
     * Prepare expense data for creation
     */
    protected function prepareExpenseData(array $data, $defaultCategory): array
    {
        return [
            'expense_number' => 'EXP-' . uniqid(),
            'expense_date' => $data['expense_date'] ?? now()->format('Y-m-d'),
            'amount' => isset($data['amount']) ? (int) round(floatval($data['amount']) * 100) : 0,
            'expense_category_id' => $defaultCategory->id,
            'vendor' => $data['vendor'] ?? null,
            'notes' => $data['description'] ?? null,
            'base_amount' => isset($data['amount']) ? (int) round(floatval($data['amount']) * 100) : 0,
        ];
    }

    /**
     * Get duplicate handling strategy
     */
    protected function getDuplicateStrategy(): string
    {
        // This could come from import job configuration or company settings
        return 'skip'; // Default strategy
    }

    /**
     * Get default currency
     */
    protected function getDefaultCurrency()
    {
        return Currency::where('code', 'MKD')->first() ?? Currency::first();
    }

    /**
     * Get default payment method
     */
    protected function getDefaultPaymentMethod()
    {
        return PaymentMethod::where('company_id', $this->importJob->company_id)
            ->first() ?? PaymentMethod::create([
                'company_id' => $this->importJob->company_id,
                'name' => 'Cash',
                'type' => 'GENERAL',
            ]);
    }

    /**
     * Get default expense category
     */
    protected function getDefaultExpenseCategory()
    {
        return ExpenseCategory::where('company_id', $this->importJob->company_id)
            ->first() ?? ExpenseCategory::create([
                'company_id' => $this->importJob->company_id,
                'name' => 'General',
                'description' => 'General expenses',
            ]);
    }

    /**
     * Handle record commit failure
     */
    protected function handleRecordCommitFailure($tempRecord, \Exception $e): void
    {
        $this->commitStats['failed_commits']++;
        $this->commitStats['commit_errors'][] = [
            'row_number' => $tempRecord->row_number,
            'error' => $e->getMessage(),
        ];

        ImportLog::create([
            'import_job_id' => $this->importJob->id,
            'log_type' => ImportLog::LOG_RECORD_FAILED,
            'severity' => ImportLog::SEVERITY_ERROR,
            'message' => "Record commit failed (row {$tempRecord->row_number})",
            'detailed_message' => "Failed to commit record: {$e->getMessage()}",
            'entity_type' => $this->importJob->type,
            'row_number' => $tempRecord->row_number,
            'process_stage' => 'committing',
            'error_context' => [
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ],
        ]);

        Log::warning('Record commit failed', [
            'import_job_id' => $this->importJob->id,
            'row_number' => $tempRecord->row_number,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * Log commit results
     */
    protected function logCommitResults(float $processingTime): void
    {
        ImportLog::logJobCompleted($this->importJob);

        ImportLog::create([
            'import_job_id' => $this->importJob->id,
            'log_type' => ImportLog::LOG_JOB_COMPLETED,
            'severity' => ImportLog::SEVERITY_INFO,
            'message' => "Import completed: {$this->commitStats['successfully_committed']} records committed",
            'detailed_message' => "Import completed in " . round($processingTime, 2) . " seconds. {$this->commitStats['failed_commits']} failed, {$this->commitStats['duplicates_skipped']} duplicates skipped, {$this->commitStats['duplicates_updated']} duplicates updated.",
            'process_stage' => 'committing',
            'processing_time' => $processingTime,
            'records_processed' => $this->commitStats['total_processed'],
            'final_data' => $this->commitStats,
        ]);
    }

    /**
     * Clean up temporary data
     */
    protected function cleanupTempData(): void
    {
        try {
            // Clean up temp tables
            $tempModels = [
                ImportTempCustomer::class,
                ImportTempInvoice::class,
                ImportTempItem::class,
                ImportTempPayment::class,
                ImportTempExpense::class,
            ];

            foreach ($tempModels as $model) {
                $model::where('import_job_id', $this->importJob->id)->delete();
            }

            // Clean up uploaded file
            if ($this->importJob->file_path) {
                \Storage::delete($this->importJob->file_path);
            }

            Log::info('Temporary data cleaned up', [
                'import_job_id' => $this->importJob->id,
            ]);

        } catch (\Exception $e) {
            Log::warning('Failed to clean up temporary data', [
                'import_job_id' => $this->importJob->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure
     */
    protected function handleJobFailure(\Exception $exception): void
    {
        Log::error('Import commit job failed', [
            'import_job_id' => $this->importJob->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'stats' => $this->commitStats,
        ]);

        // Mark import job as failed
        $this->importJob->markAsFailed(
            'Import commit failed: ' . $exception->getMessage(),
            [
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'stage' => 'committing',
                'stats' => $this->commitStats,
                'checkpoint' => $this->transactionCheckpoint,
            ]
        );

        // Log failure with rollback information
        ImportLog::logJobFailed($this->importJob, $exception->getMessage(), [
            'stage' => 'commit_rollback',
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'rollback_checkpoint' => $this->transactionCheckpoint,
            'partial_commit_stats' => $this->commitStats,
        ]);

        // Log rollback execution
        ImportLog::create([
            'import_job_id' => $this->importJob->id,
            'log_type' => ImportLog::LOG_ROLLBACK_EXECUTED,
            'severity' => ImportLog::SEVERITY_WARNING,
            'message' => 'Transaction rolled back due to commit failure',
            'detailed_message' => "All changes rolled back to checkpoint. Error: {$exception->getMessage()}",
            'process_stage' => 'rollback',
            'error_context' => [
                'checkpoint' => $this->transactionCheckpoint,
                'partial_stats' => $this->commitStats,
            ],
            'is_audit_required' => true,
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