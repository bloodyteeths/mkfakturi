<?php

namespace Modules\Mk\Bitrix\Commands;

use Illuminate\Console\Command;
use Modules\Mk\Bitrix\Services\WelcomeEmailService;

// CLAUDE-CHECKPOINT

/**
 * WelcomeSendDripCommand
 *
 * Processes queued welcome drip emails for companies and partners.
 * Runs hourly via cron during business hours (Europe/Skopje).
 *
 * Usage:
 *   php artisan welcome:send-drip
 *   php artisan welcome:send-drip --dry-run
 */
class WelcomeSendDripCommand extends Command
{
    protected $signature = 'welcome:send-drip
                            {--dry-run : Show what would be sent without sending}';

    protected $description = 'Process and send queued welcome drip emails for companies and partners';

    public function handle(WelcomeEmailService $service): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('[DRY RUN] Checking for due welcome emails...');
        } else {
            $this->info('Processing welcome drip emails...');
        }

        $sent = $service->processDrip($dryRun);

        $verb = $dryRun ? 'would be sent' : 'sent';
        $this->info("Done. {$sent} email(s) {$verb}.");

        return self::SUCCESS;
    }
}
