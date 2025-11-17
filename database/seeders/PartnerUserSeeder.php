<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Company;

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

        // Attach to first company
        $company = Company::first();

        if ($company) {
            $user->companies()->attach($company->id, ['is_owner' => false]);
            $this->command->info("✓ Partner attached to company: {$company->name}");
        } else {
            $this->command->warn('⚠ No company found to attach partner to');
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
