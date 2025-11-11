<?php

namespace Modules\Mk\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Modules\Mk\Services\StopanskaGateway;
use Modules\Mk\Services\NlbGateway;

/**
 * Bank Authentication Controller for PSD2 OAuth flows
 * 
 * Handles OAuth authentication for Macedonian banks:
 * - Stopanska Banka
 * - NLB Banka (Nova Ljubljanska Banka)
 */
class BankAuthController extends Controller
{
    /**
     * Supported banks for PSD2 integration
     */
    protected array $supportedBanks = ['stopanska', 'nlb'];

    /**
     * Initiate OAuth flow for bank authentication
     */
    public function initiateAuth(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'bank' => 'required|in:' . implode(',', $this->supportedBanks),
                'company_id' => 'required|integer',
                'redirect_uri' => 'required|url',
            ]);

            $bank = $request->input('bank');
            $companyId = $request->input('company_id');
            $redirectUri = $request->input('redirect_uri');

            // Get bank gateway
            $gateway = $this->getBankGateway($bank);

            // Generate state for CSRF protection
            $state = bin2hex(random_bytes(16));
            Cache::put("bank_oauth_state_{$state}", [
                'bank' => $bank,
                'company_id' => $companyId,
                'redirect_uri' => $redirectUri,
                'timestamp' => now()
            ], 600); // 10 minutes

            // Get authorization URL from gateway
            $authUrl = $gateway->getAuthorizationUrl($redirectUri, $state);

            Log::info('Bank OAuth flow initiated', [
                'bank' => $bank,
                'company_id' => $companyId,
                'state' => $state
            ]);

            return response()->json([
                'success' => true,
                'authorization_url' => $authUrl,
                'state' => $state
            ]);

        } catch (\Exception $e) {
            Log::error('Bank OAuth initiation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate bank authentication: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle OAuth callback from bank
     */
    public function handleCallback(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'code' => 'required|string',
                'state' => 'required|string',
            ]);

            $code = $request->input('code');
            $state = $request->input('state');

            // Retrieve and validate state
            $stateData = Cache::get("bank_oauth_state_{$state}");
            if (!$stateData) {
                throw new \Exception('Invalid or expired OAuth state');
            }

            $bank = $stateData['bank'];
            $companyId = $stateData['company_id'];

            // Get bank gateway
            $gateway = $this->getBankGateway($bank);

            // Exchange code for tokens
            $tokens = $gateway->exchangeCodeForTokens($code, $stateData['redirect_uri']);

            // Store tokens securely
            $this->storeTokens($bank, $companyId, $tokens);

            // Clean up state
            Cache::forget("bank_oauth_state_{$state}");

            Log::info('Bank OAuth completed successfully', [
                'bank' => $bank,
                'company_id' => $companyId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bank authentication completed successfully',
                'bank' => $bank,
                'expires_at' => $tokens['expires_at'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Bank OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'OAuth callback failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stored tokens for a bank and company
     */
    public function getStoredTokens(string $bank, int $companyId): ?array
    {
        try {
            if (!in_array($bank, $this->supportedBanks)) {
                throw new \Exception("Unsupported bank: {$bank}");
            }

            $cacheKey = "bank_tokens_{$bank}_{$companyId}";
            $tokens = Cache::get($cacheKey);

            if (!$tokens) {
                return null;
            }

            // Check if tokens are expired
            if (isset($tokens['expires_at']) && now()->greaterThan($tokens['expires_at'])) {
                // Try to refresh tokens
                $refreshedTokens = $this->refreshTokens($bank, $companyId, $tokens);
                if ($refreshedTokens) {
                    return $refreshedTokens;
                }
                
                // Remove expired tokens
                Cache::forget($cacheKey);
                return null;
            }

            return $tokens;

        } catch (\Exception $e) {
            Log::error('Failed to retrieve stored tokens', [
                'bank' => $bank,
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Refresh expired tokens
     */
    protected function refreshTokens(string $bank, int $companyId, array $currentTokens): ?array
    {
        try {
            if (!isset($currentTokens['refresh_token'])) {
                return null;
            }

            $gateway = $this->getBankGateway($bank);
            $newTokens = $gateway->refreshTokens($currentTokens['refresh_token']);

            // Store refreshed tokens
            $this->storeTokens($bank, $companyId, $newTokens);

            Log::info('Bank tokens refreshed successfully', [
                'bank' => $bank,
                'company_id' => $companyId
            ]);

            return $newTokens;

        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'bank' => $bank,
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Store tokens securely in cache
     */
    protected function storeTokens(string $bank, int $companyId, array $tokens): void
    {
        $cacheKey = "bank_tokens_{$bank}_{$companyId}";
        
        // Add expiry timestamp if not present
        if (!isset($tokens['expires_at']) && isset($tokens['expires_in'])) {
            $tokens['expires_at'] = now()->addSeconds($tokens['expires_in']);
        }

        // Store tokens for 7 days (or until they expire, whichever is shorter)
        $ttl = 7 * 24 * 60; // 7 days in minutes
        if (isset($tokens['expires_at'])) {
            $expiryMinutes = now()->diffInMinutes($tokens['expires_at']);
            $ttl = min($ttl, $expiryMinutes);
        }

        Cache::put($cacheKey, $tokens, $ttl);
    }

    /**
     * Get bank gateway instance
     */
    protected function getBankGateway(string $bank): StopanskaGateway|NlbGateway
    {
        return match ($bank) {
            'stopanska' => new StopanskaGateway(),
            'nlb' => new NlbGateway(),
            default => throw new \Exception("Unsupported bank: {$bank}")
        };
    }

    /**
     * Revoke bank access and clear stored tokens
     */
    public function revokeAccess(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'bank' => 'required|in:' . implode(',', $this->supportedBanks),
                'company_id' => 'required|integer',
            ]);

            $bank = $request->input('bank');
            $companyId = $request->input('company_id');

            // Get stored tokens
            $tokens = $this->getStoredTokens($bank, $companyId);
            
            if ($tokens) {
                // Try to revoke tokens with the bank
                try {
                    $gateway = $this->getBankGateway($bank);
                    $gateway->revokeTokens($tokens['access_token']);
                } catch (\Exception $e) {
                    Log::warning('Token revocation at bank failed', [
                        'bank' => $bank,
                        'error' => $e->getMessage()
                    ]);
                }

                // Remove tokens from cache
                Cache::forget("bank_tokens_{$bank}_{$companyId}");
            }

            Log::info('Bank access revoked', [
                'bank' => $bank,
                'company_id' => $companyId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bank access revoked successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to revoke bank access', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke bank access: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get authentication status for all supported banks
     */
    public function getAuthStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'company_id' => 'required|integer',
            ]);

            $companyId = $request->input('company_id');
            $status = [];

            foreach ($this->supportedBanks as $bank) {
                $tokens = $this->getStoredTokens($bank, $companyId);
                $status[$bank] = [
                    'authenticated' => !is_null($tokens),
                    'expires_at' => $tokens['expires_at'] ?? null,
                    'has_refresh_token' => isset($tokens['refresh_token'])
                ];
            }

            return response()->json([
                'success' => true,
                'status' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get auth status', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get authentication status: ' . $e->getMessage()
            ], 500);
        }
    }
}