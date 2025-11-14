<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckIfrsTablesCommand extends Command
{
    protected $signature = 'ifrs:check-tables';
    protected $description = 'Check if IFRS tables exist and report status';

    public function handle()
    {
        $this->info('Checking IFRS database tables...');

        $requiredTables = [
            'ifrs_entities',
            'ifrs_currencies',
            'ifrs_exchange_rates',
            'ifrs_reporting_periods',
            'ifrs_recycled_objects',
            'ifrs_categories',
            'ifrs_accounts',
            'ifrs_vats',
            'ifrs_balances',
            'ifrs_transactions',
            'ifrs_assignments',
            'ifrs_line_items',
            'ifrs_ledgers',
        ];

        $missing = [];
        $existing = [];

        foreach ($requiredTables as $table) {
            if (Schema::hasTable($table)) {
                $existing[] = $table;
                $count = DB::table($table)->count();
                $this->info("✓ {$table} exists ({$count} records)");
            } else {
                $missing[] = $table;
                $this->error("✗ {$table} MISSING");
            }
        }

        $this->newLine();

        if (empty($missing)) {
            $this->info('✅ All IFRS tables exist!');

            // Check if there are any entities
            $entityCount = DB::table('ifrs_entities')->count();
            $currencyCount = DB::table('ifrs_currencies')->count();
            $accountCount = DB::table('ifrs_accounts')->count();

            $this->info("Entities: {$entityCount}");
            $this->info("Currencies: {$currencyCount}");
            $this->info("Accounts: {$accountCount}");

            if ($entityCount === 0) {
                $this->warn('⚠️  No IFRS entities found. Run: php artisan db:seed --class=MkIfrsSeeder');
            }

            return 0;
        } else {
            $this->error('❌ Missing ' . count($missing) . ' IFRS tables!');
            $this->warn('Run this to create missing tables: php artisan migrate --path=vendor/ekmungai/eloquent-ifrs/database/migrations');
            return 1;
        }
    }
}
