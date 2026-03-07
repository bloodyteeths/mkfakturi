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
 * Posts journal entries to the IFRS ledger when a travel order is settled.
 * Follows the same pattern as DepreciationGLService.
 *
 * MK Chart of Accounts mapping:
 * DR 441 (Трошоци за службени патувања) — Travel Expense (per diem + mileage + expenses)
 * CR 140 (Побарувања од вработени за аванси) — Clear employee advance (if advance > 0)
 * CR 100 (Готовина) / 102 (Жиро сметка) — Reimbursement payment (if reimbursement > 0)
 * OR DR 100/102 — Employee owes back (if reimbursement < 0)
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
        if (! $company) {
            Log::warning('TravelOrderGL: Company not found', ['company_id' => $order->company_id]);

            return null;
        }

        if (! $this->isIfrsEnabled($company->id)) {
            Log::info('TravelOrderGL: IFRS disabled, skipping GL posting', ['company_id' => $company->id]);

            return null;
        }

        // Already posted — idempotency
        if ($order->ifrs_transaction_id) {
            Log::info('TravelOrderGL: Already posted', ['order_id' => $order->id, 'tx_id' => $order->ifrs_transaction_id]);

            return (string) $order->ifrs_transaction_id;
        }

        $entity = $this->getOrCreateEntity($company);
        if (! $entity) {
            Log::error('TravelOrderGL: Failed to get IFRS entity', ['company_id' => $company->id]);

            return null;
        }

        $this->setUserEntityContext($entity);

        $grandTotal = $order->grand_total; // in cents
        if ($grandTotal <= 0) {
            Log::info('TravelOrderGL: Grand total is zero, skipping', ['order_id' => $order->id]);

            return null;
        }

        // Convert cents to base currency (MKD)
        $totalAmount = $grandTotal / 100;
        $advanceAmount = max(0, $order->advance_amount) / 100;
        $reimbursement = $order->reimbursement_amount / 100; // can be negative

        $currencyId = $this->getCurrencyId($company->id);

        try {
            DB::beginTransaction();

            // Get GL accounts
            $travelExpenseAccount = $this->getTravelExpenseAccount($entity, $currencyId);
            $advanceAccount = $this->getAdvanceAccount($entity, $currencyId);
            $cashAccount = $this->getCashAccount($entity, $currencyId);

            $narration = "Патен налог {$order->travel_number}: {$order->purpose}";

            // Create IFRS Transaction (Journal Entry)
            $transaction = Transaction::create([
                'account_id' => $travelExpenseAccount->id,
                'transaction_date' => Carbon::parse($order->return_date)->endOfDay(),
                'narration' => $narration,
                'transaction_type' => Transaction::JN,
                'currency_id' => $currencyId,
                'entity_id' => $entity->id,
            ]);

            // DR 441: Travel Expense — full grand total
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $travelExpenseAccount->id,
                'amount' => $totalAmount,
                'quantity' => 1,
                'credited' => false, // DEBIT
                'entity_id' => $entity->id,
            ]);

            if ($advanceAmount > 0) {
                // CR 140: Clear the advance given to employee
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $advanceAccount->id,
                    'amount' => $advanceAmount,
                    'quantity' => 1,
                    'credited' => true, // CREDIT
                    'entity_id' => $entity->id,
                ]);
            }

            if ($reimbursement > 0) {
                // CR 100/102: Pay reimbursement to employee
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $cashAccount->id,
                    'amount' => $reimbursement,
                    'quantity' => 1,
                    'credited' => true, // CREDIT
                    'entity_id' => $entity->id,
                ]);
            } elseif ($reimbursement < 0) {
                // DR 100/102: Employee returns excess advance
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $cashAccount->id,
                    'amount' => abs($reimbursement),
                    'quantity' => 1,
                    'credited' => false, // DEBIT
                    'entity_id' => $entity->id,
                ]);
            } elseif ($advanceAmount <= 0) {
                // No advance, no reimbursement — full amount paid from cash
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $cashAccount->id,
                    'amount' => $totalAmount,
                    'quantity' => 1,
                    'credited' => true, // CREDIT
                    'entity_id' => $entity->id,
                ]);
            }

            $transaction->load('lineItems');
            $transaction->post();

            // Link transaction to travel order
            $order->update(['ifrs_transaction_id' => $transaction->id]);

            DB::commit();

            Log::info('TravelOrderGL: Settlement posted', [
                'order_id' => $order->id,
                'travel_number' => $order->travel_number,
                'total' => $totalAmount,
                'advance' => $advanceAmount,
                'reimbursement' => $reimbursement,
                'ifrs_transaction_id' => $transaction->id,
            ]);

            return (string) $transaction->id;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TravelOrderGL: Failed to post settlement', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            // Don't throw — GL failure should not block settlement
            return null;
        }
    }

    /**
     * Account 441: Трошоци за службени патувања (Business Travel Expenses)
     */
    private function getTravelExpenseAccount(Entity $entity, int $currencyId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::OPERATING_EXPENSE,
                'code' => '441',
                'entity_id' => $entity->id,
            ],
            [
                'name' => 'Трошоци за службени патувања',
                'currency_id' => $currencyId,
            ]
        );
    }

    /**
     * Account 140: Побарувања од вработени за дадени аванси
     */
    private function getAdvanceAccount(Entity $entity, int $currencyId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::RECEIVABLE,
                'code' => '140',
                'entity_id' => $entity->id,
            ],
            [
                'name' => 'Побарувања од вработени за дадени аванси',
                'currency_id' => $currencyId,
            ]
        );
    }

    /**
     * Account 100: Готовина (Cash on hand)
     * Could also be 102 (Жиро сметка) depending on payment method,
     * but for simplicity we default to 100 (cash).
     */
    private function getCashAccount(Entity $entity, int $currencyId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::BANK,
                'code' => '100',
                'entity_id' => $entity->id,
            ],
            [
                'name' => 'Готовина и парични еквиваленти',
                'currency_id' => $currencyId,
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
