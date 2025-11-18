<?php

namespace App\Console\Commands;

use App\Models\AffiliateEvent;
use App\Models\Partner;
use App\Models\Payout;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAffiliatePayouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'affiliate:process-payouts
                            {--dry-run : Show what would be paid without creating payouts}
                            {--month= : Process specific month (YYYY-MM, default: last month)}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process monthly affiliate payouts for eligible partners';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting affiliate payout processing...');

        // Determine target month
        $targetMonth = $this->option('month')
            ? Carbon::parse($this->option('month').'-01')
            : Carbon::now()->subMonth();

        $monthRef = $targetMonth->format('Y-m');
        $this->info("Processing payouts for month: {$monthRef}");

        // Calculate clawback cutoff date (30 days ago by default)
        $clawbackDays = config('affiliate.clawback_days', 30);
        $clawbackCutoff = Carbon::now()->subDays($clawbackDays);
        $this->info("Only including events created before: {$clawbackCutoff->format('Y-m-d')}");

        // Get minimum payout threshold
        $minPayout = config('affiliate.payout_min', 100.00);
        $this->info("Minimum payout threshold: €{$minPayout}");

        // Find eligible partners with pending events
        $eligiblePartners = $this->findEligiblePartners($clawbackCutoff, $minPayout);

        if ($eligiblePartners->isEmpty()) {
            $this->info('No partners eligible for payout this month.');

            return 0;
        }

        $this->info("Found {$eligiblePartners->count()} eligible partner(s) for payout.");
        $this->newLine();

        // Display payout table
        $this->displayPayoutTable($eligiblePartners);

        // Calculate totals
        $totalAmount = $eligiblePartners->sum('pending_amount');
        $totalEvents = $eligiblePartners->sum('pending_events');

        $this->newLine();
        $this->info("Total payouts: {$eligiblePartners->count()}");
        $this->info('Total amount: €'.number_format($totalAmount, 2));
        $this->info("Total events: {$totalEvents}");
        $this->newLine();

        // Handle dry-run mode
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No payouts will be created.');

            return 0;
        }

        // Confirm before processing (unless --force)
        if (! $this->option('force')) {
            if (! $this->confirm('Do you want to proceed with creating these payouts?')) {
                $this->warn('Payout processing cancelled.');

                return 0;
            }
        }

        // Process payouts
        $result = $this->processPayouts($eligiblePartners, $monthRef);

        // Display results
        $this->newLine();
        $this->info("✓ Payouts created: {$result['created']}");
        $this->info('✓ Total amount: €'.number_format($result['total_amount'], 2));

        if ($result['failed'] > 0) {
            $this->error("✗ Failed: {$result['failed']}");
        }

        $this->info('Payout processing completed successfully.');

        return 0;
    }

    /**
     * Find all partners eligible for payout
     *
     * @return \Illuminate\Support\Collection
     */
    protected function findEligiblePartners(Carbon $clawbackCutoff, float $minPayout)
    {
        return Partner::select([
            'partners.id',
            'partners.name',
            'partners.email',
            'partners.is_active',
            DB::raw('SUM(affiliate_events.amount) as pending_amount'),
            DB::raw('COUNT(affiliate_events.id) as pending_events'),
        ])
            ->join('affiliate_events', 'partners.id', '=', 'affiliate_events.affiliate_partner_id')
            ->whereNull('affiliate_events.paid_at')
            ->where('affiliate_events.is_clawed_back', false)
            ->where('affiliate_events.created_at', '<=', $clawbackCutoff)
            ->where('partners.is_active', true)
            ->groupBy('partners.id', 'partners.name', 'partners.email', 'partners.is_active')
            ->havingRaw('SUM(affiliate_events.amount) >= ?', [$minPayout])
            ->get();
    }

    /**
     * Display table of pending payouts
     *
     * @param  \Illuminate\Support\Collection  $partners
     * @return void
     */
    protected function displayPayoutTable($partners)
    {
        $headers = ['Partner Name', 'Email', 'Amount (EUR)', 'Event Count'];

        $rows = $partners->map(function ($partner) {
            return [
                $partner->name,
                $partner->email,
                '€'.number_format($partner->pending_amount, 2),
                $partner->pending_events,
            ];
        })->toArray();

        $this->table($headers, $rows);
    }

    /**
     * Process payouts for all eligible partners
     *
     * @param  \Illuminate\Support\Collection  $partners
     */
    protected function processPayouts($partners, string $monthRef): array
    {
        $created = 0;
        $failed = 0;
        $totalAmount = 0;

        $clawbackDays = config('affiliate.clawback_days', 30);
        $clawbackCutoff = Carbon::now()->subDays($clawbackDays);

        foreach ($partners as $partner) {
            try {
                DB::beginTransaction();

                // Get all unpaid events for this partner (respecting clawback period)
                $events = AffiliateEvent::where('affiliate_partner_id', $partner->id)
                    ->whereNull('paid_at')
                    ->where('is_clawed_back', false)
                    ->where('created_at', '<=', $clawbackCutoff)
                    ->get();

                if ($events->isEmpty()) {
                    $this->warn("No events found for partner {$partner->name} (ID: {$partner->id})");
                    DB::rollBack();

                    continue;
                }

                $amount = $events->sum('amount');

                // Create payout record
                $payout = Payout::create([
                    'partner_id' => $partner->id,
                    'amount' => $amount,
                    'status' => 'pending',
                    'payout_date' => Carbon::now()->addDays(config('affiliate.payout_day', 5)),
                    'payment_method' => null, // To be set by admin
                    'details' => [
                        'month_ref' => $monthRef,
                        'event_count' => $events->count(),
                        'processed_by_command' => true,
                        'clawback_cutoff' => $clawbackCutoff->toDateString(),
                    ],
                ]);

                // Mark all events as paid and link to payout
                foreach ($events as $event) {
                    $event->markAsPaid($payout);
                }

                DB::commit();

                $created++;
                $totalAmount += $amount;

                $this->info("✓ Created payout #{$payout->id} for {$partner->name}: €".number_format($amount, 2));

                Log::info('Affiliate payout created', [
                    'payout_id' => $payout->id,
                    'partner_id' => $partner->id,
                    'partner_name' => $partner->name,
                    'amount' => $amount,
                    'event_count' => $events->count(),
                    'month_ref' => $monthRef,
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                $failed++;

                $this->error("✗ Failed to create payout for {$partner->name}: {$e->getMessage()}");

                Log::error('Failed to create affiliate payout', [
                    'partner_id' => $partner->id,
                    'partner_name' => $partner->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return [
            'created' => $created,
            'failed' => $failed,
            'total_amount' => $totalAmount,
        ];
    }
}

// CLAUDE-CHECKPOINT
