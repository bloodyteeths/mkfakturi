<?php

namespace App\Services\Banking\Parsers;

use Carbon\Carbon;
use League\Csv\Reader;

/**
 * Generic CSV Parser
 *
 * Fallback parser for unknown bank CSV formats.
 * Auto-detects delimiter and attempts to map common column patterns.
 *
 * Features:
 * - Auto-detect delimiter (comma, semicolon, tab)
 * - Auto-detect encoding
 * - Flexible column name matching
 * - Support for both single amount and split credit/debit columns
 */
class GenericCsvParser extends AbstractCsvParser
{
    protected string $detectedDelimiter = ',';
    protected string $detectedEncoding = 'UTF-8';

    /**
     * Common column name patterns for detection
     */
    protected array $columnPatterns = [
        'date' => ['date', 'datum', 'датум', 'data', 'tarih', 'transaction_date', 'transactiondate'],
        'amount' => ['amount', 'iznos', 'износ', 'suma', 'сума', 'tutar', 'value', 'total'],
        'credit' => ['credit', 'kredit', 'кредит', 'прилив', 'одобрување', 'inflow', 'deposit'],
        'debit' => ['debit', 'дебит', 'одлив', 'задолжување', 'outflow', 'withdrawal'],
        'description' => ['description', 'opis', 'опис', 'açıklama', 'purpose', 'цел', 'details', 'note'],
        'reference' => ['reference', 'referenca', 'референца', 'ref', 'broj', 'број', 'number', 'id'],
        'counterparty_name' => ['counterparty', 'partner', 'партнер', 'name', 'naziv', 'назив', 'sender', 'receiver', 'примач', 'испраќач'],
        'counterparty_account' => ['account', 'smetka', 'сметка', 'iban', 'partner_account'],
        'currency' => ['currency', 'valuta', 'валута', 'curr', 'ccy'],
    ];

    public function getBankCode(): string
    {
        return 'generic';
    }

    public function getBankName(): string
    {
        return 'Generic Bank';
    }

    public function getDelimiter(): string
    {
        return $this->detectedDelimiter;
    }

    public function getEncoding(): string
    {
        return $this->detectedEncoding;
    }

    protected function getRequiredColumns(): array
    {
        // Generic parser has no strict requirements
        return [];
    }

    /**
     * Generic parser can always try to parse
     */
    public function canParse(string $content): bool
    {
        if (empty(trim($content))) {
            return false;
        }

        // Detect delimiter first
        $this->detectDelimiter($content);

        // Try to read as CSV
        try {
            $normalized = $this->normalizeEncoding($content);
            $csv = Reader::fromString($normalized);
            $csv->setDelimiter($this->detectedDelimiter);
            $csv->setHeaderOffset(0);

            $headers = $csv->getHeader();
            return count($headers) >= 2; // At least 2 columns
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Override parse to detect delimiter first
     */
    public function parse(string $content): array
    {
        $this->detectDelimiter($content);
        $this->detectEncoding($content);

        return parent::parse($content);
    }

    /**
     * Auto-detect the delimiter used in the CSV
     */
    protected function detectDelimiter(string $content): void
    {
        $firstLine = strtok($content, "\n");
        $firstLine = strtok($firstLine, "\r");

        $delimiters = [
            ';' => substr_count($firstLine, ';'),
            ',' => substr_count($firstLine, ','),
            "\t" => substr_count($firstLine, "\t"),
            '|' => substr_count($firstLine, '|'),
        ];

        // Pick delimiter with highest count
        $maxCount = max($delimiters);
        if ($maxCount > 0) {
            $this->detectedDelimiter = array_search($maxCount, $delimiters);
        }
    }

    /**
     * Auto-detect encoding
     */
    protected function detectEncoding(string $content): void
    {
        // Check for UTF-8 BOM
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $this->detectedEncoding = 'UTF-8';
            return;
        }

        // Try to detect encoding
        $detected = mb_detect_encoding($content, ['UTF-8', 'Windows-1251', 'ISO-8859-1', 'ISO-8859-2'], true);
        if ($detected) {
            $this->detectedEncoding = $detected;
        }
    }

    protected function mapRecord(array $record): array
    {
        $mapped = $this->autoMapColumns($record);

        // Determine amount
        $amount = 0;
        if (isset($mapped['amount']) && !empty($mapped['amount'])) {
            $amount = $this->parseAmount($mapped['amount']);
        } elseif (isset($mapped['credit']) || isset($mapped['debit'])) {
            $credit = !empty($mapped['credit']) ? $this->parseAmount($mapped['credit']) : 0;
            $debit = !empty($mapped['debit']) ? $this->parseAmount($mapped['debit']) : 0;
            $amount = $credit > 0 ? $credit : -$debit;
        }

        // Parse date (try various sources)
        $dateValue = $mapped['date'] ?? null;
        $transactionDate = $dateValue ? $this->parseDate($dateValue) : Carbon::now();

        return [
            'transaction_date' => $transactionDate,
            'booking_date' => $transactionDate,
            'value_date' => $transactionDate,
            'amount' => $amount,
            'currency' => $mapped['currency'] ?? 'MKD',
            'description' => $mapped['description'] ?? '',
            'reference' => $mapped['reference'] ?? null,
            'external_reference' => $mapped['reference'] ?? null,
            'counterparty_name' => $mapped['counterparty_name'] ?? null,
            'counterparty_account' => $mapped['counterparty_account'] ?? null,
            'raw_record' => $record,
        ];
    }

    /**
     * Auto-map columns based on patterns
     */
    protected function autoMapColumns(array $record): array
    {
        $mapped = [];

        foreach ($record as $columnName => $value) {
            $normalizedColumn = $this->normalizeColumnName($columnName);

            // Try to match against patterns
            foreach ($this->columnPatterns as $targetField => $patterns) {
                foreach ($patterns as $pattern) {
                    $normalizedPattern = $this->normalizeColumnName($pattern);
                    if ($normalizedColumn === $normalizedPattern ||
                        strpos($normalizedColumn, $normalizedPattern) !== false) {
                        // Don't overwrite if already mapped
                        if (!isset($mapped[$targetField]) || empty($mapped[$targetField])) {
                            $mapped[$targetField] = trim($value);
                        }
                        break 2;
                    }
                }
            }
        }

        return $mapped;
    }
}

// CLAUDE-CHECKPOINT
