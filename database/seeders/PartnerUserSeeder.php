<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Company;
use App\Models\Partner;

class PartnerUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if partner user already exists
        $existingUser = User::where('email', 'partner@demo.mk')->first();

        if ($existingUser) {
            $this->command->info('Partner user already exists (partner@demo.mk)');
            return;
        }

        // Create partner user
        $user = User::create([
            'name' => 'Partner Demo',
            'email' => 'partner@demo.mk',
            'password' => Hash::make('Partner2025!'),
            'role' => 'partner',
        ]);

        $this->command->info('✓ Partner user created successfully');

        // Create Partner record
        $partner = Partner::create([
            'user_id' => $user->id,
            'name' => 'Partner Demo',
            'email' => 'partner@demo.mk',
            'company_name' => 'Demo Accounting Bureau',
            'commission_rate' => 10.00,
            'is_active' => true,
            'kyc_status' => 'approved',
        ]);

        $this->command->info('✓ Partner record created successfully');

        // Link partner to first company (as accountant managing that company)
        $company = Company::first();

        if ($company) {
            $partner->companies()->attach($company->id, [
                'is_primary' => true,
                'is_active' => true,
                'override_commission_rate' => null,
                'permissions' => json_encode(['view_all', 'create_invoices', 'manage_customers']),
            ]);
            $this->command->info("✓ Partner linked to company: {$company->name}");
        } else {
            $this->command->warn('⚠ No company found to link partner to');
        }

        $this->command->info('');
        $this->command->info('===========================================');
        $this->command->info('PARTNER CREDENTIALS');
        $this->command->info('===========================================');
        $this->command->info('Email:    partner@demo.mk');
        $this->command->info('Password: Partner2025!');
        $this->command->info('User ID:  ' . $user->id);
        $this->command->info('===========================================');
    }
}
