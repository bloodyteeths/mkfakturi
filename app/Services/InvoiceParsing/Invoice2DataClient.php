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
        $absolutePath = Storage::disk($disk)->path($filePath);

        $baseUrl = rtrim(config('services.invoice2data.url'), '/');
        // Ensure we have an explicit scheme to avoid 301 http→https
        // redirects that can change POST to GET and cause 405 responses.
        if (! str_starts_with($baseUrl, 'http://') && ! str_starts_with($baseUrl, 'https://')) {
            $baseUrl = 'https://'.$baseUrl;
        }

        $timeout = (int) config('services.invoice2data.timeout', 90);

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(10)
                ->attach('file', file_get_contents($absolutePath), $originalName)
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
        $absolutePath = Storage::disk($disk)->path($filePath);

        $baseUrl = rtrim(config('services.invoice2data.url'), '/');
        // Ensure we have an explicit scheme to avoid 301 http→https
        // redirects that can change POST to GET and cause 405 responses.
        if (! str_starts_with($baseUrl, 'http://') && ! str_starts_with($baseUrl, 'https://')) {
            $baseUrl = 'https://'.$baseUrl;
        }

        $timeout = (int) config('services.invoice2data.timeout', 90);

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(10)
                ->attach('file', file_get_contents($absolutePath), $originalName)
                ->post($baseUrl.'/ocr', [
                    'company_id' => $companyId,
                    'request_id' => (string) Str::uuid(),
                    // Use plain text format for better compatibility
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
}
