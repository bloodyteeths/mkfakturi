<?php

namespace App\Http\Controllers\V1\Admin\Banking;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Services\NlbOAuth;
use Modules\Mk\Services\StopanskaOAuth;

/**
 * Banking OAuth Controller
 *
 * Handles OAuth2 flow for PSD2 bank connections
 * Supports Stopanska Banka and NLB Banka
 */
class BankingOAuthController extends Controller
{
    private ?Company $currentCompany = null;

    /**
     * Initiate OAuth2 authorization flow
     */
    public function start(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'provider' => 'required|in:stopanska,nlb',
            ]);

            $provider = $request->provider;
            $company = $this->resolveCompany($request);

            if (! $company) {
                return response()->json([
                    'error' => 'No company found for user',
                ], 404);
            }

            // Get the appropriate OAuth service
            $oauthService = $this->getOAuthService($provider);

            if (! $oauthService) {
                return response()->json([
                    'error' => 'Invalid bank provider',
                ], 400);
            }

            // Get redirect URI from config or generate default
            $redirectUri = $this->getRedirectUri($provider);

            // Get authorization URL
            $authUrl = $oauthService->getAuthUrl($company, $redirectUri);

            Log::info('OAuth flow started', [
                'company_id' => $company->id,
                'provider' => $provider,
                'redirect_uri' => $redirectUri,
            ]);

            return response()->json([
                'authorization_url' => $authUrl,
                'provider' => $provider,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to start OAuth flow', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to start OAuth flow',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle OAuth2 callback from bank
     *
     * @param  string  $provider  Bank provider code
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(string $provider, Request $request)
    {
        try {
            // Validate provider
            if (! in_array($provider, ['stopanska', 'nlb'])) {
                return redirect('/admin/banking')->with('error', 'Invalid bank provider');
            }

            // Get authorization code from callback
            $code = $request->query('code');
            $state = $request->query('state');
            $error = $request->query('error');

            // Check for errors
            if ($error) {
                Log::warning('OAuth callback error', [
                    'provider' => $provider,
                    'error' => $error,
                    'description' => $request->query('error_description'),
                ]);

                return redirect('/admin/banking')->with('error', 'Bank authorization failed: '.$error);
            }

            if (! $code) {
                return redirect('/admin/banking')->with('error', 'No authorization code received');
            }

            // Extract company ID from state
            $companyId = (int) $state;
            $company = Company::find($companyId);

            if (! $company) {
                return redirect('/admin/banking')->with('error', 'Invalid company');
            }

            // Get OAuth service
            $oauthService = $this->getOAuthService($provider);

            if (! $oauthService) {
                return redirect('/admin/banking')->with('error', 'Invalid bank provider');
            }

            // Exchange authorization code for access token
            $redirectUri = $this->getRedirectUri($provider);
            $token = $oauthService->exchangeCode($company, $code, $redirectUri, $state);

            Log::info('OAuth token exchanged successfully', [
                'company_id' => $company->id,
                'provider' => $provider,
                'token_id' => $token->id,
            ]);

            // Fetch bank accounts from PSD2 API
            try {
                Log::info('Attempting to fetch bank accounts from PSD2 API', [
                    'company_id' => $company->id,
                    'provider' => $provider,
                    'token_id' => $token->id,
                ]);

                $accounts = $oauthService->getAccounts($company);

                Log::info('Accounts fetched from PSD2 API', [
                    'company_id' => $company->id,
                    'provider' => $provider,
                    'count' => count($accounts),
                    'accounts' => $accounts,
                ]);

                if (empty($accounts)) {
                    Log::warning('No accounts returned from PSD2 API', [
                        'company_id' => $company->id,
                        'provider' => $provider,
                    ]);

                    return redirect('/admin/banking')->with('warning', 'Bank connected but no accounts found. This may be normal for sandbox. Check logs for details.');
                }

                // Create BankAccount records for each account
                $createdCount = 0;
                foreach ($accounts as $accountData) {
                    try {
                        $this->createBankAccount($company, $provider, $accountData);
                        $createdCount++;
                    } catch (\Exception $e) {
                        Log::error('Failed to create bank account', [
                            'company_id' => $company->id,
                            'provider' => $provider,
                            'account_data' => $accountData,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }

                Log::info('Bank accounts created in database', [
                    'company_id' => $company->id,
                    'provider' => $provider,
                    'fetched' => count($accounts),
                    'created' => $createdCount,
                ]);

                return redirect('/admin/banking')->with('success', "Bank connected successfully! {$createdCount} account(s) added.");
            } catch (\Exception $e) {
                Log::error('Failed to fetch accounts from PSD2 API', [
                    'company_id' => $company->id,
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return redirect('/admin/banking')->with('error', 'Bank connected but failed to fetch accounts: '.$e->getMessage());
            }

            // TODO: Dispatch job to sync transactions
            // \App\Jobs\SyncBankTransactions::dispatch($token);

            return redirect('/admin/banking')->with('success', 'Bank connected successfully!');
        } catch (\Exception $e) {
            Log::error('OAuth callback failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect('/admin/banking')->with('error', 'Failed to connect bank: '.$e->getMessage());
        }
    }

    /**
     * Get OAuth service instance for provider
     *
     * @return StopanskaOAuth|NlbOAuth|null
     */
    private function getOAuthService(string $provider)
    {
        switch ($provider) {
            case 'stopanska':
                return app(StopanskaOAuth::class);
            case 'nlb':
                return app(NlbOAuth::class);
            default:
                return null;
        }
    }

    /**
     * Create BankAccount record from PSD2 account data
     */
    private function createBankAccount(Company $company, string $bankCode, array $accountData): BankAccount
    {
        // Get or create currency
        $currencyCode = $accountData['currency'] ?? 'MKD';
        $currency = Currency::where('code', $currencyCode)->first();

        if (! $currency) {
            $currency = $company->currency; // Fallback to company currency
        }

        $oauthService = $this->getOAuthService($bankCode);

        return BankAccount::updateOrCreate(
            [
                'company_id' => $company->id,
                'iban' => $accountData['iban'] ?? null,
                'account_number' => $accountData['resourceId'] ?? $accountData['id'] ?? null,
            ],
            [
                'account_name' => $accountData['name'] ?? ($oauthService->getBankName().' Account'),
                'bank_name' => $oauthService->getBankName(),
                'bank_code' => $bankCode,
                'swift_code' => $oauthService->getBic(),
                'currency_id' => $currency->id,
                'current_balance' => $accountData['balances'][0]['balanceAmount']['amount'] ?? 0,
                'opening_balance' => $accountData['balances'][0]['balanceAmount']['amount'] ?? 0,
                'is_active' => true,
                'is_primary' => false, // User can set this manually later
            ]
        );
    }

    /**
     * Get the redirect URI for OAuth callback
     *
     * @param  string  $provider  Bank provider code
     * @return string Redirect URI
     */
    private function getRedirectUri(string $provider): string
    {
        // Try to get configured redirect URI first
        $configuredUri = config("mk.{$provider}.redirect_uri");

        if ($configuredUri) {
            return $configuredUri;
        }

        // Fall back to auto-generated redirect URI
        return url("/api/v1/banking/oauth/callback/{$provider}");
    }

    /**
     * Resolve the active company for the authenticated user
     */
    private function resolveCompany(Request $request): ?Company
    {
        if ($this->currentCompany instanceof Company) {
            return $this->currentCompany;
        }

        $user = $request->user();

        if (! $user) {
            return null;
        }

        $companyIdHeader = $request->header('company');
        $companyId = $companyIdHeader !== null ? (int) $companyIdHeader : null;
        $company = null;

        if ($companyId && $user->hasCompany($companyId)) {
            $company = $user->companies()->where('companies.id', $companyId)->first();
        }

        if (! $company) {
            $company = $user->companies()->first();
        }

        return $this->currentCompany = $company;
    }
}

// CLAUDE-CHECKPOINT
