<?php

namespace App\Console\Commands;

use App\Models\AffiliateEvent;
use App\Models\Partner;
use App\Models\Payout;
use App\Notifications\PayoutCalculated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculatePayouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payouts:calculate
                            {--month= : Calculate payouts for specific month (YYYY-MM), defaults to previous month}
                            {--dry-run : Simulate payout calculation without creating records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and create monthly payouts for verified partners (runs on 5th of each month)';

    /**
     * Minimum payout threshold (€100)
     */
    protected float $minimumThreshold = 100.00;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Determine month to process (previous month by default)
        $monthRef = $this->option('month') ?? now()->subMonth()->format('Y-m');

        $this->info("Calculating payouts for month: {$monthRef}");
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No records will be created');
        }

        $payoutsCreated = 0;
        $totalAmount = 0;
        $eligiblePartners = 0;
        $belowThresholdPartners = 0;

        // Get all verified partners
        $partners = Partner::where('kyc_status', 'verified')
            ->where('is_active', true)
            ->get();

        $this->info("Found {$partners->count()} verified partners");

        foreach ($partners as $partner) {
            // Calculate total unpaid commissions for this partner in this month
            $unpaidEvents = AffiliateEvent::where('affiliate_partner_id', $partner->id)
                ->where('month_ref', $monthRef)
                ->whereNull('paid_at')
                ->whereNull('payout_id')
                ->where('is_clawed_back', false)
                ->get();

            if ($unpaidEvents->isEmpty()) {
                continue;
            }

            $totalCommission = $unpaidEvents->sum('amount');
            $eligiblePartners++;

            $this->line("Partner {$partner->name}: €{$totalCommission}");

            // Check minimum threshold
            if ($totalCommission < $this->minimumThreshold) {
                $this->warn("  Below €{$this->minimumThreshold} threshold - skipping");
                $belowThresholdPartners++;

                continue;
            }

            // Validate bank account details
            if (! $this->validateBankDetails($partner)) {
                $this->error('  Missing bank details - skipping');

                continue;
            }

            if (! $dryRun) {
                // Create payout record
                DB::beginTransaction();

                try {
                    $payout = Payout::create([
                        'partner_id' => $partner->id,
                        'amount' => $totalCommission,
                        'status' => 'pending',
                        'payout_date' => now()->startOfMonth()->addDays(4), // 5th of current month
                        'payment_method' => 'bank_transfer',
                        'details' => [
                            'month_ref' => $monthRef,
                            'event_count' => $unpaidEvents->count(),
                            'events' => $unpaidEvents->pluck('id')->toArray(),
                        ],
                    ]);

                    // Mark events as paid
                    foreach ($unpaidEvents as $event) {
                        $event->paid_at = now();
                        $event->payout_id = $payout->id;
                        $event->save();
                    }

                    // Send notification to partner
                    if ($partner->user) {
                        $partner->user->notify(new PayoutCalculated($payout, $partner));
                    }

                    DB::commit();

                    $this->info("  ✓ Payout created: #{$payout->id}");
                    $payoutsCreated++;
                    $totalAmount += $totalCommission;

                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("  Failed to create payout: {$e->getMessage()}");
                    Log::error('Payout creation failed', [
                        'partner_id' => $partner->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                // Dry run - just log
                $this->info("  Would create payout: €{$totalCommission}");
                $payoutsCreated++;
                $totalAmount += $totalCommission;
            }
        }

        // Generate CSV for bank transfer (only if not dry run)
        if (! $dryRun && $payoutsCreated > 0) {
            $csvPath = $this->generatePayoutCsv($monthRef);
            $this->info("\nCSV generated: {$csvPath}");
        }

        // Summary
        $this->newLine();
        $this->info('=== Payout Summary ===');
        $this->info("Month: {$monthRef}");
        $this->info("Verified partners: {$partners->count()}");
        $this->info("Eligible partners: {$eligiblePartners}");
        $this->info("Below threshold: {$belowThresholdPartners}");
        $this->info("Payouts created: {$payoutsCreated}");
        $this->info('Total amount: €'.number_format($totalAmount, 2));

        Log::info('Payouts calculated', [
            'month_ref' => $monthRef,
            'payouts_created' => $payoutsCreated,
            'total_amount' => $totalAmount,
            'dry_run' => $dryRun,
        ]);

        return 0;
    }

    /**
     * Validate partner bank details
     */
    protected function validateBankDetails(Partner $partner): bool
    {
        if (empty($partner->bank_account)) {
            return false;
        }

        if (empty($partner->name)) {
            return false;
        }

        return true;
    }

    /**
     * Generate CSV file for bank transfer
     *
     * @return string CSV file path
     */
    protected function generatePayoutCsv(string $monthRef): string
    {
        $payouts = Payout::with('partner')
            ->where('status', 'pending')
            ->whereDate('payout_date', '>=', now()->startOfMonth())
            ->whereDate('payout_date', '<=', now()->endOfMonth())
            ->get();

        $csvFilename = "payouts-{$monthRef}.csv";
        $csvPath = storage_path("app/payouts/{$csvFilename}");

        // Ensure directory exists
        if (! file_exists(storage_path('app/payouts'))) {
            mkdir(storage_path('app/payouts'), 0755, true);
        }

        $csv = fopen($csvPath, 'w');

        // CSV Headers
        fputcsv($csv, [
            'Partner Name',
            'IBAN',
            'Amount (EUR)',
            'Reference',
            'Payout ID',
            'Month',
        ]);

        foreach ($payouts as $payout) {
            fputcsv($csv, [
                $payout->partner->name,
                $payout->partner->bank_account,
                number_format($payout->amount, 2, '.', ''),
                "PAYOUT-{$monthRef}-".str_pad($payout->id, 5, '0', STR_PAD_LEFT),
                $payout->id,
                $monthRef,
            ]);
        }

        fclose($csv);

        return $csvPath;
    }
}

// CLAUDE-CHECKPOINT
