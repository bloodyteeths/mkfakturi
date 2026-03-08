<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fix remaining non-compound JN transactions that were skipped by the
 * previous migration (unbalanced line items where main account fills the gap).
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
            Log::info('JN compound fix v2: no remaining non-compound JN transactions');
            return;
        }

        foreach ($transactions as $txn) {
            $entityId = $txn->entity_id;

            $exchangeRate = DB::table('ifrs_exchange_rates')
                ->where('entity_id', $entityId)
                ->first();

            if (! $exchangeRate) {
                Log::warning("JN compound fix v2: no exchange rate for entity {$entityId}, skipping txn {$txn->id}");
                continue;
            }

            $rate = floatval($exchangeRate->rate);

            try {
                $this->convertTransaction($txn, $entityId, $rate);
                Log::info("JN compound fix v2: converted txn {$txn->id} for entity {$entityId}");
            } catch (\Exception $e) {
                Log::error("JN compound fix v2: failed txn {$txn->id}: {$e->getMessage()}");
            }
        }
    }

    private function convertTransaction(object $txn, int $entityId, float $rate): void
    {
        $lineItems = DB::table('ifrs_line_items')
            ->where('transaction_id', $txn->id)
            ->orderBy('id')
            ->get();

        if ($lineItems->isEmpty()) {
            return;
        }

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
            return;
        }

        $totalDebits = round(array_sum(array_column($debits, 'amount')), 4);
        $totalCredits = round(array_sum(array_column($credits, 'amount')), 4);
        $gap = round(abs($totalDebits - $totalCredits), 4);
        $balanced = $gap < 0.01;

        DB::beginTransaction();
        try {
            // Delete existing ledger entries
            DB::table('ifrs_ledgers')->where('transaction_id', $txn->id)->delete();

            if ($balanced) {
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
                // Main account fills the gap on the short side
                $mainCredited = ($totalDebits > $totalCredits) ? true : false;

                DB::table('ifrs_transactions')->where('id', $txn->id)->update([
                    'account_id' => $txn->account_id,
                    'compound' => true,
                    'main_account_amount' => $gap,
                    'credited' => $mainCredited,
                    'updated_at' => now(),
                ]);

                $mainCompound = ['id' => $txn->account_id, 'amount' => $gap];
                if ($mainCredited) {
                    $allDebits = $debits;
                    $allCredits = array_merge([$mainCompound], $credits);
                } else {
                    $allDebits = array_merge([$mainCompound], $debits);
                    $allCredits = $credits;
                }
            }

            $this->createCompoundLedgers($allDebits, $allCredits, $txn, $entityId, $rate);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createCompoundLedgers(array $debits, array $credits, object $txn, int $entityId, float $rate): void
    {
        $postingDate = $txn->transaction_date;
        $currencyId = $txn->currency_id;

        $this->allocateSide($credits, $debits, $txn->id, $currencyId, $postingDate, $rate, $entityId, 'C');
        $this->allocateSide($debits, $credits, $txn->id, $currencyId, $postingDate, $rate, $entityId, 'D');
    }

    private function allocateSide(array $posts, array $folios, int $txnId, int $currencyId, string $postingDate, float $rate, int $entityId, string $entryType): void
    {
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
            'hash' => '',
        ]);

        $previousLedger = DB::table('ifrs_ledgers')->where('id', $id - 1)->first();
        $previousHash = $previousLedger && $previousLedger->hash
            ? $previousLedger->hash
            : config('app.key', 'test application key');

        $hashParts = [
            $entityId,
            $txnId,
            $currencyId,
            '',
            $postAccount,
            $folioAccount,
            '',
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

    public function down(): void
    {
    }
};

// CLAUDE-CHECKPOINT
