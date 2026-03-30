<?php

namespace App\Services\Banking\Parsers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Carbon\Carbon;

/**
 * Komercijalna Banka Statement Parser (Excel/Mac CSV export)
 *
 * Parses the visual bank statement format that users get when they
 * download an Excel statement from Komercijalna Banka and save as CSV.
 *
 * Format characteristics:
 * - Encoding: MacCyrillic (from Mac Excel save-as-CSV)
 * - Delimiter: comma
 * - 20 columns per row
 * - Header rows 0-18, transaction rows after, totals row starts with "Вкупно:"
 * - Transaction columns:
 *   [0]=row#, [2]=counterparty, [5]=account, [6]=method,
 *   [7]=debit, [9]=credit, [11]=fee, [13]=payment_code,
 *   [14]=description, [16]=reference, [19]=tx_id
 */
class KomercijalnaStatementParser implements BankParserInterface
{
    public function getBankCode(): string
    {
        return 'komercijalna_statement';
    }

    public function getBankName(): string
    {
        return 'Komercijalna Banka (Statement)';
    }

    public function getDelimiter(): string
    {
        return ',';
    }

    public function getEncoding(): string
    {
        return 'MacCyrillic';
    }

    public function canParse(string $content): bool
    {
        $content = $this->normalizeEncoding($content);
        $lower = mb_strtolower($content);

        // Must contain Komercijalna bank name
        $hasBank = mb_strpos($lower, 'комерцијална банка') !== false
            || mb_strpos($lower, 'komercijalna banka') !== false;

        // Must contain statement marker
        $hasStatement = mb_strpos($lower, 'извод за промените') !== false
            || mb_strpos($lower, 'р.бр.') !== false;

        return $hasBank && $hasStatement;
    }

    public function parse(string $content): array
    {
        $content = $this->normalizeEncoding($content);
        $content = $this->normalizeNewlines($content);

        $rows = $this->parseCsvRows($content);
        $transactions = [];

        // Extract statement date from header rows
        $statementDate = $this->extractStatementDate($rows);

        // Find transaction rows: between column headers and "Вкупно:" row
        $inTransactions = false;

        foreach ($rows as $row) {
            if (count($row) < 10) {
                continue;
            }

            $firstCol = trim($row[0] ?? '');

            // Detect totals row - end of transactions
            if (mb_strpos($firstCol, 'Вкупно') !== false) {
                break;
            }

            // Detect transaction rows: first column is a number
            if ($inTransactions && is_numeric($firstCol)) {
                $tx = $this->mapRow($row, $statementDate);
                if ($tx !== null) {
                    $transactions[] = $tx;

                    // NOTE: The пров. (fee) column in Komercijalna bank statements is
                    // informational only — fees are NOT deducted from the account balance
                    // on the same statement. The bank charges fees as batch transactions
                    // on decade boundaries (e.g. "Пров. ПП за втора декада").
                    // Do NOT extract пров. as separate debit transactions — it double-counts.
                    // CLAUDE-CHECKPOINT
                }
                continue;
            }

            // Detect start of transaction section: "Р.бр." header
            if (mb_strpos($firstCol, 'Р.бр') !== false || $firstCol === 'Р.бр.') {
                $inTransactions = true;
                continue;
            }

            // Also start after the sub-header row (должи/побарува)
            if (!$inTransactions && isset($row[7]) && mb_strpos(trim($row[7]), 'должи') !== false) {
                $inTransactions = true;
            }
        }

        return $transactions;
    }

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

        $this->log('info', 'Komercijalna statement import completed', [
            'account_id' => $account->id,
            'imported' => $result['imported'],
            'duplicates' => $result['duplicates'],
            'failed' => $result['failed'],
        ]);

        return $result;
    }

    /**
     * Map a transaction row to normalized data
     */
    protected function mapRow(array $row, ?Carbon $statementDate): ?array
    {
        $debit = $this->parseAmount($row[7] ?? '');
        $credit = $this->parseAmount($row[9] ?? '');

        // Skip rows with no monetary activity
        if ($debit == 0 && $credit == 0) {
            return null;
        }

        // Credit is positive (money in), debit is negative (money out)
        $amount = $credit > 0 ? $credit : -$debit;
        $date = $statementDate ?? Carbon::now();

        return [
            'transaction_date' => $date,
            'booking_date' => $date,
            'value_date' => $date,
            'amount' => $amount,
            'currency' => 'MKD',
            'description' => trim($row[14] ?? ''),
            'reference' => trim($row[16] ?? '') ?: null,
            'external_reference' => trim($row[19] ?? '') ?: null,
            'counterparty_name' => trim($row[2] ?? '') ?: null,
            'counterparty_account' => trim($row[5] ?? '') ?: null,
            'payment_code' => trim($row[13] ?? '') ?: null,
            'fee' => $this->parseAmount($row[11] ?? ''),
            'raw_record' => $row,
        ];
    }

    /**
     * Extract statement date from header rows
     * Looks for "Извод за промените и состојбата на сметката за ден DD.MM.YYYY"
     */
    protected function extractStatementDate(array $rows): ?Carbon
    {
        // First pass: look for "за ден DD.MM.YYYY" (actual transaction date)
        foreach ($rows as $row) {
            foreach ($row as $cell) {
                if (preg_match('/за\s+ден\s+(\d{2}\.\d{2}\.\d{4})/', trim($cell), $m)) {
                    try {
                        return Carbon::createFromFormat('d.m.Y', $m[1]);
                    } catch (\Exception $e) {
                        // continue
                    }
                }
            }
        }

        // Fallback: "Датум: DD.MM.YYYY" (print/export date)
        foreach ($rows as $row) {
            foreach ($row as $cell) {
                if (preg_match('/Датум:\s*(\d{2}\.\d{2}\.\d{4})/', trim($cell), $m)) {
                    try {
                        return Carbon::createFromFormat('d.m.Y', $m[1]);
                    } catch (\Exception $e) {
                        // continue
                    }
                }
            }
        }

        return null;
    }

    /**
     * Parse amount string (European format: 1,234.00 or 10,000.00)
     */
    protected function parseAmount(string $str): float
    {
        $str = trim($str);
        if ($str === '' || $str === '0' || $str === '0.00') {
            return 0.0;
        }

        // Remove currency symbols and spaces
        $str = preg_replace('/[^\d,.\\-]/', '', $str);

        // Komercijalna uses format like "10,000.00" (US-style with comma thousands)
        $lastComma = strrpos($str, ',');
        $lastPeriod = strrpos($str, '.');

        if ($lastComma !== false && $lastPeriod !== false) {
            if ($lastPeriod > $lastComma) {
                // US format: 10,000.00
                $str = str_replace(',', '', $str);
            } else {
                // European format: 10.000,00
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            }
        } elseif ($lastComma !== false) {
            $afterComma = substr($str, $lastComma + 1);
            if (strlen($afterComma) <= 2) {
                $str = str_replace(',', '.', $str);
            } else {
                $str = str_replace(',', '', $str);
            }
        }

        return (float) $str;
    }

    /**
     * Normalize encoding to UTF-8
     */
    protected function normalizeEncoding(string $content): string
    {
        // Remove BOM
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }

        // Check if already valid UTF-8
        if (mb_check_encoding($content, 'UTF-8')) {
            $detected = mb_detect_encoding($content, ['UTF-8', 'ASCII'], true);
            if ($detected === 'UTF-8' || $detected === 'ASCII') {
                return $content;
            }
        }

        // Try MacCyrillic first (most common for this format)
        $converted = @iconv('MacCyrillic', 'UTF-8//TRANSLIT//IGNORE', $content);
        if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }

        // Try Windows-1251
        $converted = @iconv('Windows-1251', 'UTF-8//TRANSLIT//IGNORE', $content);
        if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }

        return $content;
    }

    /**
     * Normalize newlines (Mac \r → \n)
     */
    protected function normalizeNewlines(string $content): string
    {
        return str_replace(["\r\n", "\r"], "\n", $content);
    }

    /**
     * Parse CSV content into rows (handles quoted fields with newlines)
     */
    protected function parseCsvRows(string $content): array
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream, 0, ',', '"', '\\')) !== false) {
            $rows[] = $row;
        }

        fclose($stream);
        return $rows;
    }

    /**
     * Create bank transaction from parsed data
     */
    protected function createTransaction(array $data, BankAccount $account): string
    {
        try {
            $reference = $data['external_reference']
                ?? $data['reference']
                ?? $this->generateReference($data);

            // Check for duplicate
            $exists = BankTransaction::where('bank_account_id', $account->id)
                ->where(function ($query) use ($reference, $data) {
                    $query->where('transaction_reference', $reference)
                        ->orWhere('external_reference', $reference);

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
                'remittance_info' => $data['description'] ?? '',
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
            $this->log('error', 'Failed to create transaction from statement', [
                'bank' => $this->getBankCode(),
                'reference' => $reference ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return 'failed';
        }
    }

    protected function generateReference(array $data): string
    {
        $parts = [
            'komercijalna',
            $data['transaction_date'] ?? date('Y-m-d'),
            $data['amount'] ?? '0',
            substr(md5(json_encode($data)), 0, 8),
        ];

        return implode('-', $parts);
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        if (class_exists('\Illuminate\Support\Facades\Log') && app()->bound('log')) {
            \Illuminate\Support\Facades\Log::$level($message, $context);
        }
    }
}
// CLAUDE-CHECKPOINT
