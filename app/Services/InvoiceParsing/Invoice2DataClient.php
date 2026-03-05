<?php

namespace App\Services\InvoiceParsing;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Invoice2DataClient implements InvoiceParserClient
{
    /**
     * @return array<string,mixed>
     *
     * @throws \App\Services\InvoiceParsing\Invoice2DataServiceException
     */
    public function parse(int $companyId, string $filePath, string $originalName, string $from, ?string $subject): array
    {
        $disk = config('filesystems.default', 'local');
        $fileContents = Storage::disk($disk)->get($filePath);

        $baseUrl = rtrim(config('services.invoice2data.url'), '/');
        if (! str_starts_with($baseUrl, 'http://') && ! str_starts_with($baseUrl, 'https://')) {
            $baseUrl = 'https://'.$baseUrl;
        }

        $timeout = (int) config('services.invoice2data.timeout', 90);

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(10)
                ->attach('file', $fileContents, $originalName)
                ->post($baseUrl.'/parse', [
                    'company_id' => $companyId,
                    'from' => $from,
                    'subject' => $subject,
                    'request_id' => (string) Str::uuid(),
                ]);

            $response->throw();

            return $response->json();
        } catch (ConnectionException $e) {
            Log::warning('invoice2data-service unreachable during parse', [
                'url' => $baseUrl.'/parse',
                'company_id' => $companyId,
                'file' => $originalName,
                'error' => $e->getMessage(),
            ]);

            throw new Invoice2DataServiceException(
                'Invoice parsing service is currently unavailable. Please try again later.',
                503,
                $e
            );
        }
    }

    /**
     * @return array<string,mixed>
     *
     * @throws \App\Services\InvoiceParsing\Invoice2DataServiceException
     */
    public function ocr(int $companyId, string $filePath, string $originalName): array
    {
        $disk = config('filesystems.default', 'local');
        $fileContents = Storage::disk($disk)->get($filePath);

        $baseUrl = rtrim(config('services.invoice2data.url'), '/');
        if (! str_starts_with($baseUrl, 'http://') && ! str_starts_with($baseUrl, 'https://')) {
            $baseUrl = 'https://'.$baseUrl;
        }

        $timeout = (int) config('services.invoice2data.timeout', 90);

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(10)
                ->attach('file', $fileContents, $originalName)
                ->post($baseUrl.'/ocr', [
                    'company_id' => $companyId,
                    'request_id' => (string) Str::uuid(),
                ]);

            $response->throw();

            return $response->json();
        } catch (ConnectionException $e) {
            Log::warning('invoice2data-service unreachable during OCR', [
                'url' => $baseUrl.'/ocr',
                'company_id' => $companyId,
                'file' => $originalName,
                'error' => $e->getMessage(),
            ]);

            throw new Invoice2DataServiceException(
                'Invoice OCR service is currently unavailable. Please try again later.',
                503,
                $e
            );
        }
    } // CLAUDE-CHECKPOINT

    /**
     * Parse a receipt/invoice image and return structured invoice data.
     * Uses the /parse endpoint with Gemini Vision AI for accurate extraction.
     *
     * @return array<string,mixed>
     *
     * @throws \App\Services\InvoiceParsing\Invoice2DataServiceException
     */
    public function parseReceipt(int $companyId, string $filePath, string $originalName, ?string $rawContents = null): array
    {
        $start = microtime(true);

        // Use raw contents if provided (avoids S3 round-trip), otherwise read from storage
        if ($rawContents !== null) {
            $fileContents = $rawContents;
        } else {
            $disk = config('filesystems.default', 'local');
            $fileContents = Storage::disk($disk)->get($filePath);
        }

        $readTime = round((microtime(true) - $start) * 1000);

        $baseUrl = rtrim(config('services.invoice2data.url'), '/');
        if (! str_starts_with($baseUrl, 'http://') && ! str_starts_with($baseUrl, 'https://')) {
            $baseUrl = 'https://'.$baseUrl;
        }

        $timeout = (int) config('services.invoice2data.timeout', 90);
        $parseTimeout = max($timeout, 180);

        try {
            $apiStart = microtime(true);

            $response = Http::timeout($parseTimeout)
                ->connectTimeout(30)
                ->attach('file', $fileContents, $originalName)
                ->post($baseUrl.'/parse', [
                    'company_id' => $companyId,
                    'request_id' => (string) Str::uuid(),
                ]);

            $apiTime = round((microtime(true) - $apiStart) * 1000);
            $totalTime = round((microtime(true) - $start) * 1000);

            Log::info('parseReceipt timing', [
                'file_read_ms' => $readTime,
                'api_call_ms' => $apiTime,
                'total_ms' => $totalTime,
                'file_size_kb' => round(strlen($fileContents) / 1024),
            ]);

            $response->throw();

            return $response->json();
        } catch (ConnectionException $e) {
            Log::warning('invoice2data-service unreachable during parseReceipt', [
                'url' => $baseUrl.'/parse',
                'company_id' => $companyId,
                'file' => $originalName,
                'error' => $e->getMessage(),
            ]);

            throw new Invoice2DataServiceException(
                'Invoice parsing service is currently unavailable. Please try again later.',
                503,
                $e
            );
        }
    }
}

