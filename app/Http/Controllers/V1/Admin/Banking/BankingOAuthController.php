<?php

namespace App\Http\Controllers\V1\Admin\Banking;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankToken;
use App\Models\Company;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Services\StopanskaOAuth;
use Modules\Mk\Services\NlbOAuth;

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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function start(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'provider' => 'required|in:stopanska,nlb'
            ]);

            $provider = $request->provider;
            $company = $this->resolveCompany($request);

            if (!$company) {
                return response()->json([
                    'error' => 'No company found for user'
                ], 404);
            }

            // Get the appropriate OAuth service
            $oauthService = $this->getOAuthService($provider);

            if (!$oauthService) {
                return response()->json([
                    'error' => 'Invalid bank provider'
                ], 400);
            }

            // Generate redirect URI for OAuth callback
            $redirectUri = url("/api/v1/banking/oauth/callback/{$provider}");

            // Get authorization URL
            $authUrl = $oauthService->getAuthUrl($company, $redirectUri);

            Log::info('OAuth flow started', [
                'company_id' => $company->id,
                'provider' => $provider,
                'redirect_uri' => $redirectUri
            ]);

            return response()->json([
                'authorization_url' => $authUrl,
                'provider' => $provider
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to start OAuth flow', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to start OAuth flow',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle OAuth2 callback from bank
     *
     * @param string $provider Bank provider code
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(string $provider, Request $request)
    {
        try {
            // Validate provider
            if (!in_array($provider, ['stopanska', 'nlb'])) {
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
                    'description' => $request->query('error_description')
                ]);

                return redirect('/admin/banking')->with('error', 'Bank authorization failed: ' . $error);
            }

            if (!$code) {
                return redirect('/admin/banking')->with('error', 'No authorization code received');
            }

            // Extract company ID from state
            $companyId = (int) $state;
            $company = Company::find($companyId);

            if (!$company) {
                return redirect('/admin/banking')->with('error', 'Invalid company');
            }

            // Get OAuth service
            $oauthService = $this->getOAuthService($provider);

            if (!$oauthService) {
                return redirect('/admin/banking')->with('error', 'Invalid bank provider');
            }

            // Exchange authorization code for access token
            $redirectUri = url("/api/v1/banking/oauth/callback/{$provider}");
            $token = $oauthService->exchangeCode($company, $code, $redirectUri);

            Log::info('OAuth token exchanged successfully', [
                'company_id' => $company->id,
                'provider' => $provider,
                'token_id' => $token->id
            ]);

            // Fetch bank accounts from PSD2 API
            try {
                $accounts = $oauthService->getAccounts($company);

                // Create BankAccount records for each account
                foreach ($accounts as $accountData) {
                    $this->createBankAccount($company, $provider, $accountData);
                }

                Log::info('Bank accounts created', [
                    'company_id' => $company->id,
                    'provider' => $provider,
                    'count' => count($accounts)
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to fetch initial accounts, will sync later', [
                    'company_id' => $company->id,
                    'provider' => $provider,
                    'error' => $e->getMessage()
                ]);
            }

            // TODO: Dispatch job to sync transactions
            // \App\Jobs\SyncBankTransactions::dispatch($token);

            return redirect('/admin/banking')->with('success', 'Bank connected successfully!');
        } catch (\Exception $e) {
            Log::error('OAuth callback failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect('/admin/banking')->with('error', 'Failed to connect bank: ' . $e->getMessage());
        }
    }

    /**
     * Get OAuth service instance for provider
     *
     * @param string $provider
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
     *
     * @param Company $company
     * @param string $bankCode
     * @param array $accountData
     * @return BankAccount
     */
    private function createBankAccount(Company $company, string $bankCode, array $accountData): BankAccount
    {
        // Get or create currency
        $currencyCode = $accountData['currency'] ?? 'MKD';
        $currency = Currency::where('code', $currencyCode)->first();

        if (!$currency) {
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
                'account_name' => $accountData['name'] ?? ($oauthService->getBankName() . ' Account'),
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
     * Resolve the active company for the authenticated user
     */
    private function resolveCompany(Request $request): ?Company
    {
        if ($this->currentCompany instanceof Company) {
            return $this->currentCompany;
        }

        $user = $request->user();

        if (!$user) {
            return null;
        }

        $companyIdHeader = $request->header('company');
        $companyId = $companyIdHeader !== null ? (int) $companyIdHeader : null;
        $company = null;

        if ($companyId && $user->hasCompany($companyId)) {
            $company = $user->companies()->where('companies.id', $companyId)->first();
        }

        if (!$company) {
            $company = $user->companies()->first();
        }

        return $this->currentCompany = $company;
    }
}

// CLAUDE-CHECKPOINT
