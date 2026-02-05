<?php

namespace App\Services\Banking\Parsers;

use App\Models\BankAccount;

/**
 * Bank CSV Parser Interface
 *
 * All bank-specific CSV parsers must implement this interface.
 * Each parser handles the unique CSV format exported by a specific bank.
 */
interface BankParserInterface
{
    /**
     * Get the bank identifier code
     *
     * @return string Bank identifier (e.g., 'nlb', 'stopanska', 'komercijalna')
     */
    public function getBankCode(): string;

    /**
     * Get the human-readable bank name
     *
     * @return string Bank name for display
     */
    public function getBankName(): string;

    /**
     * Check if this parser can handle the given content
     *
     * @param string $content CSV file content
     * @return bool True if this parser can parse the content
     */
    public function canParse(string $content): bool;

    /**
     * Parse CSV content and return normalized transaction data
     *
     * @param string $content CSV file content
     * @return array Array of normalized transaction records
     */
    public function parse(string $content): array;

    /**
     * Import transactions from CSV content into the database
     *
     * @param string $content CSV file content
     * @param BankAccount $account Bank account to associate transactions with
     * @return array Import result with 'imported', 'duplicates', 'failed' counts
     */
    public function import(string $content, BankAccount $account): array;

    /**
     * Get the expected CSV delimiter
     *
     * @return string Delimiter character (e.g., ',', ';', '\t')
     */
    public function getDelimiter(): string;

    /**
     * Get the expected file encoding
     *
     * @return string Encoding (e.g., 'UTF-8', 'Windows-1251', 'ISO-8859-1')
     */
    public function getEncoding(): string;
}

// CLAUDE-CHECKPOINT
