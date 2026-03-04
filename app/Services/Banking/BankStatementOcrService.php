<?php

namespace App\Services\Banking;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service to extract bank transactions from statement images via OCR.
 *
 * Sends images to the invoice2data FastAPI microservice's /parse-bank-statement
 * endpoint and maps the structured OCR response into the same transaction
 * format used by the CSV parsers.
 */
class BankStatementOcrService
{
    /**
     * Parse a bank statement image and return normalized transactions.
     *
     * @param  string  $filePath  Absolute path to the image file
     * @param  string  $originalName  Original filename for logging
     * @return array{
     *   bank_code: ?string,
     *   bank_name: ?string,
     *   statement_date: ?string,
     *   account_number: ?string,
     *   transactions: array,
     *   confidence: float,
     *   raw_text: string,
     * }
     *
     * @throws BankStatementOcrException
     */
    public function parse(string $filePath, string $originalName): array
    {
        $baseUrl = $this->getBaseUrl();
        $timeout = (int) config('services.invoice2data.timeout', 120);

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(15)
                ->attach('file', file_get_contents($filePath), $originalName)
                ->post($baseUrl . '/parse-bank-statement');

            $response->throw();

            $data = $response->json();

            if (empty($data['success'])) {
                throw new BankStatementOcrException(
                    'OCR service returned unsuccessful response: ' . ($data['detail'] ?? 'unknown error')
                );
            }

            // Map OCR transactions to the format expected by BankImportController
            $transactions = $this->mapTransactions(
                $data['transactions'] ?? [],
                $data['statement_date'] ?? null
            );

            return [
                'bank_code' => $data['bank_code'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'statement_date' => $data['statement_date'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'transactions' => $transactions,
                'confidence' => $data['confidence'] ?? 0,
                'raw_text' => $data['raw_text'] ?? '',
            ];
        } catch (ConnectionException $e) {
            Log::warning('invoice2data-service unreachable for bank statement OCR', [
                'url' => $baseUrl . '/parse-bank-statement',
                'file' => $originalName,
                'error' => $e->getMessage(),
            ]);

            throw new BankStatementOcrException(
                'Bank statement OCR service is currently unavailable. Please try again later.',
                503,
                $e
            );
        } catch (BankStatementOcrException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Bank statement OCR failed', [
                'file' => $originalName,
                'error' => $e->getMessage(),
            ]);

            throw new BankStatementOcrException(
                'Failed to process bank statement image: ' . $e->getMessage(),
                500,
                $e
            );
        }
    }

    /**
     * Map OCR-extracted transactions to the format expected by the import pipeline.
     *
     * @param  array  $ocrTransactions  Raw transactions from OCR service
     * @param  string|null  $statementDate  Statement date (DD.MM.YYYY)
     * @return array  Normalized transactions compatible with BankImportController
     */
    protected function mapTransactions(array $ocrTransactions, ?string $statementDate): array
    {
        $transactions = [];
        $date = $this->parseDate($statementDate);

        foreach ($ocrTransactions as $tx) {
            $debit = (float) ($tx['debit'] ?? 0);
            $credit = (float) ($tx['credit'] ?? 0);

            // Skip rows with no monetary activity
            if ($debit == 0 && $credit == 0) {
                continue;
            }

            // Credit is positive (money in), debit is negative (money out)
            $amount = $credit > 0 ? $credit : -$debit;

            $transactions[] = [
                'transaction_date' => $date,
                'booking_date' => $date,
                'value_date' => $date,
                'amount' => $amount,
                'currency' => 'MKD',
                'description' => $tx['description'] ?? '',
                'reference' => $tx['reference'] ?? null,
                'external_reference' => $tx['reference'] ?? null,
                'external_transaction_id' => null,
                'counterparty_name' => $tx['counterparty_name'] ?? null,
                'counterparty_account' => $tx['counterparty_account'] ?? null,
                'payment_code' => $tx['payment_code'] ?? null,
                'raw_record' => $tx,
            ];
        }

        return $transactions;
    }

    /**
     * Parse DD.MM.YYYY date string to Y-m-d format.
     */
    protected function parseDate(?string $dateStr): ?string
    {
        if (! $dateStr) {
            return date('Y-m-d');
        }

        try {
            return \Carbon\Carbon::createFromFormat('d.m.Y', $dateStr)->format('Y-m-d');
        } catch (\Exception $e) {
            return date('Y-m-d');
        }
    }

    /**
     * Get the base URL for the OCR service.
     */
    protected function getBaseUrl(): string
    {
        $baseUrl = rtrim(config('services.invoice2data.url'), '/');

        if (! str_starts_with($baseUrl, 'http://') && ! str_starts_with($baseUrl, 'https://')) {
            $baseUrl = 'https://' . $baseUrl;
        }

        return $baseUrl;
    }
}
// CLAUDE-CHECKPOINT
