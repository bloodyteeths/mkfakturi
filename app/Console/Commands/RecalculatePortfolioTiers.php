<?php

namespace App\Console\Commands;

use App\Models\Partner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Mk\Partner\Services\PortfolioTierService;

class RecalculatePortfolioTiers extends Command
{
    protected $signature = 'portfolio:recalculate
                            {--partner= : Recalculate for a specific partner ID}';

    protected $description = 'Recalculate portfolio tier overrides for all active portfolio partners';

    public function handle(PortfolioTierService $tierService): int
    {
        $partnerId = $this->option('partner');

        if ($partnerId) {
            return $this->recalculateForPartner($tierService, (int) $partnerId);
        }

        return $this->recalculateAll($tierService);
    }

    protected function recalculateAll(PortfolioTierService $tierService): int
    {
        $this->info('Recalculating portfolio tiers for all active partners...');

        // Handle grace period expirations first
        $this->handleGraceExpirations();

        // Recalculate all
        $count = $tierService->recalculateAll();

        $this->info("Recalculated tiers for {$count} partners.");
        Log::info("Portfolio tiers recalculated for {$count} partners.");

        return self::SUCCESS;
    }

    protected function recalculateForPartner(PortfolioTierService $tierService, int $partnerId): int
    {
        $partner = Partner::find($partnerId);

        if (! $partner) {
            $this->error("Partner #{$partnerId} not found.");

            return self::FAILURE;
        }

        if (! $partner->portfolio_enabled) {
            $this->warn("Partner #{$partnerId} does not have portfolio enabled.");

            return self::FAILURE;
        }

        $stats = $tierService->recalculate($partner);
        $this->info("Recalculated for partner #{$partnerId}: " . json_encode($stats));

        return self::SUCCESS;
    }

    /**
     * Handle grace period expirations and send reminders.
     */
    protected function handleGraceExpirations(): void
    {
        $reminders = config('subscriptions.portfolio.grace_reminders', [7, 1]);

        foreach ($reminders as $daysBefore) {
            $targetDate = now()->addDays($daysBefore)->startOfDay();

            $partners = Partner::where('portfolio_enabled', true)
                ->whereNotNull('portfolio_grace_ends_at')
                ->whereDate('portfolio_grace_ends_at', $targetDate)
                ->get();

            foreach ($partners as $partner) {
                Log::info("Portfolio grace period reminder: {$daysBefore} days left", [
                    'partner_id' => $partner->id,
                    'grace_ends_at' => $partner->portfolio_grace_ends_at,
                ]);

                // TODO: Send notification to partner about grace period ending
                // Notification::send($partner->user, new PortfolioGraceEndingNotification($partner, $daysBefore));
            }
        }

        // Log partners whose grace just expired
        $expiredPartners = Partner::where('portfolio_enabled', true)
            ->whereNotNull('portfolio_grace_ends_at')
            ->where('portfolio_grace_ends_at', '<', now())
            ->get();

        if ($expiredPartners->isNotEmpty()) {
            $this->info("Grace period expired for {$expiredPartners->count()} partners - tiers will be recalculated.");
        }
    }
}
// CLAUDE-CHECKPOINT
