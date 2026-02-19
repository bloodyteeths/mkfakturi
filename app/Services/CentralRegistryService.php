<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Central Registry lookup service.
 *
 * Uses the CRMPublicPortalApi backend that powers the crm.com.mk SPA.
 * The "Open Data" section (Отворени податоци → Основен профил на регистриран субјект)
 * is a free, public service launched Oct 2020 under North Macedonia's
 * Open Government Partnership commitment.
 *
 * API discovery (reverse-engineered from crm.com.mk Angular SPA):
 *   Config: GET /assets/app.config.json → { apiUrl: "https://www.crm.com.mk/CRMPublicPortalApi/api/" }
 *   Search: GET {apiUrl}freeservice/basicProfile?name={query}&ut=false
 *   Detail: POST {apiUrl}freeservice/basicProfile/{embs} (body: base64-encoded screen info)
 *
 * NOTE: The API enforces reCAPTCHA v3 (HTTP 412 "Recaptcha token missing").
 * The reCAPTCHA site key (6LcLUNAZAAAAAJ08HQkGbOwh5F2RP5LCpxwQycdS) is domain-locked
 * to crm.com.mk, so server-to-server calls cannot generate valid tokens.
 * The service returns an empty result with a 'captcha_required' flag so the UI
 * can show a "Search on crm.com.mk" fallback link.
 *
 * Returns: ЕМБС, name, address, legal form, founding date, size, status, activity code.
 */
class CentralRegistryService
{
    protected string $baseUrl;

    protected string $apiPath;

    protected int $cacheTtl;

    public function __construct()
    {
        $this->baseUrl = config('mk.central_registry.base_url', 'https://www.crm.com.mk');
        $this->apiPath = config('mk.central_registry.api_path', '/CRMPublicPortalApi/api/');
        $this->cacheTtl = config('mk.central_registry.cache_ttl', 300);
    }

    /**
     * Search the Central Registry by company name or EMBS number.
     *
     * @param  string  $query  Company name or EMBS number
     * @return array{results: array, captcha_required: bool, lookup_url: string|null}
     */
    public function search(string $query): array
    {
        $query = trim($query);

        if (strlen($query) < 2) {
            return ['results' => [], 'captcha_required' => false, 'lookup_url' => null];
        }

        $cacheKey = 'crm_lookup:'.md5($query);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($query) {
            return $this->fetchFromApi($query);
        });
    }

    /**
     * Build the crm.com.mk lookup URL for a given query.
     * Users can open this in their browser to search manually.
     */
    public function buildLookupUrl(string $query): string
    {
        $isEmbs = preg_match('/^\d{5,13}$/', $query);

        $params = $isEmbs
            ? ['embs' => $query]
            : ['s' => $query];

        return $this->baseUrl.'/mk/otvoreni-podatotsi/osnoven-profil-na-registriran-subjekt?'.http_build_query($params);
    }

    /**
     * Fetch company data from CRMPublicPortalApi.
     *
     * Endpoint discovery (from crm.com.mk Angular SPA main bundle):
     *   - GET freeservice/basicProfile?name={query}&ut=false (search by name)
     *   - POST freeservice/basicProfile/{embs} (lookup by EMBS)
     *   - GET freeservice/statusInfo/{embs} (status info)
     *
     * All freeservice/* endpoints require reCAPTCHA v3 token in header.
     */
    protected function fetchFromApi(string $query): array
    {
        $isEmbs = preg_match('/^\d{5,13}$/', $query);
        $base = rtrim($this->baseUrl.$this->apiPath, '/').'/';
        $lookupUrl = $this->buildLookupUrl($query);

        // Primary endpoints (discovered from JS bundle)
        $endpoints = $isEmbs
            ? [
                ['method' => 'GET', 'url' => $base.'freeservice/basicProfile/'.$query, 'params' => []],
                ['method' => 'GET', 'url' => $base.'freeservice/basicProfile', 'params' => ['name' => $query, 'ut' => 'true']],
            ]
            : [
                ['method' => 'GET', 'url' => $base.'freeservice/basicProfile', 'params' => ['name' => $query, 'ut' => 'false']],
            ];

        $captchaRequired = false;

        foreach ($endpoints as $endpoint) {
            $result = $this->tryEndpoint(
                $endpoint['method'],
                $endpoint['url'],
                $endpoint['params'],
                $endpoint['body'] ?? null
            );

            if ($result['captcha_required']) {
                $captchaRequired = true;
            }

            if (! empty($result['data'])) {
                return [
                    'results' => $result['data'],
                    'captcha_required' => false,
                    'lookup_url' => null,
                ];
            }
        }

        if ($captchaRequired) {
            Log::info('CentralRegistryService: API requires reCAPTCHA, returning fallback URL', [
                'query' => $query,
                'lookup_url' => $lookupUrl,
            ]);

            return [
                'results' => [],
                'captcha_required' => true,
                'lookup_url' => $lookupUrl,
            ];
        }

        return [
            'results' => [],
            'captcha_required' => false,
            'lookup_url' => $lookupUrl,
        ];
    }

    /**
     * Try a single API endpoint and parse the response.
     *
     * @return array{data: array, captcha_required: bool}
     */
    protected function tryEndpoint(string $method, string $url, array $params = [], ?array $body = null): array
    {
        try {
            $request = Http::timeout(8)
                ->connectTimeout(4)
                ->withHeaders([
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'mk,en;q=0.5',
                    'Origin' => 'https://www.crm.com.mk',
                    'Referer' => 'https://www.crm.com.mk/mk/otvoreni-podatotsi/osnoven-profil-na-registriran-subjekt',
                ]);

            $response = $method === 'POST'
                ? $request->post($url, $body ?? $params)
                : $request->get($url, $params);

            // HTTP 412 = reCAPTCHA required
            if ($response->status() === 412) {
                return ['data' => [], 'captcha_required' => true];
            }

            if (! $response->successful()) {
                return ['data' => [], 'captcha_required' => false];
            }

            $data = $response->json();

            if (empty($data)) {
                return ['data' => [], 'captcha_required' => false];
            }

            Log::info('CentralRegistryService: Successful API response', [
                'url' => $url,
                'method' => $method,
                'result_count' => is_array($data) ? count($data) : 1,
            ]);

            return ['data' => $this->normalizeApiResponse($data), 'captcha_required' => false];
        } catch (\Throwable $e) {
            return ['data' => [], 'captcha_required' => false];
        }
    }

    /**
     * Normalize the API response into our standard format.
     *
     * Handles both single-object and array responses.
     */
    protected function normalizeApiResponse(mixed $data): array
    {
        // If it's a single object (EMBS lookup), wrap it
        if (isset($data['embs']) || isset($data['EMBS']) || isset($data['embsNumber'])) {
            $data = [$data];
        }

        // If the data is wrapped in a 'data' or 'results' key
        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        } elseif (isset($data['results']) && is_array($data['results'])) {
            $data = $data['results'];
        } elseif (isset($data['items']) && is_array($data['items'])) {
            $data = $data['items'];
        }

        if (! is_array($data)) {
            return [];
        }

        $results = [];
        foreach ($data as $item) {
            if (! is_array($item)) {
                continue;
            }

            $result = [
                'embs' => $this->extractField($item, ['embs', 'EMBS', 'embsNumber', 'Embs', 'embS']),
                'name' => $this->extractField($item, ['name', 'Name', 'fullName', 'FullName', 'subjectName', 'SubjectName', 'nazivNaSubjekt']),
                'address' => $this->extractField($item, ['address', 'Address', 'adresa', 'Adresa', 'streetAddress']),
                'city' => $this->extractField($item, ['city', 'City', 'grad', 'Grad', 'municipality', 'Municipality', 'opstina', 'Opstina']),
                'activity_code' => $this->extractField($item, ['activityCode', 'ActivityCode', 'dejnost', 'Dejnost', 'nkdCode', 'NKDCode', 'activity']),
                'status' => $this->extractField($item, ['status', 'Status', 'sostojba', 'Sostojba', 'subjectStatus']),
                'edb' => $this->extractField($item, ['edb', 'EDB', 'Edb', 'taxNumber', 'TaxNumber', 'danocenBroj']),
                'legal_form' => $this->extractField($item, ['legalForm', 'LegalForm', 'pravnaForma', 'PravnaForma']),
                'size' => $this->extractField($item, ['size', 'Size', 'golemina', 'Golemina']),
            ];

            if (! empty($result['embs']) || ! empty($result['name'])) {
                $results[] = $result;
            }
        }

        return array_slice($results, 0, 20);
    }

    /**
     * Extract a field value from a data array, trying multiple key variations.
     */
    protected function extractField(array $data, array $possibleKeys): ?string
    {
        foreach ($possibleKeys as $key) {
            if (isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null) {
                return trim((string) $data[$key]);
            }
        }

        return null;
    }
}
