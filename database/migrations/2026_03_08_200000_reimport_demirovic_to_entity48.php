<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Re-import ДЕМИРОВИЌ КОМПАНИ ДООЕЛ journal entries into entity 48
 * (the real company linked to partner 24 / Maja).
 *
 * Original import went to entity 25 (test company "qwe") without counterparty names.
 * This migration creates the same 14 nalozi under entity 48 WITH counterparty_name populated.
 */
return new class extends Migration
{
    private const COMPANY_ID = 48;

    public function up(): void
    {
        // ================================================================
        // 1. FIND COMPANY & ENTITY
        // ================================================================
        $company = DB::table('companies')->where('id', self::COMPANY_ID)->first();
        if (! $company) {
            Log::warning('Demirovic migration: company 48 not found, skipping');
            return;
        }

        // Entity 48 already exists (auto-created as "(System)") — reuse it
        $entity = DB::table('ifrs_entities')->where('id', self::COMPANY_ID)->first();
        if (! $entity) {
            Log::warning('Demirovic migration: entity 48 not found, skipping');
            return;
        }

        // Fix: company 48 was incorrectly linked to entity 25 (the test company).
        // Re-point it to entity 48 (the correct one matching its ID).
        if ($company->ifrs_entity_id != $entity->id) {
            DB::table('companies')->where('id', self::COMPANY_ID)->update([
                'ifrs_entity_id' => $entity->id,
            ]);
        }

        // Idempotency: skip if entity 48 already has transactions
        $existingTxnCount = DB::table('ifrs_transactions')->where('entity_id', $entity->id)->count();
        if ($existingTxnCount > 0) {
            Log::info("Demirovic migration: entity {$entity->id} already has {$existingTxnCount} transactions, skipping");
            return;
        }

        // ================================================================
        // 2. SET UP ENTITY (currency, reporting period, exchange rate)
        // ================================================================
        $currency = DB::table('ifrs_currencies')->where('currency_code', 'MKD')->first();
        if (! $currency) {
            Log::warning('Demirovic migration: MKD currency not found, skipping');
            return;
        }
        $currencyId = $currency->id;

        // Update entity with currency if not set
        if (! $entity->currency_id) {
            DB::table('ifrs_entities')->where('id', $entity->id)->update([
                'currency_id' => $currencyId,
                'updated_at' => now(),
            ]);
        }

        // Remove "(System)" suffix from entity name
        if (str_contains($entity->name, '(System)')) {
            DB::table('ifrs_entities')->where('id', $entity->id)->update([
                'name' => trim(str_replace('(System)', '', $entity->name)),
                'updated_at' => now(),
            ]);
        }

        // Reporting period for 2026
        $hasRP = DB::table('ifrs_reporting_periods')
            ->where('entity_id', $entity->id)
            ->where('calendar_year', 2026)
            ->exists();
        if (! $hasRP) {
            DB::table('ifrs_reporting_periods')->insert([
                'entity_id' => $entity->id,
                'calendar_year' => 2026,
                'period_count' => 1,
                'status' => 'OPEN',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Exchange rate
        $hasRate = DB::table('ifrs_exchange_rates')
            ->where('entity_id', $entity->id)
            ->where('currency_id', $currencyId)
            ->exists();
        if (! $hasRate) {
            DB::table('ifrs_exchange_rates')->insert([
                'entity_id' => $entity->id,
                'currency_id' => $currencyId,
                'rate' => 1.0,
                'valid_from' => '2026-01-01',
                'valid_to' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Set user context for IFRS EntityScope
        $user = DB::table('users')->where('email', 'smetkovoditel0881@gmail.com')->first();
        if ($user) {
            auth()->loginUsingId($user->id);
        }

        // ================================================================
        // 3. ACCOUNT MAP (Macedonian chart of accounts)
        // ================================================================
        $accountMap = [
            '01203'  => ['name' => 'Деловни згради', 'type' => 'NON_CURRENT_ASSET'],
            '01215'  => ['name' => 'Машини и опрема', 'type' => 'NON_CURRENT_ASSET'],
            '013031' => ['name' => 'Алат и инвентар', 'type' => 'NON_CURRENT_ASSET'],
            '013151' => ['name' => 'Транспортни средства', 'type' => 'NON_CURRENT_ASSET'],
            '0192'   => ['name' => 'Исправка на вредност на ОС', 'type' => 'CONTRA_ASSET'],
            '1001'   => ['name' => 'Каса во денари', 'type' => 'BANK'],
            '1005'   => ['name' => 'Жиро сметка во денари', 'type' => 'BANK'],
            '1009'   => ['name' => 'Побарувања од вработени', 'type' => 'RECEIVABLE'],
            '1021'   => ['name' => 'Побарувања од основачи', 'type' => 'RECEIVABLE'],
            '1200'   => ['name' => 'Побарувања од купувачи', 'type' => 'RECEIVABLE'],
            '1300'   => ['name' => 'ДДВ - претходен данок', 'type' => 'RECEIVABLE'],
            '130618' => ['name' => 'ДДВ претходен данок 18%', 'type' => 'RECEIVABLE'],
            '1309'   => ['name' => 'Аванси за набавки', 'type' => 'RECEIVABLE'],
            '1330'   => ['name' => 'Активни временски разграничувања', 'type' => 'CURRENT_ASSET'],
            '1620'   => ['name' => 'Дадени краткорочни заеми', 'type' => 'RECEIVABLE'],
            '1621'   => ['name' => 'Побарувања по заеми', 'type' => 'RECEIVABLE'],
            '2200'   => ['name' => 'Обврски кон добавувачи', 'type' => 'PAYABLE'],
            '2300'   => ['name' => 'Краткорочни заеми', 'type' => 'CURRENT_LIABILITY'],
            '23018'  => ['name' => 'Задржан данок на доход', 'type' => 'CURRENT_LIABILITY'],
            '2340'   => ['name' => 'ПИО - пензиско осигурување', 'type' => 'CURRENT_LIABILITY'],
            '2341'   => ['name' => 'Здравствено осигурување', 'type' => 'CURRENT_LIABILITY'],
            '2342'   => ['name' => 'Данок на доход од плати', 'type' => 'CURRENT_LIABILITY'],
            '2343'   => ['name' => 'Дополнително здравствено', 'type' => 'CURRENT_LIABILITY'],
            '2344'   => ['name' => 'Придонес за вработување', 'type' => 'CURRENT_LIABILITY'],
            '2401'   => ['name' => 'Обврски за нето плати', 'type' => 'CURRENT_LIABILITY'],
            '2420'   => ['name' => 'Обврски за придонеси', 'type' => 'CURRENT_LIABILITY'],
            '286019' => ['name' => 'Одложени приходи по договори', 'type' => 'CURRENT_LIABILITY'],
            '3100'   => ['name' => 'Приходи од продажба', 'type' => 'OPERATING_REVENUE'],
            '3510'   => ['name' => 'Финансиски приходи', 'type' => 'OPERATING_REVENUE'],
            '4111'   => ['name' => 'Телекомуникации', 'type' => 'OPERATING_EXPENSE'],
            '4200'   => ['name' => 'Бруто плати на вработени', 'type' => 'OPERATING_EXPENSE'],
            '4460'   => ['name' => 'Провизии и такси на банки', 'type' => 'OPERATING_EXPENSE'],
            '44902'  => ['name' => 'Наем / Закупнина', 'type' => 'OPERATING_EXPENSE'],
            '6600'   => ['name' => 'Вонредни расходи', 'type' => 'DIRECT_EXPENSE'],
            '740001' => ['name' => 'Приходи од вршење услуги', 'type' => 'OPERATING_REVENUE'],
            '74010'  => ['name' => 'Приходи од градежништво', 'type' => 'OPERATING_REVENUE'],
            '9000'   => ['name' => 'Основна главнина', 'type' => 'EQUITY'],
            '9400'   => ['name' => 'Резерви', 'type' => 'EQUITY'],
            '9500'   => ['name' => 'Акумулирана добивка', 'type' => 'EQUITY'],
            '9510'   => ['name' => 'Добивка во тековна година', 'type' => 'EQUITY'],
        ];

        // ================================================================
        // 4. FIRMS (counterparty map)
        // ================================================================
        $firms = [
            5 => 'КОСМОС',
            16 => 'ПОПЕ И СИНОВИ ДООЕЛ',
            20 => 'НЕЏАТ ДЕМИРОВИЌ',
            22 => 'БЛЕРО-ФИКС ДООЕЛ',
            33 => 'МАЈА ВЕЛИЧКОВА',
            38 => 'М-КОНСАЛТИНГ Т.П',
            44 => 'А1 МАКЕДОНИЈА ДООЕЛ',
            49 => 'ВИЗИОН ГРУП ДООЕЛ',
            51 => 'А2-КОМПАНИ ДООЕЛ',
            64 => 'ШПАРКАСЕ БАНКА',
            68 => 'МУХАМЕД ЛИЧИНА',
            69 => 'ХАЛИТ ЌЕРИМИ',
            71 => 'ФАХРУДИН СУЉОВИЌ',
            72 => 'ИСАК АДЕМОВСКИ',
            73 => 'КАФКАЗ ГРАДБА ДООЕЛ',
            74 => 'СЕРВИС ПЕТРОЛ ДОО СКОПЈЕ',
            75 => 'МАК ГУТЕ ПРЕВОЗ ДООЕЛ',
            76 => 'ЏИДИ КОМЕРЦ',
            79 => 'ЛИЧИНА ЕДИН',
            83 => 'АДА-АН ИНЖЕНЕРИНГ 2020',
            93 => 'АДЕМЕ ШАБОТИЌ',
            97 => 'АТАНАСОВСКИ ГРУП ДООЕЛ',
            99 => 'ДАЦКО ИНЖЕНЕРИНГ ЛТД ДООЕЛ',
            101 => 'ЛИЧИНА МУХАМЕД',
            103 => 'АДЕМОВСКИ ИСАК',
            104 => 'ЕДИН ЛИЧИНА',
            123 => 'НОАР ГРУП ДООЕЛ',
            126 => 'САШКО ВЕЛКОВСКИ',
            127 => 'НЕЏАТ ШАБОТИЌ',
            131 => 'СЛОГА ИМПОРТ ДООЕЛ',
            132 => 'ТИНК ИМПАКТ ДООЕЛ',
        ];

        // ================================================================
        // 5. NALOG DATA (14 journal entries)
        // ================================================================
        $nalogTypes = [
            '00' => 'Почетно салдо',
            '10' => 'Тековна сметка - Банка',
            '11' => 'Каса / Благајна',
            '20' => 'Влезни фактури',
            '21' => 'Излезни фактури - Примена',
            '30' => 'Излезни фактури - Продажба',
            '40' => 'Плати',
        ];

        // [nalog_id, data_kn, [[konto, data, firma, opis, dolguva, pobaruva, vvrska], ...]]
        $nalozi = [
            ['00-0001', '01-01-2026', [
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
            ]],
            ['10-0001', '31-01-2026', [
                ['1005', '13-01-2026', 0, 'Б.ИЗВОД 1', 0, 3225, ''],
                ['286019', '13-01-2026', 0, 'Б.ИЗВОД 1', 3185, 0, ''],
                ['4460', '13-01-2026', 0, 'Б.ИЗВОД 1', 40, 0, ''],
                ['1005', '22-01-2026', 0, 'Б.ИЗВОД 2', 0, 10080, ''],
                ['1620', '22-01-2026', 20, 'Б.ИЗВОД 2', 10000, 0, 'B.IZVOD 2'],
                ['4460', '22-01-2026', 0, 'Б.ИЗВОД 2', 80, 0, ''],
                ['1005', '30-01-2026', 0, 'Б.ИЗВОД 3', 223504, 93690, ''],
                ['2200', '30-01-2026', 51, 'Б.ИЗВОД 3', 80000, 0, ''],
                ['2200', '30-01-2026', 38, 'Б.ИЗВОД 3', 13600, 0, ''],
                ['4460', '30-01-2026', 0, 'Б.ИЗВОД 3', 90, 0, ''],
                ['1200', '30-01-2026', 123, 'Б.ИЗВОД 3', 0, 4784, '39'],
                ['1200', '30-01-2026', 123, 'Б.ИЗВОД 3', 0, 218720, '00046-25'],
                ['1005', '31-01-2026', 0, 'Б.ИЗВОД 4', 0, 760, ''],
                ['4460', '31-01-2026', 0, 'Б.ИЗВОД 4', 500, 0, ''],
                ['4460', '31-01-2026', 0, 'Б.ИЗВОД 4', 200, 0, ''],
                ['4460', '31-01-2026', 0, 'Б.ИЗВОД 4', 60, 0, ''],
            ]],
            ['10-0002', '28-02-2026', [
                ['1005', '02-02-2026', 0, '5', 80000, 0, ''],
                ['1200', '02-02-2026', 51, '5', 0, 80000, ''],
                ['1005', '03-02-2026', 0, '6', 64000, 5805, ''],
                ['286019', '03-02-2026', 0, '6', 3185, 0, ''],
                ['1009', '03-02-2026', 0, '6', 2500, 0, ''],
                ['4460', '03-02-2026', 0, '6', 120, 0, ''],
                ['1200', '03-02-2026', 51, '6', 0, 64000, ''],
                ['1005', '10-02-2026', 0, '7', 0, 135080, ''],
                ['2200', '10-02-2026', 131, '7', 135000, 0, '200478'],
                ['4460', '10-02-2026', 0, '7', 80, 0, ''],
                ['1005', '18-02-2026', 0, '8', 0, 245080, ''],
                ['2200', '18-02-2026', 76, '8', 245000, 0, ''],
                ['4460', '18-02-2026', 0, '8', 80, 0, ''],
                ['1005', '19-02-2026', 0, '9', 130000, 0, ''],
                ['1200', '19-02-2026', 97, '9', 0, 130000, ''],
                ['1005', '26-02-2026', 0, '10', 0, 116680, ''],
                ['1620', '26-02-2026', 51, '10', 116600, 0, '10'],
                ['4460', '26-02-2026', 0, '10', 80, 0, ''],
                ['1005', '28-02-2026', 0, '11', 0, 620, ''],
                ['4460', '28-02-2026', 0, '11', 620, 0, ''],
            ]],
            ['11-0001', '31-01-2026', [
                ['1001', '02-01-2026', 0, '1', 0, 1250, ''],
                ['4460', '02-01-2026', 0, '1', 1250, 0, ''],
                ['1001', '09-01-2026', 0, '2', 0, 150, ''],
                ['4460', '09-01-2026', 0, '2', 150, 0, ''],
                ['1001', '12-01-2026', 0, '3', 0, 2122, ''],
                ['2200', '12-01-2026', 44, '3', 2122, 0, ''],
                ['1001', '20-01-2026', 0, '4', 0, 10, ''],
                ['4460', '20-01-2026', 0, '4', 10, 0, ''],
                ['1001', '26-01-2026', 0, '5', 0, 1000, ''],
                ['1620', '26-01-2026', 16, '5', 1000, 0, '5'],
                ['1001', '31-01-2026', 0, '6', 0, 10, ''],
                ['4460', '31-01-2026', 0, '6', 10, 0, ''],
            ]],
            ['11-0002', '28-02-2026', [
                ['1001', '02-02-2026', 0, '7', 300000, 450, ''],
                ['4460', '02-02-2026', 0, '7', 450, 0, ''],
                ['1200', '02-02-2026', 99, '7', 0, 300000, '00001-26'],
                ['1001', '03-02-2026', 0, '8', 2500, 301015, ''],
                ['2401', '03-02-2026', 0, '8', 179400, 0, ''],
                ['2340', '03-02-2026', 0, '8', 12924, 0, ''],
                ['2341', '03-02-2026', 0, '8', 56591, 0, ''],
                ['2342', '03-02-2026', 0, '8', 22579, 0, ''],
                ['2343', '03-02-2026', 0, '8', 1506, 0, ''],
                ['2344', '03-02-2026', 0, '8', 3615, 0, ''],
                ['1009', '03-02-2026', 0, '8', 0, 2500, ''],
                ['2401', '03-02-2026', 0, '8', 24400, 0, ''],
                ['1001', '10-02-2026', 0, '9', 0, 920, ''],
                ['4460', '10-02-2026', 0, '9', 920, 0, ''],
                ['1001', '12-02-2026', 0, '10', 240176, 0, ''],
                ['1200', '12-02-2026', 99, '10', 0, 240176, '00001-26'],
                ['1001', '18-02-2026', 0, '11', 331149, 0, ''],
                ['1200', '18-02-2026', 99, '11', 0, 331149, ''],
                ['1001', '20-02-2026', 0, '12', 0, 20, ''],
                ['4460', '20-02-2026', 0, '12', 20, 0, ''],
                ['1001', '27-02-2026', 0, '13', 245000, 0, ''],
                ['1200', '27-02-2026', 132, '13', 0, 245000, ''],
                ['1001', '28-02-2026', 0, '14', 0, 10, ''],
                ['4460', '28-02-2026', 0, '14', 10, 0, ''],
            ]],
            ['11-0003', '31-03-2026', [
                ['1001', '01-03-2026', 0, '15', 0, 1250, ''],
                ['4460', '01-03-2026', 0, '15', 1250, 0, ''],
                ['1001', '02-03-2026', 0, '16', 0, 311015, ''],
                ['2401', '02-03-2026', 0, '16', 203800, 0, ''],
                ['2340', '02-03-2026', 0, '16', 12924, 0, ''],
                ['2341', '02-03-2026', 0, '16', 56591, 0, ''],
                ['2342', '02-03-2026', 0, '16', 22579, 0, ''],
                ['2343', '02-03-2026', 0, '16', 1506, 0, ''],
                ['2344', '02-03-2026', 0, '16', 3615, 0, ''],
                ['2200', '02-03-2026', 38, '16', 10000, 0, ''],
                ['1001', '03-03-2026', 0, '17', 116000, 0, ''],
                ['1620', '03-03-2026', 51, '17', 0, 116000, ''],
                ['1001', '04-03-2026', 0, '18', 0, 560000, ''],
                ['2200', '04-03-2026', 76, '18', 560000, 0, ''],
            ]],
            ['20-0001', '28-02-2026', [
                ['2200', '18-02-2026', 76, '000209/26', 0, 245000, '000209/26'],
                ['1300', '18-02-2026', 0, '000209/26', 37373, 0, ''],
                ['3100', '18-02-2026', 0, '000209/26', 207627, 0, ''],
            ]],
            ['21-0001', '31-01-2026', [
                ['2200', '24-01-2026', 38, '00003-26', 0, 10000, '00003-26'],
                ['1300', '24-01-2026', 0, '00003-26', 1525, 0, ''],
                ['44902', '24-01-2026', 0, '00003-26', 8475, 0, ''],
                ['2200', '02-01-2026', 44, '108479156444', 0, 2122, '108479156444'],
                ['1300', '02-01-2026', 0, '108479156444', 324, 0, ''],
                ['4111', '02-01-2026', 0, '108479156444', 1798, 0, ''],
                ['2200', '31-01-2026', 44, '108543091084', 0, 2191, '108543091084'],
                ['1300', '31-01-2026', 0, '108543091084', 334, 0, ''],
                ['4111', '31-01-2026', 0, '108543091084', 1857, 0, ''],
            ]],
            ['21-0002', '28-02-2026', [
                ['2200', '10-02-2026', 131, '200478', 0, 135000, '200478'],
                ['1300', '10-02-2026', 0, '200478', 20593, 0, ''],
                ['013031', '10-02-2026', 0, '200478', 114407, 0, ''],
                ['2200', '03-02-2026', 38, '00054-26', 0, 13600, '00054-26'],
                ['1300', '03-02-2026', 0, '00054-26', 1525, 0, ''],
                ['44902', '03-02-2026', 0, '00054-26', 12075, 0, ''],
                ['2200', '24-02-2026', 38, '00134-26', 0, 10000, '00134-26'],
                ['1300', '24-02-2026', 0, '00134-26', 1525, 0, ''],
                ['44902', '24-02-2026', 0, '00134-26', 8475, 0, ''],
            ]],
            ['30-0001', '31-01-2026', [
                ['1200', '15-01-2026', 99, 'Фактура 00001-26', 540176, 0, '00001-26'],
                ['74010', '15-01-2026', 0, 'Ф-ра 00001-26 М1 Р.1', 0, 540176, ''],
            ]],
            ['30-0002', '18-02-2026', [
                ['1200', '18-02-2026', 99, 'Фактура 00002-26', 331149, 0, '00002-26'],
                ['74010', '18-02-2026', 0, 'Ф-ра 00002-26 М1 Р.2', 0, 331149, ''],
                ['1200', '18-02-2026', 97, 'Фактура 00003-26', 130000, 0, '00003-26'],
                ['74010', '18-02-2026', 0, 'Ф-ра 00003-26 М1 Р.3', 0, 130000, ''],
            ]],
            ['30-0003', '31-03-2026', [
                ['1200', '02-03-2026', 99, 'Фактура 00004-26', 331149, 0, '00004-26'],
                ['740001', '02-03-2026', 0, 'Ф-ра 00004-26 М1 Р.4', 0, 331149, ''],
            ]],
            ['40-0001', '21-01-2026', [
                ['4200', '21-01-2026', 0, 'Декларација 01-2026 101 110', 301015, 0, ''],
                ['2401', '21-01-2026', 0, 'Декларација 01-2026 101 110', 0, 203800, ''],
                ['2340', '21-01-2026', 0, 'Декларација 01-2026 101 110', 0, 12924, ''],
                ['2341', '21-01-2026', 0, 'Декларација 01-2026 101 110', 0, 56591, ''],
                ['2342', '21-01-2026', 0, 'Декларација 01-2026 101 110', 0, 22579, ''],
                ['2343', '21-01-2026', 0, 'Декларација 01-2026 101 110', 0, 1506, ''],
                ['2344', '21-01-2026', 0, 'Декларација 01-2026 101 110', 0, 3615, ''],
            ]],
            ['40-0002', '12-02-2026', [
                ['4200', '12-02-2026', 0, 'Декларација 02-2026 101 110', 301015, 0, ''],
                ['2401', '12-02-2026', 0, 'Декларација 02-2026 101 110', 0, 203800, ''],
                ['2340', '12-02-2026', 0, 'Декларација 02-2026 101 110', 0, 12924, ''],
                ['2341', '12-02-2026', 0, 'Декларација 02-2026 101 110', 0, 56591, ''],
                ['2342', '12-02-2026', 0, 'Декларација 02-2026 101 110', 0, 22579, ''],
                ['2343', '12-02-2026', 0, 'Декларација 02-2026 101 110', 0, 1506, ''],
                ['2344', '12-02-2026', 0, 'Декларација 02-2026 101 110', 0, 3615, ''],
            ]],
        ];

        // ================================================================
        // 6. CREATE IFRS ACCOUNTS
        // ================================================================
        $accountCache = [];

        // Map string types to IFRS Account constants
        $typeConstants = [
            'NON_CURRENT_ASSET' => \IFRS\Models\Account::NON_CURRENT_ASSET,
            'CONTRA_ASSET' => \IFRS\Models\Account::CONTRA_ASSET,
            'BANK' => \IFRS\Models\Account::BANK,
            'RECEIVABLE' => \IFRS\Models\Account::RECEIVABLE,
            'CURRENT_ASSET' => \IFRS\Models\Account::CURRENT_ASSET,
            'PAYABLE' => \IFRS\Models\Account::PAYABLE,
            'CURRENT_LIABILITY' => \IFRS\Models\Account::CURRENT_LIABILITY,
            'OPERATING_REVENUE' => \IFRS\Models\Account::OPERATING_REVENUE,
            'OPERATING_EXPENSE' => \IFRS\Models\Account::OPERATING_EXPENSE,
            'DIRECT_EXPENSE' => \IFRS\Models\Account::DIRECT_EXPENSE,
            'EQUITY' => \IFRS\Models\Account::EQUITY,
        ];

        // App-level account type mapping
        $appTypeMap = [
            '0' => 'asset', '1' => 'asset', '2' => 'liability',
            '3' => 'revenue', '4' => 'expense', '5' => 'expense',
            '6' => 'expense', '7' => 'revenue', '8' => 'asset', '9' => 'equity',
        ];

        foreach ($accountMap as $code => $info) {
            // Create app-level account if missing
            $appExists = DB::table('accounts')
                ->where('company_id', self::COMPANY_ID)
                ->where('code', $code)
                ->exists();
            if (! $appExists) {
                $appType = $appTypeMap[substr($code, 0, 1)] ?? 'expense';
                DB::table('accounts')->insert([
                    'company_id' => self::COMPANY_ID,
                    'code' => $code,
                    'name' => $info['name'],
                    'type' => $appType,
                    'is_active' => true,
                    'system_defined' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Create IFRS account if missing
            $ifrsAccount = DB::table('ifrs_accounts')
                ->where('entity_id', $entity->id)
                ->where('code', $code)
                ->first();

            if (! $ifrsAccount) {
                $accountType = $typeConstants[$info['type']] ?? \IFRS\Models\Account::OPERATING_EXPENSE;

                // CONTRA_ASSET may not exist in all IFRS package versions — fallback
                if ($info['type'] === 'CONTRA_ASSET' && ! defined('IFRS\Models\Account::CONTRA_ASSET')) {
                    $accountType = \IFRS\Models\Account::NON_CURRENT_ASSET;
                }

                $ifrsAccountId = DB::table('ifrs_accounts')->insertGetId([
                    'entity_id' => $entity->id,
                    'code' => $code,
                    'name' => $info['name'],
                    'account_type' => $accountType,
                    'currency_id' => $currencyId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $accountCache[$code] = $ifrsAccountId;
            } else {
                $accountCache[$code] = $ifrsAccount->id;
            }
        }

        // ================================================================
        // 7. CREATE JOURNAL ENTRIES
        // ================================================================
        $created = 0;

        foreach ($nalozi as [$nalogId, $dataKn, $entries]) {
            $prefix = explode('-', $nalogId)[0];
            $nalogType = $nalogTypes[$prefix] ?? 'Непознат тип';

            // Validate balance
            $totalD = 0;
            $totalC = 0;
            foreach ($entries as $entry) {
                $totalD += $entry[4];
                $totalC += $entry[5];
            }
            if ($totalD !== $totalC) {
                Log::warning("Demirovic migration: nalog {$nalogId} unbalanced D={$totalD} C={$totalC}, skipping");
                continue;
            }

            // Parse booking date DD-MM-YYYY
            $dateParts = explode('-', $dataKn);
            $bookingDate = sprintf('%04d-%02d-%02d', (int) $dateParts[2], (int) $dateParts[1], (int) $dateParts[0]);

            $narration = "Налог {$nalogId} ({$nalogType})";
            $firstKonto = $entries[0][0];
            $primaryAccountId = $accountCache[$firstKonto] ?? null;

            if (! $primaryAccountId) {
                Log::warning("Demirovic migration: account {$firstKonto} not found for nalog {$nalogId}");
                continue;
            }

            DB::beginTransaction();
            try {
                // Create transaction
                $txnId = DB::table('ifrs_transactions')->insertGetId([
                    'account_id' => $primaryAccountId,
                    'transaction_date' => $bookingDate,
                    'narration' => $narration,
                    'reference' => $nalogId,
                    'transaction_type' => 'JN',
                    'currency_id' => $currencyId,
                    'entity_id' => $entity->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Create line items WITH counterparty_name
                foreach ($entries as $entry) {
                    [$konto, $data, $firma, $opis, $dolguva, $pobaruva, $vvrska] = $entry;

                    $accountId = $accountCache[$konto] ?? null;
                    if (! $accountId) {
                        continue;
                    }

                    $counterpartyName = ($firma > 0 && isset($firms[$firma])) ? $firms[$firma] : null;
                    $description = trim("{$opis} {$vvrska}");

                    if ($dolguva > 0) {
                        DB::table('ifrs_line_items')->insert([
                            'transaction_id' => $txnId,
                            'account_id' => $accountId,
                            'amount' => $dolguva,
                            'quantity' => 1,
                            'credited' => false,
                            'narration' => mb_substr($description, 0, 255),
                            'counterparty_name' => $counterpartyName,
                            'entity_id' => $entity->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    if ($pobaruva > 0) {
                        DB::table('ifrs_line_items')->insert([
                            'transaction_id' => $txnId,
                            'account_id' => $accountId,
                            'amount' => $pobaruva,
                            'quantity' => 1,
                            'credited' => true,
                            'narration' => mb_substr($description, 0, 255),
                            'counterparty_name' => $counterpartyName,
                            'entity_id' => $entity->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Post to ledger using the IFRS Transaction model
                $transaction = \IFRS\Models\Transaction::withoutGlobalScope(\IFRS\Scopes\EntityScope::class)
                    ->find($txnId);
                $transaction->load('lineItems');
                $transaction->post();

                DB::commit();
                $created++;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Demirovic migration: failed nalog {$nalogId}: {$e->getMessage()}");
            }
        }

        Log::info("Demirovic migration: imported {$created}/14 nalozi to entity {$entity->id}");
    }

    public function down(): void
    {
        // Do not auto-reverse — manual cleanup if needed
    }
};
