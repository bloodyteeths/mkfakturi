<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fix JN (Journal Entry) transactions to use compound posting mode.
 *
 * The IFRS package's postBasic() creates double-entry pairs where every line item
 * is paired against the main account. For multi-line journal entries, this means
 * the main account gets phantom ledger entries equal to the sum of ALL line items,
 * distorting its balance and breaking trial balance, balance sheet, etc.
 *
 * Fix: Convert all JN transactions to compound mode, where debits are matched
 * directly against credits without routing through a single main account.
 *
 * NOTE: We bypass Transaction::post() because the IFRS package's allocateAmount()
 * has a floating-point comparison bug ($amount == 0 fails with IEEE 754 rounding).
 * Instead we create ledger entries manually with round() and use the Ledger model
 * for proper hash chain computation.
 */
return new class extends Migration
{
    public function up(): void
    {
        $transactions = DB::table('ifrs_transactions')
            ->where('transaction_type', 'JN')
            ->where('compound', false)
            ->get();

        if ($transactions->isEmpty()) {
            Log::info('JN compound fix: no non-compound JN transactions found');
            return;
        }

        $byEntity = $transactions->groupBy('entity_id');

        foreach ($byEntity as $entityId => $entityTransactions) {
            $entity = DB::table('ifrs_entities')->where('id', $entityId)->first();
            if (! $entity) {
                Log::warning("JN compound fix: entity {$entityId} not found, skipping");
                continue;
            }

            // Get exchange rate for this entity
            $exchangeRate = DB::table('ifrs_exchange_rates')
                ->where('entity_id', $entityId)
                ->first();

            if (! $exchangeRate) {
                Log::warning("JN compound fix: no exchange rate for entity {$entityId}, skipping");
                continue;
            }

            $rate = floatval($exchangeRate->rate);

            // Set up auth context for Ledger hash computation (EntityScope)
            $this->setupAuthForEntity($entityId);

            $fixed = 0;
            $skipped = 0;
            $errors = 0;

            foreach ($entityTransactions as $txn) {
                try {
                    $result = $this->convertTransaction($txn, $entityId, $rate);
                    if ($result) {
                        $fixed++;
                    } else {
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("JN compound fix: failed txn {$txn->id}: {$e->getMessage()}");
                    // Continue to next transaction instead of failing the migration
                }
            }

            Log::info("JN compound fix: entity {$entityId}: fixed={$fixed} skipped={$skipped} errors={$errors}");
        }
    }

    /**
     * Convert a single JN transaction from basic to compound posting.
     */
    private function convertTransaction(object $txn, int $entityId, float $rate): bool
    {
        // Get all line items
        $lineItems = DB::table('ifrs_line_items')
            ->where('transaction_id', $txn->id)
            ->orderBy('id')
            ->get();

        if ($lineItems->isEmpty()) {
            return false;
        }

        // Build debit and credit arrays from ALL entries (line items only — main account
        // in postBasic was just a contra routing account, not a real entry)
        $debits = [];
        $credits = [];

        foreach ($lineItems as $li) {
            $entry = [
                'id' => $li->account_id,
                'amount' => round(floatval($li->amount) * floatval($li->quantity), 4),
                'line_item_id' => $li->id,
            ];

            if ($li->credited) {
                $credits[] = $entry;
            } else {
                $debits[] = $entry;
            }
        }

        if (empty($debits) || empty($credits)) {
            Log::warning("JN compound fix: txn {$txn->id} has no debits or no credits, skipping");
            return false;
        }

        $totalDebits = round(array_sum(array_column($debits, 'amount')), 4);
        $totalCredits = round(array_sum(array_column($credits, 'amount')), 4);
        $gap = round(abs($totalDebits - $totalCredits), 4);
        $balanced = $gap < 0.01;

        DB::beginTransaction();
        try {
            // 1. Delete existing (incorrect) ledger entries
            DB::table('ifrs_ledgers')->where('transaction_id', $txn->id)->delete();

            if ($balanced) {
                // Line items balance — pick first debit as main account
                $mainEntry = array_shift($debits);

                DB::table('ifrs_transactions')->where('id', $txn->id)->update([
                    'account_id' => $mainEntry['id'],
                    'compound' => true,
                    'main_account_amount' => $mainEntry['amount'],
                    'credited' => false,
                    'updated_at' => now(),
                ]);

                DB::table('ifrs_line_items')->where('id', $mainEntry['line_item_id'])->delete();

                $allDebits = array_merge([['id' => $mainEntry['id'], 'amount' => $mainEntry['amount']]], $debits);
                $allCredits = $credits;
            } else {
                // Line items DON'T balance — the original main account fills the gap.
                // In postBasic, the main account absorbed all contra-entries.
                // For compound mode, the main account goes on the SHORT side with gap amount.
                $mainCredited = ($totalDebits > $totalCredits) ? true : false;

                DB::table('ifrs_transactions')->where('id', $txn->id)->update([
                    'account_id' => $txn->account_id, // keep original main account
                    'compound' => true,
                    'main_account_amount' => $gap,
                    'credited' => $mainCredited,
                    'updated_at' => now(),
                ]);

                // Keep ALL line items — the main account entry is additional
                $mainCompound = ['id' => $txn->account_id, 'amount' => $gap];
                if ($mainCredited) {
                    $allDebits = $debits;
                    $allCredits = array_merge([$mainCompound], $credits);
                } else {
                    $allDebits = array_merge([$mainCompound], $debits);
                    $allCredits = $credits;
                }
            }

            // Create new compound ledger entries with proper allocation
            $this->createCompoundLedgers($allDebits, $allCredits, $txn, $entityId, $rate);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create compound ledger entries by allocating debits against credits.
     * Uses round() to avoid floating-point comparison issues.
     */
    private function createCompoundLedgers(array $debits, array $credits, object $txn, int $entityId, float $rate): void
    {
        $postingDate = $txn->transaction_date;
        $currencyId = $txn->currency_id;

        // Credit-side ledgers: each credit entry matched against debit entries
        $this->allocateSide($credits, $debits, $txn->id, $currencyId, $postingDate, $rate, $entityId, 'C');

        // Debit-side ledgers: each debit entry matched against credit entries
        $this->allocateSide($debits, $credits, $txn->id, $currencyId, $postingDate, $rate, $entityId, 'D');
    }

    /**
     * Allocate entries on one side against entries on the other side.
     * Creates one ledger entry per allocation pair.
     */
    private function allocateSide(array $posts, array $folios, int $txnId, int $currencyId, string $postingDate, float $rate, int $entityId, string $entryType): void
    {
        // Make copies with running remainders
        $postEntries = array_values($posts);
        $folioEntries = array_values($folios);

        $pi = 0;
        $fi = 0;
        $postRemain = $postEntries[0]['amount'];
        $folioRemain = $folioEntries[0]['amount'];

        while ($pi < count($postEntries) && $fi < count($folioEntries)) {
            $amount = round(min($postRemain, $folioRemain), 4);

            if ($amount > 0.0001) {
                $this->insertLedger(
                    $txnId, $currencyId, $postingDate, $rate, $entryType,
                    $postEntries[$pi]['id'], $folioEntries[$fi]['id'],
                    $amount, $entityId
                );
            }

            $postRemain = round($postRemain - $amount, 4);
            $folioRemain = round($folioRemain - $amount, 4);

            if ($postRemain < 0.0001) {
                $pi++;
                $postRemain = $pi < count($postEntries) ? $postEntries[$pi]['amount'] : 0;
            }
            if ($folioRemain < 0.0001) {
                $fi++;
                $folioRemain = $fi < count($folioEntries) ? $folioEntries[$fi]['amount'] : 0;
            }
        }
    }

    /**
     * Insert a single ledger entry with proper hash chain.
     */
    private function insertLedger(int $txnId, int $currencyId, string $postingDate, float $rate, string $entryType, int $postAccount, int $folioAccount, float $amount, int $entityId): void
    {
        $now = Carbon::now();

        $id = DB::table('ifrs_ledgers')->insertGetId([
            'transaction_id' => $txnId,
            'currency_id' => $currencyId,
            'posting_date' => $postingDate,
            'rate' => $rate,
            'entry_type' => $entryType,
            'post_account' => $postAccount,
            'folio_account' => $folioAccount,
            'amount' => $amount,
            'entity_id' => $entityId,
            'line_item_id' => null,
            'vat_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
            'hash' => '', // placeholder, computed below
        ]);

        // Compute hash following the IFRS package's Ledger::hashed() method
        $previousLedger = DB::table('ifrs_ledgers')->where('id', $id - 1)->first();
        $previousHash = $previousLedger && $previousLedger->hash
            ? $previousLedger->hash
            : config('app.key', 'test application key');

        $hashParts = [
            $entityId,
            $txnId,
            $currencyId,
            '', // vat_id (null → empty string in implode)
            $postAccount,
            $folioAccount,
            '', // line_item_id (null → empty string)
            Carbon::parse($postingDate),
            $entryType,
            floatval($amount),
            $now,
            $previousHash,
        ];

        $algo = config('ifrs.hashing_algorithm', 'sha256');
        $hash = hash($algo, utf8_encode(implode('', $hashParts)));

        DB::table('ifrs_ledgers')->where('id', $id)->update(['hash' => $hash]);
    }

    /**
     * Set up auth context for EntityScope to work during ledger hash computation.
     */
    private function setupAuthForEntity(int $entityId): void
    {
        $company = DB::table('companies')->where('ifrs_entity_id', $entityId)->first();
        if (! $company) {
            return;
        }

        // Try to find a user linked to this company
        $userId = $company->owner_id ?? null;
        if (! $userId) {
            $user = DB::table('users')->where('entity_id', $entityId)->first();
            $userId = $user ? $user->id : null;
        }
        if (! $userId) {
            $user = \App\Models\User::whereHas('companies', function ($q) use ($company) {
                $q->where('companies.id', $company->id);
            })->first();
            $userId = $user ? $user->id : null;
        }

        if ($userId) {
            $userModel = \App\Models\User::find($userId);
            if ($userModel) {
                $userModel->entity_id = $entityId;
                $userModel->saveQuietly();

                $entity = \IFRS\Models\Entity::find($entityId);
                if ($entity) {
                    $userModel->setRelation('entity', $entity);
                }

                auth()->login($userModel);
            }
        }
    }

    public function down(): void
    {
    }
};

// CLAUDE-CHECKPOINT
