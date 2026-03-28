<?php

use App\Jobs\SyncBankTransactions;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\RecurringInvoice;
use App\Space\InstallUtils;
use Illuminate\Support\Facades\Schedule;

// Only run in demo environment
if (config('app.env') === 'demo') {
    Schedule::command('reset:app --force')
        ->daily()
        ->runInBackground()
        ->withoutOverlapping();
}

if (InstallUtils::isDbCreated()) {
    Schedule::command('check:invoices:status')
        ->daily();

    Schedule::command('check:estimates:status')
        ->daily();

    // Bank transaction sync - runs every 4 hours for all active companies
    // Respects PSD2 rate limits (15 req/min for Stopanska)
    Schedule::call(function () {
        $companies = Company::whereHas('bankAccounts', function ($query) {
            $query->where('is_active', true);
        })->get();

        foreach ($companies as $company) {
            SyncBankTransactions::dispatch($company, 7)
                ->onQueue('banking');
        }
    })->everyFourHours()
        ->name('sync-bank-transactions')
        ->withoutOverlapping();

    // Process recurring expenses daily at 6:00 AM
    Schedule::command('recurring-expenses:process')
        ->dailyAt('06:00')
        ->runInBackground()
        ->withoutOverlapping();

    // Backup cleanup - runs daily at 1:00 AM
    Schedule::command('backup:clean')
        ->daily()
        ->at('01:00')
        ->runInBackground()
        ->withoutOverlapping();

    // Database and file backup - runs every 6 hours (00:00, 06:00, 12:00, 18:00)
    // ~300 KB per backup, ~36 MB/year on R2
    Schedule::command('backup:run --only-db')
        ->everySixHours()
        ->runInBackground()
        ->withoutOverlapping();

    // Monitor backup health - runs every 6 hours
    Schedule::command('backup:monitor')
        ->everySixHours()
        ->runInBackground()
        ->withoutOverlapping();

    // Certificate expiry check - runs daily at 8:00 AM
    Schedule::command('certificates:check-expiry')
        ->dailyAt('08:00');

    // Award affiliate bounties - runs daily at 2:00 AM UTC (AC-01-24)
    // Checks for eligible partners (verified KYC + 3 companies OR 30 days)
    // Awards €300 accountant bounty and €50 company bounty (first paying company)
    // Note: job events cannot be marked runInBackground() in Laravel 12, so we keep it foreground here.
    Schedule::job(new \App\Jobs\AwardBounties)
        ->dailyAt('02:00')
        ->name('award-bounties')
        ->withoutOverlapping()
        ->onSuccess(function () {
            Log::info('AwardBounties job completed successfully');
        })
        ->onFailure(function () {
            Log::error('AwardBounties job failed');
        });

    // Calculate affiliate payouts - runs on 5th of each month at 3:00 AM UTC (AC-01-40)
    // Processes previous month's commissions for verified partners
    // Minimum €100 threshold, generates CSV for bank transfer
    Schedule::command('payouts:calculate')
        ->monthlyOn(5, '03:00')
        ->runInBackground()
        ->withoutOverlapping();

    // Process partner Stripe Connect payouts - runs on 5th of each month at 2:00 AM
    Schedule::command('partner:process-payouts')
        ->monthlyOn(5, '02:00')
        ->runInBackground()
        ->withoutOverlapping();

    // Process trial expirations - runs daily at 1:00 AM UTC (FG-01-41)
    // Sends trial expiry reminders (7 days, 1 day before)
    // Downgrades expired trials to Free tier
    Schedule::command('subscriptions:process-trial-expirations')
        ->dailyAt('01:00')
        ->runInBackground()
        ->withoutOverlapping();

    // Partner trial expirations - runs daily at 01:30 AM UTC
    // Downgrades expired partner trials to Free tier
    Schedule::command('partner:expire-trials')
        ->dailyAt('01:30')
        ->runInBackground()
        ->withoutOverlapping();

    // External service health monitor - runs every 15 minutes
    // Alerts ADMIN_EMAIL on first failure, suppresses repeats, sends recovery notice
    Schedule::command('services:monitor')
        ->everyFifteenMinutes()
        ->runInBackground()
        ->name('services-monitor')
        ->withoutOverlapping();

    // Health check self-test - runs every hour
    Schedule::call(function () {
        try {
            $response = \Http::timeout(30)->get(url('/health'));
            if ($response->status() !== 200) {
                \Log::error('Health check self-test failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Health check self-test exception', [
                'error' => $e->getMessage(),
            ]);
        }
    })->hourly()
        ->name('health-check-self-test');

    // Process pending GDPR data exports - runs every minute
    // Avoids HTTP timeout issues by processing exports via cron instead of inline
    Schedule::command('exports:process-pending')
        ->everyMinute()
        ->runInBackground()
        ->name('process-data-exports')
        ->withoutOverlapping();

    // MX verification for outreach leads - runs before send window
    // Verifies DNS MX records to prevent bounces from dead mailboxes
    Schedule::command('outreach:verify-mx --limit=2000')
        ->dailyAt('07:00')
        ->timezone('Europe/Skopje')
        ->runInBackground()
        ->name('outreach-verify-mx')
        ->withoutOverlapping();

    // SMTP RCPT TO verification - runs after MX, before send window
    // Catches dead mailboxes on valid domains, auto-suppresses invalid
    Schedule::command('outreach:verify-smtp --limit=500')
        ->dailyAt('07:30')
        ->timezone('Europe/Skopje')
        ->runInBackground()
        ->name('outreach-verify-smtp')
        ->withoutOverlapping();

    // Outreach email batch send - accountant leads
    // Runs every 15 minutes during business hours 09:00-17:00 Skopje
    // 3000/day target: ~83 per batch, 350/hour, 2-5s jitter
    Schedule::command('outreach:send-batch --limit=100 --type=accountant')
        ->everyFifteenMinutes()
        ->between('09:00', '17:00')
        ->weekdays()
        ->timezone('Europe/Skopje')
        ->runInBackground()
        ->name('outreach-batch-accountant')
        ->withoutOverlapping();

    // Outreach email batch send - company leads (broadcast stream, separate limits)
    // 5000/day target: ~139 per batch, 600/hour, runs parallel with accountant batch
    Schedule::command('outreach:send-batch --limit=150 --type=company')
        ->everyFifteenMinutes()
        ->between('08:00', '17:00')
        ->weekdays()
        ->timezone('Europe/Skopje')
        ->runInBackground()
        ->name('outreach-batch-company')
        ->withoutOverlapping();

    // Poll HubSpot for deals in "interested" stage and create partner accounts
    // Runs every 10 minutes to detect stage changes
    Schedule::command('hubspot:process-stage-changes')
        ->everyTenMinutes()
        ->runInBackground()
        ->name('hubspot-process-stage-changes')
        ->withoutOverlapping();

    // Sync partner activity and commission data to HubSpot
    // Updates deal properties with revenue, commissions, invoice counts, health scores
    // Runs every 6 hours - frequent enough for wife to see updated data
    Schedule::command('hubspot:sync-partner-activity')
        ->everySixHours()
        ->runInBackground()
        ->name('hubspot-sync-partner-activity')
        ->withoutOverlapping();

    // Generate recurring deadline instances - runs on 1st of each month (P8-02)
    // Creates next month's VAT, MPIN, CIT, annual FS deadlines for all companies
    Schedule::command('deadlines:generate-recurring')
        ->monthlyOn(1, '00:00')
        ->runInBackground()
        ->withoutOverlapping();

    // Welcome drip emails for new signups (companies + partners)
    // Sends Day 2, 5, 10, 14 follow-ups during business hours
    Schedule::command('welcome:send-drip')
        ->hourly()
        ->between('08:00', '18:00')
        ->timezone('Europe/Skopje')
        ->runInBackground()
        ->name('welcome-send-drip')
        ->withoutOverlapping();

    // Send deadline reminder notifications - runs daily at 09:00 (P8-02)
    // Reminds company owners and partners about upcoming deadlines
    // Also updates overdue/due_today statuses
    Schedule::command('deadlines:send-reminders')
        ->dailyAt('09:00')
        ->runInBackground()
        ->withoutOverlapping();

    // Recalculate portfolio tier overrides for all accountant portfolios
    // Handles grace period expirations and sliding scale recalculation
    Schedule::command('portfolio:recalculate')
        ->dailyAt('06:00')
        ->timezone('Europe/Skopje')
        ->runInBackground()
        ->withoutOverlapping()
        ->name('portfolio-recalculate');

    // Fiscal fraud detection — daily checks at 6:30 AM Skopje
    // Checks: missing Z-reports, cash discrepancies, receipt gaps
    Schedule::command('fiscal:fraud-check')
        ->dailyAt('06:30')
        ->timezone('Europe/Skopje')
        ->runInBackground()
        ->withoutOverlapping()
        ->name('fiscal-fraud-check');

    // Post monthly depreciation GL entries on 1st of each month at 00:30
    Schedule::command('depreciation:post-monthly')
        ->monthlyOn(1, '00:30')
        ->timezone('Europe/Skopje')
        ->runInBackground()
        ->withoutOverlapping()
        ->name('post-monthly-depreciation');

    // Note: Commented out until proper parameters are configured
    // Schedule::job(new \App\Jobs\PantheonExportJob([], 1))
    //     ->dailyAt('02:00')
    //     ->runInBackground()
    //     ->withoutOverlapping();

    // Only query recurring invoices if tables exist
    if (\Schema::hasTable('recurring_invoices')) {
        $recurringInvoices = RecurringInvoice::where('status', 'ACTIVE')->get();
        foreach ($recurringInvoices as $recurringInvoice) {
            try {
                // Validate cron expression before scheduling
                $cron = new \Cron\CronExpression($recurringInvoice->frequency);
                $cron->getNextRunDate(); // Test if valid

                $timeZone = CompanySetting::getSetting('time_zone', $recurringInvoice->company_id) ?? 'Europe/Skopje';

                Schedule::call(function () use ($recurringInvoice) {
                    try {
                        // Reload to get fresh data
                        $invoice = RecurringInvoice::find($recurringInvoice->id);
                        if ($invoice && $invoice->status === 'ACTIVE') {
                            $invoice->generateInvoice();
                        }
                    } catch (\Exception $e) {
                        \Log::error('Failed to generate recurring invoice', [
                            'recurring_invoice_id' => $recurringInvoice->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                })
                    ->cron($recurringInvoice->frequency)
                    ->timezone($timeZone)
                    ->name("recurring-invoice-{$recurringInvoice->id}")
                    ->withoutOverlapping();
            } catch (\Exception $e) {
                \Log::warning('Invalid cron expression for recurring invoice', [
                    'recurring_invoice_id' => $recurringInvoice->id,
                    'frequency' => $recurringInvoice->frequency,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

