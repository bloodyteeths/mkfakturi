<?php

namespace App\Console\Commands;

use App\Models\CompanySubscription;
use App\Notifications\TrialExpired;
use App\Notifications\TrialExpiring;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ProcessTrialExpirations Command
 *
 * FG-01-41: Runs daily to:
 * 1. Send trial expiry reminders (7 days, 1 day before)
 * 2. Downgrade expired trials to Free tier
 * 3. Send expiry notification
 */
class ProcessTrialExpirations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:process-trial-expirations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process trial expirations: send reminders and downgrade expired trials to Free tier';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing trial expirations...');

        $reminderDays = config('subscriptions.trial.email_reminders', [7, 1, 0]);
        $processed = 0;
        $reminded = 0;
        $expired = 0;

        foreach ($reminderDays as $daysBeforeExpiry) {
            $reminded += $this->sendReminders($daysBeforeExpiry);
        }

        $expired = $this->expireTrials();

        $this->info("Processed {$expired} expired trials");
        $this->info("Sent {$reminded} trial expiry reminders");

        Log::info('Trial expirations processed', [
            'expired' => $expired,
            'reminded' => $reminded,
        ]);

        return 0;
    }

    /**
     * Send trial expiry reminders
     *
     * @return int Number of reminders sent
     */
    protected function sendReminders(int $daysBeforeExpiry): int
    {
        $targetDate = Carbon::now()->addDays($daysBeforeExpiry)->startOfDay();
        $endOfDay = $targetDate->copy()->endOfDay();

        $subscriptions = CompanySubscription::where('status', 'trial')
            ->whereBetween('trial_ends_at', [$targetDate, $endOfDay])
            ->with('company.owner')
            ->get();

        $count = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $owner = $subscription->company->owner;

                if (! $owner || ! $owner->email) {
                    $this->warn("Company {$subscription->company_id} has no owner or email");

                    continue;
                }

                // Send reminder notification
                $owner->notify(new TrialExpiring($subscription, $daysBeforeExpiry));

                $count++;

                $this->info("Sent {$daysBeforeExpiry}-day reminder to {$owner->email} for company {$subscription->company->name}");
            } catch (\Exception $e) {
                Log::error('Failed to send trial reminder', [
                    'company_id' => $subscription->company_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Expire trials that have ended
     *
     * @return int Number of expired trials
     */
    protected function expireTrials(): int
    {
        $now = Carbon::now();

        $expiredSubscriptions = CompanySubscription::where('status', 'trial')
            ->where('trial_ends_at', '<', $now)
            ->with('company.owner')
            ->get();

        $count = 0;

        foreach ($expiredSubscriptions as $subscription) {
            try {
                // Downgrade to Free tier
                $subscription->update([
                    'plan' => 'free',
                    'status' => 'active', // Active Free tier
                    'trial_ends_at' => null,
                ]);

                $owner = $subscription->company->owner;

                if ($owner && $owner->email) {
                    // Send expiry notification
                    $owner->notify(new TrialExpired($subscription));

                    $this->info("Expired trial for company {$subscription->company->name}, downgraded to Free");
                } else {
                    $this->warn("Company {$subscription->company_id} has no owner or email for expiry notification");
                }

                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to expire trial', [
                    'company_id' => $subscription->company_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }
}
// CLAUDE-CHECKPOINT
