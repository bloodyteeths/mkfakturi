<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Insert missing opening balance (00-0001) for ДЕМИРОВИЌ КОМПАНИ entity 48.
 *
 * The retry2 migration imported 13/14 nalozi. The opening balance failed because
 * account codes had leading zeros (01203, 01215, 013151, 0192) that don't match
 * the stored codes (1203, 1215, 13151, 192). Fix: ltrim leading zeros.
 */
return new class extends Migration
{
    private const COMPANY_ID = 48;

    public function up(): void
    {
        $entity = DB::table('ifrs_entities')->where('id', self::COMPANY_ID)->first();
        if (! $entity) {
            return;
        }

        // Idempotency: skip if 00-0001 already exists
        if (DB::table('ifrs_transactions')->where('entity_id', $entity->id)->where('reference', '00-0001')->exists()) {
            return;
        }

        $currency = DB::table('ifrs_currencies')->where('currency_code', 'MKD')->first();
        if (! $currency) {
            return;
        }

        $exchangeRate = DB::table('ifrs_exchange_rates')
            ->where('entity_id', $entity->id)
            ->where('currency_id', $currency->id)
            ->first();
        if (! $exchangeRate) {
            return;
        }

        // Set user entity_id temporarily for EntityScope
        $user = \App\Models\User::where('email', 'smetkovoditel0881@gmail.com')->first();
        if (! $user) {
            return;
        }
        $originalEntityId = $user->entity_id;
        $user->entity_id = $entity->id;
        $user->saveQuietly();
        auth()->loginUsingId($user->id);

        // Build account cache
        $accountCache = [];
        foreach (DB::table('ifrs_accounts')->where('entity_id', $entity->id)->get() as $acct) {
            $accountCache[$acct->code] = $acct->id;
        }

        $firms = [
            5 => 'КОСМОС', 16 => 'ПОПЕ И СИНОВИ ДООЕЛ', 20 => 'НЕЏАТ ДЕМИРОВИЌ',
            22 => 'БЛЕРО-ФИКС ДООЕЛ', 33 => 'МАЈА ВЕЛИЧКОВА', 38 => 'М-КОНСАЛТИНГ Т.П',
            44 => 'А1 МАКЕДОНИЈА ДООЕЛ', 49 => 'ВИЗИОН ГРУП ДООЕЛ', 51 => 'А2-КОМПАНИ ДООЕЛ',
            64 => 'ШПАРКАСЕ БАНКА', 68 => 'МУХАМЕД ЛИЧИНА', 69 => 'ХАЛИТ ЌЕРИМИ',
            71 => 'ФАХРУДИН СУЉОВИЌ', 72 => 'ИСАК АДЕМОВСКИ', 73 => 'КАФКАЗ ГРАДБА ДООЕЛ',
            74 => 'СЕРВИС ПЕТРОЛ ДОО СКОПЈЕ', 75 => 'МАК ГУТЕ ПРЕВОЗ ДООЕЛ', 76 => 'ЏИДИ КОМЕРЦ',
            79 => 'ЛИЧИНА ЕДИН', 83 => 'АДА-АН ИНЖЕНЕРИНГ 2020', 93 => 'АДЕМЕ ШАБОТИЌ',
            97 => 'АТАНАСОВСКИ ГРУП ДООЕЛ', 99 => 'ДАЦКО ИНЖЕНЕРИНГ ЛТД ДООЕЛ',
            101 => 'ЛИЧИНА МУХАМЕД', 103 => 'АДЕМОВСКИ ИСАК', 104 => 'ЕДИН ЛИЧИНА',
            123 => 'НОАР ГРУП ДООЕЛ', 126 => 'САШКО ВЕЛКОВСКИ', 127 => 'НЕЏАТ ШАБОТИЌ',
            131 => 'СЛОГА ИМПОРТ ДООЕЛ', 132 => 'ТИНК ИМПАКТ ДООЕЛ',
        ];

        $entries = [
            ['01203', '01-01-2026', 0, 'Прен. салдо 2025', 694158, 0, ''],
            ['01215', '01-01-2026', 0, 'Прен. салдо 2025', 256820, 0, ''],
            ['013151', '01-01-2026', 0, 'Прен. салдо 2025', 60952, 0, ''],
            ['0192', '01-01-2026', 0, 'Прен. салдо 2025', 0, 841981, ''],
            ['1001', '01-01-2026', 0, 'Прен. салдо 2025', 4659, 0, ''],
            ['1005', '01-01-2026', 0, 'Прен. салдо 2025', 415577, 0, ''],
            ['1009', '01-01-2026', 0, 'Прен. салдо 2025', 0, 1800, ''],
            ['1009', '01-01-2026', 33, 'Прен. салдо 2025', 1800, 0, ''],
            ['1021', '01-01-2026', 0, 'Прен. салдо 2025', 53544, 0, ''],
            ['1021', '01-01-2026', 20, 'Прен. салдо 2025', 180000, 0, ''],
            ['1200', '01-01-2026', 5, 'PREN. SALDO 2024', 1364474, 0, 'PREN. SALDO 2024'],
            ['1200', '01-01-2026', 22, 'PREN. SALDO 2024', 690, 0, 'PREN. SALDO 2024'],
            ['1200', '01-01-2026', 49, 'PREN. SALDO 2024', 116716, 0, 'PREN. SALDO 2024'],
            ['1200', '01-01-2026', 51, 'PREN. SALDO 2024', 698500, 0, 'PREN. SALDO 2024'],
            ['1200', '01-01-2026', 74, 'PREN. SALDO 2024', 10000, 0, 'PREN. SALDO 2024'],
            ['1200', '01-01-2026', 75, 'PREN. SALDO 2024', 210450, 0, 'PREN. SALDO 2024'],
            ['1200', '01-01-2026', 123, '00046-25', 218720, 0, '00046-25'],
            ['1200', '01-01-2026', 123, '39', 4784, 0, '39'],
            ['130618', '01-01-2026', 0, 'Прен. салдо 2025', 0, 21167, ''],
            ['130618', '01-01-2026', 83, 'Прен. салдо 2025', 21167, 0, ''],
            ['1309', '01-01-2026', 0, 'Прен. салдо 2025', 262679, 0, 'PREN. SALDO 2025'],
            ['1330', '01-01-2026', 0, 'Прен. салдо 2025', 61703, 0, ''],
            ['1620', '01-01-2026', 38, 'IZVOD 44', 10000, 0, 'IZVOD 44'],
            ['1620', '01-01-2026', 51, 'POZAJMICA', 77000, 0, 'POZAJMICA'],
            ['1621', '01-01-2026', 93, 'Прен. салдо 2025', 15000, 0, ''],
            ['2300', '01-01-2026', 0, 'Прен. салдо 2025', 348469, 0, ''],
            ['2300', '01-01-2026', 73, 'Прен. салдо 2025', 0, 348469, ''],
            ['23018', '01-01-2026', 0, 'Прен. салдо 2025', 21167, 0, ''],
            ['23018', '01-01-2026', 83, 'Прен. салдо 2025', 0, 21167, ''],
            ['2401', '01-01-2026', 0, 'Прен. салдо 2025', 0, 267367, ''],
            ['2401', '01-01-2026', 20, 'Прен. салдо 2025', 33000, 0, ''],
            ['2401', '01-01-2026', 68, 'Прен. салдо 2025', 20300, 0, ''],
            ['2401', '01-01-2026', 69, 'Прен. салдо 2025', 44700, 0, ''],
            ['2401', '01-01-2026', 71, 'Прен. салдо 2025', 44700, 0, ''],
            ['2401', '01-01-2026', 72, 'Прен. салдо 2025', 20300, 0, ''],
            ['2401', '01-01-2026', 79, 'Прен. салдо 2025', 6767, 0, ''],
            ['2401', '01-01-2026', 101, 'Прен. салдо 2025', 24400, 0, ''],
            ['2401', '01-01-2026', 103, 'Прен. салдо 2025', 24400, 0, ''],
            ['2401', '01-01-2026', 104, 'Прен. салдо 2025', 24400, 0, ''],
            ['2401', '01-01-2026', 127, 'Прен. салдо 2025', 24400, 0, ''],
            ['2420', '01-01-2026', 0, 'Прен. салдо 2025', 0, 145128, ''],
            ['2420', '01-01-2026', 20, 'Прен. салдо 2025', 18141, 0, ''],
            ['2420', '01-01-2026', 68, 'Прен. салдо 2025', 18141, 0, ''],
            ['2420', '01-01-2026', 69, 'Прен. салдо 2025', 18141, 0, ''],
            ['2420', '01-01-2026', 71, 'Прен. салдо 2025', 18141, 0, ''],
            ['2420', '01-01-2026', 72, 'Прен. салдо 2025', 18141, 0, ''],
            ['2420', '01-01-2026', 104, 'Прен. салдо 2025', 18141, 0, ''],
            ['2420', '01-01-2026', 126, 'Прен. салдо 2025', 18141, 0, ''],
            ['2420', '01-01-2026', 127, 'Прен. салдо 2025', 18141, 0, ''],
            ['286019', '01-01-2026', 0, 'Прен. салдо 2025', 60378, 0, ''],
            ['286019', '01-01-2026', 64, 'Прен. салдо 2025', 0, 461153, ''],
            ['3510', '01-01-2026', 0, 'Прен. салдо 2025', 157485, 0, ''],
            ['6600', '01-01-2026', 0, 'Прен. салдо 2025', 3097014, 0, ''],
            ['9000', '01-01-2026', 0, 'Прен. салдо 2025', 0, 307500, ''],
            ['9400', '01-01-2026', 0, 'Прен. салдо 2025', 0, 411264, ''],
            ['9500', '01-01-2026', 0, 'Прен. салдо 2025', 0, 5798137, ''],
            ['9510', '01-01-2026', 0, 'Прен. салдо 2025', 0, 211268, ''],
        ];

        // Resolve account code → strip leading zeros if needed
        $resolveAccount = function (string $code) use ($accountCache) {
            if (isset($accountCache[$code])) {
                return $accountCache[$code];
            }
            $stripped = ltrim($code, '0');
            return $accountCache[$stripped] ?? null;
        };

        $primaryAccountId = $resolveAccount('01203'); // 1203 = Деловни згради
        if (! $primaryAccountId) {
            $user->entity_id = $originalEntityId;
            $user->saveQuietly();
            return;
        }

        DB::beginTransaction();
        try {
            $txnId = DB::table('ifrs_transactions')->insertGetId([
                'account_id' => $primaryAccountId,
                'transaction_date' => '2026-01-02', // Shifted from Jan 1 (IFRS restriction)
                'narration' => 'Налог 00-0001 (Почетно салдо)',
                'reference' => '00-0001',
                'transaction_type' => 'JN',
                'currency_id' => $currency->id,
                'exchange_rate_id' => $exchangeRate->id,
                'entity_id' => $entity->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($entries as $entry) {
                [$konto, $data, $firma, $opis, $dolguva, $pobaruva, $vvrska] = $entry;
                $accountId = $resolveAccount($konto);
                if (! $accountId) {
                    throw new \RuntimeException("Account not found for code: {$konto}");
                }

                $counterpartyName = ($firma > 0 && isset($firms[$firma])) ? $firms[$firma] : null;
                $description = trim("{$opis} {$vvrska}");

                if ($dolguva > 0) {
                    DB::table('ifrs_line_items')->insert([
                        'transaction_id' => $txnId, 'account_id' => $accountId,
                        'amount' => $dolguva, 'quantity' => 1, 'credited' => false,
                        'narration' => mb_substr($description, 0, 255),
                        'counterparty_name' => $counterpartyName,
                        'entity_id' => $entity->id,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                if ($pobaruva > 0) {
                    DB::table('ifrs_line_items')->insert([
                        'transaction_id' => $txnId, 'account_id' => $accountId,
                        'amount' => $pobaruva, 'quantity' => 1, 'credited' => true,
                        'narration' => mb_substr($description, 0, 255),
                        'counterparty_name' => $counterpartyName,
                        'entity_id' => $entity->id,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            $transaction = \IFRS\Models\Transaction::find($txnId);
            $transaction->load('lineItems');
            $transaction->post();

            DB::commit();
            Log::info("Demirovic opening balance: posted 00-0001 with {$transaction->lineItems->count()} lines");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Demirovic opening balance failed: {$e->getMessage()}");
            throw $e; // Let migration fail visibly
        }

        // Restore user's original entity_id
        $user->entity_id = $originalEntityId;
        $user->saveQuietly();
    }

    public function down(): void
    {
    }
};
