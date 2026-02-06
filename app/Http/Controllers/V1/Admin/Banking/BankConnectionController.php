<?php

namespace App\Http\Controllers\V1\Admin\Banking;

use App\Http\Controllers\Controller;
use App\Models\BankConnection;
use App\Models\BankProvider;
use App\Models\Company;
use App\Services\Banking\Psd2GatewayClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Bank Connection Controller
 *
 * Manages PSD2 bank connections for companies
 * Handles OAuth flow, connection lifecycle, account/transaction fetching
 */
class BankConnectionController extends Controller
{
    protected Psd2GatewayClient $gatewayClient;

    public function __construct(Psd2GatewayClient $gatewayClient)
    {
        $this->gatewayClient = $gatewayClient;
    }

    /**
     * POST /api/v1/{company}/bank/oauth/start
     * Initiate OAuth flow for a bank provider
     */
    public function startOAuth(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bank_provider_key' => 'required|string|in:nlb,stopanska,komercijalna',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            $companyId = $request->header('company');
            $company = Company::findOrFail($companyId);

            $bankProviderKey = $request->input('bank_provider_key');

            // Find or create bank provider
            $provider = BankProvider::where('key', $bankProviderKey)
                ->where('is_active', true)
                ->firstOrFail();

            // Create pending connection record
            $connection = BankConnection::create([
                'company_id' => $company->id,
                'bank_provider_id' => $provider->id,
                'created_by' => $request->user()->id,
                'status' => BankConnection::STATUS_PENDING,
            ]);

            // Get authorization URL from gateway client
            $authorizationUrl = $this->gatewayClient->getAuthorizationUrl(
                $bankProviderKey,
                $company->id
            );

            Log::info('OAuth flow initiated', [
                'company_id' => $company->id,
                'bank_provider' => $bankProviderKey,
                'connection_id' => $connection->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'authorization_url' => $authorizationUrl,
                    'connection_id' => $connection->id,
                    'bank_provider' => $bankProviderKey,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to initiate OAuth flow', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to initiate OAuth flow',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/bank/oauth/callback
     * Handle OAuth callback from bank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleOAuthCallback(Request $request)
    {
        try {
            $code = $request->query('code');
            $state = $request->query('state');
            $error = $request->query('error');

            // Check for OAuth errors
            if ($error) {
                Log::warning('OAuth callback error', [
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
            $company = Company::findOrFail($companyId);

            // Find the pending connection for this company
            $connection = BankConnection::where('company_id', $company->id)
                ->where('status', BankConnection::STATUS_PENDING)
                ->orderBy('created_at', 'desc')
                ->firstOrFail();

            // Exchange code for token
            $this->gatewayClient->exchangeCodeForToken($connection->id, $code, $state);

            // Sync accounts from the bank
            $accountsCount = $this->gatewayClient->syncAccounts($connection->id);

            Log::info('OAuth callback processed successfully', [
                'company_id' => $company->id,
                'connection_id' => $connection->id,
                'accounts_synced' => $accountsCount,
            ]);

            return redirect('/admin/banking')->with('success', "Bank connected successfully! {$accountsCount} accounts synchronized.");
        } catch (\Exception $e) {
            Log::error('OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect('/admin/banking')->with('error', 'Failed to complete bank connection: '.$e->getMessage());
        }
    }

    /**
     * GET /api/v1/{company}/bank/connections
     * List all bank connections for the company
     */
    public function listConnections(Request $request): JsonResponse
    {
        try {
            $companyId = $request->header('company');

            // P0-13: explicit tenant scope
            $connections = BankConnection::with('bankProvider', 'consents')
                ->where('company_id', $companyId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($connection) {
                    return [
                        'id' => $connection->id,
                        'bank_provider' => [
                            'key' => $connection->bankProvider->key,
                            'name' => $connection->bankProvider->name,
                            'logo_url' => $connection->bankProvider->logo_url,
                        ],
                        'status' => $connection->status,
                        'connected_at' => $connection->connected_at?->toIso8601String(),
                        'expires_at' => $connection->expires_at?->toIso8601String(),
                        'last_synced_at' => $connection->last_synced_at?->toIso8601String(),
                        'is_active' => $connection->isActive(),
                        'is_expired' => $connection->isExpired(),
                        'created_at' => $connection->created_at->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $connections,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list bank connections', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to list connections',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/{company}/bank/connections/{id}
     * Revoke consent and disconnect bank connection
     *
     * @param  int  $id  Connection ID
     */
    public function revokeConnection(Request $request, int $id): JsonResponse
    {
        try {
            $companyId = $request->header('company');

            // Verify connection belongs to this company
            $connection = BankConnection::where('id', $id)
                ->where('company_id', $companyId)
                ->firstOrFail();

            // Revoke consent via gateway client
            $success = $this->gatewayClient->revokeConsent($connection->id);

            Log::info('Bank connection revoked', [
                'company_id' => $companyId,
                'connection_id' => $connection->id,
                'success' => $success,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bank connection revoked successfully',
                'data' => [
                    'connection_id' => $connection->id,
                    'status' => BankConnection::STATUS_REVOKED,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to revoke bank connection', [
                'connection_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to revoke connection',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/{company}/bank/accounts
     * List all bank accounts from all active connections
     */
    public function listAccounts(Request $request): JsonResponse
    {
        try {
            $companyId = $request->header('company');

            // Get all active connections
            $connections = BankConnection::with('bankProvider')
                ->where('company_id', $companyId)
                ->where('status', BankConnection::STATUS_ACTIVE)
                ->get();

            $allAccounts = [];

            foreach ($connections as $connection) {
                try {
                    // Fetch accounts from each bank via gateway client
                    $accounts = $this->gatewayClient->fetchAccounts($connection->id);

                    // Add bank provider info to each account
                    foreach ($accounts as $account) {
                        $allAccounts[] = [
                            'connection_id' => $connection->id,
                            'bank_provider' => $connection->bankProvider->name,
                            'bank_code' => $connection->bankProvider->key,
                            'account_id' => $account['id'] ?? $account['resourceId'] ?? null,
                            'iban' => $account['iban'] ?? null,
                            'account_number' => $account['accountNumber'] ?? $account['maskedPan'] ?? null,
                            'currency' => $account['currency'] ?? 'MKD',
                            'balance' => $account['balance'] ?? $account['currentBalance'] ?? 0,
                            'product' => $account['product'] ?? null,
                            'name' => $account['name'] ?? null,
                        ];
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch accounts for connection', [
                        'connection_id' => $connection->id,
                        'bank' => $connection->bankProvider->key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $allAccounts,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list bank accounts', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to list accounts',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/{company}/bank/accounts/{id}/transactions
     * Fetch transactions for a specific account
     *
     * @param  string  $accountId  External account ID
     */
    public function getAccountTransactions(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date|before_or_equal:date_to',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'connection_id' => 'required|integer|exists:bank_connections,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            $companyId = $request->header('company');
            $connectionId = $request->input('connection_id');

            // Verify connection belongs to this company
            $connection = BankConnection::where('id', $connectionId)
                ->where('company_id', $companyId)
                ->where('status', BankConnection::STATUS_ACTIVE)
                ->firstOrFail();

            // Date range defaults
            $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
            $dateTo = $request->input('date_to', now()->toDateString());

            // Fetch transactions via gateway client
            $transactions = $this->gatewayClient->fetchTransactions(
                $connection->id,
                $accountId,
                $dateFrom,
                $dateTo
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'account_id' => $accountId,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'transaction_count' => count($transactions),
                    'transactions' => $transactions,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch account transactions', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch transactions',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/{company}/bank/accounts/{id}/sync
     * Sync transactions for a specific bank account
     *
     * @param  int  $bankAccountId  Local bank account ID
     */
    public function syncBankAccount(Request $request, int $bankAccountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date|before_or_equal:date_to',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            $companyId = $request->header('company');

            // P0-13: Verify bank account belongs to this company via forCompany() scope
            $bankAccount = \App\Models\BankAccount::forCompany($companyId)
                ->where('id', $bankAccountId)
                ->firstOrFail();

            // Date range
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            // Sync transactions via gateway client
            $syncedCount = $this->gatewayClient->syncTransactions(
                $bankAccountId,
                $dateFrom,
                $dateTo
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully synchronized {$syncedCount} transactions",
                'data' => [
                    'bank_account_id' => $bankAccountId,
                    'synced_count' => $syncedCount,
                    'date_from' => $dateFrom ?? now()->subDays(30)->toDateString(),
                    'date_to' => $dateTo ?? now()->toDateString(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync bank account', [
                'bank_account_id' => $bankAccountId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to sync bank account',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

// CLAUDE-CHECKPOINT
