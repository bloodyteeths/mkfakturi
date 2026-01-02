<?php

namespace Modules\Mk\Bitrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Bitrix\Controllers\PartnerTriggerController;
use Modules\Mk\Bitrix\Services\HubSpotApiClient;

/**
 * HubSpotGeneratePartnerLinksCommand
 *
 * Generates signed URLs for creating partners from HubSpot deals.
 * These URLs can be clicked directly from HubSpot deal notes.
 *
 * Usage:
 *   php artisan hubspot:generate-partner-links
 *   php artisan hubspot:generate-partner-links --stage=interested
 *   php artisan hubspot:generate-partner-links --log-to-hubspot
 */
class HubSpotGeneratePartnerLinksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hubspot:generate-partner-links
                            {--stage=interested : Deal stage to filter by (interested, clicked, etc.)}
                            {--log-to-hubspot : Log the URLs as notes in HubSpot deals}
                            {--limit=50 : Maximum number of deals to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate signed partner creation URLs for HubSpot deals without partners';

    /**
     * HubSpot API client.
     */
    protected HubSpotApiClient $hubspot;

    /**
     * Create a new command instance.
     *
     * @param HubSpotApiClient $hubspot
     */
    public function __construct(HubSpotApiClient $hubspot)
    {
        parent::__construct();
        $this->hubspot = $hubspot;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $stage = $this->option('stage');
        $logToHubSpot = $this->option('log-to-hubspot');
        $limit = (int) $this->option('limit');

        $this->info('HubSpot Partner Link Generator');
        $this->line('==============================');
        $this->newLine();

        // Check if HubSpot is configured
        $accessToken = config('hubspot.access_token');
        if (empty($accessToken)) {
            $this->error('HubSpot access token not configured. Set HUBSPOT_ACCESS_TOKEN in .env');
            return self::FAILURE;
        }

        $this->info("Stage filter: {$stage}");
        $this->info("Log to HubSpot: " . ($logToHubSpot ? 'Yes' : 'No'));
        $this->info("Max deals: {$limit}");
        $this->newLine();

        // Get deals in the specified stage without partner_id
        $stageId = $this->hubspot->getStageId($stage);

        if (!$stageId) {
            $this->warn("Unknown stage '{$stage}'. Using as literal stage ID.");
            $stageId = $stage;
        }

        $this->info("Looking for deals in stage: {$stageId}");
        $this->newLine();

        try {
            $deals = $this->hubspot->searchDeals(
                [
                    [
                        'propertyName' => 'dealstage',
                        'operator' => 'EQ',
                        'value' => $stageId,
                    ],
                ],
                ['dealname', 'facturino_lead_id', 'facturino_partner_id', 'dealstage'],
                $limit
            );

            // Filter out deals that already have a partner
            $dealsWithoutPartner = array_filter($deals, function ($deal) {
                $properties = $deal['properties'] ?? [];
                return empty($properties['facturino_partner_id']);
            });

            $count = count($dealsWithoutPartner);

            if ($count === 0) {
                $this->info('No deals found in this stage without a partner.');
                return self::SUCCESS;
            }

            $this->info("Found {$count} deals without partner_id.");
            $this->newLine();

            // Display table header
            $this->line(str_repeat('-', 100));
            $this->line(sprintf('%-15s %-35s %-50s', 'Deal ID', 'Deal Name', 'Partner Creation URL'));
            $this->line(str_repeat('-', 100));

            $linksGenerated = 0;
            $linksLogged = 0;

            foreach ($dealsWithoutPartner as $deal) {
                $dealId = $deal['id'];
                $properties = $deal['properties'] ?? [];
                $dealName = $properties['dealname'] ?? 'Unnamed Deal';
                $leadId = $properties['facturino_lead_id'] ?? null;

                // Only generate link if deal has a lead_id
                if (!$leadId) {
                    $this->line(sprintf(
                        '%-15s %-35s %-50s',
                        $dealId,
                        $this->truncate($dealName, 33),
                        '(No facturino_lead_id - skipped)'
                    ));
                    continue;
                }

                // Generate signed URL
                $url = PartnerTriggerController::generateCreatePartnerUrl($dealId);
                $linksGenerated++;

                $this->line(sprintf(
                    '%-15s %-35s %-50s',
                    $dealId,
                    $this->truncate($dealName, 33),
                    $this->truncate($url, 48)
                ));

                // Log to HubSpot if requested
                if ($logToHubSpot) {
                    $contactId = $this->hubspot->getContactIdForDeal($dealId);

                    if ($contactId) {
                        $noteBody = "Create Partner Link (click to create partner account):\n{$url}";
                        $result = $this->hubspot->logNote($contactId, $noteBody);

                        if ($result) {
                            $linksLogged++;
                        }
                    }
                }
            }

            $this->line(str_repeat('-', 100));
            $this->newLine();

            $this->info("Links generated: {$linksGenerated}");

            if ($logToHubSpot) {
                $this->info("Links logged to HubSpot: {$linksLogged}");
            }

            Log::info('Partner links generated', [
                'stage' => $stage,
                'deals_found' => $count,
                'links_generated' => $linksGenerated,
                'links_logged' => $linksLogged,
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to fetch deals: ' . $e->getMessage());

            Log::error('Partner link generation failed', [
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Truncate a string to a maximum length.
     *
     * @param string $string
     * @param int $maxLength
     * @return string
     */
    protected function truncate(string $string, int $maxLength): string
    {
        if (strlen($string) <= $maxLength) {
            return $string;
        }

        return substr($string, 0, $maxLength - 3) . '...';
    }
}

// CLAUDE-CHECKPOINT
