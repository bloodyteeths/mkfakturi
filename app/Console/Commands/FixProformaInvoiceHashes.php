<?php

namespace App\Console\Commands;

use App\Models\ProformaInvoice;
use Illuminate\Console\Command;
use Vinkla\Hashids\Facades\Hashids;

class FixProformaInvoiceHashes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'proforma:fix-hashes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix proforma invoices with missing unique_hash values';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $proformaInvoices = ProformaInvoice::whereNull('unique_hash')
            ->orWhere('unique_hash', '')
            ->get();

        if ($proformaInvoices->isEmpty()) {
            $this->info('All proforma invoices have unique_hash values.');
            return 0;
        }

        $this->info("Found {$proformaInvoices->count()} proforma invoices with missing unique_hash.");

        $bar = $this->output->createProgressBar($proformaInvoices->count());
        $bar->start();

        foreach ($proformaInvoices as $proformaInvoice) {
            $proformaInvoice->unique_hash = Hashids::connection(ProformaInvoice::class)->encode($proformaInvoice->id);
            $proformaInvoice->save();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done! All proforma invoices now have unique_hash values.');

        return 0;
    }
}
