<?php

namespace App\Http\Controllers\V1\Admin\Banking;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankImportLog;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Services\Banking\BankStatementOcrException;
use App\Services\Banking\BankStatementOcrService;
use App\Services\Banking\DeduplicationService;
use App\Services\Banking\ImportLoggingService;
use App\Services\Banking\Parsers\CsvParserFactory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Bank Import Controller
 *
 * Handles CSV file upload, preview, and import of bank transactions.
 * Uses the bank-specific CSV parsers to parse different bank formats.
 *
 * P0-03: Wired with ImportLoggingService for import analytics.
 * P0-11: Wired with DeduplicationService for fingerprint-based dedup.
 */
class BankImportController extends Controller
{
    private ?Company $currentCompany = null;

    /**
     * @param  ImportLoggingService  $loggingService  P0-03: Import logging
     * @param  DeduplicationService  $deduplicationService  P0-11: Transaction dedup
     * @param  BankStatementOcrService  $ocrService  OCR-based statement parsing
     */
    public function __construct(
        protected ImportLoggingService $loggingService,
        protected DeduplicationService $deduplicationService,
        protected BankStatementOcrService $ocrService,
    ) {}

    /**
     * Preview CSV import - parse file and show what will be imported
     */
    public function preview(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $importLog = null;

        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt,xls,xlsx,jpg,jpeg,png,pdf|max:10240', // 10MB max
                'bank_code' => 'required|string',
                'account_id' => 'required|integer',
            ]);

            $company = $this->resolveCompany($request);
            if (!$company) {
                return response()->json([
                    'error' => true,
                    'message' => 'Company not found',
                ], 404);
            }

            // Verify account belongs to company (P0-13: explicit tenant scope)
            $account = BankAccount::forCompany($company->id)
                ->where('id', $request->account_id)
                ->first();

            if (!$account) {
                return response()->json([
                    'error' => true,
                    'message' => 'Bank account not found',
                ], 404);
            }

            $file = $request->file('file');
            $isImage = $this->isImageFile($file);

            if ($isImage) {
                // OCR-based import for image files
                return $this->previewFromImage($request, $file, $company, $startTime);
            }

            // CSV/Excel import path
            $content = $this->readFileContent($file);

            // Get parser (auto-detect or specific bank)
            $bankCode = $request->bank_code;
            if ($bankCode === 'auto') {
                $parser = CsvParserFactory::detectParser($content);
            } else {
                $parser = CsvParserFactory::createByBankCode($bankCode);
            }

            // P0-03: Start import log (store original file to R2)
            $importLog = $this->loggingService->startImport(
                $company->id,
                $request->user()->id,
                $parser->getBankCode(),
                $file->getClientOriginalName(),
                (int) $file->getSize(),
                $file
            );

            // Parse transactions
            $transactions = $parser->parse($content);

            if (empty($transactions)) {
                $parseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
                $this->loggingService->failImport(
                    $importLog,
                    'No valid transactions found in the file',
                    $parseTimeMs
                );

                return response()->json([
                    'error' => true,
                    'message' => 'No valid transactions found in the file',
                ], 422);
            }

            // Check for duplicates using DeduplicationService
            $newTransactions = [];
            $duplicateCount = 0;

            foreach ($transactions as $tx) {
                // P0-11: Use DeduplicationService for fingerprint-based dedup
                $txForDedup = $this->prepareTransactionForDedup($tx, $account);
                $isDuplicate = $this->deduplicationService->isDuplicate($txForDedup, $company->id);

                $tx['is_duplicate'] = $isDuplicate;
                $newTransactions[] = $tx;

                if ($isDuplicate) {
                    $duplicateCount++;
                }
            }

            // Generate import ID for confirmation
            $importId = Str::uuid()->toString();

            // Cache the parsed transactions for confirmation step (15 minutes)
            Cache::put("import:{$importId}", [
                'account_id' => $account->id,
                'company_id' => $company->id,
                'transactions' => $newTransactions,
                'bank_code' => $parser->getBankCode(),
                'import_log_id' => $importLog->id,
            ], now()->addMinutes(15));

            // P0-03: Update log with preview results (keep as pending — not yet confirmed)
            $parseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            $importLog->update([
                'total_rows' => count($newTransactions),
                'parsed_rows' => count($newTransactions),
                'duplicate_rows' => $duplicateCount,
                'parse_time_ms' => $parseTimeMs,
                'status' => BankImportLog::STATUS_PENDING,
            ]);

            Log::info('CSV import preview generated', [
                'company_id' => $company->id,
                'account_id' => $account->id,
                'total' => count($newTransactions),
                'new' => count($newTransactions) - $duplicateCount,
                'duplicates' => $duplicateCount,
                'detected_bank' => $parser->getBankName(),
                'import_log_id' => $importLog->id,
            ]);

            return response()->json([
                'data' => [
                    'import_id' => $importId,
                    'total' => count($newTransactions),
                    'new' => count($newTransactions) - $duplicateCount,
                    'duplicates' => $duplicateCount,
                    'detected_bank' => $parser->getBankName(),
                    'transactions' => array_slice($newTransactions, 0, 10), // First 10 for preview
                    'import_log_id' => $importLog?->id,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Let Laravel return 422 with field errors
        } catch (\Exception $e) {
            $parseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            // P0-03: Log failure
            if ($importLog) {
                $this->loggingService->failImport($importLog, $e->getMessage(), $parseTimeMs);
            }

            Log::error('CSV import preview failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Failed to parse CSV file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm and execute the import
     */
    public function confirm(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $request->validate([
                'import_id' => 'required|string|uuid',
                'transactions' => 'sometimes|array',
                'transactions.*.transaction_date' => 'sometimes|date',
                'transactions.*.description' => 'sometimes|string|max:500',
                'transactions.*.amount' => 'sometimes|numeric',
                'transactions.*.counterparty_name' => 'sometimes|nullable|string|max:255',
                'transactions.*.excluded' => 'sometimes|boolean',
            ]);

            $importId = $request->import_id;
            $importData = Cache::get("import:{$importId}");

            if (!$importData) {
                return response()->json([
                    'error' => true,
                    'message' => 'Import session expired. Please upload the file again.',
                ], 422);
            }

            $company = $this->resolveCompany($request);
            if (!$company || $company->id !== $importData['company_id']) {
                return response()->json([
                    'error' => true,
                    'message' => 'Invalid import session',
                ], 403);
            }

            // Apply user edits to cached transactions if provided
            if ($request->has('transactions')) {
                $userEdits = $request->input('transactions');
                $cachedTx = $importData['transactions'];
                $editableFields = ['transaction_date', 'description', 'amount', 'counterparty_name'];

                foreach ($userEdits as $index => $edit) {
                    if (!isset($cachedTx[$index])) {
                        continue;
                    }
                    // Mark excluded transactions as duplicates so they're skipped
                    if (!empty($edit['excluded'])) {
                        $cachedTx[$index]['is_duplicate'] = true;

                        continue;
                    }
                    foreach ($editableFields as $field) {
                        if (array_key_exists($field, $edit)) {
                            $cachedTx[$index][$field] = $edit[$field];
                        }
                    }
                }
                $importData['transactions'] = $cachedTx;
            }

            // P0-13: Validate bank account belongs to the company (tenant-scoped)
            $account = BankAccount::forCompany($company->id)
                ->where('id', $importData['account_id'])
                ->first();
            if (!$account) {
                return response()->json([
                    'error' => true,
                    'message' => 'Bank account not found',
                ], 404);
            }

            // P0-03: Retrieve or create import log
            $importLog = null;
            if (!empty($importData['import_log_id'])) {
                $importLog = BankImportLog::forCompany($company->id)
                    ->where('id', $importData['import_log_id'])
                    ->first();
            }

            // P0-11: Prepare transactions for DeduplicationService
            $transactionsForDedup = [];
            foreach ($importData['transactions'] as $tx) {
                if ($tx['is_duplicate']) {
                    continue; // Skip duplicates identified in preview
                }
                $transactionsForDedup[] = $this->prepareTransactionForDedup($tx, $account);
            }

            // P0-11: Use DeduplicationService::importWithDedupe for atomic import
            $source = $importData['source'] ?? BankTransaction::SOURCE_CSV_IMPORT;
            $result = $this->deduplicationService->importWithDedupe(
                $transactionsForDedup,
                $company->id,
                $source
            );

            $duplicatesFromPreview = count(array_filter(
                $importData['transactions'],
                fn ($tx) => $tx['is_duplicate']
            ));

            // Clear the cached import data
            Cache::forget("import:{$importId}");

            // P0-03: Complete import log with final results
            $parseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            if ($importLog) {
                $this->loggingService->completeImport(
                    $importLog,
                    count($importData['transactions']),
                    count($importData['transactions']),
                    $result->created,
                    $duplicatesFromPreview + $result->duplicates,
                    $result->failed,
                    $result->errors ?: null,
                    $parseTimeMs
                );
            }

            // Recalculate current_balance from transactions
            $this->recalculateAccountBalance($account);

            Log::info('CSV import completed', [
                'company_id' => $company->id,
                'account_id' => $account->id,
                'imported' => $result->created,
                'duplicates' => $duplicatesFromPreview + $result->duplicates,
                'failed' => $result->failed,
                'import_log_id' => $importLog?->id,
            ]);

            return response()->json([
                'data' => [
                    'imported' => $result->created,
                    'duplicates' => $duplicatesFromPreview + $result->duplicates,
                    'failed' => $result->failed,
                    'import_log_id' => $importLog?->id,
                ],
            ]);
        } catch (\Exception $e) {
            $parseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            // P0-03: Log failure if we have an import log
            if (isset($importLog) && $importLog) {
                $this->loggingService->failImport($importLog, $e->getMessage(), $parseTimeMs);
            }

            Log::error('CSV import confirm failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of supported banks
     */
    public function supportedBanks(): JsonResponse
    {
        $banks = CsvParserFactory::getSupportedBanks();

        return response()->json([
            'data' => $banks,
        ]);
    }

    /**
     * Get paginated import history for the company.
     *
     * P0-03: Import logging analytics endpoint.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function importHistory(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);
        if (!$company) {
            return response()->json([
                'error' => true,
                'message' => 'Company not found',
            ], 404);
        }

        $query = BankImportLog::forCompany($company->id)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc');

        // Filter by bank code
        if ($request->filled('bank_code')) {
            $query->forBank($request->bank_code);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', Carbon::parse($request->from_date)->startOfDay());
        }
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', Carbon::parse($request->to_date)->endOfDay());
        }

        $perPage = min((int) ($request->per_page ?? 15), 100);
        $logs = $query->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Get aggregated import statistics for the company.
     *
     * P0-03: Import logging analytics endpoint.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function importStats(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);
        if (!$company) {
            return response()->json([
                'error' => true,
                'message' => 'Company not found',
            ], 404);
        }

        $stats = $this->loggingService->getStats(
            $company->id,
            $request->from_date,
            $request->to_date
        );

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Download the original bank statement file from storage.
     */
    public function downloadFile(Request $request, int $log): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $company = $this->resolveCompany($request);
        if (!$company) {
            return response()->json(['error' => true, 'message' => 'Company not found'], 404);
        }

        $importLog = BankImportLog::forCompany($company->id)->findOrFail($log);

        if (!$importLog->file_path) {
            return response()->json(['error' => true, 'message' => 'File not available'], 404);
        }

        $disk = config('filesystems.media_disk', 's3compat');

        if (!Storage::disk($disk)->exists($importLog->file_path)) {
            return response()->json(['error' => true, 'message' => 'File not found in storage'], 404);
        }

        return Storage::disk($disk)->download($importLog->file_path, $importLog->file_name);
    }

    /**
     * Preview import from an image file using OCR.
     */
    private function previewFromImage(
        Request $request,
        \Illuminate\Http\UploadedFile $file,
        Company $company,
        float $startTime
    ): JsonResponse {
        $importLog = null;

        try {
            $account = BankAccount::forCompany($company->id)
                ->where('id', $request->account_id)
                ->first();

            if (!$account) {
                return response()->json([
                    'error' => true,
                    'message' => 'Bank account not found',
                ], 404);
            }

            // P0-03: Start import log (store original file to R2)
            $importLog = $this->loggingService->startImport(
                $company->id,
                $request->user()->id,
                'ocr',
                $file->getClientOriginalName(),
                (int) $file->getSize(),
                $file
            );

            // Send file directly to OCR service (handles both images and PDFs natively)
            $ocrFilePath = $file->getRealPath();
            $ocrFileName = $file->getClientOriginalName();
            $ocrResult = $this->ocrService->parse($ocrFilePath, $ocrFileName);

            $transactions = $ocrResult['transactions'];

            if (empty($transactions)) {
                $parseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
                $this->loggingService->failImport(
                    $importLog,
                    'No transactions found in the bank statement image',
                    $parseTimeMs
                );

                return response()->json([
                    'error' => true,
                    'message' => 'No transactions could be extracted from the image. Please ensure the image is clear and shows a bank statement table.',
                ], 422);
            }

            // Check for duplicates
            $newTransactions = [];
            $duplicateCount = 0;

            foreach ($transactions as $tx) {
                $txForDedup = $this->prepareTransactionForDedup($tx, $account);
                $isDuplicate = $this->deduplicationService->isDuplicate($txForDedup, $company->id);

                $tx['is_duplicate'] = $isDuplicate;
                $newTransactions[] = $tx;

                if ($isDuplicate) {
                    $duplicateCount++;
                }
            }

            // Cache for confirmation step
            $importId = Str::uuid()->toString();
            Cache::put("import:{$importId}", [
                'account_id' => $account->id,
                'company_id' => $company->id,
                'transactions' => $newTransactions,
                'bank_code' => $ocrResult['bank_code'] ?? 'ocr',
                'import_log_id' => $importLog->id,
                'source' => BankTransaction::SOURCE_OCR_IMPORT,
            ], now()->addMinutes(15));

            $parseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            $importLog->update([
                'total_rows' => count($newTransactions),
                'parsed_rows' => count($newTransactions),
                'duplicate_rows' => $duplicateCount,
                'parse_time_ms' => $parseTimeMs,
                'status' => BankImportLog::STATUS_PENDING,
            ]);

            $detectedBank = $ocrResult['bank_name']
                ? $ocrResult['bank_name'] . ' (OCR)'
                : 'OCR Import';

            Log::info('Bank statement OCR preview generated', [
                'company_id' => $company->id,
                'account_id' => $account->id,
                'total' => count($newTransactions),
                'new' => count($newTransactions) - $duplicateCount,
                'duplicates' => $duplicateCount,
                'detected_bank' => $detectedBank,
                'confidence' => $ocrResult['confidence'],
                'import_log_id' => $importLog->id,
            ]);

            return response()->json([
                'data' => [
                    'import_id' => $importId,
                    'total' => count($newTransactions),
                    'new' => count($newTransactions) - $duplicateCount,
                    'duplicates' => $duplicateCount,
                    'detected_bank' => $detectedBank,
                    'transactions' => array_slice($newTransactions, 0, 10),
                    'import_log_id' => $importLog?->id,
                    'ocr_confidence' => $ocrResult['confidence'],
                ],
            ]);
        } catch (BankStatementOcrException $e) {
            $parseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            if ($importLog) {
                $this->loggingService->failImport($importLog, $e->getMessage(), $parseTimeMs);
            }

            Log::error('Bank statement OCR preview failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        } catch (\Exception $e) {
            $parseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            if ($importLog) {
                $this->loggingService->failImport($importLog, $e->getMessage(), $parseTimeMs);
            }

            Log::error('Bank statement OCR preview failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Failed to process bank statement image: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if the uploaded file is an image or PDF (for OCR processing).
     */
    private function isImageFile(\Illuminate\Http\UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return in_array($extension, ['jpg', 'jpeg', 'png', 'pdf']);
    }

    /**
     * Read file content, converting Excel (xls/xlsx) to CSV if needed.
     */
    private function readFileContent(\Illuminate\Http\UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['xls', 'xlsx'])) {
            $spreadsheet = IOFactory::load($file->getRealPath());

            $tmpFile = tempnam(sys_get_temp_dir(), 'bank_import_') . '.csv';
            $writer = IOFactory::createWriter($spreadsheet, 'Csv');
            $writer->setDelimiter(',');
            $writer->setEnclosure('"');
            $writer->setSheetIndex(0);
            $writer->save($tmpFile);

            $content = file_get_contents($tmpFile);
            unlink($tmpFile);

            return $content;
        }

        return file_get_contents($file->getRealPath());
    }

    /**
     * Prepare a parsed transaction array for the DeduplicationService.
     *
     * Maps CSV-parsed fields to the format expected by BankTransaction model
     * and DeduplicationService (which needs bank_account_id, company_id, etc.).
     *
     * @param  array  $tx  Parsed transaction data from CSV parser
     * @param  BankAccount  $account  The target bank account
     * @return array Data suitable for DeduplicationService::importWithDedupe()
     */
    private function prepareTransactionForDedup(array $tx, BankAccount $account): array
    {
        $amount = (float) ($tx['amount'] ?? 0);
        $isCredit = $amount > 0;

        $transactionDate = $tx['transaction_date'] ?? null;
        if ($transactionDate instanceof Carbon) {
            $transactionDate = $transactionDate->format('Y-m-d');
        }

        $bookingDate = $tx['booking_date'] ?? $transactionDate;
        if ($bookingDate instanceof Carbon) {
            $bookingDate = $bookingDate->format('Y-m-d');
        }

        $valueDate = $tx['value_date'] ?? $transactionDate;
        if ($valueDate instanceof Carbon) {
            $valueDate = $valueDate->format('Y-m-d');
        }

        $reference = $tx['reference'] ?? $this->generateReference($tx);

        return [
            'bank_account_id' => $account->id,
            'company_id' => $account->company_id,
            'transaction_reference' => $reference,
            'external_reference' => $tx['external_reference'] ?? $reference,
            'external_transaction_id' => $tx['external_transaction_id'] ?? null,
            'amount' => abs($amount),
            'currency' => $tx['currency'] ?? $account->currency->code ?? 'MKD',
            'transaction_type' => $isCredit ? BankTransaction::TYPE_CREDIT : BankTransaction::TYPE_DEBIT,
            'booking_status' => BankTransaction::BOOKING_BOOKED,
            'transaction_date' => $transactionDate,
            'booking_date' => $bookingDate,
            'value_date' => $valueDate,
            'description' => $tx['description'] ?? '',
            'remittance_info' => $tx['remittance_info'] ?? $tx['description'] ?? '',
            'debtor_name' => $isCredit ? ($tx['counterparty_name'] ?? null) : null,
            'creditor_name' => !$isCredit ? ($tx['counterparty_name'] ?? null) : null,
            'debtor_account' => $isCredit ? ($tx['counterparty_account'] ?? null) : null,
            'creditor_account' => !$isCredit ? ($tx['counterparty_account'] ?? null) : null,
            'processing_status' => BankTransaction::STATUS_UNPROCESSED,
            'source' => BankTransaction::SOURCE_CSV_IMPORT,
            'raw_data' => $tx,
        ];
    }

    /**
     * Generate a unique reference for a transaction
     */
    private function generateReference(array $data): string
    {
        $parts = [
            'csv',
            $data['transaction_date'] instanceof Carbon
                ? $data['transaction_date']->format('Y-m-d')
                : ($data['transaction_date'] ?? date('Y-m-d')),
            $data['amount'] ?? '0',
            substr(md5(json_encode($data)), 0, 8),
        ];

        return implode('-', $parts);
    }

    /**
     * Resolve the current company from the request.
     *
     * P0-13: Validates that the authenticated user has access to the requested company.
     * Never returns a company the user doesn't belong to.
     */
    private function resolveCompany(Request $request): ?Company
    {
        if ($this->currentCompany) {
            return $this->currentCompany;
        }

        $user = $request->user();

        if (! $user) {
            return null;
        }

        $companyIdHeader = $request->header('company');
        $companyId = $companyIdHeader !== null ? (int) $companyIdHeader : null;
        $company = null;

        if ($companyId) {
            // Super admins can access any company
            if ($user->role === 'super admin') {
                $company = Company::find($companyId);
            } elseif ($user->hasCompany($companyId)) {
                $company = $user->companies()->where('companies.id', $companyId)->first();
            }
        }

        if (! $company) {
            $company = $user->companies()->first();
        }

        return $this->currentCompany = $company;
    }

    /**
     * Recalculate bank account current_balance from opening_balance + sum of transactions.
     *
     * Credits are positive, debits are negative. Transaction amounts are stored as
     * absolute values with a separate transaction_type column, so we reconstruct
     * the signed sum: SUM(credits) - SUM(debits).
     */
    private function recalculateAccountBalance(BankAccount $account): void
    {
        $credits = BankTransaction::where('bank_account_id', $account->id)
            ->where('transaction_type', BankTransaction::TYPE_CREDIT)
            ->sum('amount');

        $debits = BankTransaction::where('bank_account_id', $account->id)
            ->where('transaction_type', BankTransaction::TYPE_DEBIT)
            ->sum('amount');

        $balance = (float) $account->opening_balance + (float) $credits - (float) $debits;

        $account->update(['current_balance' => round($balance, 2)]);
    }
}

