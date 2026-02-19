<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


/**
 * OpenID Connect provider for Macedonian eID/OneID login.
 *
 * Pure HTTP implementation — does NOT require Laravel Socialite.
 * Reads configuration from config('services.oneid').
 *
 * @see https://eid.mk (Macedonian eID identity provider)
 */
class OneIdProvider
{
    /** @var string */
    protected string $clientId;

    /** @var string */
    protected string $clientSecret;

    /** @var string */
    protected string $redirectUri;

    /** @var string */
    protected string $authorizeUrl;

    /** @var string */
    protected string $tokenUrl;

    /** @var string */
    protected string $userinfoUrl;

    /**
     * Create a new OneIdProvider instance.
     *
     * Reads all OIDC endpoints and credentials from config('services.oneid').
     */
    public function __construct()
    {
        $config = config('services.oneid');

        $this->clientId = $config['client_id'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? '';
        $this->redirectUri = $config['redirect'] ?? '';
        $this->authorizeUrl = $config['authorize_url'] ?? 'https://eid.mk/connect/authorize';
        $this->tokenUrl = $config['token_url'] ?? 'https://eid.mk/connect/token';
        $this->userinfoUrl = $config['userinfo_url'] ?? 'https://eid.mk/connect/userinfo';
    }

    /**
     * Build the OIDC authorization URL for the redirect step.
     *
     * @param  string  $state  CSRF state token stored in session
     * @return string  Fully-qualified authorize URL with query parameters
     */
    public function getAuthorizationUrl(string $state): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
        ];

        return $this->authorizeUrl . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Exchange an authorization code for tokens (access_token, id_token).
     *
     * @param  string  $code  The authorization code from the callback
     * @return array{access_token: string, id_token: string|null, token_type: string}
     *
     * @throws \RuntimeException When the token exchange fails
     */
    public function exchangeCode(string $code): array
    {
        Log::info('OneID: Exchanging authorization code for tokens');

        $response = Http::timeout(10)
            ->asForm()
            ->post($this->tokenUrl, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

        if ($response->failed()) {
            Log::error('OneID: Token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException(
                'OneID token exchange failed: ' . $response->status()
            );
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'] ?? '',
            'id_token' => $data['id_token'] ?? null,
            'token_type' => $data['token_type'] ?? 'Bearer',
        ];
    }

    /**
     * Fetch the authenticated user's profile from the OIDC userinfo endpoint.
     *
     * @param  string  $accessToken  Bearer token from exchangeCode()
     * @return array{sub: string, name: string|null, email: string|null, phone: string|null}
     *
     * @throws \RuntimeException When the userinfo request fails
     */
    public function getUserInfo(string $accessToken): array
    {
        Log::info('OneID: Fetching user info');

        $response = Http::timeout(10)
            ->withToken($accessToken)
            ->get($this->userinfoUrl);

        if ($response->failed()) {
            Log::error('OneID: Userinfo request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException(
                'OneID userinfo request failed: ' . $response->status()
            );
        }

        $data = $response->json();

        return [
            'sub' => $data['sub'] ?? '',
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone_number'] ?? $data['phone'] ?? null,
        ];
    }
}
