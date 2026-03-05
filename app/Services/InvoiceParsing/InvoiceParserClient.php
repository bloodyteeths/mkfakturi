<?php

namespace App\Services\InvoiceParsing;

interface InvoiceParserClient
{
    /**
     * Parse a PDF invoice and return normalized invoice data.
     *
     * @return array<string,mixed>
     */
    public function parse(int $companyId, string $filePath, string $originalName, string $from, ?string $subject): array;

    /**
     * Extract text from an image using OCR.
     *
     * @return array<string,mixed>
     */
    public function ocr(int $companyId, string $filePath, string $originalName): array;

    /**
     * Parse a receipt/invoice image and return structured invoice data.
     *
     * @return array<string,mixed>
     */
    public function parseReceipt(int $companyId, string $filePath, string $originalName, ?string $rawContents = null): array;
} // CLAUDE-CHECKPOINT
