<?php

namespace Database\Seeders;

use App\Models\BankProvider;
use Illuminate\Database\Seeder;

class BankProviderSeeder extends Seeder
{
    /**
     * Seed Macedonian bank providers for PSD2 integration.
     *
     * Seeds the 3 major Macedonian banks with their PSD2 API configurations.
     * Uses updateOrCreate() to make the seeder idempotent (safe to run multiple times).
     *
     * Initial implementation focuses on AIS (Account Information Service) only.
     * PIS (Payment Initiation Service) support will be added in later phases.
     *
     * Environment: Starts with sandbox for development and testing.
     *
     * @return void
     */
    public function run(): void
    {
        $providers = [
            [
                'key' => 'nlb',
                'name' => 'NLB Banka AD Skopje',
                'base_url' => 'https://sandbox-api.nlb.mk',
                'environment' => 'sandbox',
                'supports_ais' => true,
                'supports_pis' => false,
                'is_active' => true,
                'metadata' => [
                    'production_url' => 'https://api.nlb.mk',
                    'rate_limit' => 15, // requests per minute (PSD2 standard)
                    'description' => 'NLB Bank - largest bank in North Macedonia',
                ],
            ],
            [
                'key' => 'stopanska',
                'name' => 'Stopanska Banka AD Skopje',
                'base_url' => 'https://sandbox-api.stb.mk',
                'environment' => 'sandbox',
                'supports_ais' => true,
                'supports_pis' => false,
                'is_active' => true,
                'metadata' => [
                    'production_url' => 'https://api.stb.mk',
                    'rate_limit' => 15, // requests per minute (PSD2 standard)
                    'description' => 'Stopanska Bank - major commercial bank in North Macedonia',
                ],
            ],
            [
                'key' => 'komercijalna',
                'name' => 'Komercijalna Banka AD Skopje',
                'base_url' => 'https://sandbox-api.kb.mk',
                'environment' => 'sandbox',
                'supports_ais' => true,
                'supports_pis' => false,
                'is_active' => true,
                'metadata' => [
                    'production_url' => 'https://api.kb.mk',
                    'rate_limit' => 15, // requests per minute (PSD2 standard)
                    'description' => 'Komercijalna Bank - leading commercial bank in North Macedonia',
                ],
            ],
        ];

        foreach ($providers as $provider) {
            BankProvider::updateOrCreate(
                ['key' => $provider['key']], // Match on unique key
                $provider // Update or create with all data
            );
        }

        $this->command->info('Seeded ' . count($providers) . ' Macedonian bank providers (sandbox environment)');
    }
}

// CLAUDE-CHECKPOINT
