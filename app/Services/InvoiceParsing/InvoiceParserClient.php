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
} // CLAUDE-CHECKPOINT
