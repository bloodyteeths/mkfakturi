<?php

namespace Modules\Mk\Services;

use App\Services\Banking\Psd2Client;

/**
 * Stopanska Banka OAuth2 Gateway
 *
 * PSD2 OAuth implementation for Stopanska Banka AD Skopje
 * Macedonia's largest bank with Berlin Group NextGenPSD2 API
 *
 * Developer Portal: https://ob.stb.kibs.mk/docs/getting-started
 * API Documentation: https://ob.stb.kibs.mk/docs/getting-started
 *
 * Rate Limit: 15 requests per minute (4 second intervals)
 */
class StopanskaOAuth extends Psd2Client
{
    /**
     * Get the bank code identifier
     *
     * @return string
     */
    protected function getBankCode(): string
    {
        return 'stopanska';
    }

    /**
     * Get the base URL for the PSD2 API
     *
     * @return string
     */
    protected function getBaseUrl(): string
    {
        $environment = config('mk.stopanska.environment', 'sandbox');

        if ($environment === 'production') {
            return config('mk.stopanska.production_base_url', 'https://api.ob.stb.kibs.mk/xs2a/v1');
        }

        return config('mk.stopanska.sandbox_base_url', 'https://sandbox-api.ob.stb.kibs.mk/xs2a/v1');
    }

    /**
     * Get the OAuth2 client ID
     *
     * @return string
     */
    protected function getClientId(): string
    {
        return config('mk.stopanska.client_id', env('STOPANSKA_CLIENT_ID', ''));
    }

    /**
     * Get the OAuth2 client secret
     *
     * @return string
     */
    protected function getClientSecret(): string
    {
        return config('mk.stopanska.client_secret', env('STOPANSKA_CLIENT_SECRET', ''));
    }

    /**
     * Stopanska hosts OAuth endpoints under /oauth2 on the same domain
     */
    protected function getAuthorizePath(): string
    {
        return '/oauth2/authorize';
    }

    protected function getTokenPath(): string
    {
        return '/oauth2/token';
    }

    /**
     * Get BIC/SWIFT code for Stopanska Banka
     *
     * @return string
     */
    public function getBic(): string
    {
        return 'STBAMK22XXX';
    }

    /**
     * Get bank display name
     *
     * @return string
     */
    public function getBankName(): string
    {
        return 'Stopanska Banka AD Skopje';
    }

    /**
     * Get bank logo URL
     *
     * @return string
     */
    public function getLogoUrl(): string
    {
        return '/images/banks/stopanska-logo.png';
    }

    /**
     * Check if rate limiting is enabled
     *
     * @return bool
     */
    protected function isRateLimitEnabled(): bool
    {
        return config('mk.stopanska.rate_limit_enabled', true);
    }

    /**
     * Get rate limit interval in seconds
     * Stopanska allows 15 requests per minute = 4 second intervals
     *
     * @return int
     */
    protected function getRateLimitInterval(): int
    {
        return 4; // 15 requests per minute
    }
}

// CLAUDE-CHECKPOINT
