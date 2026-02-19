<?php

namespace App\Services\EFaktura;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * UJP E-Invoice REST API Client.
 *
 * Provides programmatic access to the Macedonian tax authority (UJP)
 * e-Invoice system via the official REST API. Handles authentication,
 * invoice submission, status checking, validation, and inbox management.
 *
 * NOTE: These endpoints are based on the expected UJP API specification.
 * The official API is not yet publicly documented — endpoint paths and
 * request/response shapes may change once the API is officially released.
 *
 * Configuration is read from config/mk.php 'efaktura' section:
 * - portal_url: Base URL for the API (shared with portal mode)
 * - api_key: API key for authentication
 * - api_secret: API secret for authentication
 * - environment: 'production' or 'sandbox'
 * - timeout: HTTP request timeout in seconds
 *
 * @see config/mk.php efaktura section
 * @see tools/efaktura_upload.php for API endpoint constants
 */
class UjpApiClient
{
    /**
     * Base URL for the UJP API.
     */
    protected string $baseUrl;

    /**
     * API key for authentication.
     */
    protected string $apiKey;

    /**
     * API secret for authentication.
     */
    protected string $apiSecret;

    /**
     * Environment: 'production' or 'sandbox'.
     */
    protected string $environment;

    /**
     * HTTP request timeout in seconds.
     */
    protected int $timeout;

    /**
     * Cache key for the bearer token.
     */
    protected const TOKEN_CACHE_KEY = 'ujp_api_bearer_token';

    /**
     * Token cache TTL in seconds (55 minutes — tokens typically expire at 60 min).
     */
    protected const TOKEN_CACHE_TTL = 3300;

    /**
     * API endpoint paths.
     *
     * Based on tools/efaktura_upload.php API_ENDPOINTS constants.
     * These are expected endpoints — not yet officially documented by UJP.
     */
    protected const ENDPOINTS = [
        'auth' => '/api/v1/auth/token',
        'submit' => '/api/v1/invoices/submit',
        'status' => '/api/v1/invoices/{id}/status',
        'validate' => '/api/v1/invoices/validate',
        'inbox' => '/api/v1/invoices/inbox',
        'accept' => '/api/v1/invoices/{id}/accept',
        'reject' => '/api/v1/invoices/{id}/reject',
        'xml' => '/api/v1/invoices/{id}/xml',
    ];

    /**
     * Create a new UjpApiClient instance.
     *
     * Reads configuration from config/mk.php efaktura section.
     */
    public function __construct()
    {
        $config = config('mk.efaktura', []);

        $this->baseUrl = rtrim($config['portal_url'] ?? 'https://e-ujp.ujp.gov.mk', '/');
        $this->apiKey = $config['api_key'] ?? '';
        $this->apiSecret = $config['api_secret'] ?? '';
        $this->environment = $config['environment'] ?? 'production';
        $this->timeout = (int) ($config['timeout'] ?? 30);
    }

    /**
     * Authenticate with the UJP API and obtain a bearer token.
     *
     * Posts api_key and api_secret to the auth endpoint.
     * The token is cached for 55 minutes (tokens typically expire at 60 min).
     *
     * NOTE: Auth endpoint and request shape are based on expected UJP API spec.
     *
     * @return string Bearer token
     *
     * @throws \RuntimeException If authentication fails
     */
    public function authenticate(): string
    {
        // Return cached token if available
        $cachedToken = Cache::get(self::TOKEN_CACHE_KEY);
        if ($cachedToken) {
            return $cachedToken;
        }

        Log::info('UjpApiClient: Authenticating with UJP API', [
            'environment' => $this->environment,
            'base_url' => $this->baseUrl,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl . self::ENDPOINTS['auth'], [
                    'api_key' => $this->apiKey,
                    'api_secret' => $this->apiSecret,
                ]);

            if (! $response->successful()) {
                Log::error('UjpApiClient: Authentication failed', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);

                throw new \RuntimeException(
                    'UJP API authentication failed with HTTP ' . $response->status()
                );
            }

            $token = $response->json('access_token');

            if (empty($token)) {
                throw new \RuntimeException(
                    'UJP API authentication succeeded but no access_token in response'
                );
            }

            // Cache the token for 55 minutes
            Cache::put(self::TOKEN_CACHE_KEY, $token, self::TOKEN_CACHE_TTL);

            Log::info('UjpApiClient: Authentication successful, token cached');

            return $token;

        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('UjpApiClient: Authentication exception', [
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'UJP API authentication failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Submit a signed UBL XML invoice to the UJP e-Invoice system.
     *
     * Posts the signed XML to the submit endpoint with Content-Type application/xml
     * and Bearer authentication. Returns the submission result including status
     * and receipt number.
     *
     * NOTE: Request/response shape is based on expected UJP API spec.
     *
     * @param  string  $signedXml  The digitally signed UBL XML content
     * @return array{success: bool, status: string, receipt_number: string|null, response: array}
     *
     * @throws \RuntimeException If submission fails
     */
    public function submitInvoice(string $signedXml): array
    {
        Log::info('UjpApiClient: Submitting invoice via API', [
            'xml_size' => strlen($signedXml),
        ]);

        try {
            $token = $this->authenticate();

            $response = Http::timeout($this->timeout)
                ->connectTimeout(10)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/xml',
                    'Accept' => 'application/json',
                ])
                ->withBody($signedXml, 'application/xml')
                ->post($this->baseUrl . self::ENDPOINTS['submit']);

            $responseData = $response->json() ?? [];

            if (! $response->successful()) {
                Log::error('UjpApiClient: Invoice submission failed', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);

                return [
                    'success' => false,
                    'status' => $responseData['status'] ?? 'failed',
                    'receipt_number' => null,
                    'response' => $responseData,
                    'error_message' => $responseData['message'] ?? 'Submission failed with HTTP ' . $response->status(),
                ];
            }

            $result = [
                'success' => true,
                'status' => $responseData['status'] ?? 'accepted',
                'receipt_number' => $responseData['receipt_number'] ?? $responseData['id'] ?? null,
                'response' => $responseData,
            ];

            Log::info('UjpApiClient: Invoice submitted successfully', [
                'receipt_number' => $result['receipt_number'],
                'status' => $result['status'],
            ]);

            return $result;

        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('UjpApiClient: Invoice submission exception', [
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'UJP API invoice submission failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Check the status of a previously submitted invoice.
     *
     * Queries the status endpoint using the receipt number returned from submitInvoice.
     *
     * NOTE: Endpoint path and response shape are based on expected UJP API spec.
     *
     * @param  string  $receiptNumber  The receipt number from submission
     * @return array{status: string, details: array}
     */
    public function checkStatus(string $receiptNumber): array
    {
        Log::info('UjpApiClient: Checking invoice status', [
            'receipt_number' => $receiptNumber,
        ]);

        try {
            $token = $this->authenticate();

            $endpoint = str_replace('{id}', $receiptNumber, self::ENDPOINTS['status']);

            $response = Http::timeout($this->timeout)
                ->connectTimeout(10)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl . $endpoint);

            if (! $response->successful()) {
                Log::warning('UjpApiClient: Status check failed', [
                    'receipt_number' => $receiptNumber,
                    'status' => $response->status(),
                ]);

                return [
                    'status' => 'unknown',
                    'details' => [
                        'http_status' => $response->status(),
                        'error' => $response->body(),
                    ],
                ];
            }

            $data = $response->json() ?? [];

            return [
                'status' => $data['status'] ?? 'unknown',
                'details' => $data,
            ];

        } catch (\Throwable $e) {
            Log::error('UjpApiClient: Status check exception', [
                'receipt_number' => $receiptNumber,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'unknown',
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Validate a UBL XML invoice without submitting it.
     *
     * Posts the XML to the validation endpoint. Useful for pre-submission
     * checks to catch errors before attempting actual submission.
     *
     * NOTE: Endpoint path and response shape are based on expected UJP API spec.
     *
     * @param  string  $xml  UBL XML content (may or may not be signed)
     * @return array{valid: bool, errors: array, warnings: array}
     */
    public function validateInvoice(string $xml): array
    {
        Log::info('UjpApiClient: Validating invoice XML', [
            'xml_size' => strlen($xml),
        ]);

        try {
            $token = $this->authenticate();

            $response = Http::timeout($this->timeout)
                ->connectTimeout(10)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/xml',
                    'Accept' => 'application/json',
                ])
                ->withBody($xml, 'application/xml')
                ->post($this->baseUrl . self::ENDPOINTS['validate']);

            $data = $response->json() ?? [];

            if (! $response->successful()) {
                Log::warning('UjpApiClient: Validation request failed', [
                    'status' => $response->status(),
                ]);

                return [
                    'valid' => false,
                    'errors' => $data['errors'] ?? [
                        'Validation request failed with HTTP ' . $response->status(),
                    ],
                    'warnings' => $data['warnings'] ?? [],
                ];
            }

            return [
                'valid' => $data['valid'] ?? ($response->successful() && empty($data['errors'])),
                'errors' => $data['errors'] ?? [],
                'warnings' => $data['warnings'] ?? [],
            ];

        } catch (\Throwable $e) {
            Log::error('UjpApiClient: Validation exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'errors' => ['Validation failed: ' . $e->getMessage()],
                'warnings' => [],
            ];
        }
    }

    /**
     * Poll the UJP inbox for new incoming e-invoices.
     *
     * Fetches invoices with status=new from the inbox endpoint.
     * Returns normalized array of inbox items.
     *
     * NOTE: Endpoint path and response shape are based on expected UJP API spec.
     *
     * @return array<int, array{portal_id: string, xml: string, received_at: string, status: string}>
     */
    public function pollInbox(): array
    {
        Log::info('UjpApiClient: Polling inbox for new invoices');

        try {
            $token = $this->authenticate();

            $response = Http::timeout($this->timeout)
                ->connectTimeout(10)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl . self::ENDPOINTS['inbox'], [
                    'status' => 'new',
                ]);

            if (! $response->successful()) {
                Log::error('UjpApiClient: Inbox poll failed', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);

                return [];
            }

            $items = $response->json('data') ?? [];

            Log::info('UjpApiClient: Inbox poll returned items', [
                'count' => count($items),
            ]);

            return array_map(function ($item) {
                return [
                    'portal_id' => $item['id'] ?? $item['document_id'] ?? '',
                    'xml' => $item['xml'] ?? $item['ubl_content'] ?? '',
                    'received_at' => $item['received_at'] ?? $item['date'] ?? now()->toIso8601String(),
                    'status' => $item['status'] ?? 'new',
                ];
            }, $items);

        } catch (\Throwable $e) {
            Log::error('UjpApiClient: Inbox poll exception', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Accept an incoming e-invoice.
     *
     * Posts acceptance to the accept endpoint for a specific invoice ID.
     *
     * NOTE: Endpoint path and response shape are based on expected UJP API spec.
     *
     * @param  string  $invoiceId  The portal/API invoice ID
     * @return array{success: bool, status: string, message: string}
     */
    public function acceptIncoming(string $invoiceId): array
    {
        Log::info('UjpApiClient: Accepting incoming invoice', [
            'invoice_id' => $invoiceId,
        ]);

        try {
            $token = $this->authenticate();

            $endpoint = str_replace('{id}', $invoiceId, self::ENDPOINTS['accept']);

            $response = Http::timeout($this->timeout)
                ->connectTimeout(10)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl . $endpoint);

            $data = $response->json() ?? [];

            if (! $response->successful()) {
                Log::error('UjpApiClient: Accept incoming invoice failed', [
                    'invoice_id' => $invoiceId,
                    'status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'status' => $data['status'] ?? 'error',
                    'message' => $data['message'] ?? 'Accept failed with HTTP ' . $response->status(),
                ];
            }

            Log::info('UjpApiClient: Incoming invoice accepted', [
                'invoice_id' => $invoiceId,
            ]);

            return [
                'success' => true,
                'status' => $data['status'] ?? 'accepted',
                'message' => $data['message'] ?? 'Invoice accepted',
            ];

        } catch (\Throwable $e) {
            Log::error('UjpApiClient: Accept incoming exception', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Accept failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Reject an incoming e-invoice with a reason.
     *
     * Posts rejection with reason to the reject endpoint for a specific invoice ID.
     *
     * NOTE: Endpoint path and response shape are based on expected UJP API spec.
     *
     * @param  string  $invoiceId  The portal/API invoice ID
     * @param  string  $reason  The reason for rejection
     * @return array{success: bool, status: string, message: string}
     */
    public function rejectIncoming(string $invoiceId, string $reason): array
    {
        Log::info('UjpApiClient: Rejecting incoming invoice', [
            'invoice_id' => $invoiceId,
            'reason' => $reason,
        ]);

        try {
            $token = $this->authenticate();

            $endpoint = str_replace('{id}', $invoiceId, self::ENDPOINTS['reject']);

            $response = Http::timeout($this->timeout)
                ->connectTimeout(10)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl . $endpoint, [
                    'reason' => $reason,
                ]);

            $data = $response->json() ?? [];

            if (! $response->successful()) {
                Log::error('UjpApiClient: Reject incoming invoice failed', [
                    'invoice_id' => $invoiceId,
                    'status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'status' => $data['status'] ?? 'error',
                    'message' => $data['message'] ?? 'Reject failed with HTTP ' . $response->status(),
                ];
            }

            Log::info('UjpApiClient: Incoming invoice rejected', [
                'invoice_id' => $invoiceId,
            ]);

            return [
                'success' => true,
                'status' => $data['status'] ?? 'rejected',
                'message' => $data['message'] ?? 'Invoice rejected',
            ];

        } catch (\Throwable $e) {
            Log::error('UjpApiClient: Reject incoming exception', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Reject failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Download the UBL XML content of a specific invoice.
     *
     * Fetches the raw XML from the xml endpoint for a specific invoice ID.
     *
     * NOTE: Endpoint path is based on expected UJP API spec.
     *
     * @param  string  $invoiceId  The portal/API invoice ID
     * @return string|null UBL XML content, or null on failure
     */
    public function downloadInvoiceXml(string $invoiceId): ?string
    {
        Log::info('UjpApiClient: Downloading invoice XML', [
            'invoice_id' => $invoiceId,
        ]);

        try {
            $token = $this->authenticate();

            $endpoint = str_replace('{id}', $invoiceId, self::ENDPOINTS['xml']);

            $response = Http::timeout($this->timeout)
                ->connectTimeout(10)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/xml',
                ])
                ->get($this->baseUrl . $endpoint);

            if (! $response->successful()) {
                Log::warning('UjpApiClient: Download invoice XML failed', [
                    'invoice_id' => $invoiceId,
                    'status' => $response->status(),
                ]);

                return null;
            }

            Log::info('UjpApiClient: Invoice XML downloaded', [
                'invoice_id' => $invoiceId,
                'size' => strlen($response->body()),
            ]);

            return $response->body();

        } catch (\Throwable $e) {
            Log::error('UjpApiClient: Download invoice XML exception', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Invalidate the cached authentication token.
     *
     * Call this if you receive a 401 Unauthorized response and need
     * to force re-authentication.
     *
     * @return void
     */
    public function invalidateToken(): void
    {
        Cache::forget(self::TOKEN_CACHE_KEY);

        Log::info('UjpApiClient: Token cache invalidated');
    }
}
