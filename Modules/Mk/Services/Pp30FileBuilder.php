<?php

namespace Modules\Mk\Services;

use Modules\Mk\Models\PaymentBatch;

/**
 * PP30 File Builder
 *
 * Generates Macedonian domestic payment order format (PP30/PP50).
 * PP30 is for standard domestic transfers, PP50 for public revenue payments.
 *
 * The output is a structured CSV file compatible with Macedonian bank
 * electronic banking systems (e-banking).
 *
 * Columns:
 * - Debtor Name, Debtor Account, Debtor Bank
 * - Creditor Name, Creditor Account, Creditor Bank
 * - Amount, Currency, Purpose Code, Payment Reference, Description
 */
class Pp30FileBuilder
{
    /**
     * Build PP30/PP50 CSV content from a payment batch.
     *
     * @param PaymentBatch $batch
     * @param bool $isPublicRevenue True for PP50 (public revenue), false for PP30
     * @return string CSV file content
     */
    public function build(PaymentBatch $batch, bool $isPublicRevenue = false): string
    {
        $batch->load(['items', 'company', 'bankAccount']);

        $company = $batch->company;
        $bankAccount = $batch->bankAccount;

        // Debtor info from company and bank account
        $debtorName = $company->name ?? 'N/A';
        $debtorAccount = $bankAccount->iban ?? ($bankAccount->account_number ?? '');
        $debtorBank = $bankAccount->bank_name ?? $this->extractBankNameFromIban($debtorAccount);

        $output = fopen('php://temp', 'r+');

        // Header row
        $headers = [
            'Debtor Name',
            'Debtor Account',
            'Debtor Bank',
            'Creditor Name',
            'Creditor Account',
            'Creditor Bank',
            'Amount',
            'Currency',
            'Purpose Code',
            'Payment Reference',
            'Description',
            'Batch Number',
            'Execution Date',
        ];

        if ($isPublicRevenue) {
            $headers[] = 'Revenue Code';
            $headers[] = 'Municipality Code';
        }

        fputcsv($output, $headers);

        // Data rows
        foreach ($batch->items as $item) {
            $row = [
                $debtorName,
                $this->formatAccount($debtorAccount),
                $debtorBank,
                $item->creditor_name,
                $this->formatAccount($item->creditor_iban ?? ''),
                $item->creditor_bank_name ?? $this->extractBankNameFromIban($item->creditor_iban ?? ''),
                number_format($item->amount / 100, 2, '.', ''),
                $item->currency_code ?? 'MKD',
                $item->purpose_code ?? '',
                $item->payment_reference ?? '',
                $item->description ?? '',
                $batch->batch_number,
                $batch->batch_date->format('Y-m-d'),
            ];

            if ($isPublicRevenue) {
                $row[] = $item->purpose_code ?? '';
                $row[] = '';
            }

            fputcsv($output, $row);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Format account number/IBAN: remove spaces, uppercase.
     */
    private function formatAccount(string $account): string
    {
        return strtoupper(str_replace(' ', '', $account));
    }

    /**
     * Extract bank name from Macedonian IBAN based on bank code.
     */
    private function extractBankNameFromIban(string $iban): string
    {
        $iban = strtoupper(str_replace(' ', '', $iban));

        if (! str_starts_with($iban, 'MK') || strlen($iban) < 7) {
            return '';
        }

        $bankCode = substr($iban, 4, 3);

        $banks = [
            '210' => 'Комерцијална Банка',
            '250' => 'НЛБ Банка',
            '270' => 'Халк Банка',
            '300' => 'Стопанска Банка',
            '380' => 'УНИ Банка',
            '500' => 'Шпаркасе Банка',
            '530' => 'ПроКредит Банка',
            '290' => 'Силк Роуд Банка',
            '320' => 'ТТК Банка',
        ];

        return $banks[$bankCode] ?? '';
    }
}

// CLAUDE-CHECKPOINT
