<?php

namespace Modules\Mk\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Services\NlbGateway;
use Modules\Mk\Http\BankAuthController;
use OakLabs\Psd2\Authorization;
use App\Models\BankAccount;
use Carbon\Carbon;

/**
 * Sync NLB Bank Transactions Job
 * 
 * Fetches transactions from NLB Banka via PSD2 API and stores them in database
 * Respects 15 req/min rate limit (similar to Stopanska)
 */
class SyncNlb implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $companyId;
    protected $bankAccountId;
    protected $daysBack;
    protected $maxTransactions;

    /**
     * Create a new job instance
     */
    public function __construct(int $companyId, ?int $bankAccountId = null, int $daysBack = 30, int $maxTransactions = 100)
    {
        $this->companyId = $companyId;
        $this->bankAccountId = $bankAccountId;
        $this->daysBack = $daysBack;
        $this->maxTransactions = $maxTransactions;
    }

    /**
     * Execute the job
     */
    public function handle()
    {
        Log::info('Starting NLB Bank sync', [
            'company_id' => $this->companyId,
            'bank_account_id' => $this->bankAccountId,
            'days_back' => $this->daysBack
        ]);

        try {
            // Get stored bank tokens
            $authController = new BankAuthController();
            $tokens = $authController->getStoredTokens('nlb', $this->companyId);

            if (!$tokens) {
                throw new \Exception('No NLB bank connection found for company ' . $this->companyId);
            }

            // Check if token is expired
            if ($tokens['expires_at'] && Carbon::parse($tokens['expires_at'])->isPast()) {
                throw new \Exception('NLB bank token has expired for company ' . $this->companyId);
            }

            // Initialize NLB gateway
            $gateway = new NlbGateway();
            $gateway->setAccessToken($tokens['access_token']);

            // Get account details first
            $accounts = $gateway->getAccountDetails();
            
            if (empty($accounts)) {
                Log::warning('No accounts found for NLB sync', ['company_id' => $this->companyId]);
                return;
            }

            $totalSynced = 0;

            foreach ($accounts as $account) {
                // Skip if specific bank account ID is requested and doesn't match
                if ($this->bankAccountId) {
                    $bankAccount = BankAccount::where('company_id', $this->companyId)
                        ->where('id', $this->bankAccountId)
                        ->first();
                    
                    if (!$bankAccount || $bankAccount->account_number !== $account->getAccountNumber()) {
                        continue;
                    }
                }

                // Sync transactions for this account
                $synced = $this->syncAccountTransactions($gateway, $account);
                $totalSynced += $synced;

                // Respect rate limiting (15 req/min = 4-second delay between requests)
                if ($totalSynced > 0) {
                    sleep(4);
                }
            }

            Log::info('NLB Bank sync completed', [
                'company_id' => $this->companyId,
                'total_synced' => $totalSynced
            ]);

        } catch (\Exception $e) {
            Log::error('NLB Bank sync failed', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Sync transactions for a specific account
     */
    protected function syncAccountTransactions(NlbGateway $gateway, $account): int
    {
        try {
            // Find or create bank account record
            $bankAccount = $this->findOrCreateBankAccount($account);

            // Get transactions (respecting pagination and limits)
            $transactions = $gateway->getSepaTransactions(1, min($this->maxTransactions, 100));

            $syncedCount = 0;
            $cutoffDate = Carbon::now()->subDays($this->daysBack);

            foreach ($transactions as $transaction) {
                // Skip old transactions
                $transactionDate = Carbon::parse($transaction->getCreatedAt());
                if ($transactionDate->lt($cutoffDate)) {
                    continue;
                }

                // Check if transaction already exists
                if ($this->transactionExists($transaction, $bankAccount->id)) {
                    continue;
                }

                // Store transaction
                $this->storeTransaction($transaction, $bankAccount);
                $syncedCount++;
            }

            // Update bank account balance
            $this->updateBankAccountBalance($bankAccount, $account);

            Log::info('NLB account transactions synced', [
                'account_id' => $account->getId(),
                'synced_count' => $syncedCount
            ]);

            return $syncedCount;

        } catch (\Exception $e) {
            Log::error('Failed to sync NLB account transactions', [
                'account_id' => $account->getId(),
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Find or create bank account record
     */
    protected function findOrCreateBankAccount($account): BankAccount
    {
        $bankAccount = BankAccount::where('company_id', $this->companyId)
            ->where('account_number', $account->getAccountNumber())
            ->first();

        if (!$bankAccount) {
            $bankAccount = BankAccount::create([
                'company_id' => $this->companyId,
                'currency_id' => $this->getCurrencyId($account->getCurrency()),
                'name' => $account->getName() ?? 'NLB Account',
                'account_number' => $account->getAccountNumber(),
                'iban' => $account->getIban(),
                'swift_code' => $account->getBic(),
                'bank_name' => 'NLB Banka AD Skopje',
                'bank_code' => 'NLB',
                'opening_balance' => 0,
                'current_balance' => $account->getBalance(),
                'is_primary' => false,
                'is_active' => true,
            ]);

            Log::info('Created new NLB bank account', [
                'bank_account_id' => $bankAccount->id,
                'account_number' => $account->getAccountNumber()
            ]);
        }

        return $bankAccount;
    }

    /**
     * Check if transaction already exists
     */
    protected function transactionExists($transaction, int $bankAccountId): bool
    {
        return \DB::table('bank_transactions')
            ->where('bank_account_id', $bankAccountId)
            ->where('external_reference', $transaction->getExternalUid())
            ->exists();
    }

    /**
     * Store transaction in database
     */
    protected function storeTransaction($transaction, BankAccount $bankAccount): void
    {
        \DB::table('bank_transactions')->insert([
            'bank_account_id' => $bankAccount->id,
            'company_id' => $this->companyId,
            'external_reference' => $transaction->getExternalUid(),
            'transaction_reference' => $transaction->getTransactionUid(),
            'amount' => $transaction->getAmount(),
            'currency' => $transaction->getCurrency() ?? $bankAccount->currency->code,
            'description' => $transaction->getDescription(),
            'transaction_date' => Carbon::parse($transaction->getCreatedAt()),
            'booking_status' => $transaction->getBookingStatus() ?? 'booked',
            'debtor_name' => $transaction->getDebtorName(),
            'creditor_name' => $transaction->getCreditorName(),
            'debtor_iban' => $transaction->getIban(),
            'creditor_iban' => $transaction->getIban(),
            'remittance_info' => $transaction->getRemittanceInformation(),
            'source' => 'psd2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Update bank account balance
     */
    protected function updateBankAccountBalance(BankAccount $bankAccount, $account): void
    {
        $bankAccount->update([
            'current_balance' => $account->getBalance(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get currency ID for the given currency code
     */
    protected function getCurrencyId(string $currencyCode): int
    {
        $currency = \DB::table('currencies')
            ->where('code', $currencyCode)
            ->first();

        return $currency ? $currency->id : 1; // Default to first currency if not found
    }

    /**
     * Failed job handling
     */
    public function failed(\Throwable $exception)
    {
        Log::error('NLB sync job failed permanently', [
            'company_id' => $this->companyId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Could send notification to company admin here
    }
}