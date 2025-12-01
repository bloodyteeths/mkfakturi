<?php

namespace App\Console\Commands;

use App\Services\PartnerPayoutService;
use Illuminate\Console\Command;

class ProcessPartnerPayouts extends Command
{
    protected $signature = 'partner:process-payouts';

    protected $description = 'Process monthly partner commission payouts';

    public function handle(PartnerPayoutService $payoutService)
    {
        $this->info('Processing partner payouts...');

        $payoutService->processMonthlyPayouts();

        $this->info('Partner payouts processed successfully!');
    }
}
