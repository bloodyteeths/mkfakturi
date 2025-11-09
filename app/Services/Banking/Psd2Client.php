<?php

namespace App\Services\Banking;

use App\Models\BankToken;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Abstract PSD2 Client
 *
 * Base class for PSD2 OAuth2 banking integrations
 * Implements OAuth flow, token management, and transaction fetching
 *
 * Supported banks:
 * - Stopanska Banka (StopanskaGateway)
 * - NLB Banka (NlbGateway)
 */
abstract class Psd2Client
{
    /**
     * Get the bank code identifier
     *
     * @return string Bank code (e.g., 'stopanska', 'nlb')
     */
    abstract protected function getBankCode(): string;

    /**
     * Get the base URL for the PSD2 API
     *
     * @return string Base URL
     */
    abstract protected function getBaseUrl(): string;

    /**
     * Get the OAuth2 client ID
     *
     * @return string Client ID
     */
    abstract protected function getClientId(): string;

    /**
     * Get the OAuth2 client secret
     *
     * @return string Client secret
     */
    abstract protected function getClientSecret(): string;

    /**
     * Get the base URL used for OAuth flows (defaults to API base)
     */
    protected function getAuthBaseUrl(): string
    {
        return $this->getBaseUrl();
    }

    /**
     * Get the authorize endpoint path
     */
    protected function getAuthorizePath(): string
    {
        return '/oauth/authorize';
    }

    /**
     * Get the token endpoint path
     */
    protected function getTokenPath(): string
    {
        return '/oauth/token';
    }

    /**
     * Get the OAuth scopes to request
     *
     * Override in bank-specific implementations if different scopes are needed
     *
     * @return string Space-separated list of scopes
     */
    protected function getScopes(): string
    {
        return 'accounts transactions';
    }

    /**
     * Generate OAuth2 authorization URL
     *
     * @param Company $company Company requesting authorization
     * @param string $redirectUri Callback URL after authorization
     * @return string Authorization URL
     */
    public function getAuthUrl(Company $company, string $redirectUri): string
    {
        $state = $this->generateState($company->id);

        $params = [
            'client_id' => $this->getClientId(),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $this->getScopes(),
            'state' => $state,
        ];

        // Add PKCE parameters if required by the bank
        if ($this->requiresPkce()) {
            $codeVerifier = $this->generateCodeVerifier();
            $codeChallenge = $this->generateCodeChallenge($codeVerifier);

            // Store code verifier in cache for later use in token exchange
            // Key format: pkce_verifier_{bank_code}_{state}
            $cacheKey = "pkce_verifier_{$this->getBankCode()}_{$state}";
            cache()->put($cacheKey, $codeVerifier, now()->addMinutes(10));

            $params['code_challenge'] = $codeChallenge;
            $params['code_challenge_method'] = 'S256';

            Log::info('PKCE enabled for OAuth flow', [
                'bank' => $this->getBankCode(),
                'company_id' => $company->id,
                'state' => $state,
                'cache_key' => $cacheKey
            ]);
        }

        return rtrim($this->getAuthBaseUrl(), '/') . $this->getAuthorizePath() . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     *
     * @param Company $company Company receiving the token
     * @param string $code Authorization code from OAuth callback
     * @param string $redirectUri Same redirect URI used in authorization
     * @param string|null $state State parameter from callback (needed for PKCE)
     * @return BankToken Created or updated token
     * @throws \Exception If token exchange fails
     */
    public function exchangeCode(Company $company, string $code, string $redirectUri, ?string $state = null): BankToken
    {
        try {
            $tokenUrl = rtrim($this->getAuthBaseUrl(), '/') . $this->getTokenPath();

            $params = [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'redirect_uri' => $redirectUri,
            ];

            // Add code_verifier for PKCE if required
            if ($this->requiresPkce() && $state) {
                $cacheKey = "pkce_verifier_{$this->getBankCode()}_{$state}";
                $codeVerifier = cache()->pull($cacheKey); // Get and remove from cache

                if ($codeVerifier) {
                    $params['code_verifier'] = $codeVerifier;
                    Log::info('Using PKCE code verifier for token exchange', [
                        'bank' => $this->getBankCode(),
                        'company_id' => $company->id,
                        'cache_key' => $cacheKey
                    ]);
                } else {
                    Log::warning('PKCE code verifier not found in cache', [
                        'bank' => $this->getBankCode(),
                        'company_id' => $company->id,
                        'cache_key' => $cacheKey
                    ]);
                }
            }

            $response = Http::asForm()->post($tokenUrl, $params);

            if (!$response->successful()) {
                Log::error('Token exchange HTTP error', [
                    'bank' => $this->getBankCode(),
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Token exchange failed: ' . $response->body());
            }

            $data = $response->json();

            return BankToken::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'bank_code' => $this->getBankCode(),
                ],
                [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'token_type' => $data['token_type'] ?? 'Bearer',
                    'expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                    'scope' => $data['scope'] ?? 'accounts transactions',
                ]
            );
        } catch (\Exception $e) {
            Log::error('PSD2 token exchange failed', [
                'bank' => $this->getBankCode(),
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get a valid access token (auto-refresh if expiring)
     *
     * @param Company $company Company whose token to retrieve
     * @return BankToken Valid access token
     * @throws \Exception If no token found or refresh fails
     */
    public function getValidToken(Company $company): BankToken
    {
        $token = BankToken::where('company_id', $company->id)
            ->where('bank_code', $this->getBankCode())
            ->first();

        if (!$token) {
            throw new \Exception('No token found for company ' . $company->id . ' and bank ' . $this->getBankCode());
        }

        if ($token->isExpiringSoon()) {
            $token = $this->refreshToken($token);
        }

        return $token;
    }

    /**
     * Refresh an expired or expiring token
     *
     * @param BankToken $token Token to refresh
     * @return BankToken Refreshed token
     * @throws \Exception If refresh fails
     */
    public function refreshToken(BankToken $token): BankToken
    {
        if (!$token->refresh_token) {
            throw new \Exception('No refresh token available');
        }

        try {
            $tokenUrl = rtrim($this->getAuthBaseUrl(), '/') . $this->getTokenPath();

            $response = Http::asForm()->post($tokenUrl, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $token->refresh_token,
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
            ]);

            if (!$response->successful()) {
                throw new \Exception('Token refresh failed: ' . $response->body());
            }

            $data = $response->json();

            $token->update([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? $token->refresh_token,
                'token_type' => $data['token_type'] ?? 'Bearer',
                'expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
            ]);

            Log::info('PSD2 token refreshed', [
                'bank' => $this->getBankCode(),
                'company_id' => $token->company_id,
            ]);

            return $token->fresh();
        } catch (\Exception $e) {
            Log::error('PSD2 token refresh failed', [
                'bank' => $this->getBankCode(),
                'company_id' => $token->company_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Fetch transactions from PSD2 API
     *
     * @param Company $company Company whose transactions to fetch
     * @param string $accountId Bank account ID
     * @param Carbon $from Start date
     * @param Carbon $to End date
     * @return array Array of transaction data
     * @throws \Exception If API call fails
     */
    public function getTransactions(Company $company, string $accountId, Carbon $from, Carbon $to): array
    {
        $token = $this->getValidToken($company);

        try {
            $response = Http::withToken($token->access_token)
                ->get($this->getBaseUrl() . "/accounts/{$accountId}/transactions", [
                    'dateFrom' => $from->format('Y-m-d'),
                    'dateTo' => $to->format('Y-m-d'),
                ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch transactions: ' . $response->body());
            }

            $data = $response->json();

            Log::info('PSD2 transactions fetched', [
                'bank' => $this->getBankCode(),
                'company_id' => $company->id,
                'account_id' => $accountId,
                'count' => count($data['transactions'] ?? []),
            ]);

            return $data['transactions'] ?? [];
        } catch (\Exception $e) {
            Log::error('PSD2 transaction fetch failed', [
                'bank' => $this->getBankCode(),
                'company_id' => $company->id,
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get list of accounts from PSD2 API
     *
     * @param Company $company Company whose accounts to fetch
     * @return array Array of account data
     * @throws \Exception If API call fails
     */
    public function getAccounts(Company $company): array
    {
        $token = $this->getValidToken($company);

        try {
            $url = $this->getBaseUrl() . '/accounts';

            Log::info('Fetching accounts from PSD2 API', [
                'bank' => $this->getBankCode(),
                'company_id' => $company->id,
                'url' => $url,
                'has_token' => !empty($token->access_token)
            ]);

            $response = Http::withToken($token->access_token)
                ->get($url);

            Log::info('PSD2 API response received', [
                'bank' => $this->getBankCode(),
                'company_id' => $company->id,
                'status' => $response->status(),
                'body_length' => strlen($response->body()),
                'body_preview' => substr($response->body(), 0, 500)
            ]);

            if (!$response->successful()) {
                Log::error('PSD2 API returned error', [
                    'bank' => $this->getBankCode(),
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'headers' => $response->headers()
                ]);
                throw new \Exception('Failed to fetch accounts (HTTP ' . $response->status() . '): ' . $response->body());
            }

            $data = $response->json();

            $accounts = $data['accounts'] ?? [];

            Log::info('Accounts extracted from response', [
                'bank' => $this->getBankCode(),
                'company_id' => $company->id,
                'count' => count($accounts),
                'response_keys' => array_keys($data)
            ]);

            return $accounts;
        } catch (\Exception $e) {
            Log::error('PSD2 account fetch failed', [
                'bank' => $this->getBankCode(),
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Revoke OAuth token
     *
     * @param Company $company Company whose token to revoke
     * @return bool True if successful
     */
    public function revokeToken(Company $company): bool
    {
        $token = BankToken::where('company_id', $company->id)
            ->where('bank_code', $this->getBankCode())
            ->first();

        if (!$token) {
            return false;
        }

        try {
            // Attempt to revoke on bank side (not all banks support this)
            Http::withToken($token->access_token)
                ->post($this->getBaseUrl() . '/oauth/revoke');

            // Delete token from database regardless of revoke response
            $token->delete();

            Log::info('PSD2 token revoked', [
                'bank' => $this->getBankCode(),
                'company_id' => $company->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::warning('PSD2 token revoke failed (deleted anyway)', [
                'bank' => $this->getBankCode(),
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            // Still delete the token even if revoke failed
            $token->delete();

            return true;
        }
    }

    /**
     * Generate state parameter for OAuth flow
     *
     * Simply return the company ID as the state parameter.
     * OAuth providers return state unchanged, allowing us to extract company ID in callback.
     *
     * @param int $companyId Company ID
     * @return string Company ID as string
     */
    protected function generateState(int $companyId): string
    {
        return (string)$companyId;
    }

    /**
     * Verify state parameter from OAuth callback
     *
     * @param string $state State from callback
     * @param int $companyId Expected company ID
     * @return bool True if valid
     */
    protected function verifyState(string $state, int $companyId): bool
    {
        // Note: For production, should store state in session/cache
        // This is a simplified implementation
        return !empty($state);
    }

    /**
     * Check if this bank requires PKCE (Proof Key for Code Exchange)
     *
     * Override in bank-specific implementations if PKCE is required
     *
     * @return bool True if PKCE is required
     */
    protected function requiresPkce(): bool
    {
        return false; // Default: PKCE not required
    }

    /**
     * Generate a random code verifier for PKCE
     *
     * @return string Base64url-encoded random string (43-128 characters)
     */
    protected function generateCodeVerifier(): string
    {
        $randomBytes = random_bytes(32); // 32 bytes = 43 chars when base64url encoded
        return rtrim(strtr(base64_encode($randomBytes), '+/', '-_'), '=');
    }

    /**
     * Generate code challenge from code verifier for PKCE
     *
     * @param string $codeVerifier The code verifier
     * @return string Base64url-encoded SHA256 hash of the verifier
     */
    protected function generateCodeChallenge(string $codeVerifier): string
    {
        $hash = hash('sha256', $codeVerifier, true);
        return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    }
}

// CLAUDE-CHECKPOINT
