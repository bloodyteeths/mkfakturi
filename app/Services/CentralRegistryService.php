<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Central Registry lookup service.
 *
 * Searches the Macedonian Central Registry (crm.com.mk) by company name
 * or EMBS number and returns structured company data.
 *
 * The crm.com.mk website is public Open Government data - no API key needed.
 */
class CentralRegistryService
{
    protected string $baseUrl;

    protected int $cacheTtl;

    public function __construct()
    {
        $this->baseUrl = config('mk.central_registry.base_url', 'https://www.crm.com.mk');
        $this->cacheTtl = config('mk.central_registry.cache_ttl', 300);
    }

    /**
     * Search the Central Registry by company name or EMBS number.
     *
     * @param  string  $query  Company name or EMBS number
     * @return array<int, array{embs: string, name: string, address: string|null, city: string|null, activity_code: string|null, status: string|null, edb: string|null}>
     */
    public function search(string $query): array
    {
        $query = trim($query);

        if (strlen($query) < 2) {
            return [];
        }

        $cacheKey = 'crm_lookup:'.md5($query);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($query) {
            return $this->fetchFromRegistry($query);
        });
    }

    /**
     * Fetch company data from crm.com.mk.
     *
     * @param  string  $query  Search term
     * @return array
     */
    protected function fetchFromRegistry(string $query): array
    {
        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withHeaders([
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'mk,en;q=0.5',
                ])
                ->get($this->baseUrl.'/mk/search', [
                    'q' => $query,
                ]);

            if (! $response->successful()) {
                Log::warning('CentralRegistryService: Non-200 response', [
                    'status' => $response->status(),
                    'query' => $query,
                ]);

                return [];
            }

            return $this->parseHtmlResponse($response->body());
        } catch (\Throwable $e) {
            Log::warning('CentralRegistryService: Request failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Parse the HTML response from crm.com.mk search results.
     *
     * @param  string  $html  Raw HTML response
     * @return array
     */
    protected function parseHtmlResponse(string $html): array
    {
        $results = [];

        if (empty($html)) {
            return $results;
        }

        try {
            libxml_use_internal_errors(true);
            $doc = new \DOMDocument;
            $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
            $xpath = new \DOMXPath($doc);

            // Search result rows - crm.com.mk uses table-based layout
            $rows = $xpath->query('//table[contains(@class, "result")]//tr[td]');

            if ($rows === false || $rows->length === 0) {
                // Try alternative selector patterns
                $rows = $xpath->query('//div[contains(@class, "search-result")]');
            }

            if ($rows === false) {
                return $results;
            }

            foreach ($rows as $row) {
                $cells = $xpath->query('.//td', $row);
                if ($cells === false || $cells->length < 2) {
                    continue;
                }

                $result = [
                    'embs' => '',
                    'name' => '',
                    'address' => null,
                    'city' => null,
                    'activity_code' => null,
                    'status' => null,
                    'edb' => null,
                ];

                // EMBS is typically in the first column
                if ($cells->length >= 1) {
                    $result['embs'] = trim($cells->item(0)->textContent);
                }

                // Company name in the second column
                if ($cells->length >= 2) {
                    $result['name'] = trim($cells->item(1)->textContent);
                }

                // Address in the third column
                if ($cells->length >= 3) {
                    $addressText = trim($cells->item(2)->textContent);
                    $parts = explode(',', $addressText);
                    $result['address'] = trim($parts[0] ?? '');
                    $result['city'] = trim($parts[1] ?? '');
                }

                // Activity code in the fourth column
                if ($cells->length >= 4) {
                    $result['activity_code'] = trim($cells->item(3)->textContent);
                }

                // Status in the fifth column
                if ($cells->length >= 5) {
                    $result['status'] = trim($cells->item(4)->textContent);
                }

                // Skip empty results
                if (! empty($result['embs']) || ! empty($result['name'])) {
                    $results[] = $result;
                }
            }

            libxml_clear_errors();
        } catch (\Throwable $e) {
            Log::warning('CentralRegistryService: HTML parse error', [
                'error' => $e->getMessage(),
            ]);
        }

        return array_slice($results, 0, 20);
    }
}
// CLAUDE-CHECKPOINT
