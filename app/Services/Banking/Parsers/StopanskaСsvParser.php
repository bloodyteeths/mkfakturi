<?php

namespace App\Services\Banking\Parsers;

use Carbon\Carbon;

/**
 * Stopanska Banka CSV Parser
 *
 * Parses CSV exports from Stopanska Banka AD Skopje.
 *
 * CSV Format:
 * - Delimiter: Comma (,)
 * - Encoding: UTF-8
 * - Date format: dd.mm.yyyy or yyyy-mm-dd
 * - Amount format: Usually signed (positive/negative)
 *
 * Expected columns (Macedonian):
 * - Датум (Date)
 * - Износ (Amount) or Кредит/Дебит (Credit/Debit)
 * - Опис (Description)
 * - Референца (Reference)
 * - Назив на партнер (Counterparty Name)
 * - Сметка (Account)
 */
class StopanskaСsvParser extends AbstractCsvParser
{
    /**
     * Column name mappings
     */
    protected array $columnMap = [
        // Macedonian names
        'датум' => 'date',
        'датумнатрансакција' => 'date',
        'износ' => 'amount',
        'сума' => 'amount',
        'валута' => 'currency',
        'опис' => 'description',
        'описнатрансакција' => 'description',
        'референца' => 'reference',
        'реф' => 'reference',
        'назив' => 'counterparty_name',
        'називнапартнер' => 'counterparty_name',
        'партнер' => 'counterparty_name',
        'примач' => 'counterparty_name',
        'испраќач' => 'counterparty_name',
        'сметка' => 'counterparty_account',
        'сметканапартнер' => 'counterparty_account',
        'кредит' => 'credit',
        'дебит' => 'debit',
        'прилив' => 'credit',
        'одлив' => 'debit',
        // English alternatives
        'date' => 'date',
        'transactiondate' => 'date',
        'amount' => 'amount',
        'sum' => 'amount',
        'currency' => 'currency',
        'description' => 'description',
        'reference' => 'reference',
        'ref' => 'reference',
        'name' => 'counterparty_name',
        'counterparty' => 'counterparty_name',
        'partner' => 'counterparty_name',
        'account' => 'counterparty_account',
        'credit' => 'credit',
        'debit' => 'debit',
    ];

    public function getBankCode(): string
    {
        return 'stopanska';
    }

    public function getBankName(): string
    {
        return 'Stopanska Banka';
    }

    public function getDelimiter(): string
    {
        return ',';
    }

    public function getEncoding(): string
    {
        return 'UTF-8';
    }

    protected function getRequiredColumns(): array
    {
        return ['датум', 'износ'];
    }

    /**
     * Override canParse to check for Stopanska-specific patterns
     */
    public function canParse(string $content): bool
    {
        if (parent::canParse($content)) {
            return true;
        }

        $content = $this->normalizeEncoding($content);
        $lowerContent = mb_strtolower($content);

        // Look for Stopanska-specific markers
        $markers = ['stopanska', 'стопанска', 'датум', 'износ'];
        $markerCount = 0;

        foreach ($markers as $marker) {
            if (strpos($lowerContent, $marker) !== false) {
                $markerCount++;
            }
        }

        // Also check if it's comma-delimited (Stopanska uses comma)
        $firstLine = strtok($content, "\n");
        $commaCount = substr_count($firstLine, ',');
        $semicolonCount = substr_count($firstLine, ';');

        return $markerCount >= 2 || ($markerCount >= 1 && $commaCount > $semicolonCount);
    }

    protected function mapRecord(array $record): array
    {
        $normalized = $this->normalizeRecord($record);

        // Determine amount
        $amount = 0;
        if (isset($normalized['amount']) && !empty($normalized['amount'])) {
            $amount = $this->parseAmount($normalized['amount']);
        } elseif (isset($normalized['credit']) || isset($normalized['debit'])) {
            $credit = $this->parseAmount($normalized['credit'] ?? '0');
            $debit = $this->parseAmount($normalized['debit'] ?? '0');
            $amount = $credit > 0 ? $credit : -$debit;
        }

        // Parse date
        $transactionDate = isset($normalized['date'])
            ? $this->parseDate($normalized['date'])
            : Carbon::now();

        return [
            'transaction_date' => $transactionDate,
            'booking_date' => $transactionDate,
            'value_date' => $transactionDate,
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

            foreach ($this->columnMap as $sourceKey => $targetKey) {
                if ($this->normalizeColumnName($sourceKey) === $normalizedKey) {
                    $normalized[$targetKey] = trim($value);
                    break;
                }
            }

            if (!isset($normalized[$normalizedKey])) {
                $normalized[$normalizedKey] = trim($value);
            }
        }

        return $normalized;
    }
}

// CLAUDE-CHECKPOINT
