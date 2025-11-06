<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Console\Command;

class CleanDemoData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:demo-data {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove demo data created by DemoSeeder (demo company, invoices, expenses)';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  This will delete the demo company and all its data. Continue?')) {
                $this->info('Operation cancelled');
                return;
            }
        }

        $this->info('🧹 Cleaning demo data...');

        // Find and delete demo company
        $demoCompany = Company::where('slug', 'makedonska-softver-doo')->first();
        if ($demoCompany) {
            $this->info("Found demo company: {$demoCompany->name} (ID: {$demoCompany->id})");

            // Count related data
            $invoiceCount = Invoice::where('company_id', $demoCompany->id)->count();
            $expenseCount = Expense::where('company_id', $demoCompany->id)->count();

            $this->line("  - {$invoiceCount} invoices");
            $this->line("  - {$expenseCount} expenses");

            // Delete company (cascade will handle related data)
            $demoCompany->delete();
            $this->info("✅ Deleted demo company and all related data");
        } else {
            $this->warn('No demo company found (slug: makedonska-softver-doo)');
        }

        // Find and delete demo user
        $demoUser = User::where('email', 'marko.petrovski@megasoft.mk')->first();
        if ($demoUser) {
            $this->info("Found demo user: {$demoUser->email}");
            $demoUser->delete();
            $this->info("✅ Deleted demo user");
        } else {
            $this->info('No demo user found');
        }

        // Also clean any invoices with demo pattern (ФАК-xxxxxx)
        $demoInvoices = Invoice::where('invoice_number', 'LIKE', 'ФАК-%')->get();
        if ($demoInvoices->count() > 0) {
            $this->warn("Found {$demoInvoices->count()} invoices with demo pattern (ФАК-xxxxxx)");
            foreach ($demoInvoices as $invoice) {
                $this->line("  - Invoice {$invoice->invoice_number} (Company ID: {$invoice->company_id})");
            }

            if ($this->confirm('Delete these invoices as well?', true)) {
                $deleted = Invoice::where('invoice_number', 'LIKE', 'ФАК-%')->delete();
                $this->info("✅ Deleted {$deleted} demo invoices");
            }
        }

        $this->newLine();
        $this->info('🎉 Demo data cleanup complete!');
        $this->info('Your production data is now clean.');
    }
}
