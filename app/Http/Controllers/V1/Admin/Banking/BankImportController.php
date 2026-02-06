<?php

namespace App\Http\Controllers\V1\Admin\Banking;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Services\Banking\Parsers\CsvParserFactory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Bank Import Controller
 *
 * Handles CSV file upload, preview, and import of bank transactions.
 * Uses the bank-specific CSV parsers to parse different bank formats.
 */
class BankImportController extends Controller
{
    private ?Company $currentCompany = null;

    /**
     * Preview CSV import - parse file and show what will be imported
     */
    public function preview(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
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

            // Read file content
            $content = file_get_contents($request->file('file')->getRealPath());

            // Get parser (auto-detect or specific bank)
            $bankCode = $request->bank_code;
            if ($bankCode === 'auto') {
                $parser = CsvParserFactory::detectParser($content);
            } else {
                $parser = CsvParserFactory::createByBankCode($bankCode);
            }

            // Parse transactions
            $transactions = $parser->parse($content);

            if (empty($transactions)) {
                return response()->json([
                    'error' => true,
                    'message' => 'No valid transactions found in the file',
                ], 422);
            }

            // Check for duplicates
            $newTransactions = [];
            $duplicateCount = 0;

            foreach ($transactions as $tx) {
                $isDuplicate = $this->isDuplicateTransaction($tx, $account);
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
            ], now()->addMinutes(15));

            Log::info('CSV import preview generated', [
                'company_id' => $company->id,
                'account_id' => $account->id,
                'total' => count($newTransactions),
                'new' => count($newTransactions) - $duplicateCount,
                'duplicates' => $duplicateCount,
                'detected_bank' => $parser->getBankName(),
            ]);

            return response()->json([
                'data' => [
                    'import_id' => $importId,
                    'total' => count($newTransactions),
                    'new' => count($newTransactions) - $duplicateCount,
                    'duplicates' => $duplicateCount,
                    'detected_bank' => $parser->getBankName(),
                    'transactions' => array_slice($newTransactions, 0, 10), // First 10 for preview
                ],
            ]);
        } catch (\Exception $e) {
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
        try {
            $request->validate([
                'import_id' => 'required|string|uuid',
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

            // Import transactions
            $imported = 0;
            $duplicates = 0;
            $failed = 0;

            foreach ($importData['transactions'] as $tx) {
                if ($tx['is_duplicate']) {
                    $duplicates++;
                    continue;
                }

                try {
                    $this->createTransaction($tx, $account);
                    $imported++;
                } catch (\Exception $e) {
                    Log::warning('Failed to import transaction', [
                        'error' => $e->getMessage(),
                        'transaction' => $tx,
                    ]);
                    $failed++;
                }
            }

            // Clear the cached import data
            Cache::forget("import:{$importId}");

            Log::info('CSV import completed', [
                'company_id' => $company->id,
                'account_id' => $account->id,
                'imported' => $imported,
                'duplicates' => $duplicates,
                'failed' => $failed,
            ]);

            return response()->json([
                'data' => [
                    'imported' => $imported,
                    'duplicates' => $duplicates,
                    'failed' => $failed,
                ],
            ]);
        } catch (\Exception $e) {
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
     * Check if a transaction is a duplicate
     */
    private function isDuplicateTransaction(array $data, BankAccount $account): bool
    {
        $reference = $data['reference'] ?? $this->generateReference($data);

        return BankTransaction::where('bank_account_id', $account->id)
            ->where(function ($query) use ($reference, $data) {
                $query->where('transaction_reference', $reference)
                    ->orWhere('external_reference', $reference);

                // Also check by amount + date + description combo
                if (isset($data['transaction_date']) && isset($data['amount'])) {
                    $date = $data['transaction_date'];
                    if ($date instanceof Carbon) {
                        $date = $date->format('Y-m-d');
                    }

                    $query->orWhere(function ($q) use ($data, $date) {
                        $q->where('transaction_date', $date)
                            ->where('amount', abs($data['amount']))
                            ->where('description', $data['description'] ?? '');
                    });
                }
            })
            ->exists();
    }

    /**
     * Create a bank transaction from parsed data
     */
    private function createTransaction(array $data, BankAccount $account): BankTransaction
    {
        $reference = $data['reference'] ?? $this->generateReference($data);
        $amount = (float) $data['amount'];
        $isCredit = $amount > 0;

        $transactionDate = $data['transaction_date'];
        if ($transactionDate instanceof Carbon) {
            $transactionDate = $transactionDate->format('Y-m-d');
        }

        $bookingDate = $data['booking_date'] ?? $transactionDate;
        if ($bookingDate instanceof Carbon) {
            $bookingDate = $bookingDate->format('Y-m-d');
        }

        $valueDate = $data['value_date'] ?? $transactionDate;
        if ($valueDate instanceof Carbon) {
            $valueDate = $valueDate->format('Y-m-d');
        }

        return BankTransaction::create([
            'bank_account_id' => $account->id,
            'company_id' => $account->company_id,
            'transaction_reference' => $reference,
            'external_reference' => $data['external_reference'] ?? $reference,
            'amount' => abs($amount),
            'currency' => $data['currency'] ?? $account->currency->code ?? 'MKD',
            'transaction_type' => $isCredit ? BankTransaction::TYPE_CREDIT : BankTransaction::TYPE_DEBIT,
            'booking_status' => BankTransaction::BOOKING_BOOKED,
            'transaction_date' => $transactionDate,
            'booking_date' => $bookingDate,
            'value_date' => $valueDate,
            'description' => $data['description'] ?? '',
            'remittance_info' => $data['remittance_info'] ?? $data['description'] ?? '',
            'debtor_name' => $isCredit ? ($data['counterparty_name'] ?? null) : null,
            'creditor_name' => !$isCredit ? ($data['counterparty_name'] ?? null) : null,
            'debtor_account' => $isCredit ? ($data['counterparty_account'] ?? null) : null,
            'creditor_account' => !$isCredit ? ($data['counterparty_account'] ?? null) : null,
            'processing_status' => BankTransaction::STATUS_UNPROCESSED,
            'source' => BankTransaction::SOURCE_CSV_IMPORT,
            'raw_data' => $data,
        ]);
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

        // P0-13: Always verify user has access to the requested company
        if ($companyId && $user->hasCompany($companyId)) {
            $company = $user->companies()->where('companies.id', $companyId)->first();
        }

        if (! $company) {
            $company = $user->companies()->first();
        }

        return $this->currentCompany = $company;
    }
}

// CLAUDE-CHECKPOINT
