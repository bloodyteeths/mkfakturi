<?php

namespace Modules\Mk\Services;

use App\Services\Banking\Psd2Client;

/**
 * Komercijalna Banka OAuth2 Gateway
 *
 * PSD2 OAuth implementation for Komercijalna Banka AD Skopje
 * Hosted on KIBS Open Banking Platform (same as Stopanska)
 *
 * Developer Portal: https://ob.kb.mk/docs/getting-started
 * Sandbox API: https://sandbox-api.ob.kb.mk/xs2a/v1
 * Production API: https://api.ob.kb.mk/xs2a/v1
 *
 * Rate Limit: 15 requests per minute (4 second intervals)
 */
class KomerOAuth extends Psd2Client
{
    /**
     * Get the bank code identifier
     */
    protected function getBankCode(): string
    {
        return 'komercijalna';
    }

    /**
     * Get the base URL for the PSD2 API
     */
    protected function getBaseUrl(): string
    {
        $environment = config('mk.komercijalna.environment', 'sandbox');

        if ($environment === 'production') {
            return config('mk.komercijalna.production_base_url', 'https://api.ob.kb.mk/xs2a/v1');
        }

        return config('mk.komercijalna.sandbox_base_url', 'https://sandbox-api.ob.kb.mk/xs2a/v1');
    }

    /**
     * Get the OAuth2 client ID
     */
    protected function getClientId(): string
    {
        return config('mk.komercijalna.client_id', env('MK_KOMER_CLIENT_ID', ''));
    }

    /**
     * Get the OAuth2 client secret
     */
    protected function getClientSecret(): string
    {
        return config('mk.komercijalna.client_secret', env('MK_KOMER_CLIENT_SECRET', ''));
    }

    /**
     * KIBS platform uses /oauth2 paths (same as Stopanska)
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
     * Get BIC/SWIFT code for Komercijalna Banka
     */
    public function getBic(): string
    {
        return 'KOBMKM22XXX';
    }

    /**
     * Get bank display name
     */
    public function getBankName(): string
    {
        return 'Komercijalna Banka AD Skopje';
    }

    /**
     * Get bank logo URL
     */
    public function getLogoUrl(): string
    {
        return '/images/banks/komercijalna-logo.png';
    }

    /**
     * Get the OAuth scopes for Komercijalna PSD2 API
     */
    protected function getScopes(): string
    {
        return config('mk.komercijalna.scopes', 'accounts transactions');
    }

    /**
     * Get the path to the client certificate file for mTLS
     *
     * Komercijalna requires mTLS for production PSD2 API access.
     *
     * @return string|null Path to certificate file (.pem or .crt)
     */
    protected function getCertificatePath(): ?string
    {
        $certPath = config('mk.komercijalna.mtls_cert_path', env('MK_KOMER_MTLS_CERT'));

        if (! $certPath) {
            return null;
        }

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
        $keyPath = config('mk.komercijalna.mtls_key_path', env('MK_KOMER_MTLS_KEY'));

        if (! $keyPath) {
            return null;
        }

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
        return config('mk.komercijalna.mtls_key_password', env('MK_KOMER_MTLS_KEY_PASSWORD'));
    }
}
