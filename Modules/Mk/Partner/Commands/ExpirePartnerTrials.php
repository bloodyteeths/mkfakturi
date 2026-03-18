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
                $message->getHeaders()->addTextHeader('X-PM-Message-Stream', 'broadcast');
            }
        );
    }
}
// CLAUDE-CHECKPOINT
