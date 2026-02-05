<?php

namespace App\Services\Banking\Parsers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Carbon\Carbon;
use League\Csv\Reader;
use League\Csv\Statement;

/**
 * Abstract CSV Parser
 *
 * Base class for bank-specific CSV parsers using League CSV.
 * Handles encoding detection, parsing, and transaction creation.
 */
abstract class AbstractCsvParser implements BankParserInterface
{
    /**
     * Get the header row offset (0-based)
     * Override in subclass if headers are not on first row
     */
    protected function getHeaderOffset(): int
    {
        return 0;
    }

    /**
     * Map a CSV record to normalized transaction data
     *
     * @param array $record CSV row as associative array
     * @return array Normalized transaction data
     */
    abstract protected function mapRecord(array $record): array;

    /**
     * Get required column names for validation
     *
     * @return array List of required column names
     */
    abstract protected function getRequiredColumns(): array;

    /**
     * Parse CSV content and return normalized transaction data
     */
    public function parse(string $content): array
    {
        $content = $this->normalizeEncoding($content);

        $csv = Reader::fromString($content);
        $csv->setDelimiter($this->getDelimiter());
        $csv->setHeaderOffset($this->getHeaderOffset());

        $records = (new Statement())->process($csv);
        $transactions = [];

        foreach ($records as $index => $record) {
            try {
                $mapped = $this->mapRecord($record);
                if ($this->isValidTransaction($mapped)) {
                    $transactions[] = $mapped;
                }
            } catch (\Exception $e) {
                $this->log('warning', 'Failed to parse CSV row', [
                    'bank' => $this->getBankCode(),
                    'row' => $index + 1,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $transactions;
    }

    /**
     * Import transactions from CSV content into the database
     */
    public function import(string $content, BankAccount $account): array
    {
        $result = [
            'imported' => 0,
            'duplicates' => 0,
            'failed' => 0,
            'transactions' => [],
        ];

        $transactions = $this->parse($content);

        foreach ($transactions as $txData) {
            $status = $this->createTransaction($txData, $account);

            if ($status === 'created') {
                $result['imported']++;
                $result['transactions'][] = $txData;
            } elseif ($status === 'duplicate') {
                $result['duplicates']++;
            } else {
                $result['failed']++;
            }
        }

        $this->log('info', 'CSV import completed', [
            'bank' => $this->getBankCode(),
            'account_id' => $account->id,
            'imported' => $result['imported'],
            'duplicates' => $result['duplicates'],
            'failed' => $result['failed'],
        ]);

        return $result;
    }

    /**
     * Check if this parser can handle the given content
     */
    public function canParse(string $content): bool
    {
        try {
            $content = $this->normalizeEncoding($content);
            $csv = Reader::fromString($content);
            $csv->setDelimiter($this->getDelimiter());
            $csv->setHeaderOffset($this->getHeaderOffset());

            $headers = $csv->getHeader();
            $requiredColumns = $this->getRequiredColumns();

            // Check if all required columns exist
            foreach ($requiredColumns as $column) {
                $found = false;
                foreach ($headers as $header) {
                    if ($this->normalizeColumnName($header) === $this->normalizeColumnName($column)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Normalize encoding to UTF-8
     */
    protected function normalizeEncoding(string $content): string
    {
        $expectedEncoding = $this->getEncoding();

        // Remove BOM if present
        $content = $this->removeBom($content);

        // Check if already valid UTF-8 (regardless of expected encoding)
        // This handles test data and already-converted content
        if (mb_check_encoding($content, 'UTF-8')) {
            // Verify it actually contains valid multibyte sequences
            // by checking if UTF-8 detection works properly
            $detected = mb_detect_encoding($content, ['UTF-8', 'ASCII'], true);
            if ($detected === 'UTF-8' || $detected === 'ASCII') {
                return $content;
            }
        }

        // Try to convert from expected encoding
        $converted = @iconv($expectedEncoding, 'UTF-8//TRANSLIT//IGNORE', $content);
        if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }

        // Fallback: detect and convert
        $detected = mb_detect_encoding($content, ['UTF-8', 'Windows-1251', 'ISO-8859-1', 'ISO-8859-2'], true);
        if ($detected && $detected !== 'UTF-8') {
            $converted = @iconv($detected, 'UTF-8//TRANSLIT//IGNORE', $content);
            if ($converted !== false) {
                return $converted;
            }
        }

        return $content;
    }

    /**
     * Remove BOM (Byte Order Mark) from content
     */
    protected function removeBom(string $content): string
    {
        // UTF-8 BOM
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            return substr($content, 3);
        }
        // UTF-16 LE BOM
        if (substr($content, 0, 2) === "\xFF\xFE") {
            return substr($content, 2);
        }
        // UTF-16 BE BOM
        if (substr($content, 0, 2) === "\xFE\xFF") {
            return substr($content, 2);
        }
        return $content;
    }

    /**
     * Normalize column name for comparison
     * Supports both Latin and Cyrillic characters
     */
    protected function normalizeColumnName(string $name): string
    {
        // Remove all non-letter, non-number characters (including spaces, underscores)
        // Use Unicode property \p{L} to match any letter (Latin, Cyrillic, etc.)
        $cleaned = preg_replace('/[^\p{L}0-9]/u', '', $name);

        return mb_strtolower(trim($cleaned));
    }

    /**
     * Check if transaction data is valid
     */
    protected function isValidTransaction(array $data): bool
    {
        return isset($data['amount'])
            && $data['amount'] != 0
            && isset($data['transaction_date']);
    }

    /**
     * Create bank transaction from parsed data
     */
    protected function createTransaction(array $data, BankAccount $account): string
    {
        try {
            $reference = $data['reference'] ?? $this->generateReference($data);

            // Check for duplicate
            $exists = BankTransaction::where('bank_account_id', $account->id)
                ->where(function ($query) use ($reference, $data) {
                    $query->where('transaction_reference', $reference)
                        ->orWhere('external_reference', $reference);

                    // Also check by amount + date + description combo
                    if (isset($data['transaction_date']) && isset($data['amount'])) {
                        $query->orWhere(function ($q) use ($data) {
                            $q->where('transaction_date', $data['transaction_date'])
                                ->where('amount', abs($data['amount']))
                                ->where('description', $data['description'] ?? '');
                        });
                    }
                })
                ->exists();

            if ($exists) {
                return 'duplicate';
            }

            $amount = (float) $data['amount'];
            $isCredit = $amount > 0;

            BankTransaction::create([
                'bank_account_id' => $account->id,
                'company_id' => $account->company_id,
                'transaction_reference' => $reference,
                'external_reference' => $data['external_reference'] ?? $reference,
                'amount' => abs($amount),
                'currency' => $data['currency'] ?? $account->currency->code ?? 'MKD',
                'transaction_type' => $isCredit ? BankTransaction::TYPE_CREDIT : BankTransaction::TYPE_DEBIT,
                'booking_status' => BankTransaction::BOOKING_BOOKED,
                'transaction_date' => $data['transaction_date'],
                'booking_date' => $data['booking_date'] ?? $data['transaction_date'],
                'value_date' => $data['value_date'] ?? $data['transaction_date'],
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

            return 'created';
        } catch (\Exception $e) {
            $this->log('error', 'Failed to create transaction from CSV', [
                'bank' => $this->getBankCode(),
                'reference' => $reference ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return 'failed';
        }
    }

    /**
     * Generate a unique reference for a transaction
     */
    protected function generateReference(array $data): string
    {
        $parts = [
            $this->getBankCode(),
            $data['transaction_date'] ?? date('Y-m-d'),
            $data['amount'] ?? '0',
            substr(md5(json_encode($data)), 0, 8),
        ];

        return implode('-', $parts);
    }

    /**
     * Parse amount string to float
     * Handles various formats: 1,234.56 or 1.234,56 or -1234.56
     */
    protected function parseAmount(string $amountStr): float
    {
        $amountStr = trim($amountStr);

        // Remove currency symbols and spaces
        $amountStr = preg_replace('/[^\d,.\-]/', '', $amountStr);

        // Determine decimal separator
        // If comma is after period, comma is decimal (European: 1.234,56)
        // If period is after comma, period is decimal (US: 1,234.56)
        $lastComma = strrpos($amountStr, ',');
        $lastPeriod = strrpos($amountStr, '.');

        if ($lastComma !== false && $lastPeriod !== false) {
            if ($lastComma > $lastPeriod) {
                // European format: 1.234,56
                $amountStr = str_replace('.', '', $amountStr);
                $amountStr = str_replace(',', '.', $amountStr);
            } else {
                // US format: 1,234.56
                $amountStr = str_replace(',', '', $amountStr);
            }
        } elseif ($lastComma !== false) {
            // Only comma - check if it's decimal or thousands
            $afterComma = substr($amountStr, $lastComma + 1);
            if (strlen($afterComma) <= 2) {
                // Likely decimal: 1234,56
                $amountStr = str_replace(',', '.', $amountStr);
            } else {
                // Likely thousands: 1,234
                $amountStr = str_replace(',', '', $amountStr);
            }
        }

        return (float) $amountStr;
    }

    /**
     * Parse date string to Carbon instance
     */
    protected function parseDate(string $dateStr): Carbon
    {
        $dateStr = trim($dateStr);

        // Common Macedonian bank date formats
        $formats = [
            'd.m.Y',      // 05.02.2026
            'd/m/Y',      // 05/02/2026
            'Y-m-d',      // 2026-02-05
            'd-m-Y',      // 05-02-2026
            'd.m.Y H:i',  // 05.02.2026 14:30
            'd.m.Y H:i:s', // 05.02.2026 14:30:00
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateStr);
                if ($date && $date->isValid()) {
                    return $date;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Fallback to Carbon's natural parsing
        try {
            return Carbon::parse($dateStr);
        } catch (\Exception $e) {
            $this->log('warning', 'Failed to parse date', [
                'date_string' => $dateStr,
                'bank' => $this->getBankCode(),
            ]);
            return Carbon::now();
        }
    }

    /**
     * Log a message if Laravel's Log facade is available
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        if (class_exists('\Illuminate\Support\Facades\Log') && app()->bound('log')) {
            \Illuminate\Support\Facades\Log::$level($message, $context);
        }
    }
}

// CLAUDE-CHECKPOINT
