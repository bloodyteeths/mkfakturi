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
     *
     * @return string
     */
    protected function getBankCode(): string
    {
        return 'nlb';
    }

    /**
     * Get the base URL for the PSD2 API
     *
     * @return string
     */
    protected function getBaseUrl(): string
    {
        $environment = config('mk.nlb.environment', 'sandbox');

        if ($environment === 'production') {
            return config('mk.nlb.production_base_url', 'https://api-ob.nlb.mk/xs2a/v1');
        }

        return config('mk.nlb.sandbox_base_url', 'https://sandbox-api-ob.nlb.mk/xs2a/v1');
    }

    /**
     * Get the OAuth2 client ID
     *
     * @return string
     */
    protected function getClientId(): string
    {
        return config('mk.nlb.client_id', env('NLB_CLIENT_ID', ''));
    }

    /**
     * Get the OAuth2 client secret
     *
     * @return string
     */
    protected function getClientSecret(): string
    {
        return config('mk.nlb.client_secret', env('NLB_CLIENT_SECRET', ''));
    }

    /**
     * Get BIC/SWIFT code for NLB Banka
     *
     * @return string
     */
    public function getBic(): string
    {
        return 'NLBMKMK2XXX';
    }

    /**
     * Get bank display name
     *
     * @return string
     */
    public function getBankName(): string
    {
        return 'NLB Banka AD Skopje';
    }

    /**
     * Get bank logo URL
     *
     * @return string
     */
    public function getLogoUrl(): string
    {
        return '/images/banks/nlb-logo.png';
    }
}

// CLAUDE-CHECKPOINT
