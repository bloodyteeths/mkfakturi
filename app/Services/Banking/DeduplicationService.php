<?php

namespace App\Services\Banking;

use App\Models\BankTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * P0-11: Transaction Deduplication Service
 *
 * Provides atomic deduplication for bank transaction imports.
 * Uses fingerprints (SHA256 hashes) and the unique constraint
 * on (company_id, fingerprint) to prevent duplicate transactions
 * across CSV, email, and PSD2 import sources.
 */
class DeduplicationService
{
    /**
     * @param  TransactionFingerprint  $fingerprinter  The fingerprint generator
     */
    public function __construct(
        protected TransactionFingerprint $fingerprinter
    ) {}

    /**
     * Check if a transaction already exists for the given company.
     *
     * Looks up by fingerprint in the bank_transactions table.
     *
     * @param  array  $tx  Transaction data array
     * @param  int  $companyId  The company to check against
     * @return bool True if a transaction with this fingerprint already exists
     */
    public function isDuplicate(array $tx, int $companyId): bool
    {
        $tx['company_id'] = $companyId;
        $fingerprint = $this->fingerprinter->generate($tx);

        return BankTransaction::where('company_id', $companyId)
            ->where('fingerprint', $fingerprint)
            ->exists();
    }

    /**
     * Import an array of transactions with automatic deduplication.
     *
     * Uses firstOrCreate for atomic insert-or-skip semantics.
     * Each transaction is fingerprinted and checked against existing
     * records. Duplicates are silently skipped.
     *
     * @param  array  $transactions  Array of transaction data arrays
     * @param  int  $companyId  The company ID to import for
     * @param  string  $source  Import source (psd2, csv_import, manual)
     * @return ImportResult Value object with import statistics
     */
    public function importWithDedupe(array $transactions, int $companyId, string $source): ImportResult
    {
        $created = 0;
        $duplicates = 0;
        $failed = 0;
        $errors = [];
        $createdIds = [];
        $duplicateFingerprints = [];

        foreach ($transactions as $index => $tx) {
            try {
                $tx['company_id'] = $companyId;
                $tx['source'] = $source;

                $fingerprint = $this->fingerprinter->generate($tx);
                $tx['fingerprint'] = $fingerprint;

                // Set external_transaction_id if present in source data
                if (! empty($tx['external_transaction_id'])) {
                    $tx['external_transaction_id'] = $tx['external_transaction_id'];
                }

                // Use a DB transaction for atomicity
                $result = DB::transaction(function () use ($tx, $companyId, $fingerprint) {
                    // firstOrCreate is atomic: either finds existing or creates new
                    // The unique constraint on (company_id, fingerprint) provides DB-level safety
                    $record = BankTransaction::firstOrCreate(
                        [
                            'company_id' => $companyId,
                            'fingerprint' => $fingerprint,
                        ],
                        $this->prepareTransactionData($tx)
                    );

                    return $record;
                });

                if ($result->wasRecentlyCreated) {
                    $created++;
                    $createdIds[] = $result->id;
                } else {
                    $duplicates++;
                    $duplicateFingerprints[] = $fingerprint;
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = sprintf(
                    'Transaction #%d failed: %s',
                    $index,
                    $e->getMessage()
                );
                Log::warning('P0-11: Transaction import failed', [
                    'index' => $index,
                    'company_id' => $companyId,
                    'source' => $source,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $result = new ImportResult(
            created: $created,
            duplicates: $duplicates,
            failed: $failed,
            errors: $errors,
            createdIds: $createdIds,
            duplicateFingerprints: $duplicateFingerprints,
        );

        Log::info('P0-11: ' . $result->summary(), [
            'company_id' => $companyId,
            'source' => $source,
        ]);

        return $result;
    }

    /**
     * Prepare transaction data for insertion.
     *
     * Filters and maps raw transaction data to BankTransaction fillable attributes.
     *
     * @param  array  $tx  Raw transaction data
     * @return array Cleaned data ready for BankTransaction::create()
     */
    protected function prepareTransactionData(array $tx): array
    {
        // Only include keys that are in the model's fillable list
        $fillable = (new BankTransaction)->getFillable();

        return array_intersect_key($tx, array_flip($fillable));
    }
}

// CLAUDE-CHECKPOINT
