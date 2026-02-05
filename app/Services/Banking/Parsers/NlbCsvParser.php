<?php

namespace App\Services\Banking\Parsers;

use Carbon\Carbon;

/**
 * NLB Bank CSV Parser
 *
 * Parses CSV exports from NLB Banka (Nova Ljubljanska Banka) Macedonia.
 *
 * CSV Format:
 * - Delimiter: Semicolon (;)
 * - Encoding: Windows-1251 / CP1250 (Cyrillic)
 * - Date format: dd.mm.yyyy
 * - Amount format: European (1.234,56)
 *
 * Expected columns (Macedonian):
 * - Датум (Date)
 * - Износ (Amount)
 * - Валута (Currency)
 * - Опис (Description)
 * - Референца (Reference)
 * - Партнер (Counterparty Name)
 * - Сметка на партнер (Counterparty Account)
 */
class NlbCsvParser extends AbstractCsvParser
{
    /**
     * Column name mappings (Macedonian -> English)
     */
    protected array $columnMap = [
        // Primary Macedonian names
        'датум' => 'date',
        'износ' => 'amount',
        'валута' => 'currency',
        'опис' => 'description',
        'референца' => 'reference',
        'партнер' => 'counterparty_name',
        'сметканапартнер' => 'counterparty_account',
        'сметка' => 'counterparty_account',
        // Alternative/English names
        'date' => 'date',
        'amount' => 'amount',
        'currency' => 'currency',
        'description' => 'description',
        'reference' => 'reference',
        'counterparty' => 'counterparty_name',
        'partner' => 'counterparty_name',
        'account' => 'counterparty_account',
        // NLB specific variations
        'датумнакнижење' => 'booking_date',
        'датумнавредност' => 'value_date',
        'bookingdate' => 'booking_date',
        'valuedate' => 'value_date',
        'кредит' => 'credit',
        'дебит' => 'debit',
        'credit' => 'credit',
        'debit' => 'debit',
    ];

    public function getBankCode(): string
    {
        return 'nlb';
    }

    public function getBankName(): string
    {
        return 'NLB Banka';
    }

    public function getDelimiter(): string
    {
        return ';';
    }

    public function getEncoding(): string
    {
        return 'Windows-1251';
    }

    protected function getRequiredColumns(): array
    {
        // At minimum need date and amount (either combined or separate credit/debit)
        return ['датум', 'износ'];
    }

    /**
     * Override canParse to also check for NLB-specific patterns
     */
    public function canParse(string $content): bool
    {
        // First check parent validation
        if (parent::canParse($content)) {
            return true;
        }

        // Check for NLB-specific markers
        $content = $this->normalizeEncoding($content);
        $lowerContent = mb_strtolower($content);

        // Look for NLB-specific column names or patterns
        $nlbMarkers = ['nlb', 'нлб', 'датум', 'износ', 'референца'];
        $markerCount = 0;

        foreach ($nlbMarkers as $marker) {
            if (strpos($lowerContent, $marker) !== false) {
                $markerCount++;
            }
        }

        return $markerCount >= 2;
    }

    protected function mapRecord(array $record): array
    {
        $normalized = $this->normalizeRecord($record);

        // Determine amount
        $amount = 0;
        if (isset($normalized['amount'])) {
            $amount = $this->parseAmount($normalized['amount']);
        } elseif (isset($normalized['credit']) && isset($normalized['debit'])) {
            $credit = $this->parseAmount($normalized['credit'] ?? '0');
            $debit = $this->parseAmount($normalized['debit'] ?? '0');
            $amount = $credit > 0 ? $credit : -$debit;
        }

        // Parse dates
        $transactionDate = isset($normalized['date'])
            ? $this->parseDate($normalized['date'])
            : Carbon::now();

        $bookingDate = isset($normalized['booking_date'])
            ? $this->parseDate($normalized['booking_date'])
            : $transactionDate;

        $valueDate = isset($normalized['value_date'])
            ? $this->parseDate($normalized['value_date'])
            : $transactionDate;

        return [
            'transaction_date' => $transactionDate,
            'booking_date' => $bookingDate,
            'value_date' => $valueDate,
            'amount' => $amount,
            'currency' => $normalized['currency'] ?? 'MKD',
            'description' => $normalized['description'] ?? '',
            'reference' => $normalized['reference'] ?? null,
            'external_reference' => $normalized['reference'] ?? null,
            'counterparty_name' => $normalized['counterparty_name'] ?? null,
            'counterparty_account' => $normalized['counterparty_account'] ?? null,
            'raw_record' => $record,
        ];
    }

    /**
     * Normalize record keys using column map
     */
    protected function normalizeRecord(array $record): array
    {
        $normalized = [];

        foreach ($record as $key => $value) {
            $normalizedKey = $this->normalizeColumnName($key);

            // Find mapping
            foreach ($this->columnMap as $sourceKey => $targetKey) {
                if ($this->normalizeColumnName($sourceKey) === $normalizedKey) {
                    $normalized[$targetKey] = trim($value);
                    break;
                }
            }

            // Keep original if no mapping found
            if (!isset($normalized[$normalizedKey])) {
                $normalized[$normalizedKey] = trim($value);
            }
        }

        return $normalized;
    }
}

// CLAUDE-CHECKPOINT
