<?php

namespace Modules\Mk\Partner\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Expire Partner Trials
 *
 * Daily command that finds partners whose trial expired without subscribing
 * and downgrades them to 'free' tier. Sends notification email.
 */
class ExpirePartnerTrials extends Command
{
    protected $signature = 'partner:expire-trials';

    protected $description = 'Expire partner trials that have ended without a subscription';

    /**
     * Only send the "trial ended" email if the trial lapsed within this many
     * days. A trial that ended weeks/months ago (e.g. a backlog that built up
     * while this cron was down) is downgraded silently — sending a stale "your
     * trial just ended" notice months late only confuses partners.
     */
    private const EMAIL_IF_EXPIRED_WITHIN_DAYS = 3;

    public function handle(): int
    {
        $expiredUsers = User::where('role', 'partner')
            ->whereNotNull('partner_trial_ends_at')
            ->where('partner_trial_ends_at', '<', now())
            ->whereNull('stripe_subscription_id')
            ->where('partner_subscription_tier', '!=', 'free')
            ->get();

        if ($expiredUsers->isEmpty()) {
            $this->info('No expired partner trials found.');
            return 0;
        }

        $count = 0;
        foreach ($expiredUsers as $user) {
            $user->update(['partner_subscription_tier' => 'free']);
            $count++;

            Log::info('Partner trial expired, downgraded to free', [
                'user_id' => $user->id,
                'email' => $user->email,
                'trial_ended_at' => $user->partner_trial_ends_at,
            ]);

            // Staleness guard: only notify if the trial lapsed recently.
            $endedAt = \Illuminate\Support\Carbon::parse($user->partner_trial_ends_at);
            if ($endedAt->lt(now()->subDays(self::EMAIL_IF_EXPIRED_WITHIN_DAYS))) {
                Log::info('Skipped stale trial-expired email', [
                    'user_id' => $user->id,
                    'trial_ended_at' => $user->partner_trial_ends_at,
                ]);

                continue;
            }

            // Send notification email
            try {
                $this->sendTrialExpiredEmail($user);
            } catch (\Throwable $e) {
                Log::warning('Trial expired email failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Expired {$count} partner trials.");

        return 0;
    }

    protected function sendTrialExpiredEmail(User $user): void
    {
        $partner = $user->partner;
        if (!$partner) return;

        // Use Postmark broadcast stream
        \Illuminate\Support\Facades\Mail::raw(
            "Вашиот бесплатен пробен период заврши.\n\n" .
            "За да продолжите да го користите Facturino со сите функции, " .
            "изберете план на: https://app.facturino.mk/partner/billing\n\n" .
            "Ви благодариме,\nТимот на Facturino",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Вашиот пробен период заврши — Facturino')
                    ->from('partners@facturino.mk', 'Facturino');
                $headers = $message->getHeaders();
                $headers->addTextHeader('X-PM-Message-Stream', 'broadcast');
                // Keep the billing link clean — don't rewrite it through
                // track.pstmrk.it (looks phishy to recipients).
                $headers->addTextHeader('X-PM-TrackLinks', 'None');
                $headers->addTextHeader('X-PM-TrackOpens', 'false');
            }
        );
    }
}
// CLAUDE-CHECKPOINT
