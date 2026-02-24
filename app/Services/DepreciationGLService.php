<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\FixedAsset;
use Carbon\Carbon;
use IFRS\Models\Account;
use IFRS\Models\Entity;
use IFRS\Models\LineItem;
use IFRS\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Depreciation General Ledger Service
 *
 * Posts monthly depreciation journal entries to the IFRS ledger for fixed assets.
 * Follows the same pattern as PayrollGLService.
 *
 * Journal entry per asset per month:
 * DR 431 (Амортизација на материјални средства) — Depreciation Expense
 * CR 1600 (Акумулирана амортизација) — Accumulated Depreciation (contra-asset)
 */
class DepreciationGLService
{
    /**
     * Post monthly depreciation for all active assets of a company.
     *
     * @return array{posted: int, skipped: int, errors: int}
     */
    public function postMonthlyDepreciation(Company $company, Carbon $month): array
    {
        $month = $month->copy()->startOfMonth();
        $results = ['posted' => 0, 'skipped' => 0, 'errors' => 0];

        if (! $this->isIfrsEnabled($company->id)) {
            Log::info('IFRS disabled, skipping depreciation GL posting', ['company_id' => $company->id]);

            return $results;
        }

        $entity = $this->getOrCreateEntity($company);
        if (! $entity) {
            Log::error('Failed to get IFRS entity for depreciation', ['company_id' => $company->id]);

            return $results;
        }

        $this->setUserEntityContext($entity);

        $assets = FixedAsset::forCompany($company->id)
            ->active()
            ->where('acquisition_date', '<=', $month->copy()->endOfMonth())
            ->get();

        foreach ($assets as $asset) {
            try {
                $txId = $this->postAssetDepreciation($asset, $month, $entity);
                if ($txId) {
                    $results['posted']++;
                } else {
                    $results['skipped']++;
                }
            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Failed to post depreciation for asset', [
                    'asset_id' => $asset->id,
                    'month' => $month->toDateString(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Monthly depreciation posting complete', [
            'company_id' => $company->id,
            'month' => $month->format('Y-m'),
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Post depreciation for a single asset for a given month.
     *
     * @return string|null IFRS transaction ID if posted, null if skipped
     */
    public function postAssetDepreciation(FixedAsset $asset, Carbon $month, Entity $entity): ?string
    {
        $month = $month->copy()->startOfMonth();

        // Idempotency: check if already posted
        if ($this->isAlreadyPosted($asset->id, $month)) {
            return null;
        }

        // Skip if asset was acquired after this month
        if ($asset->acquisition_date->gt($month->copy()->endOfMonth())) {
            return null;
        }

        // Skip if asset was disposed before this month
        if ($asset->disposal_date && $asset->disposal_date->lt($month)) {
            return null;
        }

        // Skip if asset is fully depreciated (useful life exceeded)
        $monthsElapsed = $asset->acquisition_date->diffInMonths($month->copy()->endOfMonth());
        if ($monthsElapsed >= $asset->useful_life_months) {
            return null;
        }

        $amount = $asset->monthly_depreciation;
        if ($amount <= 0) {
            return null;
        }

        try {
            DB::beginTransaction();

            $depreciationExpenseAccount = $this->getDepreciationExpenseAccount($asset, $entity);
            $accumulatedDepreciationAccount = $this->getAccumulatedDepreciationAccount($asset, $entity);

            $narration = "Monthly depreciation: {$asset->name} ({$month->format('Y-m')})";

            // Create IFRS Transaction (Journal Entry)
            $transaction = Transaction::create([
                'account_id' => $depreciationExpenseAccount->id,
                'transaction_date' => $month->copy()->endOfMonth(),
                'narration' => $narration,
                'transaction_type' => Transaction::JN,
                'currency_id' => $this->getCurrencyId($asset->company_id),
                'entity_id' => $entity->id,
            ]);

            // DR: Depreciation Expense
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $depreciationExpenseAccount->id,
                'amount' => $amount,
                'quantity' => 1,
                'credited' => false,
                'entity_id' => $entity->id,
            ]);

            // CR: Accumulated Depreciation (contra-asset)
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $accumulatedDepreciationAccount->id,
                'amount' => $amount,
                'quantity' => 1,
                'credited' => true,
                'entity_id' => $entity->id,
            ]);

            $transaction->load('lineItems');
            $transaction->post();

            // Record in tracking table for idempotency
            DB::table('fixed_asset_depreciation_entries')->insert([
                'fixed_asset_id' => $asset->id,
                'company_id' => $asset->company_id,
                'month' => $month->toDateString(),
                'amount' => $amount,
                'ifrs_transaction_id' => $transaction->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            Log::info('Depreciation posted to GL', [
                'asset_id' => $asset->id,
                'asset_name' => $asset->name,
                'month' => $month->format('Y-m'),
                'amount' => $amount,
                'ifrs_transaction_id' => $transaction->id,
            ]);

            return (string) $transaction->id;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check if depreciation already posted for asset+month.
     */
    protected function isAlreadyPosted(int $assetId, Carbon $month): bool
    {
        return DB::table('fixed_asset_depreciation_entries')
            ->where('fixed_asset_id', $assetId)
            ->where('month', $month->toDateString())
            ->exists();
    }

    /**
     * Get depreciation expense account for an asset.
     * Uses asset's linked account or falls back to default 431.
     */
    private function getDepreciationExpenseAccount(FixedAsset $asset, Entity $entity): Account
    {
        // If asset has a specific depreciation expense account configured, use it
        // (Note: the asset model has account_id for the asset GL account,
        //  but depreciation expense is a separate account — code 431)
        return Account::firstOrCreate(
            [
                'account_type' => Account::OPERATING_EXPENSE,
                'code' => '431',
                'entity_id' => $entity->id,
            ],
            [
                'name' => 'Амортизација на материјални средства',
                'currency_id' => $this->getCurrencyId($asset->company_id),
            ]
        );
    }

    /**
     * Get accumulated depreciation account (contra-asset).
     * Uses asset's linked depreciation_account_id or falls back to default 1600.
     */
    private function getAccumulatedDepreciationAccount(FixedAsset $asset, Entity $entity): Account
    {
        // If asset has a specific accumulated depreciation account, use it
        if ($asset->depreciation_account_id) {
            $userAccount = \App\Models\Account::find($asset->depreciation_account_id);
            if ($userAccount) {
                return Account::firstOrCreate(
                    [
                        'account_type' => Account::CONTRA_ASSET,
                        'code' => $userAccount->code ?: '1600',
                        'entity_id' => $entity->id,
                    ],
                    [
                        'name' => $userAccount->name ?: 'Акумулирана амортизација',
                        'currency_id' => $this->getCurrencyId($asset->company_id),
                    ]
                );
            }
        }

        return Account::firstOrCreate(
            [
                'account_type' => Account::CONTRA_ASSET,
                'code' => '1600',
                'entity_id' => $entity->id,
            ],
            [
                'name' => 'Акумулирана амортизација',
                'currency_id' => $this->getCurrencyId($asset->company_id),
            ]
        );
    }

    private function isIfrsEnabled(int $companyId): bool
    {
        $globalEnabled = config('ifrs.enabled', false) ||
            (function_exists('feature') && feature('accounting-backbone'));

        if (! $globalEnabled) {
            return false;
        }

        $companySetting = CompanySetting::getSetting('ifrs_enabled', $companyId);

        return $companySetting === 'YES' || $companySetting === true || $companySetting === '1';
    }

    private function getOrCreateEntity(Company $company): ?Entity
    {
        if (class_exists('App\Domain\Accounting\IfrsAdapter')) {
            $adapter = new \App\Domain\Accounting\IfrsAdapter();
            $reflection = new \ReflectionClass($adapter);
            $method = $reflection->getMethod('getOrCreateEntityForCompany');
            $method->setAccessible(true);

            return $method->invoke($adapter, $company);
        }

        return null;
    }

    private function getCurrencyId(int $companyId): int
    {
        if (class_exists('App\Domain\Accounting\IfrsAdapter')) {
            $adapter = new \App\Domain\Accounting\IfrsAdapter();
            $reflection = new \ReflectionClass($adapter);
            $method = $reflection->getMethod('getCurrencyId');
            $method->setAccessible(true);

            return $method->invoke($adapter, $companyId);
        }

        return 1;
    }

    private function setUserEntityContext(?Entity $entity): void
    {
        if (! $entity) {
            return;
        }

        $user = auth()->user();
        if ($user) {
            $user->entity_id = $entity->id;
            $user->setRelation('entity', $entity);
        }
    }
}

// CLAUDE-CHECKPOINT
