<?php

namespace App\Services\Banking\Parsers;

use Carbon\Carbon;

/**
 * Komercijalna Banka CSV Parser
 *
 * Parses CSV exports from Komercijalna Banka AD Skopje.
 *
 * CSV Format:
 * - Delimiter: Tab (\t)
 * - Encoding: UTF-8 or Windows-1251
 * - Date format: dd.mm.yyyy
 * - Amount format: European (1.234,56)
 *
 * Expected columns (Macedonian):
 * - Датум (Date)
 * - Износ (Amount) or separate Задолжување/Одобрување (Debit/Credit)
 * - Опис (Description)
 * - Број на документ (Document Number/Reference)
 * - Назив (Name/Counterparty)
 * - Сметка (Account)
 */
class KomercijalnaCsvParser extends AbstractCsvParser
{
    /**
     * Column name mappings
     */
    protected array $columnMap = [
        // Macedonian names
        'датум' => 'date',
        'датумнакнижење' => 'date',
        'износ' => 'amount',
        'салдо' => 'balance',
        'валута' => 'currency',
        'опис' => 'description',
        'описнатрансакција' => 'description',
        'цел' => 'description',
        'бројнадокумент' => 'reference',
        'референца' => 'reference',
        'документ' => 'reference',
        'назив' => 'counterparty_name',
        'називнакомитент' => 'counterparty_name',
        'партнер' => 'counterparty_name',
        'комитент' => 'counterparty_name',
        'сметка' => 'counterparty_account',
        'сметканакомитент' => 'counterparty_account',
        'задолжување' => 'debit',
        'дебит' => 'debit',
        'одобрување' => 'credit',
        'кредит' => 'credit',
        // English alternatives
        'date' => 'date',
        'bookingdate' => 'date',
        'amount' => 'amount',
        'balance' => 'balance',
        'currency' => 'currency',
        'description' => 'description',
        'purpose' => 'description',
        'documentnumber' => 'reference',
        'reference' => 'reference',
        'document' => 'reference',
        'name' => 'counterparty_name',
        'customername' => 'counterparty_name',
        'partner' => 'counterparty_name',
        'account' => 'counterparty_account',
        'customeraccount' => 'counterparty_account',
        'debit' => 'debit',
        'credit' => 'credit',
    ];

    public function getBankCode(): string
    {
        return 'komercijalna';
    }

    public function getBankName(): string
    {
        return 'Komercijalna Banka';
    }

    public function getDelimiter(): string
    {
        return "\t";
    }

    public function getEncoding(): string
    {
        return 'UTF-8';
    }

    protected function getRequiredColumns(): array
    {
        return ['датум'];
    }

    /**
     * Override canParse to check for Komercijalna-specific patterns
     */
    public function canParse(string $content): bool
    {
        if (parent::canParse($content)) {
            return true;
        }

        $content = $this->normalizeEncoding($content);
        $lowerContent = mb_strtolower($content);

        // Look for Komercijalna-specific markers
        $markers = ['komercijalna', 'комерцијална', 'задолжување', 'одобрување'];
        $markerCount = 0;

        foreach ($markers as $marker) {
            if (strpos($lowerContent, $marker) !== false) {
                $markerCount++;
            }
        }

        // Check if it's tab-delimited
        $firstLine = strtok($content, "\n");
        $tabCount = substr_count($firstLine, "\t");

        return $markerCount >= 1 && $tabCount >= 3;
    }

    protected function mapRecord(array $record): array
    {
        $normalized = $this->normalizeRecord($record);

        // Determine amount - Komercijalna often has separate debit/credit columns
        $amount = 0;
        if (isset($normalized['amount']) && !empty($normalized['amount'])) {
            $amount = $this->parseAmount($normalized['amount']);
        } elseif (isset($normalized['credit']) || isset($normalized['debit'])) {
            $credit = !empty($normalized['credit']) ? $this->parseAmount($normalized['credit']) : 0;
            $debit = !empty($normalized['debit']) ? $this->parseAmount($normalized['debit']) : 0;
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
