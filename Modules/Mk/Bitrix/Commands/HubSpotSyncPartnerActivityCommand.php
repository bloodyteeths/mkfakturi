<?php

namespace Modules\Mk\Bitrix\Commands;

use App\Models\Partner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Bitrix\Models\HubSpotLeadMap;
use Modules\Mk\Bitrix\Models\OutreachLead;
use Modules\Mk\Bitrix\Services\HubSpotService;
use Modules\Mk\Bitrix\Services\PartnerMetricsService;

/**
 * HubSpotSyncPartnerActivityCommand
 *
 * Syncs partner activity and commission data to HubSpot deals.
 * Updates deal properties with real-time metrics from Facturino.
 *
 * Usage:
 *   php artisan hubspot:sync-partner-activity
 *   php artisan hubspot:sync-partner-activity --partner=123
 *   php artisan hubspot:sync-partner-activity --dry-run
 *   php artisan hubspot:sync-partner-activity --force
 */
class HubSpotSyncPartnerActivityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hubspot:sync-partner-activity
                            {--partner= : Sync specific partner ID only}
                            {--dry-run : Show what would be synced without updating}
                            {--force : Sync all even if recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync partner activity and commission data to HubSpot deals';

    /**
     * Rate limit delay between API calls (milliseconds).
     */
    protected const API_DELAY_MS = 200;

    /**
     * Minimum hours since last sync (unless --force is used).
     */
    protected const SYNC_THRESHOLD_HOURS = 4;

    /**
     * Execute the console command.
     *
     * @param HubSpotService $hubSpotService
     * @param PartnerMetricsService $metricsService
     * @return int
     */
    public function handle(HubSpotService $hubSpotService, PartnerMetricsService $metricsService): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $specificPartnerId = $this->option('partner');

        $this->info('HubSpot Partner Activity Sync');
        $this->line('==============================');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to HubSpot');
            $this->newLine();
        }

        // Check HubSpot configuration
        if (!$hubSpotService->isConfigured()) {
            $this->error('HubSpot is not configured. Set HUBSPOT_ACCESS_TOKEN in .env');
            return self::FAILURE;
        }

        // Get mappings to sync
        $mappings = $this->getMappingsToSync($specificPartnerId, $force);

        if ($mappings->isEmpty()) {
            $this->info('No mappings found that need syncing.');
            return self::SUCCESS;
        }

        $this->info("Found {$mappings->count()} mapping(s) to sync.");
        $this->newLine();

        // Initialize statistics
        $stats = [
            'processed' => 0,
            'synced' => 0,
            'skipped_no_partner' => 0,
            'skipped_recently_synced' => 0,
            'errors' => [],
        ];

        // Create progress bar
        $progressBar = $this->output->createProgressBar($mappings->count());
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $progressBar->start();

        foreach ($mappings as $mapping) {
            $progressBar->setMessage("Processing: {$mapping->email}");
            $result = $this->syncMapping($mapping, $hubSpotService, $metricsService, $dryRun, $force);

            // Update statistics
            $stats['processed']++;
            if (isset($result['synced']) && $result['synced']) {
                $stats['synced']++;
            }
            if (isset($result['skipped_no_partner']) && $result['skipped_no_partner']) {
                $stats['skipped_no_partner']++;
            }
            if (isset($result['skipped_recently_synced']) && $result['skipped_recently_synced']) {
                $stats['skipped_recently_synced']++;
            }
            if (isset($result['error'])) {
                $stats['errors'][] = $result['error'];
            }

            $progressBar->advance();

            // Rate limiting delay between API calls
            if (!$dryRun) {
                usleep(self::API_DELAY_MS * 1000);
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->displaySummary($stats);

        return empty($stats['errors']) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Get HubSpot mappings that need syncing.
     *
     * @param string|null $specificPartnerId
     * @param bool $force
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getMappingsToSync(?string $specificPartnerId, bool $force)
    {
        $query = HubSpotLeadMap::query()
            ->whereNotNull('hubspot_deal_id');

        // If specific partner requested, find via outreach_lead
        if ($specificPartnerId) {
            $outreachLeadIds = OutreachLead::where('partner_id', $specificPartnerId)
                ->pluck('id');

            $query->whereIn('outreach_lead_id', $outreachLeadIds);
        }

        // Unless force, only get those needing sync
        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<', now()->subHours(self::SYNC_THRESHOLD_HOURS));
            });
        }

        return $query->get();
    }

    /**
     * Sync a single mapping to HubSpot.
     *
     * @param HubSpotLeadMap $mapping
     * @param HubSpotService $hubSpotService
     * @param PartnerMetricsService $metricsService
     * @param bool $dryRun
     * @param bool $force
     * @return array
     */
    protected function syncMapping(
        HubSpotLeadMap $mapping,
        HubSpotService $hubSpotService,
        PartnerMetricsService $metricsService,
        bool $dryRun,
        bool $force
    ): array {
        // Find partner via outreach_lead
        $partner = $this->findPartnerForMapping($mapping);

        if (!$partner) {
            return ['skipped_no_partner' => true];
        }

        // Check if recently synced (unless force)
        if (!$force && $mapping->last_synced_at) {
            $hoursSinceSync = $mapping->last_synced_at->diffInHours(now());
            if ($hoursSinceSync < self::SYNC_THRESHOLD_HOURS) {
                return ['skipped_recently_synced' => true];
            }
        }

        try {
            // Get metrics for this partner
            $metrics = $metricsService->getMetrics($partner);

            // Build HubSpot deal properties
            $properties = $this->buildDealProperties($metrics, $partner);

            if ($dryRun) {
                $this->logDryRun($mapping, $partner, $properties);
                return ['synced' => true];
            }

            // Update HubSpot deal
            $success = $hubSpotService->updateDeal($mapping->hubspot_deal_id, $properties);

            if ($success) {
                // Update last_synced_at
                $mapping->update(['last_synced_at' => now()]);

                Log::info('Partner activity synced to HubSpot', [
                    'partner_id' => $partner->id,
                    'hubspot_deal_id' => $mapping->hubspot_deal_id,
                    'properties_count' => count($properties),
                ]);

                return ['synced' => true];
            }

            return ['error' => "Failed to update deal {$mapping->hubspot_deal_id}"];

        } catch (\Exception $e) {
            Log::error('HubSpot partner activity sync failed', [
                'mapping_id' => $mapping->id,
                'hubspot_deal_id' => $mapping->hubspot_deal_id,
                'error' => $e->getMessage(),
            ]);

            return ['error' => "{$mapping->email}: {$e->getMessage()}"];
        }
    }

    /**
     * Find partner for a HubSpot mapping.
     *
     * @param HubSpotLeadMap $mapping
     * @return Partner|null
     */
    protected function findPartnerForMapping(HubSpotLeadMap $mapping): ?Partner
    {
        // First try via outreach_lead
        if ($mapping->outreach_lead_id) {
            $outreachLead = OutreachLead::find($mapping->outreach_lead_id);
            if ($outreachLead && $outreachLead->partner_id) {
                return Partner::find($outreachLead->partner_id);
            }
        }

        // Fall back to email lookup
        return Partner::where('email', $mapping->email)->first();
    }

    /**
     * Build deal properties array for HubSpot update.
     *
     * @param array $metrics
     * @param Partner $partner
     * @return array
     */
    protected function buildDealProperties(array $metrics, Partner $partner): array
    {
        $properties = [
            // Commission data
            'fct_total_revenue' => round($metrics['total_revenue'], 2),
            'fct_commission_rate' => round($metrics['commission_rate'], 2),
            'fct_commission_earned' => round($metrics['commission_earned'], 2),
            'fct_commission_paid' => round($metrics['commission_paid'], 2),
            'fct_commission_pending' => round($metrics['commission_pending'], 2),

            // Activity data
            'fct_invoice_count' => $metrics['invoice_count'],
            'fct_total_invoiced' => round($metrics['total_invoiced'], 2),

            // Status data
            'fct_partner_status' => $metrics['partner_status'],
            'fct_health_score' => $metrics['health_score'],
            'fct_company_count' => $metrics['company_count'],

            // Links and sync info
            'fct_facturino_url' => $metrics['facturino_url'],
            'fct_last_sync_date' => now()->format('Y-m-d'),

            // Partner ID reference
            'fct_partner_id' => (string) $partner->id,
        ];

        // Add dates if available (HubSpot expects Y-m-d format for date fields)
        if ($metrics['last_login_date']) {
            $properties['fct_last_login_date'] = $metrics['last_login_date'];
        }

        if ($metrics['last_activity_date']) {
            $properties['fct_last_activity_date'] = $metrics['last_activity_date'];
        }

        return $properties;
    }

    /**
     * Log dry run details.
     *
     * @param HubSpotLeadMap $mapping
     * @param Partner $partner
     * @param array $properties
     * @return void
     */
    protected function logDryRun(HubSpotLeadMap $mapping, Partner $partner, array $properties): void
    {
        $this->newLine();
        $this->line("  [DRY-RUN] Would update deal {$mapping->hubspot_deal_id}");
        $this->line("    Partner: {$partner->name} (ID: {$partner->id})");
        $this->line("    Email: {$mapping->email}");
        $this->line("    Properties:");

        foreach ($properties as $key => $value) {
            $displayValue = is_numeric($value) ? number_format($value, 2) : $value;
            $this->line("      {$key}: {$displayValue}");
        }
    }

    /**
     * Display sync summary.
     *
     * @param array $stats
     * @return void
     */
    protected function displaySummary(array $stats): void
    {
        $this->info('Sync Summary');
        $this->line('------------');
        $this->line("Processed: {$stats['processed']}");
        $this->line("Successfully synced: {$stats['synced']}");
        $this->line("Skipped (no partner): {$stats['skipped_no_partner']}");
        $this->line("Skipped (recently synced): {$stats['skipped_recently_synced']}");

        if (!empty($stats['errors'])) {
            $this->newLine();
            $this->error("Errors (" . count($stats['errors']) . "):");
            foreach ($stats['errors'] as $error) {
                $this->line("  - {$error}");
            }
        }

        $this->newLine();
        if (empty($stats['errors'])) {
            $this->info('Partner activity sync completed successfully.');
        } else {
            $this->warn('Partner activity sync completed with errors.');
        }
    }
}

// CLAUDE-CHECKPOINT
