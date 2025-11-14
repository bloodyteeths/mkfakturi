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

    // Database and file backup - runs daily at 2:00 AM
    Schedule::command('backup:run')
        ->daily()
        ->at('02:00')
        ->runInBackground()
        ->withoutOverlapping();

    // Monitor backup health - runs every 6 hours
    Schedule::command('backup:monitor')
        ->everySixHours()
        ->runInBackground()
        ->withoutOverlapping();

    // Certificate expiry check - runs daily at 8:00 AM
    Schedule::command('certificates:check-expiry')
        ->dailyAt('08:00')
        ->emailOutputOnFailure(config('mail.from.address', 'admin@facturino.mk'));

    // Health check self-test - runs every hour
    Schedule::call(function () {
        try {
            $response = \Http::timeout(30)->get(url('/health'));
            if ($response->status() !== 200) {
                \Log::error('Health check self-test failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Health check self-test exception', [
                'error' => $e->getMessage()
            ]);
        }
    })->hourly()
        ->name('health-check-self-test');

    // Note: Commented out until proper parameters are configured
    // Schedule::job(new \App\Jobs\PantheonExportJob([], 1))
    //     ->dailyAt('02:00')
    //     ->runInBackground()
    //     ->withoutOverlapping();

    // Only query recurring invoices if tables exist
    if (\Schema::hasTable('recurring_invoices')) {
        $recurringInvoices = RecurringInvoice::where('status', 'ACTIVE')->get();
        foreach ($recurringInvoices as $recurringInvoice) {
            $timeZone = CompanySetting::getSetting('time_zone', $recurringInvoice->company_id);

            Schedule::call(function () use ($recurringInvoice) {
                $recurringInvoice->generateInvoice();
            })->cron($recurringInvoice->frequency)->timezone($timeZone);
        }
    }
}

// CLAUDE-CHECKPOINT
