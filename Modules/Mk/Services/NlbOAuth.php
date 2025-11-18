<?php

namespace Modules\Mk\Services;

use App\Services\Banking\Psd2Client;

/**
 * NLB Banka OAuth2 Gateway
 *
 * PSD2 OAuth implementation for Nova Ljubljanska Banka Macedonia
 * Second largest bank with Berlin Group NextGenPSD2 API
 *
 * Developer Portal: https://developer-ob.nlb.mk/
 * API Documentation: https://api-ob.nlb.mk/docs
 *
 * Rate Limit: Standard PSD2 limits apply
 */
class NlbOAuth extends Psd2Client
{
    /**
     * Get the bank code identifier
     */
    protected function getBankCode(): string
    {
        return 'nlb';
    }

    /**
     * Get the base URL for the PSD2 API
     */
    protected function getBaseUrl(): string
    {
        $environment = config('mk.nlb.environment', 'sandbox');

        if ($environment === 'production') {
            return config('mk.nlb.production_base_url', 'https://developer-ob.nlb.mk/apis/xs2a/v1');
        }

        return config('mk.nlb.sandbox_base_url', 'https://developer-ob.nlb.mk/apis/xs2a/v1');
    }

    /**
     * Get the OAuth2 client ID
     */
    protected function getClientId(): string
    {
        return config('mk.nlb.client_id', env('NLB_CLIENT_ID', ''));
    }

    /**
     * Get the OAuth2 client secret
     */
    protected function getClientSecret(): string
    {
        return config('mk.nlb.client_secret', env('NLB_CLIENT_SECRET', ''));
    }

    /**
     * OAuth flows for NLB are hosted on auth.mk.open-bank.io
     */
    protected function getAuthBaseUrl(): string
    {
        $environment = config('mk.nlb.environment', 'sandbox');

        if ($environment === 'production') {
            return config(
                'mk.nlb.auth_production_base_url',
                config('mk.nlb.auth_sandbox_base_url', 'https://auth.sandbox.mk.open-bank.io/v1/authentication/tenants/nlb')
            );
        }

        return config('mk.nlb.auth_sandbox_base_url', 'https://auth.sandbox.mk.open-bank.io/v1/authentication/tenants/nlb');
    }

    protected function getAuthorizePath(): string
    {
        return '/connect/authorize';
    }

    protected function getTokenPath(): string
    {
        return '/connect/token';
    }

    /**
     * Get BIC/SWIFT code for NLB Banka
     */
    public function getBic(): string
    {
        return 'NLBMKMK2XXX';
    }

    /**
     * Get bank display name
     */
    public function getBankName(): string
    {
        return 'NLB Banka AD Skopje';
    }

    /**
     * Get bank logo URL
     */
    public function getLogoUrl(): string
    {
        return '/images/banks/nlb-logo.png';
    }

    /**
     * NLB Bank requires PKCE for OAuth
     */
    protected function requiresPkce(): bool
    {
        return true;
    }

    /**
     * Get the OAuth scopes for NLB Bank PSD2 API
     *
     * NLB Bank doesn't expose scope configuration in their developer portal,
     * so scopes are automatically granted based on the application type.
     *
     * Trying minimal OpenID Connect scope as NLB uses OIDC.
     * Can be overridden via NLB_SCOPES environment variable.
     *
     * @return string Space-separated list of scopes
     */
    protected function getScopes(): string
    {
        // Check config first, allows override via environment variable
        // Try just 'openid' as NLB may auto-grant PSD2 scopes
        return config('mk.nlb.scopes', 'openid');
    }

    /**
     * Get the path to the client certificate file for mTLS
     *
     * NLB Bank requires mTLS (Mutual TLS) for PSD2 API access.
     * Certificate must be obtained from NLB developer portal.
     *
     * @return string|null Path to certificate file (.pem or .crt)
     */
    protected function getCertificatePath(): ?string
    {
        $certPath = config('mk.nlb.mtls_cert_path', env('NLB_MTLS_CERT_PATH'));

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
        $keyPath = config('mk.nlb.mtls_key_path', env('NLB_MTLS_KEY_PATH'));

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
        return config('mk.nlb.mtls_key_password', env('NLB_MTLS_KEY_PASSWORD'));
    }
}

// CLAUDE-CHECKPOINT
