<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Silber\Bouncer\BouncerFacade as Bouncer;

class ResetAdminCommand extends Command
{
    protected $signature = 'admin:reset {--email=admin@facturino.mk} {--password=password}';
    protected $description = 'Reset or create admin user with specified credentials';

    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');

        $this->info("Looking for existing admin user with email: {$email}");

        // Find or create the user
        $admin = User::where('email', $email)->first();

        if ($admin) {
            $this->info("Found existing user. Resetting password...");
            $admin->password = Hash::make($password);
            $admin->save();
            $this->info("Password updated for user: {$admin->email}");
        } else {
            $this->info("No user found. Creating new admin user...");

            // Create currency if needed
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

            // Create company if needed
            $company = Company::first();
            if (!$company) {
                $company = Company::create([
                    'name' => 'Facturino',
                    'unique_hash' => str()->random(20),
                ]);

                // Set company settings
                CompanySetting::setSettings([
                    'currency' => $currency->id,
                    'time_zone' => 'Europe/Skopje',
                    'language' => 'mk',
                    'fiscal_year' => '1-12',
                    'carbon_date_format' => 'd.m.Y',
                    'moment_date_format' => 'DD.MM.YYYY',
                    'notification_email' => $email,
                ], $company->id);
            }

            // Create admin user
            $admin = User::create([
                'name' => 'Администратор',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'super admin',
            ]);

            // Assign role and company
            Bouncer::assign('super admin')->to($admin);
            $admin->companies()->syncWithoutDetaching([$company->id]);

            $this->info("Admin user created successfully!");
        }

        $this->info("");
        $this->info("=================================");
        $this->info("Admin credentials:");
        $this->info("Email: {$email}");
        $this->info("Password: {$password}");
        $this->info("=================================");

        return 0;
    }
}
