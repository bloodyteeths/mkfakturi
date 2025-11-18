<?php

namespace App\Services\Banking;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Support\Facades\Log;
use Jejik\MT940\Exception\NoParserFoundException;
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
     * @param  string  $filePath  Path to MT940 file
     * @param  BankAccount  $account  Bank account to associate transactions with
     * @return int Number of transactions imported
     *
     * @throws \Exception If parsing fails
     */
    public function parseFile(string $filePath, BankAccount $account): int
    {
        if (! file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        try {
            $contents = file_get_contents($filePath);

            $reader = new Reader;
            $statements = $reader->getStatements($contents);

            return $this->importStatementsFromReader($statements, $account, basename($filePath));
        } catch (NoParserFoundException $e) {
            Log::warning('MT940 parser not found, using generic fallback parser', [
                'file' => basename($filePath),
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return $this->parseFallbackMt940($contents ?? '', $account, basename($filePath));
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
     * Import MT940 statements returned by the library reader.
     *
     * @param  iterable<\Jejik\MT940\StatementInterface>  $statements
     */
    protected function importStatementsFromReader(iterable $statements, BankAccount $account, string $fileName): int
    {
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
            'file' => $fileName,
            'account_id' => $account->id,
            'imported' => $imported,
            'duplicates' => $duplicates,
        ]);

        return $imported;
    }

    /**
     * Generic fallback parser for simple MT940 statements when no bank-specific
     * parser is available from the jejik/mt940 library.
     *
     * This implements a minimal subset of MT940 sufficient for our unit tests:
     * it looks for :61: (transaction) and :86: (description) lines, extracts
     * date, amount, and a reference if present, and creates BankTransaction
     * records while preserving idempotency by reference.
     */
    protected function parseFallbackMt940(string $text, BankAccount $account, string $fileName): int
    {
        if (trim($text) === '') {
            return 0;
        }

        // Normalise line endings
        $normalized = preg_replace("/(\r\n|\r|\n)/", "\n", $text);
        $lines = explode("\n", (string) $normalized);

        $transactions = [];
        $currentIndex = -1;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, ':61:')) {
                $currentIndex++;
                $payload = substr($line, 4);

                $transactions[$currentIndex] = $this->parseFallbackTransactionLine($payload);

                continue;
            }

            if ($currentIndex >= 0 && str_starts_with($line, ':86:')) {
                $description = trim(substr($line, 4));
                $transactions[$currentIndex]['description'] = $description;
            }
        }

        $imported = 0;
        $duplicates = 0;

        foreach ($transactions as $tx) {
            $reference = $tx['reference'] ?? null;

            if ($reference) {
                $exists = BankTransaction::where('bank_account_id', $account->id)
                    ->where('transaction_reference', $reference)
                    ->exists();

                if ($exists) {
                    $duplicates++;

                    continue;
                }
            }

            $amount = $tx['amount'] ?? 0.0;
            $isCredit = $amount > 0;

            BankTransaction::create([
                'bank_account_id' => $account->id,
                'company_id' => $account->company_id,
                'transaction_reference' => $reference ?? uniqid('mt940_', true),
                'amount' => abs($amount),
                'currency' => $account->currency->code ?? 'MKD',
                'transaction_type' => $isCredit ? BankTransaction::TYPE_CREDIT : BankTransaction::TYPE_DEBIT,
                'booking_status' => BankTransaction::BOOKING_BOOKED,
                'transaction_date' => $tx['value_date'] ?? now(),
                'booking_date' => $tx['book_date'] ?? ($tx['value_date'] ?? now()),
                'value_date' => $tx['value_date'] ?? now(),
                'description' => $tx['description'] ?? null,
                'remittance_info' => $tx['description'] ?? null,
                'debtor_name' => $isCredit ? null : null,
                'creditor_name' => $isCredit ? null : null,
                'debtor_account' => null,
                'creditor_account' => null,
                'processing_status' => BankTransaction::STATUS_UNPROCESSED,
                'source' => BankTransaction::SOURCE_CSV_IMPORT,
                'raw_data' => $tx,
            ]);

            $imported++;
        }

        Log::info('MT940 file parsed with generic fallback parser', [
            'file' => $fileName,
            'account_id' => $account->id,
            'imported' => $imported,
            'duplicates' => $duplicates,
        ]);

        return $imported;
    }

    /**
     * Parse a single :61: transaction line from an MT940 statement.
     *
     * The format is loosely:
     *   YYMMDD[MMDD]?[CD][R]?amount[N...]//reference
     *
     * We only need amount, reference and dates for our use-case.
     */
    protected function parseFallbackTransactionLine(string $payload): array
    {
        $result = [
            'amount' => 0.0,
        ];

        // Basic pattern: date, optional entry date, debit/credit flag, remaining payload
        if (preg_match('/^(?<valueDate>\d{6})(?<entryDate>\d{4})?(?<dc>[CD])(?<rest>.+)$/', $payload, $matches)) {
            $valueDate = $matches['valueDate'] ?? null;
            $entryDate = $matches['entryDate'] ?? null;
            $dc = $matches['dc'] ?? 'C';

            if ($valueDate) {
                try {
                    $yearPrefix = (int) substr($valueDate, 0, 2) > 70 ? '19' : '20';
                    $result['value_date'] = \Carbon\Carbon::createFromFormat('Ymd', $yearPrefix.$valueDate);
                } catch (\Exception $e) {
                    $result['value_date'] = now();
                }
            }

            if ($entryDate) {
                try {
                    $yearPrefix = isset($result['value_date'])
                        ? $result['value_date']->format('Y')
                        : date('Y');
                    $result['book_date'] = \Carbon\Carbon::createFromFormat('Ymd', $yearPrefix.$entryDate);
                } catch (\Exception $e) {
                    $result['book_date'] = $result['value_date'] ?? now();
                }
            }

            // Extract amount and reference from the full payload
            if (preg_match('/^[0-9]{6}(?:[0-9]{4})?(?<dc2>[CD])[A-Z]?(?<amount>[0-9,]+).*\/\/(?<reference>[A-Z0-9\-]+)/', $payload, $restMatches)) {
                $amount = (float) str_replace(',', '.', $restMatches['amount']);
                $isDebit = ($dc === 'D') || (($restMatches['dc2'] ?? '') === 'D');

                $result['amount'] = $isDebit ? -$amount : $amount;
                $result['reference'] = $restMatches['reference'] ?? null;
            }
        }

        return $result;
    }

    /**
     * Create a bank transaction from MT940 transaction object
     *
     * @param  \Jejik\MT940\Transaction  $mt940Transaction  MT940 transaction
     * @param  BankAccount  $account  Bank account
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
                'creditor_name' => ! $isCredit ? $this->extractCounterpartyName($mt940Transaction) : null,
                'debtor_account' => $isCredit ? $this->extractCounterpartyAccount($mt940Transaction) : null,
                'creditor_account' => ! $isCredit ? $this->extractCounterpartyAccount($mt940Transaction) : null,
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
     * @param  \Jejik\MT940\Transaction  $transaction
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
     * @param  \Jejik\MT940\Transaction  $transaction
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
     * @param  string  $filePath  Path to CSV file
     * @param  BankAccount  $account  Bank account
     * @param  array  $columnMapping  Column name mapping
     * @return int Number of transactions imported
     *
     * @throws \Exception If parsing fails
     */
    public function parseCsv(string $filePath, BankAccount $account, array $columnMapping = []): int
    {
        if (! file_exists($filePath)) {
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
     * @param  array  $data  CSV row data
     * @param  BankAccount  $account  Bank account
     * @param  array  $mapping  Column mapping
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
                'creditor_name' => ! $isCredit ? ($data[$mapping['counterparty']] ?? null) : null,
                'debtor_account' => $isCredit ? ($data[$mapping['counterparty_account']] ?? null) : null,
                'creditor_account' => ! $isCredit ? ($data[$mapping['counterparty_account']] ?? null) : null,
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
     * @param  string  $dateString  Date string
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
