<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Silber\Bouncer\BouncerFacade as Bouncer;

class RailwayInstallSeeder extends Seeder
{
    /**
     * Seed the application's database for Railway deployment.
     */
    public function run(): void
    {
        // Check if already installed
        if (Setting::getSetting('profile_complete') === 'COMPLETED') {
            $this->command->info('App already installed, skipping...');
            return;
        }

        $this->command->info('Setting up Railway installation...');

        // Create Macedonian Denar currency
        $currency = Currency::firstOrCreate(
            ['code' => 'MKD'],
            [
                'name' => 'Macedonian Denar',
                'symbol' => 'ден',
                'precision' => 2,
                'thousand_separator' => '.',
                'decimal_separator' => ',',
            ]
        );

        // Create default company
        $company = Company::firstOrCreate(
            ['name' => env('COMPANY_NAME', 'Facturino')],
            [
                'unique_hash' => str()->random(20),
            ]
        );

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@facturino.mk')],
            [
                'name' => env('ADMIN_NAME', 'Администратор'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'role' => 'super admin',
            ]
        );

        // Assign admin role
        Bouncer::assign('super admin')->to($admin);
        $admin->companies()->syncWithoutDetaching([$company->id]);

        // Set company settings for Macedonia
        CompanySetting::setSettings([
            'currency' => $currency->id,
            'time_zone' => 'Europe/Skopje',
            'language' => 'mk',
            'fiscal_year' => '1-12',
            'carbon_date_format' => 'd.m.Y',
            'moment_date_format' => 'DD.MM.YYYY',
            'notification_email' => env('ADMIN_EMAIL', 'admin@facturino.mk'),
        ], $company->id);

        // Mark as installed
        Setting::setSetting('profile_complete', 'COMPLETED');
        Setting::setSetting('admin_email', env('ADMIN_EMAIL', 'admin@facturino.app'));

        $this->command->info('Installation complete!');
        $this->command->info('Admin Email: ' . $admin->email);
        $this->command->info('Admin Password: ' . env('ADMIN_PASSWORD', 'password'));
    }
}
