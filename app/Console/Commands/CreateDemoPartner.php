<?php

namespace App\Console\Commands;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Console\Command;

class CreateDemoPartner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'partner:create-demo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Partner record for partner@demo.mk user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::where('email', 'partner@demo.mk')->first();

        if (! $user) {
            $this->error('User partner@demo.mk not found');

            return 1;
        }

        $existing = Partner::where('user_id', $user->id)->first();

        if ($existing) {
            $this->info("Partner already exists: ID {$existing->id}");
            $this->info("Email: {$existing->email}");

            return 0;
        }

        $partner = Partner::create([
            'user_id' => $user->id,
            'name' => 'Demo Partner',
            'email' => $user->email,
            'company_name' => 'Demo Partner Company',
            'commission_rate' => 15.00,
            'is_active' => true,
            'kyc_status' => 'verified',
        ]);

        $this->info("✅ Created Partner ID: {$partner->id}");
        $this->info("Email: {$partner->email}");
        $this->info('Partner portal is now ready to use!');

        return 0;
    }
}
// CLAUDE-CHECKPOINT
