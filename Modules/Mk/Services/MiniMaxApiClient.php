<?php

namespace Modules\Mk\Services;

use App\Models\MiniMaxToken;
use App\Models\Company;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\PendingRequest;
use Carbon\Carbon;

/**
 * MiniMax API Client
 * 
 * Professional API client for MiniMax accounting system integration
 * Handles authentication, rate limiting, error handling, and API communication
 * 
 * Features:
 * - Encrypted token management via MiniMaxToken model
 * - Automatic retry logic with exponential backoff
 * - Rate limiting compliance (50 req/min)
 * - Environment switching (sandbox/production)
 * - Comprehensive error handling and logging
 * - Token refresh and validation
 * 
 * Technical Specifications:
 * - Base URL: https://api.minimax.mk/v1/ (prod) / https://sandbox-api.minimax.mk/v1/ (sandbox)
 * - Authentication: Bearer token
 * - Rate limit: 50 requests per minute
 * - Timeout: 30 seconds
 * - Retry attempts: 3 with exponential backoff
 * 
 * @version 1.0.0
 * @created 2025-07-26
 * @author AI-02 Agent (ROADMAP3 multiagent implementation)
 */
class MiniMaxApiClient
{
    // API endpoints configuration
    protected const API_BASE_URL_PRODUCTION = 'https://api.minimax.mk/v1';
    protected const API_BASE_URL_SANDBOX = 'https://sandbox-api.minimax.mk/v1';
    
    // API endpoints
    protected const ENDPOINT_INVOICES = '/invoices';
    protected const ENDPOINT_PAYMENTS = '/payments';
    protected const ENDPOINT_STATUS = '/status';
    protected const ENDPOINT_TOKEN_VALIDATE = '/auth/validate';
    protected const ENDPOINT_TOKEN_REFRESH = '/auth/refresh';
    
    // Rate limiting
    protected const RATE_LIMIT_MAX_REQUESTS = 50;
    protected const RATE_LIMIT_WINDOW_MINUTES = 1;
    
    // Retry configuration
    protected const MAX_RETRY_ATTEMPTS = 3;
    protected const RETRY_DELAY_BASE = 1000; // milliseconds
    
    // Request timeout
    protected const REQUEST_TIMEOUT = 30; // seconds
    
    protected Company $company;
    protected ?MiniMaxToken $token = null;
    protected bool $isSandbox;
    protected string $baseUrl;
    protected array $defaultHeaders;
    
    /**
     * Initialize MiniMax API client
     */
    public function __construct(Company $company, ?bool $forceSandbox = null)
    {
        $this->company = $company;
        $this->isSandbox = $forceSandbox ?? (config('app.env') !== 'production');
        $this->baseUrl = $this->isSandbox ? self::API_BASE_URL_SANDBOX : self::API_BASE_URL_PRODUCTION;
        
        $this->defaultHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'InvoiceShelf-MK/1.0 (MiniMax Integration)',
            'X-Client-Version' => '1.0.0',
        ];
        
        $this->loadToken();
        
        Log::info('MiniMaxApiClient initialized', [
            'company_id' => $this->company->id,
            'environment' => $this->isSandbox ? 'sandbox' : 'production',
            'base_url' => $this->baseUrl,
            'has_token' => !is_null($this->token),
        ]);
    }
    
    /**
     * Create invoice in MiniMax system
     *
     * @param array $invoiceData Invoice data formatted for MiniMax API
     * @return array API response with success status and MiniMax invoice ID
     * @throws Exception When API request fails or validation errors occur
     */
    public function createInvoice(array $invoiceData): array
    {
        $this->validateInvoiceData($invoiceData);
        
        Log::info('Creating invoice in MiniMax', [
            'company_id' => $this->company->id,
            'invoice_number' => $invoiceData['invoice_number'] ?? 'N/A',
            'total' => $invoiceData['total'] ?? 0,
        ]);
        
        $response = $this->makeRequest('POST', self::ENDPOINT_INVOICES, $invoiceData);
        
        if (!$response->successful()) {
            throw new Exception("Failed to create invoice in MiniMax: " . $this->getErrorMessage($response));
        }
        
        $data = $response->json();
        
        Log::info('Invoice created successfully in MiniMax', [
            'company_id' => $this->company->id,
            'minimax_id' => $data['id'] ?? null,
            'status' => $data['status'] ?? 'unknown',
        ]);
        
        return [
            'success' => true,
            'minimax_id' => $data['id'],
            'status' => $data['status'] ?? 'created',
            'created_at' => $data['created_at'] ?? now()->toISOString(),
            'response' => $data,
        ];
    }
    
    /**
     * Update existing invoice in MiniMax system
     *
     * @param string $miniMaxId MiniMax invoice ID
     * @param array $invoiceData Updated invoice data
     * @return array API response
     * @throws Exception When API request fails
     */
    public function updateInvoice(string $miniMaxId, array $invoiceData): array
    {
        $this->validateInvoiceData($invoiceData);
        
        Log::info('Updating invoice in MiniMax', [
            'company_id' => $this->company->id,
            'minimax_id' => $miniMaxId,
            'invoice_number' => $invoiceData['invoice_number'] ?? 'N/A',
        ]);
        
        $response = $this->makeRequest('PUT', self::ENDPOINT_INVOICES . '/' . $miniMaxId, $invoiceData);
        
        if (!$response->successful()) {
            throw new Exception("Failed to update invoice in MiniMax: " . $this->getErrorMessage($response));
        }
        
        $data = $response->json();
        
        Log::info('Invoice updated successfully in MiniMax', [
            'company_id' => $this->company->id,
            'minimax_id' => $miniMaxId,
        ]);
        
        return [
            'success' => true,
            'minimax_id' => $miniMaxId,
            'status' => $data['status'] ?? 'updated',
            'updated_at' => $data['updated_at'] ?? now()->toISOString(),
            'response' => $data,
        ];
    }
    
    /**
     * Create payment in MiniMax system
     *
     * @param array $paymentData Payment data formatted for MiniMax API
     * @return array API response with success status and MiniMax payment ID
     * @throws Exception When API request fails or validation errors occur
     */
    public function createPayment(array $paymentData): array
    {
        $this->validatePaymentData($paymentData);
        
        Log::info('Creating payment in MiniMax', [
            'company_id' => $this->company->id,
            'payment_number' => $paymentData['payment_number'] ?? 'N/A',
            'amount' => $paymentData['amount'] ?? 0,
        ]);
        
        $response = $this->makeRequest('POST', self::ENDPOINT_PAYMENTS, $paymentData);
        
        if (!$response->successful()) {
            throw new Exception("Failed to create payment in MiniMax: " . $this->getErrorMessage($response));
        }
        
        $data = $response->json();
        
        Log::info('Payment created successfully in MiniMax', [
            'company_id' => $this->company->id,
            'minimax_id' => $data['id'] ?? null,
            'status' => $data['status'] ?? 'unknown',
        ]);
        
        return [
            'success' => true,
            'minimax_id' => $data['id'],
            'status' => $data['status'] ?? 'created',
            'created_at' => $data['created_at'] ?? now()->toISOString(),
            'response' => $data,
        ];
    }
    
    /**
     * Update existing payment in MiniMax system
     *
     * @param string $miniMaxId MiniMax payment ID
     * @param array $paymentData Updated payment data
     * @return array API response
     * @throws Exception When API request fails
     */
    public function updatePayment(string $miniMaxId, array $paymentData): array
    {
        $this->validatePaymentData($paymentData);
        
        Log::info('Updating payment in MiniMax', [
            'company_id' => $this->company->id,
            'minimax_id' => $miniMaxId,
            'payment_number' => $paymentData['payment_number'] ?? 'N/A',
        ]);
        
        $response = $this->makeRequest('PUT', self::ENDPOINT_PAYMENTS . '/' . $miniMaxId, $paymentData);
        
        if (!$response->successful()) {
            throw new Exception("Failed to update payment in MiniMax: " . $this->getErrorMessage($response));
        }
        
        $data = $response->json();
        
        Log::info('Payment updated successfully in MiniMax', [
            'company_id' => $this->company->id,
            'minimax_id' => $miniMaxId,
        ]);
        
        return [
            'success' => true,
            'minimax_id' => $miniMaxId,
            'status' => $data['status'] ?? 'updated',
            'updated_at' => $data['updated_at'] ?? now()->toISOString(),
            'response' => $data,
        ];
    }
    
    /**
     * Get status of a specific entity in MiniMax
     *
     * @param string $entityType 'invoice' or 'payment'
     * @param string $miniMaxId MiniMax entity ID
     * @return array Status information
     * @throws Exception When API request fails
     */
    public function getEntityStatus(string $entityType, string $miniMaxId): array
    {
        $endpoint = ($entityType === 'invoice' ? self::ENDPOINT_INVOICES : self::ENDPOINT_PAYMENTS) . '/' . $miniMaxId . '/status';
        
        Log::info('Getting entity status from MiniMax', [
            'company_id' => $this->company->id,
            'entity_type' => $entityType,
            'minimax_id' => $miniMaxId,
        ]);
        
        $response = $this->makeRequest('GET', $endpoint);
        
        if (!$response->successful()) {
            throw new Exception("Failed to get {$entityType} status from MiniMax: " . $this->getErrorMessage($response));
        }
        
        $data = $response->json();
        
        return [
            'success' => true,
            'entity_type' => $entityType,
            'minimax_id' => $miniMaxId,
            'status' => $data['status'] ?? 'unknown',
            'last_updated' => $data['last_updated'] ?? null,
            'sync_errors' => $data['errors'] ?? [],
            'response' => $data,
        ];
    }
    
    /**
     * Get general system status from MiniMax API
     *
     * @return array System status information
     * @throws Exception When API request fails
     */
    public function getSystemStatus(): array
    {
        Log::info('Getting system status from MiniMax', [
            'company_id' => $this->company->id,
        ]);
        
        $response = $this->makeRequest('GET', self::ENDPOINT_STATUS);
        
        if (!$response->successful()) {
            throw new Exception("Failed to get system status from MiniMax: " . $this->getErrorMessage($response));
        }
        
        $data = $response->json();
        
        return [
            'success' => true,
            'system_status' => $data['status'] ?? 'unknown',
            'api_version' => $data['version'] ?? null,
            'server_time' => $data['server_time'] ?? null,
            'rate_limit_remaining' => $data['rate_limit_remaining'] ?? null,
            'response' => $data,
        ];
    }
    
    /**
     * Validate and refresh authentication token
     *
     * @return bool True if token is valid or successfully refreshed
     * @throws Exception When token validation or refresh fails
     */
    public function validateToken(): bool
    {
        if (!$this->token) {
            throw new Exception('No MiniMax token configured for this company');
        }
        
        Log::info('Validating MiniMax token', [
            'company_id' => $this->company->id,
            'token_name' => $this->token->token_name,
        ]);
        
        $response = $this->makeRequest('GET', self::ENDPOINT_TOKEN_VALIDATE, [], false);
        
        if ($response->successful()) {
            Log::info('MiniMax token is valid', [
                'company_id' => $this->company->id,
            ]);
            return true;
        }
        
        // Try to refresh token if validation failed
        if ($response->status() === 401) {
            Log::warning('MiniMax token expired, attempting refresh', [
                'company_id' => $this->company->id,
            ]);
            
            return $this->refreshToken();
        }
        
        throw new Exception("Token validation failed: " . $this->getErrorMessage($response));
    }
    
    /**
     * Refresh authentication token
     *
     * @return bool True if token was successfully refreshed
     * @throws Exception When token refresh fails
     */
    public function refreshToken(): bool
    {
        if (!$this->token) {
            throw new Exception('No MiniMax token configured for refresh');
        }
        
        Log::info('Refreshing MiniMax token', [
            'company_id' => $this->company->id,
            'token_name' => $this->token->token_name,
        ]);
        
        $response = $this->makeRequest('POST', self::ENDPOINT_TOKEN_REFRESH, [
            'refresh_token' => $this->token->api_token,
        ], false);
        
        if (!$response->successful()) {
            throw new Exception("Token refresh failed: " . $this->getErrorMessage($response));
        }
        
        $data = $response->json();
        
        if (!isset($data['access_token'])) {
            throw new Exception('Invalid token refresh response: missing access_token');
        }
        
        // Update token in database
        $this->token->update([
            'api_token' => $data['access_token'],
        ]);
        
        Log::info('MiniMax token refreshed successfully', [
            'company_id' => $this->company->id,
        ]);
        
        return true;
    }
    
    /**
     * Test API connection with current configuration
     *
     * @return array Connection test results
     */
    public function testConnection(): array
    {
        $startTime = microtime(true);
        
        try {
            Log::info('Testing MiniMax API connection', [
                'company_id' => $this->company->id,
                'base_url' => $this->baseUrl,
                'environment' => $this->isSandbox ? 'sandbox' : 'production',
            ]);
            
            $systemStatus = $this->getSystemStatus();
            $tokenValid = $this->token ? $this->validateToken() : false;
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $result = [
                'success' => true,
                'environment' => $this->isSandbox ? 'sandbox' : 'production',
                'base_url' => $this->baseUrl,
                'response_time_ms' => $responseTime,
                'system_status' => $systemStatus,
                'token_valid' => $tokenValid,
                'company_id' => $this->company->id,
                'tested_at' => now()->toISOString(),
            ];
            
            Log::info('MiniMax API connection test successful', $result);
            
            return $result;
            
        } catch (Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $result = [
                'success' => false,
                'environment' => $this->isSandbox ? 'sandbox' : 'production',
                'base_url' => $this->baseUrl,
                'response_time_ms' => $responseTime,
                'error' => $e->getMessage(),
                'company_id' => $this->company->id,
                'tested_at' => now()->toISOString(),
            ];
            
            Log::error('MiniMax API connection test failed', $result);
            
            return $result;
        }
    }
    
    /**
     * Get current rate limit status
     *
     * @return array Rate limit information
     */
    public function getRateLimitStatus(): array
    {
        $cacheKey = "minimax_rate_limit_{$this->company->id}";
        $requests = Cache::get($cacheKey, []);
        $now = time();
        
        // Remove requests older than the rate limit window
        $windowStart = $now - (self::RATE_LIMIT_WINDOW_MINUTES * 60);
        $requests = array_filter($requests, fn($timestamp) => $timestamp > $windowStart);
        
        $remainingRequests = max(0, self::RATE_LIMIT_MAX_REQUESTS - count($requests));
        $resetTime = $windowStart + (self::RATE_LIMIT_WINDOW_MINUTES * 60);
        
        return [
            'max_requests' => self::RATE_LIMIT_MAX_REQUESTS,
            'window_minutes' => self::RATE_LIMIT_WINDOW_MINUTES,
            'requests_made' => count($requests),
            'requests_remaining' => $remainingRequests,
            'reset_at' => Carbon::createFromTimestamp($resetTime)->toISOString(),
            'reset_in_seconds' => max(0, $resetTime - $now),
        ];
    }
    
    /**
     * Load MiniMax token for the company
     */
    protected function loadToken(): void
    {
        $this->token = MiniMaxToken::active()
            ->forCompany($this->company->id)
            ->first();
        
        if ($this->token) {
            Log::debug('MiniMax token loaded', [
                'company_id' => $this->company->id,
                'token_name' => $this->token->token_name,
            ]);
        } else {
            Log::warning('No active MiniMax token found', [
                'company_id' => $this->company->id,
            ]);
        }
    }
    
    /**
     * Make HTTP request to MiniMax API with retry logic and rate limiting
     */
    protected function makeRequest(string $method, string $endpoint, array $data = [], bool $requireAuth = true): Response
    {
        if ($requireAuth && !$this->token) {
            throw new Exception('MiniMax API token is required for this operation');
        }
        
        // Check rate limiting
        $this->checkRateLimit();
        
        $url = $this->baseUrl . $endpoint;
        $headers = $this->defaultHeaders;
        
        if ($requireAuth && $this->token) {
            $headers['Authorization'] = 'Bearer ' . $this->token->api_token;
        }
        
        $attempt = 0;
        $lastException = null;
        
        while ($attempt < self::MAX_RETRY_ATTEMPTS) {
            try {
                $request = Http::withHeaders($headers)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->retry(1, 0); // Disable Http client's built-in retry since we handle it manually
                
                Log::debug('Making MiniMax API request', [
                    'method' => $method,
                    'url' => $url,
                    'attempt' => $attempt + 1,
                    'max_attempts' => self::MAX_RETRY_ATTEMPTS,
                    'company_id' => $this->company->id,
                ]);
                
                $response = match(strtoupper($method)) {
                    'GET' => $request->get($url, $data),
                    'POST' => $request->post($url, $data),
                    'PUT' => $request->put($url, $data),
                    'PATCH' => $request->patch($url, $data),
                    'DELETE' => $request->delete($url, $data),
                    default => throw new Exception("Unsupported HTTP method: {$method}"),
                };
                
                // Track successful request for rate limiting
                $this->trackRequest();
                
                Log::debug('MiniMax API request completed', [
                    'method' => $method,
                    'url' => $url,
                    'status' => $response->status(),
                    'attempt' => $attempt + 1,
                ]);
                
                return $response;
                
            } catch (Exception $e) {
                $lastException = $e;
                $attempt++;
                
                Log::warning('MiniMax API request failed', [
                    'method' => $method,
                    'url' => $url,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'will_retry' => $attempt < self::MAX_RETRY_ATTEMPTS,
                ]);
                
                if ($attempt < self::MAX_RETRY_ATTEMPTS) {
                    $delay = self::RETRY_DELAY_BASE * (2 ** ($attempt - 1)); // Exponential backoff
                    usleep($delay * 1000); // Convert to microseconds
                }
            }
        }
        
        throw new Exception("MiniMax API request failed after {$attempt} attempts: " . $lastException->getMessage());
    }
    
    /**
     * Check and enforce rate limiting
     */
    protected function checkRateLimit(): void
    {
        $rateLimitStatus = $this->getRateLimitStatus();
        
        if ($rateLimitStatus['requests_remaining'] <= 0) {
            $waitSeconds = $rateLimitStatus['reset_in_seconds'];
            
            Log::warning('MiniMax API rate limit exceeded', [
                'company_id' => $this->company->id,
                'requests_made' => $rateLimitStatus['requests_made'],
                'reset_in_seconds' => $waitSeconds,
            ]);
            
            throw new Exception("MiniMax API rate limit exceeded. Reset in {$waitSeconds} seconds.");
        }
    }
    
    /**
     * Track API request for rate limiting
     */
    protected function trackRequest(): void
    {
        $cacheKey = "minimax_rate_limit_{$this->company->id}";
        $requests = Cache::get($cacheKey, []);
        $requests[] = time();
        
        // Keep only requests within the current window
        $windowStart = time() - (self::RATE_LIMIT_WINDOW_MINUTES * 60);
        $requests = array_filter($requests, fn($timestamp) => $timestamp > $windowStart);
        
        Cache::put($cacheKey, array_values($requests), now()->addMinutes(self::RATE_LIMIT_WINDOW_MINUTES));
    }
    
    /**
     * Validate invoice data structure
     */
    protected function validateInvoiceData(array $data): void
    {
        $required = ['invoice_number', 'invoice_date', 'customer', 'items', 'total'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Invoice data validation failed: '{$field}' is required");
            }
        }
        
        if (!is_array($data['customer'])) {
            throw new Exception('Invoice data validation failed: customer must be an object');
        }
        
        if (!is_array($data['items']) || empty($data['items'])) {
            throw new Exception('Invoice data validation failed: items must be a non-empty array');
        }
        
        if (!is_numeric($data['total']) || $data['total'] <= 0) {
            throw new Exception('Invoice data validation failed: total must be a positive number');
        }
    }
    
    /**
     * Validate payment data structure
     */
    protected function validatePaymentData(array $data): void
    {
        $required = ['payment_number', 'payment_date', 'amount', 'payment_method'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Payment data validation failed: '{$field}' is required");
            }
        }
        
        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new Exception('Payment data validation failed: amount must be a positive number');
        }
        
        if (!isset($data['customer']) && !isset($data['invoice'])) {
            throw new Exception('Payment data validation failed: either customer or invoice must be specified');
        }
    }
    
    /**
     * Extract error message from API response
     */
    protected function getErrorMessage(Response $response): string
    {
        $body = $response->body();
        
        if ($response->header('Content-Type') === 'application/json') {
            $data = $response->json();
            return $data['message'] ?? $data['error'] ?? 'Unknown API error';
        }
        
        return "HTTP {$response->status()}: " . ($body ?: 'No error message');
    }
    
    /**
     * Get current company
     */
    public function getCompany(): Company
    {
        return $this->company;
    }
    
    /**
     * Get current token
     */
    public function getToken(): ?MiniMaxToken
    {
        return $this->token;
    }
    
    /**
     * Check if running in sandbox mode
     */
    public function isSandbox(): bool
    {
        return $this->isSandbox;
    }
    
    /**
     * Get base URL for current environment
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
    
    /**
     * Set new token for the client
     */
    public function setToken(MiniMaxToken $token): self
    {
        $this->token = $token;
        
        Log::info('MiniMax token updated', [
            'company_id' => $this->company->id,
            'token_name' => $token->token_name,
        ]);
        
        return $this;
    }
    
    /**
     * Remove current token
     */
    public function clearToken(): self
    {
        $this->token = null;
        
        Log::info('MiniMax token cleared', [
            'company_id' => $this->company->id,
        ]);
        
        return $this;
    }
}

