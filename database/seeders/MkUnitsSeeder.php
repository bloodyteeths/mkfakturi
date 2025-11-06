<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class MkUnitsSeeder extends Seeder
{
    /**
     * Seed UN/ECE Recommendation 20 units for Macedonian e-invoice compliance.
     *
     * These are the most commonly used units in commercial transactions.
     * Units are global (company_id = null) and can be used by all companies.
     *
     * Source: UN/ECE Recommendation No. 20 - Codes for units of measure
     * Used in: UBL 2.1, Peppol BIS Billing 3.0, North Macedonia e-Faktura
     *
     * @return void
     */
    public function run(): void
    {
        $units = [
            // Most common units
            ['code' => 'C62', 'name' => 'Парче (Each)'],
            ['code' => 'EA', 'name' => 'Парче (Each)'],  // Alternative to C62
            ['code' => 'PCE', 'name' => 'Парче (Piece)'],

            // Mass units
            ['code' => 'KGM', 'name' => 'Килограм (Kilogram)'],
            ['code' => 'GRM', 'name' => 'Грам (Gram)'],
            ['code' => 'TNE', 'name' => 'Тон (Tonne)'],

            // Volume units
            ['code' => 'LTR', 'name' => 'Литар (Litre)'],
            ['code' => 'MLT', 'name' => 'Милилитар (Millilitre)'],
            ['code' => 'MTQ', 'name' => 'Кубен метар (Cubic metre)'],
            ['code' => 'CMQ', 'name' => 'Кубен сантиметар (Cubic centimetre)'],

            // Length units
            ['code' => 'MTR', 'name' => 'Метар (Metre)'],
            ['code' => 'CMT', 'name' => 'Сантиметар (Centimetre)'],
            ['code' => 'MMT', 'name' => 'Милиметар (Millimetre)'],
            ['code' => 'KMT', 'name' => 'Километар (Kilometre)'],

            // Area units
            ['code' => 'MTK', 'name' => 'Квадратен метар (Square metre)'],
            ['code' => 'CMK', 'name' => 'Квадратен сантиметар (Square centimetre)'],

            // Time units
            ['code' => 'HUR', 'name' => 'Час (Hour)'],
            ['code' => 'MIN', 'name' => 'Минута (Minute)'],
            ['code' => 'SEC', 'name' => 'Секунда (Second)'],
            ['code' => 'DAY', 'name' => 'Ден (Day)'],
            ['code' => 'MON', 'name' => 'Месец (Month)'],
            ['code' => 'ANN', 'name' => 'Година (Year)'],

            // Packaging units
            ['code' => 'BX', 'name' => 'Кутија (Box)'],
            ['code' => 'PK', 'name' => 'Пакет (Package)'],
            ['code' => 'PAL', 'name' => 'Палета (Pallet)'],

            // Set/Group units
            ['code' => 'SET', 'name' => 'Сет (Set)'],
            ['code' => 'DZN', 'name' => 'Дузина (Dozen)'],
            ['code' => 'PR', 'name' => 'Пар (Pair)'],

            // Service units
            ['code' => 'E48', 'name' => 'Услуга (Service unit)'],
            ['code' => 'ACT', 'name' => 'Активност (Activity)'],

            // Power/Energy units
            ['code' => 'KWH', 'name' => 'Киловат час (Kilowatt hour)'],
            ['code' => 'WHR', 'name' => 'Ват час (Watt hour)'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(
                ['code' => $unit['code'], 'company_id' => null],
                ['name' => $unit['name']]
            );
        }

        $this->command->info('✅ Seeded ' . count($units) . ' UN/ECE Recommendation 20 units');
    }
}
