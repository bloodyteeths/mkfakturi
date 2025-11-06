<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeds essential data needed for the application to function:
     * - Currencies, Countries (reference data)
     * - Initial admin users
     * - Macedonia-specific VAT rates
     * - Partner records for admin users
     * - Sample partner/bureau data
     *
     * NOTE: DemoSeeder is NOT called here to prevent demo invoices/expenses
     * from polluting production data. To seed demo data for testing, run:
     * php artisan db:seed --class=DemoSeeder (only works in local/staging)
     */
    public function run(): void
    {
        $this->call(CurrenciesTableSeeder::class);
        $this->call(CountriesTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(MkVatSeeder::class);
        $this->call(PartnerSeeder::class); // Create Partner records for admin users
        $this->call(PartnerTablesSeeder::class); // Create sample partner data

        // DemoSeeder is intentionally NOT called here
        // It creates fake invoices/expenses for testing only
        // Run manually: php artisan db:seed --class=DemoSeeder
    }
}
