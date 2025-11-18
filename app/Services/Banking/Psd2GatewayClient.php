<?php

namespace App\Services\Banking;

use App\Models\BankAccount;
use App\Models\BankConnection;
use App\Models\BankConsent;
use App\Models\BankProvider;
use App\Models\BankTransaction;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Services\KomerGateway;
use Modules\Mk\Services\NlbGateway;
use Modules\Mk\Services\StopanskaGateway;

/**
 * PSD2 Gateway Client
 *
 * Unified service for interacting with PSD2 banking gateway
 * Provides high-level operations across multiple Macedonian banks
 *
 * Features:
 * - OAuth2 authorization flow management
 * - Account fetching and synchronization
 * - Transaction retrieval with date ranges
 * - Connection lifecycle management
 * - Multi-bank support (NLB, Stopanska, Komercijalna)
 */
class Psd2GatewayClient
{
    /**
     * Get authorization URL for OAuth flow
     *
     * @param  string  $bankProviderKey  Bank provider key (nlb, stopanska, komercijalna)
     * @param  int  $companyId  Company ID
     * @return string Authorization URL
     *
     * @throws \Exception If bank provider not found or not supported
     */
    public function getAuthorizationUrl(string $bankProviderKey, int $companyId): string
    {
        $company = Company::findOrFail($companyId);
        $provider = BankProvider::where('key', $bankProviderKey)
            ->where('is_active', true)
            ->firstOrFail();

        // Get bank-specific gateway
        $gateway = $this->getGateway($bankProviderKey);

        // Generate redirect URI
        $redirectUri = $this->getRedirectUri($bankProviderKey);

        // Get authorization URL from gateway
        $authUrl = $gateway->getAuthUrl($company, $redirectUri);

        Log::info('Generated OAuth authorization URL', [
            'bank' => $bankProviderKey,
            'company_id' => $companyId,
            'redirect_uri' => $redirectUri,
        ]);

        return $authUrl;
    }

    /**
     * Exchange authorization code for access token
     *
     * @param  int  $connectionId  Bank connection ID
     * @param  string  $code  Authorization code from OAuth callback
     * @param  string|null  $state  State parameter from callback
     *
     * @throws \Exception If exchange fails
     */
    public function exchangeCodeForToken(int $connectionId, string $code, ?string $state = null): void
    {
        $connection = BankConnection::with('bankProvider', 'company')->findOrFail($connectionId);

        // Get bank-specific gateway
        $gateway = $this->getGateway($connection->bankProvider->key);

        // Get redirect URI
        $redirectUri = $this->getRedirectUri($connection->bankProvider->key);

        try {
            // Exchange code for token
            $token = $gateway->exchangeCode($connection->company, $code, $redirectUri, $state);

            // Create or update consent record
            $consent = BankConsent::updateOrCreate(
                [
                    'bank_connection_id' => $connection->id,
                    'consent_id' => $token->id.'_'.time(), // Unique consent ID
                ],
                [
                    'status' => BankConsent::STATUS_ACTIVE,
                    'scope' => $token->scope ?? 'accounts transactions',
                    'access_token' => $token->access_token,
                    'refresh_token' => $token->refresh_token,
                    'token_type' => $token->token_type ?? 'Bearer',
                    'granted_at' => now(),
                    'expires_at' => $token->expires_at,
                ]
            );

            // Activate connection
            $connection->update([
                'status' => BankConnection::STATUS_ACTIVE,
                'connected_at' => now(),
                'expires_at' => $token->expires_at,
            ]);

            Log::info('OAuth token exchanged and consent created', [
                'connection_id' => $connection->id,
                'bank' => $connection->bankProvider->key,
                'consent_id' => $consent->id,
            ]);
        } catch (\Exception $e) {
            // Update connection with error status
            $connection->update([
                'status' => BankConnection::STATUS_ERROR,
                'metadata' => [
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);

            Log::error('Failed to exchange OAuth code', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Fetch accounts from bank for a connection
     *
     * @param  int  $connectionId  Bank connection ID
     * @return array Array of account data
     *
     * @throws \Exception If fetch fails
     */
    public function fetchAccounts(int $connectionId): array
    {
        $connection = BankConnection::with('bankProvider', 'company')->findOrFail($connectionId);

        if (! $connection->isActive()) {
            throw new \Exception('Bank connection is not active');
        }

        // Get bank-specific gateway
        $gateway = $this->getGateway($connection->bankProvider->key);

        try {
            // Fetch accounts from PSD2 API
            $accounts = $gateway->getAccounts($connection->company);

            // Update last sync timestamp
            $connection->update(['last_synced_at' => now()]);

            Log::info('Fetched accounts from bank', [
                'connection_id' => $connection->id,
                'bank' => $connection->bankProvider->key,
                'account_count' => count($accounts),
            ]);

            return $accounts;
        } catch (\Exception $e) {
            Log::error('Failed to fetch accounts', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Fetch transactions from bank for a specific account
     *
     * @param  int  $connectionId  Bank connection ID
     * @param  string  $accountId  Bank account identifier
     * @param  \DateTime|string  $dateFrom  Start date
     * @param  \DateTime|string  $dateTo  End date
     * @return array Array of transaction data
     *
     * @throws \Exception If fetch fails
     */
    public function fetchTransactions(
        int $connectionId,
        string $accountId,
        $dateFrom,
        $dateTo
    ): array {
        $connection = BankConnection::with('bankProvider', 'company')->findOrFail($connectionId);

        if (! $connection->isActive()) {
            throw new \Exception('Bank connection is not active');
        }

        // Convert dates to Carbon instances
        $from = $dateFrom instanceof Carbon ? $dateFrom : Carbon::parse($dateFrom);
        $to = $dateTo instanceof Carbon ? $dateTo : Carbon::parse($dateTo);

        // Get bank-specific gateway
        $gateway = $this->getGateway($connection->bankProvider->key);

        try {
            // Fetch transactions from PSD2 API
            $transactions = $gateway->getTransactions($connection->company, $accountId, $from, $to);

            // Update last sync timestamp
            $connection->update(['last_synced_at' => now()]);

            Log::info('Fetched transactions from bank', [
                'connection_id' => $connection->id,
                'bank' => $connection->bankProvider->key,
                'account_id' => $accountId,
                'transaction_count' => count($transactions),
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
            ]);

            return $transactions;
        } catch (\Exception $e) {
            Log::error('Failed to fetch transactions', [
                'connection_id' => $connection->id,
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync accounts for a connection (fetch and store in database)
     *
     * @param  int  $connectionId  Bank connection ID
     * @return int Number of accounts synchronized
     */
    public function syncAccounts(int $connectionId): int
    {
        $connection = BankConnection::with('bankProvider', 'company')->findOrFail($connectionId);

        // Fetch accounts from API
        $accounts = $this->fetchAccounts($connectionId);

        $syncedCount = 0;

        foreach ($accounts as $accountData) {
            // Create or update bank account in database
            BankAccount::updateOrCreate(
                [
                    'company_id' => $connection->company_id,
                    'bank_code' => $connection->bankProvider->key,
                    'external_id' => $accountData['id'] ?? $accountData['resourceId'] ?? null,
                ],
                [
                    'bank_name' => $connection->bankProvider->name,
                    'account_number' => $accountData['accountNumber'] ?? $accountData['maskedPan'] ?? null,
                    'iban' => $accountData['iban'] ?? null,
                    'currency_id' => $this->getCurrencyId($accountData['currency'] ?? 'MKD'),
                    'current_balance' => $accountData['balance'] ?? $accountData['currentBalance'] ?? 0,
                    'is_active' => true,
                    'metadata' => [
                        'product' => $accountData['product'] ?? null,
                        'cashAccountType' => $accountData['cashAccountType'] ?? null,
                        'bic' => $accountData['bic'] ?? null,
                        'name' => $accountData['name'] ?? null,
                    ],
                ]
            );

            $syncedCount++;
        }

        Log::info('Synchronized bank accounts', [
            'connection_id' => $connection->id,
            'synced_count' => $syncedCount,
        ]);

        return $syncedCount;
    }

    /**
     * Sync transactions for a bank account
     *
     * @param  int  $bankAccountId  Local bank account ID
     * @param  \DateTime|string|null  $dateFrom  Start date (defaults to 30 days ago)
     * @param  \DateTime|string|null  $dateTo  End date (defaults to today)
     * @return int Number of transactions synchronized
     */
    public function syncTransactions(
        int $bankAccountId,
        $dateFrom = null,
        $dateTo = null
    ): int {
        $bankAccount = BankAccount::with('company')->findOrFail($bankAccountId);

        // Find active connection for this bank
        $connection = BankConnection::where('company_id', $bankAccount->company_id)
            ->whereHas('bankProvider', function ($q) use ($bankAccount) {
                $q->where('key', $bankAccount->bank_code);
            })
            ->where('status', BankConnection::STATUS_ACTIVE)
            ->firstOrFail();

        // Default date range: last 30 days
        $from = $dateFrom ? Carbon::parse($dateFrom) : Carbon::now()->subDays(30);
        $to = $dateTo ? Carbon::parse($dateTo) : Carbon::now();

        // Fetch transactions from API
        $transactions = $this->fetchTransactions(
            $connection->id,
            $bankAccount->external_id,
            $from,
            $to
        );

        $syncedCount = 0;

        foreach ($transactions as $txnData) {
            // Create or update transaction in database
            BankTransaction::updateOrCreate(
                [
                    'bank_account_id' => $bankAccount->id,
                    'reference' => $txnData['transactionId'] ?? $txnData['endToEndId'] ?? null,
                ],
                [
                    'company_id' => $bankAccount->company_id,
                    'transaction_date' => $txnData['bookingDate'] ?? $txnData['valueDate'] ?? now(),
                    'amount' => abs($txnData['transactionAmount']['amount'] ?? $txnData['amount'] ?? 0),
                    'type' => $this->determineTransactionType($txnData),
                    'counterparty_name' => $txnData['debtorName'] ?? $txnData['creditorName'] ?? null,
                    'counterparty_account' => $txnData['debtorAccount'] ?? $txnData['creditorAccount'] ?? null,
                    'description' => $txnData['remittanceInformationUnstructured'] ?? $txnData['additionalInformation'] ?? null,
                    'status' => 'booked',
                    'metadata' => $txnData,
                ]
            );

            $syncedCount++;
        }

        // Update bank account balance if provided
        if (isset($transactions[0]['balanceAfterTransaction'])) {
            $bankAccount->update([
                'current_balance' => $transactions[0]['balanceAfterTransaction']['amount'],
            ]);
        }

        Log::info('Synchronized bank transactions', [
            'bank_account_id' => $bankAccount->id,
            'synced_count' => $syncedCount,
        ]);

        return $syncedCount;
    }

    /**
     * Revoke consent and disconnect bank connection
     *
     * @param  int  $connectionId  Bank connection ID
     * @return bool Success status
     */
    public function revokeConsent(int $connectionId): bool
    {
        $connection = BankConnection::with('bankProvider', 'company')->findOrFail($connectionId);

        // Get bank-specific gateway
        $gateway = $this->getGateway($connection->bankProvider->key);

        try {
            // Revoke token on bank side
            $gateway->revokeToken($connection->company);

            // Update connection status
            $connection->update([
                'status' => BankConnection::STATUS_REVOKED,
            ]);

            // Revoke all consents for this connection
            BankConsent::where('bank_connection_id', $connection->id)
                ->update(['status' => BankConsent::STATUS_REVOKED]);

            Log::info('Bank connection revoked', [
                'connection_id' => $connection->id,
                'bank' => $connection->bankProvider->key,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to revoke consent', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            // Still update local status even if revoke failed on bank side
            $connection->update([
                'status' => BankConnection::STATUS_REVOKED,
            ]);

            return false;
        }
    }

    /**
     * Get the appropriate gateway instance for a bank
     *
     * @param  string  $bankKey  Bank provider key
     * @return Psd2Client|object Gateway instance
     *
     * @throws \Exception If bank not supported
     */
    protected function getGateway(string $bankKey)
    {
        return match ($bankKey) {
            'nlb' => new NlbGateway,
            'stopanska' => new StopanskaGateway,
            'komercijalna' => new KomerGateway,
            default => throw new \Exception("Unsupported bank: {$bankKey}"),
        };
    }

    /**
     * Get redirect URI for OAuth callback
     *
     * @param  string  $bankKey  Bank provider key
     * @return string Redirect URI
     */
    protected function getRedirectUri(string $bankKey): string
    {
        // Check if custom redirect URI is configured
        $configKey = strtoupper($bankKey).'_REDIRECT_URI';
        $customUri = config("services.psd2.banks.{$bankKey}.redirect_uri");

        if ($customUri) {
            return $customUri;
        }

        // Generate default redirect URI
        return url("/api/v1/banking/oauth/callback/{$bankKey}");
    }

    /**
     * Get currency ID by code
     *
     * @param  string  $code  Currency code (MKD, EUR, USD)
     * @return int|null Currency ID
     */
    protected function getCurrencyId(string $code): ?int
    {
        static $currencies = [];

        if (! isset($currencies[$code])) {
            $currency = \App\Models\Currency::where('code', $code)->first();
            $currencies[$code] = $currency ? $currency->id : null;
        }

        return $currencies[$code];
    }

    /**
     * Determine transaction type from transaction data
     *
     * @param  array  $txnData  Transaction data
     * @return string Transaction type (credit or debit)
     */
    protected function determineTransactionType(array $txnData): string
    {
        // Check if amount is explicitly signed
        $amount = $txnData['transactionAmount']['amount'] ?? $txnData['amount'] ?? 0;

        if ($amount > 0) {
            return 'credit';
        } elseif ($amount < 0) {
            return 'debit';
        }

        // Check credit/debit indicator
        if (isset($txnData['creditDebitIndicator'])) {
            return strtolower($txnData['creditDebitIndicator']) === 'crdt' ? 'credit' : 'debit';
        }

        // Fallback: check if debtor or creditor name exists
        if (isset($txnData['debtorName'])) {
            return 'debit';  // Money going out
        }

        if (isset($txnData['creditorName'])) {
            return 'credit';  // Money coming in
        }

        // Default to debit
        return 'debit';
    }
}

// CLAUDE-CHECKPOINT
