<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Silber\Bouncer\BouncerFacade;

class SetupAdminRoles extends Command
{
    protected $signature = 'roles:setup-admin';
    protected $description = 'Create admin roles for all existing companies';

    public function handle()
    {
        $this->info('Setting up admin roles for all companies...');

        $companies = Company::all();
        $count = 0;

        foreach ($companies as $company) {
            BouncerFacade::scope()->to($company->id);

            // Check if admin role already exists
            $adminRole = BouncerFacade::role()->where('name', 'admin')
                ->where('scope', $company->id)
                ->first();

            if (!$adminRole) {
                $this->info("Creating admin role for company: {$company->name} (ID: {$company->id})");

                $admin = BouncerFacade::role()->firstOrCreate([
                    'name' => 'admin',
                    'title' => 'Admin',
                    'scope' => $company->id,
                ]);

                // Grant same permissions as super admin
                foreach (config('abilities.abilities') as $ability) {
                    BouncerFacade::allow($admin)->to($ability['ability'], $ability['model']);
                }

                $count++;
            } else {
                $this->line("Admin role already exists for company: {$company->name}");
            }
        }

        $this->info("✓ Created admin roles for {$count} companies");

        return 0;
    }
}
