<?php

namespace App\Jobs;

use App\Models\BankAccount;
use App\Models\BankToken;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Services\Banking\Psd2Client;
use Modules\Mk\Services\StopanskaOAuth;
use Modules\Mk\Services\NlbOAuth;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sync Bank Transactions Job
 *
 * Scheduled job to fetch transactions from PSD2 banking APIs
 * Runs every 4 hours (configurable)
 *
 * Rate Limiting:
 * - Stopanska: 15 req/min (4 second intervals)
 * - NLB: Standard PSD2 limits
 *
 * Queue: banking
 */
class SyncBankTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The company to sync transactions for
     *
     * @var Company
     */
    protected Company $company;

    /**
     * Number of days to look back for transactions
     *
     * @var int
     */
    protected int $daysBack;

    /**
     * Create a new job instance.
     *
     * @param Company $company Company to sync
     * @param int $daysBack Number of days to look back (default: 7)
     */
    public function __construct(Company $company, int $daysBack = 7)
    {
        $this->company = $company;
        $this->daysBack = $daysBack;
        $this->onQueue('banking');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::info('Starting bank transaction sync', [
            'company_id' => $this->company->id,
            'days_back' => $this->daysBack,
        ]);

        $from = Carbon::now()->subDays($this->daysBack);
        $to = Carbon::now();

        $totalImported = 0;
        $errors = [];

        // Get all active bank accounts for this company
        $bankAccounts = BankAccount::where('company_id', $this->company->id)
            ->where('is_active', true)
            ->get();

        foreach ($bankAccounts as $bankAccount) {
            try {
                $imported = $this->syncAccount($bankAccount, $from, $to);
                $totalImported += $imported;

                Log::info('Bank account synced', [
                    'company_id' => $this->company->id,
                    'account_id' => $bankAccount->id,
                    'imported' => $imported,
                ]);

                // Rate limiting: Wait 4 seconds between accounts (for Stopanska)
                if ($bankAccount->bank_code === 'stopanska') {
                    sleep(4);
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'account_id' => $bankAccount->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to sync bank account', [
                    'company_id' => $this->company->id,
                    'account_id' => $bankAccount->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Bank transaction sync completed', [
            'company_id' => $this->company->id,
            'total_imported' => $totalImported,
            'errors' => count($errors),
        ]);
    }

    /**
     * Sync transactions for a single bank account
     *
     * @param BankAccount $account Bank account to sync
     * @param Carbon $from Start date
     * @param Carbon $to End date
     * @return int Number of transactions imported
     * @throws \Exception If sync fails
     */
    protected function syncAccount(BankAccount $account, Carbon $from, Carbon $to): int
    {
        // Get PSD2 client for this bank
        $client = $this->getClientForBank($account->bank_code);

        if (!$client) {
            throw new \Exception("No PSD2 client found for bank: {$account->bank_code}");
        }

        // Check if company has valid token for this bank
        $token = BankToken::where('company_id', $account->company_id)
            ->where('bank_code', $account->bank_code)
            ->valid()
            ->first();

        if (!$token) {
            Log::warning('No valid token found for bank', [
                'company_id' => $account->company_id,
                'bank_code' => $account->bank_code,
            ]);

            return 0;
        }

        // Fetch transactions from PSD2 API
        $transactions = $client->getTransactions($this->company, $account->account_number, $from, $to);

        $imported = 0;

        foreach ($transactions as $transactionData) {
            if ($this->createTransaction($transactionData, $account)) {
                $imported++;
            }
        }

        return $imported;
    }

    /**
     * Create a bank transaction from PSD2 API data
     *
     * @param array $data Transaction data from API
     * @param BankAccount $account Bank account
     * @return bool True if created, false if duplicate
     */
    protected function createTransaction(array $data, BankAccount $account): bool
    {
        $reference = $data['transactionId'] ?? $data['endToEndId'] ?? uniqid('psd2_');

        // Check for duplicate by reference (idempotency)
        $exists = BankTransaction::where('bank_account_id', $account->id)
            ->where('transaction_reference', $reference)
            ->exists();

        if ($exists) {
            return false; // Duplicate
        }

        $amount = (float) ($data['amount']['amount'] ?? 0);
        $isCredit = $amount > 0;

        BankTransaction::create([
            'bank_account_id' => $account->id,
            'company_id' => $account->company_id,
            'transaction_reference' => $reference,
            'external_reference' => $data['externalUid'] ?? null,
            'transaction_id' => $data['transactionId'] ?? null,
            'end_to_end_id' => $data['endToEndId'] ?? null,
            'amount' => abs($amount),
            'currency' => $data['amount']['currency'] ?? 'MKD',
            'transaction_type' => $isCredit ? BankTransaction::TYPE_CREDIT : BankTransaction::TYPE_DEBIT,
            'booking_status' => $this->mapBookingStatus($data['bookingStatus'] ?? 'booked'),
            'transaction_date' => $this->parseDate($data['transactionDate'] ?? now()),
            'booking_date' => $this->parseDate($data['bookingDate'] ?? $data['transactionDate'] ?? now()),
            'value_date' => $this->parseDate($data['valueDate'] ?? $data['transactionDate'] ?? now()),
            'description' => $data['remittanceInformationUnstructured'] ?? $data['description'] ?? '',
            'remittance_info' => $data['remittanceInformationUnstructured'] ?? null,
            'debtor_name' => $isCredit ? ($data['debtorName'] ?? null) : null,
            'debtor_iban' => $isCredit ? ($data['debtorAccount']['iban'] ?? null) : null,
            'debtor_account' => $isCredit ? ($data['debtorAccount']['account'] ?? null) : null,
            'creditor_name' => !$isCredit ? ($data['creditorName'] ?? null) : null,
            'creditor_iban' => !$isCredit ? ($data['creditorAccount']['iban'] ?? null) : null,
            'creditor_account' => !$isCredit ? ($data['creditorAccount']['account'] ?? null) : null,
            'debtor_bic' => $data['debtorAgent'] ?? null,
            'creditor_bic' => $data['creditorAgent'] ?? null,
            'processing_status' => BankTransaction::STATUS_UNPROCESSED,
            'source' => BankTransaction::SOURCE_PSD2,
            'raw_data' => $data,
        ]);

        return true;
    }

    /**
     * Get PSD2 client for a bank code
     *
     * @param string $bankCode Bank identifier
     * @return Psd2Client|null
     */
    protected function getClientForBank(string $bankCode): ?Psd2Client
    {
        return match ($bankCode) {
            'stopanska' => app(StopanskaOAuth::class),
            'nlb' => app(NlbOAuth::class),
            default => null,
        };
    }

    /**
     * Map PSD2 booking status to our constants
     *
     * @param string $status PSD2 booking status
     * @return string Our booking status
     */
    protected function mapBookingStatus(string $status): string
    {
        return match (strtolower($status)) {
            'booked' => BankTransaction::BOOKING_BOOKED,
            'pending' => BankTransaction::BOOKING_PENDING,
            'information', 'info' => BankTransaction::BOOKING_INFO,
            default => BankTransaction::BOOKING_BOOKED,
        };
    }

    /**
     * Parse date string to Carbon instance
     *
     * @param string|Carbon $date Date string or Carbon instance
     * @return Carbon
     */
    protected function parseDate($date): Carbon
    {
        if ($date instanceof Carbon) {
            return $date;
        }

        try {
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return Carbon::now();
        }
    }
}

// CLAUDE-CHECKPOINT
