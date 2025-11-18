<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Partner;
use App\Models\PartnerCompanyLink;
use App\Models\User;
use Illuminate\Console\Command;

class FixPartnerLogin extends Command
{
    protected $signature = 'partner:fix-login {user_id=4}';

    protected $description = 'Fix partner login by ensuring Partner record and company links exist';

    public function handle()
    {
        $userId = $this->argument('user_id');

        $this->info("Checking user ID {$userId}...");
        $user = User::find($userId);

        if (! $user) {
            $this->error("User {$userId} not found!");

            return 1;
        }

        $this->info("User found: {$user->email}, Role: {$user->role}");

        // Check/create partner record
        $partner = Partner::where('user_id', $userId)->first();

        if (! $partner) {
            $this->warn('No Partner record found. Creating...');
            $partner = Partner::create([
                'user_id' => $userId,
                'name' => $user->name ?? 'Demo Partner',
                'email' => $user->email,
                'company_name' => 'Demo Partner LLC',
                'is_active' => true,
                'kyc_status' => 'approved',
                'partner_tier' => 'free',
            ]);
            $this->info("Partner created with ID: {$partner->id}");
        } else {
            $this->info("Partner found: ID={$partner->id}, Name={$partner->name}, Active=".($partner->is_active ? 'yes' : 'no'));
        }

        // Check company links
        $links = PartnerCompanyLink::where('partner_id', $partner->id)->get();
        $this->info("Found {$links->count()} company link(s)");

        foreach ($links as $link) {
            $company = Company::find($link->company_id);
            $this->info("  - Company {$link->company_id}: ".($company ? $company->name : 'DELETED').
                       ', Primary='.($link->is_primary ? 'yes' : 'no').
                       ', Active='.($link->is_active ? 'yes' : 'no'));
        }

        // Link to company 2 if not already linked
        if (! $links->contains('company_id', 2)) {
            $company2 = Company::find(2);
            if (! $company2) {
                $this->error('Company 2 not found!');
                $this->info('Available companies:');
                Company::all()->each(function ($c) {
                    $this->info("  - ID {$c->id}: {$c->name}");
                });

                return 1;
            }

            $this->info('Linking partner to company 2...');
            PartnerCompanyLink::create([
                'partner_id' => $partner->id,
                'company_id' => 2,
                'is_primary' => true,
                'is_active' => true,
            ]);
            $this->info('Link created successfully!');
        }

        $this->info("\n✓ Partner {$partner->email} (ID: {$partner->id}) is ready to login.");

        return 0;
    }
}
// CLAUDE-CHECKPOINT
