<?php

namespace App\Services\Banking;

use App\Models\BankAccount;
use App\Models\BankConsent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PSD2 Account Sync Service
 *
 * Responsible for synchronizing bank accounts discovered through PSD2
 * consent flows into the normalized bank_accounts table.
 *
 * Uses updateOrCreate with (company_id, external_id) as the unique key
 * to handle idempotent syncing without creating duplicates.
 *
 * Rate-limit: PSD2 APIs allow 15 req/min (see CLAUDE.md section 7).
 *
 * @see \App\Models\BankAccount
 * @see \App\Models\BankConsent
 */
class Psd2AccountSyncService
{
    /**
     * Sync bank accounts from a PSD2 consent response.
     *
     * Takes the raw PSD2 account list (from Psd2Client::getAccounts) and
     * upserts them into bank_accounts, linking each to the consent that
     * discovered them.
     *
     * @param  BankConsent  $consent  The active PSD2 consent
     * @param  array  $psd2Accounts  Raw account data from PSD2 API
     *                                Expected keys per account:
     *                                - resourceId (string, required): PSD2 account ID
     *                                - iban (string, optional): IBAN
     *                                - bban (string, optional): Basic Bank Account Number
     *                                - currency (string, optional): ISO 4217 code, default 'MKD'
     *                                - cashAccountType (string, optional): e.g. 'CACC', 'SVGS'
     *                                - name (string, optional): Account name from bank
     *                                - bic (string, optional): BIC/SWIFT code
     * @return Collection<BankAccount>  Collection of synced BankAccount models
     *
     * @throws \InvalidArgumentException  If consent has no valid bank connection
     * @throws \Throwable  On database transaction failure
     */
    public function syncAccountsFromConsent(BankConsent $consent, array $psd2Accounts): Collection
    {
        // Load the bank connection to get company_id
        $consent->loadMissing('bankConnection');
        $bankConnection = $consent->bankConnection;

        if (! $bankConnection) {
            throw new \InvalidArgumentException(
                "BankConsent #{$consent->id} has no associated BankConnection."
            );
        }

        $companyId = $bankConnection->company_id;

        // Derive bank name from the provider if available
        $bankName = $this->resolveBankName($bankConnection);

        Log::info('Starting PSD2 account sync', [
            'consent_id' => $consent->id,
            'company_id' => $companyId,
            'account_count' => count($psd2Accounts),
        ]);

        $syncedAccounts = collect();

        DB::transaction(function () use ($consent, $psd2Accounts, $companyId, $bankName, &$syncedAccounts) {
            foreach ($psd2Accounts as $psd2Account) {
                // resourceId is the mandatory PSD2 account identifier
                $externalId = $psd2Account['resourceId'] ?? null;

                if (! $externalId) {
                    Log::warning('Skipping PSD2 account without resourceId', [
                        'consent_id' => $consent->id,
                        'account_data' => $psd2Account,
                    ]);

                    continue;
                }

                $accountType = $this->mapAccountType($psd2Account['cashAccountType'] ?? null);

                $account = BankAccount::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'external_id' => $externalId,
                    ],
                    [
                        'bank_consent_id' => $consent->id,
                        'iban' => $psd2Account['iban'] ?? null,
                        'account_number' => $psd2Account['bban'] ?? null,
                        'currency' => $psd2Account['currency'] ?? 'MKD',
                        'bank_name' => $bankName,
                        'bank_code' => $psd2Account['bic'] ?? null,
                        'account_type' => $accountType,
                        'account_name' => $psd2Account['name'] ?? ($bankName . ' - ' . $accountType),
                        'status' => BankAccount::STATUS_ACTIVE,
                        'is_active' => true,
                        'last_synced_at' => now(),
                    ]
                );

                $syncedAccounts->push($account);

                Log::info('PSD2 account synced', [
                    'bank_account_id' => $account->id,
                    'external_id' => $externalId,
                    'iban' => $account->masked_iban,
                    'was_recently_created' => $account->wasRecentlyCreated,
                ]);
            }
        });

        // Mark any accounts from this consent that are no longer in the
        // PSD2 response as disconnected (account may have been closed at bank)
        $this->markRemovedAccountsAsDisconnected($consent, $companyId, $syncedAccounts);

        Log::info('PSD2 account sync completed', [
            'consent_id' => $consent->id,
            'company_id' => $companyId,
            'synced_count' => $syncedAccounts->count(),
            'new_count' => $syncedAccounts->filter(fn ($a) => $a->wasRecentlyCreated)->count(),
            'updated_count' => $syncedAccounts->reject(fn ($a) => $a->wasRecentlyCreated)->count(),
        ]);

        return $syncedAccounts;
    }

    /**
     * Disconnect bank accounts that were previously linked to a consent
     * but are no longer returned by the PSD2 API.
     *
     * @param  BankConsent  $consent  The consent being synced
     * @param  int  $companyId  Company ID
     * @param  Collection  $syncedAccounts  Accounts that were just synced
     * @return int  Number of accounts disconnected
     */
    public function markRemovedAccountsAsDisconnected(
        BankConsent $consent,
        int $companyId,
        Collection $syncedAccounts
    ): int {
        $syncedIds = $syncedAccounts->pluck('id')->toArray();

        $removedAccounts = BankAccount::where('company_id', $companyId)
            ->where('bank_consent_id', $consent->id)
            ->where('status', BankAccount::STATUS_ACTIVE)
            ->whereNotIn('id', $syncedIds)
            ->get();

        $count = 0;
        foreach ($removedAccounts as $account) {
            $account->disconnect();
            $count++;

            Log::warning('PSD2 account disconnected (no longer in API response)', [
                'bank_account_id' => $account->id,
                'external_id' => $account->external_id,
                'iban' => $account->masked_iban,
            ]);
        }

        return $count;
    }

    /**
     * Disconnect all accounts linked to a consent (e.g., when consent is revoked).
     *
     * @param  BankConsent  $consent  The revoked/expired consent
     * @return int  Number of accounts disconnected
     */
    public function disconnectAccountsForConsent(BankConsent $consent): int
    {
        $accounts = BankAccount::where('bank_consent_id', $consent->id)
            ->where('status', BankAccount::STATUS_ACTIVE)
            ->get();

        $count = 0;
        foreach ($accounts as $account) {
            $account->disconnect();
            $count++;
        }

        Log::info('All accounts disconnected for revoked/expired consent', [
            'consent_id' => $consent->id,
            'disconnected_count' => $count,
        ]);

        return $count;
    }

    /**
     * Find an existing bank account by IBAN for a company.
     *
     * Useful for CSV imports that need to link to an existing bank account.
     *
     * @param  int  $companyId  Company ID
     * @param  string  $iban  IBAN to search for
     * @return BankAccount|null
     */
    public function findByIban(int $companyId, string $iban): ?BankAccount
    {
        return BankAccount::where('company_id', $companyId)
            ->where('iban', $iban)
            ->first();
    }

    /**
     * Find an existing bank account by external PSD2 resource ID.
     *
     * @param  int  $companyId  Company ID
     * @param  string  $externalId  PSD2 resourceId
     * @return BankAccount|null
     */
    public function findByExternalId(int $companyId, string $externalId): ?BankAccount
    {
        return BankAccount::where('company_id', $companyId)
            ->where('external_id', $externalId)
            ->first();
    }

    /**
     * Resolve bank name from the BankConnection's provider.
     *
     * @param  \App\Models\BankConnection  $connection
     * @return string
     */
    private function resolveBankName($connection): string
    {
        // Try to load the bank provider name
        if ($connection->relationLoaded('bankProvider')) {
            $provider = $connection->bankProvider;
        } else {
            $connection->loadMissing('bankProvider');
            $provider = $connection->bankProvider;
        }

        if ($provider && ! empty($provider->name)) {
            return $provider->name;
        }

        // Fallback: use metadata or generic name
        $metadata = $connection->metadata ?? [];

        return $metadata['bank_name'] ?? 'Unknown Bank';
    }

    /**
     * Map PSD2 cashAccountType codes to human-readable types.
     *
     * Common PSD2 (ISO 20022) account type codes:
     * - CACC: Current Account
     * - SVGS: Savings Account
     * - CASH: Cash Account
     * - TRAN: Transitory Account
     *
     * @param  string|null  $psd2Type  PSD2 cashAccountType code
     * @return string  Mapped account type
     */
    private function mapAccountType(?string $psd2Type): string
    {
        $mapping = [
            'CACC' => BankAccount::TYPE_CHECKING,
            'SVGS' => BankAccount::TYPE_SAVINGS,
            'CASH' => BankAccount::TYPE_CHECKING,
            'TRAN' => BankAccount::TYPE_CHECKING,
            'checking' => BankAccount::TYPE_CHECKING,
            'savings' => BankAccount::TYPE_SAVINGS,
            'business' => BankAccount::TYPE_BUSINESS,
        ];

        return $mapping[$psd2Type] ?? BankAccount::TYPE_CHECKING;
    }
}

// CLAUDE-CHECKPOINT
