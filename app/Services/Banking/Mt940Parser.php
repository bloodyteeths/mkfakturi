<?php

namespace App\Services\Banking;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Support\Facades\Log;
use Jejik\MT940\Reader;

/**
 * MT940 Parser Service
 *
 * Parses MT940 bank statement files (Swift standard format)
 * Used as fallback when PSD2 OAuth is not available
 *
 * Supports:
 * - MT940 text files
 * - CSV exports from Macedonian banks
 * - Idempotency via transaction reference
 */
class Mt940Parser
{
    /**
     * Parse MT940 file and create bank transactions
     *
     * @param string $filePath Path to MT940 file
     * @param BankAccount $account Bank account to associate transactions with
     * @return int Number of transactions imported
     * @throws \Exception If parsing fails
     */
    public function parseFile(string $filePath, BankAccount $account): int
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        try {
            $reader = new Reader();
            $statements = $reader->getStatements(file_get_contents($filePath));

            $imported = 0;
            $duplicates = 0;

            foreach ($statements as $statement) {
                foreach ($statement->getTransactions() as $transaction) {
                    $result = $this->createTransaction($transaction, $account);

                    if ($result === 'created') {
                        $imported++;
                    } elseif ($result === 'duplicate') {
                        $duplicates++;
                    }
                }
            }

            Log::info('MT940 file parsed', [
                'file' => basename($filePath),
                'account_id' => $account->id,
                'imported' => $imported,
                'duplicates' => $duplicates,
            ]);

            return $imported;
        } catch (\Exception $e) {
            Log::error('MT940 parsing failed', [
                'file' => basename($filePath),
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create a bank transaction from MT940 transaction object
     *
     * @param \Jejik\MT940\Transaction $mt940Transaction MT940 transaction
     * @param BankAccount $account Bank account
     * @return string Result: 'created', 'duplicate', or 'failed'
     */
    protected function createTransaction($mt940Transaction, BankAccount $account): string
    {
        try {
            $reference = $mt940Transaction->getReference();

            // Check for duplicate by reference
            $exists = BankTransaction::where('bank_account_id', $account->id)
                ->where('transaction_reference', $reference)
                ->exists();

            if ($exists) {
                return 'duplicate';
            }

            $amount = $mt940Transaction->getAmount();
            $isCredit = $amount > 0;

            BankTransaction::create([
                'bank_account_id' => $account->id,
                'company_id' => $account->company_id,
                'transaction_reference' => $reference,
                'amount' => abs($amount),
                'currency' => $account->currency->code ?? 'MKD',
                'transaction_type' => $isCredit ? BankTransaction::TYPE_CREDIT : BankTransaction::TYPE_DEBIT,
                'booking_status' => BankTransaction::BOOKING_BOOKED,
                'transaction_date' => $mt940Transaction->getValueDate(),
                'booking_date' => $mt940Transaction->getBookDate() ?? $mt940Transaction->getValueDate(),
                'value_date' => $mt940Transaction->getValueDate(),
                'description' => $mt940Transaction->getDescription(),
                'remittance_info' => $mt940Transaction->getDescription(),
                'debtor_name' => $isCredit ? $this->extractCounterpartyName($mt940Transaction) : null,
                'creditor_name' => !$isCredit ? $this->extractCounterpartyName($mt940Transaction) : null,
                'debtor_account' => $isCredit ? $this->extractCounterpartyAccount($mt940Transaction) : null,
                'creditor_account' => !$isCredit ? $this->extractCounterpartyAccount($mt940Transaction) : null,
                'processing_status' => BankTransaction::STATUS_UNPROCESSED,
                'source' => BankTransaction::SOURCE_CSV_IMPORT,
                'raw_data' => [
                    'reference' => $reference,
                    'description' => $mt940Transaction->getDescription(),
                    'amount' => $amount,
                    'value_date' => $mt940Transaction->getValueDate()->format('Y-m-d'),
                ],
            ]);

            return 'created';
        } catch (\Exception $e) {
            Log::warning('Failed to create transaction from MT940', [
                'reference' => $reference ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return 'failed';
        }
    }

    /**
     * Extract counterparty name from MT940 transaction
     *
     * @param \Jejik\MT940\Transaction $transaction
     * @return string|null Counterparty name
     */
    protected function extractCounterpartyName($transaction): ?string
    {
        // Try to get from transaction object
        $name = $transaction->getName();

        if ($name) {
            return trim($name);
        }

        // Fallback: Parse from description
        $description = $transaction->getDescription();
        if (preg_match('/NAME:([^|]+)/', $description, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Extract counterparty account from MT940 transaction
     *
     * @param \Jejik\MT940\Transaction $transaction
     * @return string|null Counterparty account number
     */
    protected function extractCounterpartyAccount($transaction): ?string
    {
        // Try to get contraAccount (some MT940 parsers provide this)
        if (method_exists($transaction, 'getContraAccount')) {
            $account = $transaction->getContraAccount();
            if ($account) {
                return method_exists($account, 'getNumber') ? $account->getNumber() : $account;
            }
        }

        // Fallback: Parse from description
        $description = $transaction->getDescription();
        if (preg_match('/ACCT:([0-9A-Z]+)/', $description, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Parse CSV file and create bank transactions
     * Alternative to MT940 for banks that don't support MT940 format
     *
     * @param string $filePath Path to CSV file
     * @param BankAccount $account Bank account
     * @param array $columnMapping Column name mapping
     * @return int Number of transactions imported
     * @throws \Exception If parsing fails
     */
    public function parseCsv(string $filePath, BankAccount $account, array $columnMapping = []): int
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        try {
            $imported = 0;
            $duplicates = 0;

            $handle = fopen($filePath, 'r');
            $headers = fgetcsv($handle);

            // Default column mapping for Macedonian banks
            $defaultMapping = [
                'date' => 'Datum',
                'reference' => 'Referenca',
                'amount' => 'Iznos',
                'description' => 'Opis',
                'counterparty' => 'Partner',
                'counterparty_account' => 'Smetka',
            ];

            $mapping = array_merge($defaultMapping, $columnMapping);

            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($headers, $row);

                $result = $this->createTransactionFromCsv($data, $account, $mapping);

                if ($result === 'created') {
                    $imported++;
                } elseif ($result === 'duplicate') {
                    $duplicates++;
                }
            }

            fclose($handle);

            Log::info('CSV file parsed', [
                'file' => basename($filePath),
                'account_id' => $account->id,
                'imported' => $imported,
                'duplicates' => $duplicates,
            ]);

            return $imported;
        } catch (\Exception $e) {
            Log::error('CSV parsing failed', [
                'file' => basename($filePath),
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            if (isset($handle)) {
                fclose($handle);
            }

            throw $e;
        }
    }

    /**
     * Create a bank transaction from CSV row
     *
     * @param array $data CSV row data
     * @param BankAccount $account Bank account
     * @param array $mapping Column mapping
     * @return string Result: 'created', 'duplicate', or 'failed'
     */
    protected function createTransactionFromCsv(array $data, BankAccount $account, array $mapping): string
    {
        try {
            $reference = $data[$mapping['reference']] ?? uniqid('csv_');

            // Check for duplicate
            $exists = BankTransaction::where('bank_account_id', $account->id)
                ->where('transaction_reference', $reference)
                ->exists();

            if ($exists) {
                return 'duplicate';
            }

            $amount = (float) str_replace([',', ' '], '', $data[$mapping['amount']] ?? 0);
            $isCredit = $amount > 0;

            BankTransaction::create([
                'bank_account_id' => $account->id,
                'company_id' => $account->company_id,
                'transaction_reference' => $reference,
                'amount' => abs($amount),
                'currency' => $account->currency->code ?? 'MKD',
                'transaction_type' => $isCredit ? BankTransaction::TYPE_CREDIT : BankTransaction::TYPE_DEBIT,
                'booking_status' => BankTransaction::BOOKING_BOOKED,
                'transaction_date' => $this->parseDate($data[$mapping['date']] ?? now()),
                'value_date' => $this->parseDate($data[$mapping['date']] ?? now()),
                'description' => $data[$mapping['description']] ?? '',
                'debtor_name' => $isCredit ? ($data[$mapping['counterparty']] ?? null) : null,
                'creditor_name' => !$isCredit ? ($data[$mapping['counterparty']] ?? null) : null,
                'debtor_account' => $isCredit ? ($data[$mapping['counterparty_account']] ?? null) : null,
                'creditor_account' => !$isCredit ? ($data[$mapping['counterparty_account']] ?? null) : null,
                'processing_status' => BankTransaction::STATUS_UNPROCESSED,
                'source' => BankTransaction::SOURCE_CSV_IMPORT,
                'raw_data' => $data,
            ]);

            return 'created';
        } catch (\Exception $e) {
            Log::warning('Failed to create transaction from CSV', [
                'reference' => $reference ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return 'failed';
        }
    }

    /**
     * Parse date string to Carbon instance
     *
     * @param string $dateString Date string
     * @return \Carbon\Carbon
     */
    protected function parseDate(string $dateString): \Carbon\Carbon
    {
        try {
            // Try common formats
            return \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            // Fallback to today if parsing fails
            return now();
        }
    }
}

// CLAUDE-CHECKPOINT
