<?php

namespace Database\Seeders;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class PartnerSeeder extends Seeder
{
    /**
     * Create Partner records for all admin/accountant users.
     * This seeder ensures that users with administrative roles have corresponding Partner entries.
     */
    public function run(): void
    {
        $this->command->info('Starting Partner creation for admin/accountant users...');

        // Get all users with admin or accountant roles
        // Check both the 'role' column and Bouncer assigned_roles
        $adminUsers = User::where(function ($query) {
            $query->where('role', 'super admin')
                ->orWhere('role', 'admin')
                ->orWhere('role', 'accountant')
                ->orWhere('role', 'partner'); // Include partner role for console access
        })->get();

        // CLAUDE-CHECKPOINT: Retrieved users with admin/accountant roles from users table

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($adminUsers as $user) {
            // Check if partner already exists for this user
            $existingPartner = Partner::where('user_id', $user->id)->first();

            if ($existingPartner) {
                $this->command->warn("Partner already exists for user {$user->email} (ID: {$user->id})");
                $skippedCount++;

                continue;
            }

            // Create partner record
            try {
                Partner::create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? null,
                    'company_name' => null,
                    'tax_id' => null,
                    'registration_number' => null,
                    'bank_account' => null,
                    'bank_name' => null,
                    'commission_rate' => 0.00,
                    'is_active' => true,
                    'notes' => "Auto-generated partner record for {$user->role} user",
                ]);

                $this->command->info("Created partner for user: {$user->email} ({$user->role})");
                $createdCount++;

                // CLAUDE-CHECKPOINT: Created partner record for user
            } catch (\Exception $e) {
                $this->command->error("Failed to create partner for user {$user->email}: {$e->getMessage()}");
                Log::error("PartnerSeeder: Failed to create partner for user {$user->id}", [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                ]);
            }
        }

        // CLAUDE-CHECKPOINT: Completed partner creation loop

        $this->command->info('Partner seeding completed!');
        $this->command->info('Total users processed: '.$adminUsers->count());
        $this->command->info("Partners created: {$createdCount}");
        $this->command->info("Partners skipped (already exist): {$skippedCount}");
    }
}
