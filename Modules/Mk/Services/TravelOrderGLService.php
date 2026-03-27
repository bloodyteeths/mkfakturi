<?php

namespace Modules\Mk\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use Carbon\Carbon;
use IFRS\Models\Account;
use IFRS\Models\Entity;
use IFRS\Models\LineItem;
use IFRS\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Models\TravelOrder;

/**
 * Travel Order General Ledger Service
 *
 * Posts itemized journal entries per expense category when a travel order is settled.
 * Per Правилник за контниот план 174/2011:
 *
 * DR 440 (Дневници за службени патувања)      — Per diem
 * DR 403 (Трошоци за енергија / горива)       — Fuel
 * DR 449 (Останати трошоци од работењето)     — Tolls, parking, other
 * DR 419 (Останати услуги)                    — Forwarding, communication
 * DR 410 (Транспортни услуги)                 — Vehicle maintenance
 * DR 130 (Претходен ДДВ)                      — Input VAT (deductible expenses)
 * CR 143 (Побарувања за аконтации за патувања) — Clear employee advance
 * CR 102 (Благајна) / 100 (Банка)             — Reimbursement payment
 */
class TravelOrderGLService
{
    /**
     * Post settlement journal entry for a travel order.
     *
     * @return string|null IFRS transaction ID if posted, null if skipped
     */
    public function postSettlement(TravelOrder $order): ?string
    {
        $company = Company::find($order->company_id);
        if (!$company) {
            Log::warning('TravelOrderGL: Company not found', ['company_id' => $order->company_id]);
            return null;
        }

        if (!$this->isIfrsEnabled($company->id)) {
            Log::info('TravelOrderGL: IFRS disabled, skipping GL posting', ['company_id' => $company->id]);
            return null;
        }

        // Already posted — idempotency
        if ($order->ifrs_transaction_id) {
            Log::info('TravelOrderGL: Already posted', ['order_id' => $order->id, 'tx_id' => $order->ifrs_transaction_id]);
            return (string) $order->ifrs_transaction_id;
        }

        $entity = $this->getOrCreateEntity($company);
        if (!$entity) {
            Log::error('TravelOrderGL: Failed to get IFRS entity', ['company_id' => $company->id]);
            return null;
        }

        $this->setUserEntityContext($entity);

        $grandTotal = $order->grand_total; // in cents
        if ($grandTotal <= 0) {
            Log::info('TravelOrderGL: Grand total is zero, skipping', ['order_id' => $order->id]);
            return null;
        }

        $currencyId = $this->getCurrencyId($company->id);

        try {
            DB::beginTransaction();

            $narration = "Патен налог {$order->travel_number}: {$order->purpose}";

            // Get the main debit account (440 for per-diem — used as transaction header)
            $perDiemAccount = $this->getAccount($entity, $currencyId, '440', 'Дневници за службени патувања', Account::OPERATING_EXPENSE);

            // Create IFRS Transaction (Journal Entry)
            $transaction = Transaction::create([
                'account_id' => $perDiemAccount->id,
                'transaction_date' => Carbon::parse($order->return_date)->endOfDay(),
                'narration' => $narration,
                'transaction_type' => Transaction::JN,
                'currency_id' => $currencyId,
                'entity_id' => $entity->id,
            ]);

            // ─── DEBIT side: itemized by expense category ───

            // Group expenses by GL code
            $order->load('expenses');
            $glGroups = [];
            foreach ($order->expenses as $expense) {
                $code = $expense->gl_account_code ?? '449';
                $amountCents = $expense->amount_mkd ?? $expense->amount;
                $vatCents = $expense->vat_amount ?? 0;
                $glGroups[$code] = ($glGroups[$code] ?? 0) + $amountCents;
                if ($vatCents > 0) {
                    $glGroups['130'] = ($glGroups['130'] ?? 0) + $vatCents;
                }
            }

            // DR 440: Per-diem (always from total_per_diem, not expenses)
            $perDiemAmount = $order->total_per_diem / 100;
            if ($perDiemAmount > 0) {
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $perDiemAccount->id,
                    'amount' => $perDiemAmount,
                    'quantity' => 1,
                    'credited' => false,
                    'entity_id' => $entity->id,
                ]);
            }

            // DR: Mileage (if any) — also goes to 440
            $mileageAmount = $order->total_mileage_cost / 100;
            if ($mileageAmount > 0) {
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $perDiemAccount->id,
                    'amount' => $mileageAmount,
                    'quantity' => 1,
                    'credited' => false,
                    'entity_id' => $entity->id,
                    'narration' => 'Километража',
                ]);
            }

            // DR: Each expense GL group
            $glConfig = config('travel-expenses.categories', []);
            $glNameMap = [
                '403' => ['Трошоци за енергија (горива)', Account::OPERATING_EXPENSE],
                '410' => ['Транспортни услуги', Account::OPERATING_EXPENSE],
                '419' => ['Останати услуги', Account::OPERATING_EXPENSE],
                '440' => ['Дневници за службени патувања', Account::OPERATING_EXPENSE],
                '449' => ['Останати трошоци од работењето', Account::OPERATING_EXPENSE],
                '130' => ['Претходен ДДВ', Account::RECEIVABLE],
            ];

            foreach ($glGroups as $glCode => $totalCents) {
                if ($totalCents <= 0) {
                    continue;
                }
                // Skip 440 from expenses — already handled via per_diem above
                // But expenses categorized under 440 (accommodation, transport, meals) should still be posted
                $amount = $totalCents / 100;
                $nameInfo = $glNameMap[$glCode] ?? ['Останати трошоци', Account::OPERATING_EXPENSE];
                $account = $this->getAccount($entity, $currencyId, $glCode, $nameInfo[0], $nameInfo[1]);

                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $account->id,
                    'amount' => $amount,
                    'quantity' => 1,
                    'credited' => false,
                    'entity_id' => $entity->id,
                ]);
            }

            // ─── CREDIT side ───

            $advanceAmount = max(0, $order->advance_amount) / 100;
            $reimbursement = $order->reimbursement_amount / 100;
            $totalAmount = $grandTotal / 100;

            // CR 143: Clear employee advance
            if ($advanceAmount > 0) {
                $advanceAccount = $this->getAccount($entity, $currencyId, '143', 'Побарувања за аконтации за службени патувања', Account::RECEIVABLE);
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $advanceAccount->id,
                    'amount' => $advanceAmount,
                    'quantity' => 1,
                    'credited' => true,
                    'entity_id' => $entity->id,
                ]);
            }

            // CR/DR 102: Cash reimbursement or return
            $cashAccount = $this->getAccount($entity, $currencyId, '102', 'Парични средства во благајна', Account::BANK);

            if ($reimbursement > 0) {
                // CR 102: Company pays employee the difference
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $cashAccount->id,
                    'amount' => $reimbursement,
                    'quantity' => 1,
                    'credited' => true,
                    'entity_id' => $entity->id,
                ]);
            } elseif ($reimbursement < 0) {
                // DR 102: Employee returns excess advance
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $cashAccount->id,
                    'amount' => abs($reimbursement),
                    'quantity' => 1,
                    'credited' => false,
                    'entity_id' => $entity->id,
                ]);
            } elseif ($advanceAmount <= 0) {
                // No advance, no reimbursement — full amount paid from cash
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $cashAccount->id,
                    'amount' => $totalAmount,
                    'quantity' => 1,
                    'credited' => true,
                    'entity_id' => $entity->id,
                ]);
            }

            $transaction->load('lineItems');
            $transaction->post();

            // Link transaction to travel order
            $order->update(['ifrs_transaction_id' => $transaction->id]);

            DB::commit();

            Log::info('TravelOrderGL: Settlement posted (itemized)', [
                'order_id' => $order->id,
                'travel_number' => $order->travel_number,
                'total' => $totalAmount,
                'advance' => $advanceAmount,
                'reimbursement' => $reimbursement,
                'gl_groups' => array_map(fn($v) => $v / 100, $glGroups),
                'ifrs_transaction_id' => $transaction->id,
            ]);

            return (string) $transaction->id;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TravelOrderGL: Failed to post settlement', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get or create a GL account by code.
     */
    private function getAccount(Entity $entity, int $currencyId, string $code, string $name, string $type): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => $type,
                'code' => $code,
                'entity_id' => $entity->id,
            ],
            [
                'name' => $name,
                'currency_id' => $currencyId,
            ]
        );
    }

    private function isIfrsEnabled(int $companyId): bool
    {
        $globalEnabled = config('ifrs.enabled', false) ||
            env('FEATURE_ACCOUNTING_BACKBONE', false) ||
            (function_exists('feature') && feature('accounting-backbone'));

        if (!$globalEnabled) {
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
        if (!$entity) {
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
