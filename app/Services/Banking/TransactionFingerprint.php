<?php

namespace App\Services\Banking;

/**
 * P0-11: Transaction Fingerprint Generator
 *
 * Generates SHA256 fingerprints for bank transactions to enable
 * deduplication across CSV, email, and PSD2 imports.
 *
 * Priority order:
 * 1. If external_transaction_id exists, use it (most reliable - bank-provided)
 * 2. Otherwise build composite from: company_id, bank_account_id, transaction_date,
 *    amount (normalized), currency, type, reference (normalized),
 *    description first 100 chars (normalized), counterparty_account
 */
class TransactionFingerprint
{
    /**
     * Generate a robust fingerprint for transaction deduplication.
     *
     * @param  array  $tx  Transaction data array with keys matching BankTransaction columns
     * @return string SHA256 hex hash (64 characters)
     */
    public function generate(array $tx): string
    {
        // If bank provides a transaction ID, use it (most reliable)
        if (! empty($tx['external_transaction_id'])) {
            return hash('sha256', ($tx['company_id'] ?? '') . '|' . $tx['external_transaction_id']);
        }

        // Build composite fingerprint from transaction fields
        $parts = [
            $tx['company_id'] ?? '',
            $tx['bank_account_id'] ?? '',
            $this->normalizeDate($tx['transaction_date'] ?? ''),
            $this->normalizeAmount($tx['amount'] ?? '0'),
            mb_strtoupper(trim($tx['currency'] ?? 'MKD')),
            mb_strtolower(trim($tx['transaction_type'] ?? '')),
            $this->normalizeText($tx['transaction_reference'] ?? $tx['payment_reference'] ?? ''),
            $this->normalizeText(mb_substr($tx['description'] ?? '', 0, 100)),
            $this->normalizeText($tx['creditor_account'] ?? $tx['creditor_iban'] ?? $tx['debtor_account'] ?? $tx['debtor_iban'] ?? ''),
        ];

        return hash('sha256', implode('|', $parts));
    }

    /**
     * Normalize an amount string using bcadd for precision.
     *
     * Ensures consistent representation regardless of formatting differences.
     * E.g. "1000.00", "1000", "1000.0" all become "1000.00"
     *
     * @param  string|float|int  $amount  The amount to normalize
     * @return string Normalized amount with 2 decimal places
     */
    public function normalizeAmount($amount): string
    {
        // Convert to string, strip any non-numeric chars except minus and dot
        $cleaned = preg_replace('/[^0-9.\-]/', '', (string) $amount);

        if ($cleaned === '' || $cleaned === '-') {
            $cleaned = '0';
        }

        // Use bcadd for precision: adding 0 normalizes the number
        return bcadd($cleaned, '0', 2);
    }

    /**
     * Normalize text for consistent fingerprinting.
     *
     * Converts to lowercase, removes extra whitespace, and keeps only
     * letters and numbers (with full Unicode support for Cyrillic etc).
     *
     * @param  string  $text  The text to normalize
     * @return string Normalized text
     */
    public function normalizeText(string $text): string
    {
        // Convert to lowercase (multibyte safe for Cyrillic)
        $text = mb_strtolower($text, 'UTF-8');

        // Remove all characters except Unicode letters and digits
        $text = preg_replace('/[^\p{L}\p{N}]/u', '', $text);

        return $text;
    }

    /**
     * Normalize a date value to Y-m-d format.
     *
     * @param  string|\DateTimeInterface  $date  The date to normalize
     * @return string Date in Y-m-d format, or empty string if unparseable
     */
    public function normalizeDate($date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        if (empty($date)) {
            return '';
        }

        try {
            return (new \DateTime($date))->format('Y-m-d');
        } catch (\Exception $e) {
            return (string) $date;
        }
    }
}

// CLAUDE-CHECKPOINT
