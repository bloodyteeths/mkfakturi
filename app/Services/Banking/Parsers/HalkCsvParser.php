<?php

namespace App\Services\Banking\Parsers;

use Carbon\Carbon;

/**
 * Halk Banka CSV Parser
 *
 * Parses CSV exports from Halk Banka AD Skopje (Халк Банка).
 * Market share: ~12.3%
 *
 * CSV Format:
 * - Delimiter: Semicolon (;)
 * - Encoding: Windows-1251
 * - Date format: dd.mm.yyyy
 * - Amount format: European (1.234,56)
 */
class HalkCsvParser extends AbstractCsvParser
{
    protected array $columnMap = [
        'датум' => 'date',
        'датумнакнижење' => 'date',
        'датумнавредност' => 'value_date',
        'износ' => 'amount',
        'салдо' => 'balance',
        'валута' => 'currency',
        'опис' => 'description',
        'описнатрансакција' => 'description',
        'цел' => 'description',
        'целнаплаќање' => 'description',
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
        'date' => 'date',
        'bookingdate' => 'date',
        'valuedate' => 'value_date',
        'amount' => 'amount',
        'currency' => 'currency',
        'description' => 'description',
        'reference' => 'reference',
        'name' => 'counterparty_name',
        'account' => 'counterparty_account',
        'debit' => 'debit',
        'credit' => 'credit',
    ];

    public function getBankCode(): string
    {
        return 'halk';
    }

    public function getBankName(): string
    {
        return 'Халк Банка';
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
        return ['датум'];
    }

    public function canParse(string $content): bool
    {
        if (parent::canParse($content)) {
            return true;
        }

        $content = $this->normalizeEncoding($content);
        $lowerContent = mb_strtolower($content);

        $markers = ['halk', 'халк', 'halk banka', 'халкбанка'];
        $markerCount = 0;

        foreach ($markers as $marker) {
            if (strpos($lowerContent, $marker) !== false) {
                $markerCount++;
            }
        }

        $firstLine = strtok($content, "\n");
        $semicolonCount = substr_count($firstLine, ';');

        return $markerCount >= 1 && $semicolonCount >= 3;
    }

    protected function mapRecord(array $record): array
    {
        $normalized = $this->normalizeRecord($record);

        $amount = 0;
        if (isset($normalized['amount']) && !empty($normalized['amount'])) {
            $amount = $this->parseAmount($normalized['amount']);
        } elseif (isset($normalized['credit']) || isset($normalized['debit'])) {
            $credit = !empty($normalized['credit']) ? $this->parseAmount($normalized['credit']) : 0;
            $debit = !empty($normalized['debit']) ? $this->parseAmount($normalized['debit']) : 0;
            $amount = $credit > 0 ? $credit : -$debit;
        }

        $transactionDate = isset($normalized['date'])
            ? $this->parseDate($normalized['date'])
            : Carbon::now();

        $valueDate = isset($normalized['value_date'])
            ? $this->parseDate($normalized['value_date'])
            : $transactionDate;

        return [
            'transaction_date' => $transactionDate,
            'booking_date' => $transactionDate,
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
