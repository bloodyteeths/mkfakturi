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

    /**
     * Classify a document using AI vision (invoice, receipt, bank_statement, contract, tax_form, product_list, other).
     *
     * @return array{type: string, confidence: float, summary: string}
     */
    public function classify(int $companyId, string $filePath, string $originalName): array;

    /**
     * Parse a product list / price catalog and return structured product data.
     *
     * @return array{products: array, currency: string, source_company: string|null}
     */
    public function parseProductList(int $companyId, string $filePath, string $originalName): array;

    /**
     * Parse a tax form (UJP) and return structured field data.
     *
     * @return array{form_type: string, declarant: array, period: array, fields: array, totals: array}
     */
    public function parseTaxForm(int $companyId, string $filePath, string $originalName): array;
} // CLAUDE-CHECKPOINT
