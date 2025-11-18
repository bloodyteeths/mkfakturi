<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class SyncAbilitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'abilities:sync {--company= : Sync abilities for a specific company ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync abilities from config/abilities.php to all company super admin roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Syncing abilities for all companies...');
        $this->info('Reading abilities from: config/abilities.php');
        $this->newLine();

        $companyId = $this->option('company');

        if ($companyId) {
            $companies = Company::where('id', $companyId)->get();
            if ($companies->isEmpty()) {
                $this->error("❌ Company ID {$companyId} not found!");

                return 1;
            }
        } else {
            $companies = Company::all();
        }

        if ($companies->isEmpty()) {
            $this->warn('⚠️  No companies found in database');

            return 0;
        }

        $this->info("Found {$companies->count()} ".str('company')->plural($companies->count()));
        $this->newLine();

        $progressBar = $this->output->createProgressBar($companies->count());
        $progressBar->start();

        foreach ($companies as $company) {
            $this->syncCompanyAbilities($company);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('✅ Abilities synced successfully for all companies!');
        $this->info('All tenants now have up-to-date abilities from config/abilities.php');

        return 0;
    }

    /**
     * Sync abilities for a single company
     */
    protected function syncCompanyAbilities(Company $company): void
    {
        try {
            // This method reads from config('abilities.abilities') and assigns
            // all abilities to the super admin role for this company
            $company->setupRoles();
        } catch (\Exception $e) {
            $this->newLine();
            $this->error("❌ Failed to sync abilities for company {$company->id} ({$company->name}): ".$e->getMessage());
            $this->newLine();
        }
    }
}
// CLAUDE-CHECKPOINT
