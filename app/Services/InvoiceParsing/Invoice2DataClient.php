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
        $disk = env('FILESYSTEM_DISK', 'public');
        $fileContents = $this->readFileFromStorage($disk, $filePath, $originalName, $companyId);

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
        $disk = env('FILESYSTEM_DISK', 'public');
        $fileContents = $this->readFileFromStorage($disk, $filePath, $originalName, $companyId);

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
    }

    /**
     * Read file from storage with null-safety and diagnostic logging.
     *
     * Storage::get() silently returns null when the S3/R2 disk doesn't have
     * 'throw' => true and the read fails (permissions, missing file, etc.).
     *
     * @throws Invoice2DataServiceException
     */
    protected function readFileFromStorage(string $disk, string $filePath, string $originalName, int $companyId): string
    {
        $exists = Storage::disk($disk)->exists($filePath);

        if (! $exists) {
            Log::error('Invoice2DataClient: file does not exist in storage', [
                'disk' => $disk,
                'path' => $filePath,
                'file' => $originalName,
                'company_id' => $companyId,
            ]);

            throw new Invoice2DataServiceException(
                "File not found in storage [{$disk}]: {$filePath}",
                404
            );
        }

        $fileContents = Storage::disk($disk)->get($filePath);

        if ($fileContents === null || $fileContents === '') {
            Log::error('Invoice2DataClient: Storage::get() returned null/empty despite file existing', [
                'disk' => $disk,
                'path' => $filePath,
                'file' => $originalName,
                'company_id' => $companyId,
                'exists_check' => $exists,
                'driver' => config("filesystems.disks.{$disk}.driver"),
            ]);

            throw new Invoice2DataServiceException(
                "Failed to read file from storage [{$disk}]: {$filePath}. Storage::get() returned null.",
                500
            );
        }

        return $fileContents;
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
            $disk = env('FILESYSTEM_DISK', 'public');
            $fileContents = $this->readFileFromStorage($disk, $filePath, $originalName, $companyId);
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

