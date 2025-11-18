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
     */
    protected function getBankCode(): string
    {
        return 'stopanska';
    }

    /**
     * Get the base URL for the PSD2 API
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
     */
    protected function getClientId(): string
    {
        return config('mk.stopanska.client_id', env('STOPANSKA_CLIENT_ID', ''));
    }

    /**
     * Get the OAuth2 client secret
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
     */
    public function getBic(): string
    {
        return 'STBAMK22XXX';
    }

    /**
     * Get bank display name
     */
    public function getBankName(): string
    {
        return 'Stopanska Banka AD Skopje';
    }

    /**
     * Get bank logo URL
     */
    public function getLogoUrl(): string
    {
        return '/images/banks/stopanska-logo.png';
    }

    /**
     * Check if rate limiting is enabled
     */
    protected function isRateLimitEnabled(): bool
    {
        return config('mk.stopanska.rate_limit_enabled', true);
    }

    /**
     * Get rate limit interval in seconds
     * Stopanska allows 15 requests per minute = 4 second intervals
     */
    protected function getRateLimitInterval(): int
    {
        return 4; // 15 requests per minute
    }

    /**
     * Get the path to the client certificate file for mTLS
     *
     * Stopanska Bank may require mTLS for PSD2 API access.
     *
     * @return string|null Path to certificate file (.pem or .crt)
     */
    protected function getCertificatePath(): ?string
    {
        $certPath = config('mk.stopanska.mtls_cert_path', env('STOPANSKA_MTLS_CERT_PATH'));

        if (! $certPath) {
            return null;
        }

        // If relative path, resolve from storage directory
        if (! str_starts_with($certPath, '/')) {
            return storage_path('certificates/'.$certPath);
        }

        return $certPath;
    }

    /**
     * Get the path to the client certificate key file for mTLS
     *
     * @return string|null Path to private key file (.key)
     */
    protected function getCertificateKeyPath(): ?string
    {
        $keyPath = config('mk.stopanska.mtls_key_path', env('STOPANSKA_MTLS_KEY_PATH'));

        if (! $keyPath) {
            return null;
        }

        // If relative path, resolve from storage directory
        if (! str_starts_with($keyPath, '/')) {
            return storage_path('certificates/'.$keyPath);
        }

        return $keyPath;
    }

    /**
     * Get the password for the certificate key (if encrypted)
     *
     * @return string|null Certificate key password
     */
    protected function getCertificateKeyPassword(): ?string
    {
        return config('mk.stopanska.mtls_key_password', env('STOPANSKA_MTLS_KEY_PASSWORD'));
    }
}

// CLAUDE-CHECKPOINT
