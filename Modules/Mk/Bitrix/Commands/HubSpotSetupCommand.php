<?php

namespace Modules\Mk\Bitrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Bitrix\Services\HubSpotService;

/**
 * HubSpotSetupCommand
 *
 * Sets up HubSpot CRM with required pipeline and custom properties.
 * Idempotent - skips existing items unless --force is used.
 *
 * Usage:
 *   php artisan hubspot:setup          - Full setup
 *   php artisan hubspot:setup --test   - Dry run, test connection only
 *   php artisan hubspot:setup --force  - Recreate all (delete existing pipeline)
 */
class HubSpotSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hubspot:setup
                            {--test : Dry run - test connection only}
                            {--force : Force recreate pipeline and properties}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up HubSpot CRM with Facturino Partners pipeline and custom properties';

    /**
     * Pipeline name.
     */
    protected const PIPELINE_NAME = 'Facturino Partners';

    /**
     * Pipeline stages definition.
     * Order: label => [displayOrder, probability, closedLost flag]
     *
     * @var array<string, array{0: int, 1: string, 2?: bool}>
     */
    protected array $pipelineStages = [
        'New Lead' => [0, '0', false],
        'Emailed' => [1, '0.2', false],
        'Followup Due' => [2, '0.4', false],
        'Interested' => [3, '0.6', false],
        'Invite Sent' => [4, '0.8', false],
        'Partner Active' => [5, '1', false], // won (closedWon)
        'Lost' => [6, '0', true], // closed lost
    ];

    /**
     * Map of internal stage keys to stage labels for .env output.
     *
     * @var array<string, string>
     */
    protected array $stageKeyMap = [
        'new_lead' => 'New Lead',
        'emailed' => 'Emailed',
        'followup_due' => 'Followup Due',
        'interested' => 'Interested',
        'invite_sent' => 'Invite Sent',
        'partner_active' => 'Partner Active',
        'lost' => 'Lost',
    ];

    /**
     * Custom properties for companies.
     *
     * @var array<string, array{label: string, type: string, group: string}>
     */
    protected array $companyProperties = [
        'fct_source' => [
            'label' => 'Facturino Source',
            'type' => 'string',
            'group' => 'companyinformation',
        ],
        'fct_source_url' => [
            'label' => 'Facturino Source URL',
            'type' => 'string',
            'group' => 'companyinformation',
        ],
        'fct_city' => [
            'label' => 'Facturino City',
            'type' => 'string',
            'group' => 'companyinformation',
        ],
        'fct_phone' => [
            'label' => 'Facturino Phone',
            'type' => 'string',
            'group' => 'companyinformation',
        ],
    ];

    /**
     * Custom properties for deals.
     *
     * @var array<string, array{label: string, type: string, group: string}>
     */
    protected array $dealProperties = [
        'fct_last_touch_date' => [
            'label' => 'Last Touch Date',
            'type' => 'date',
            'group' => 'dealinformation',
        ],
        'fct_next_followup_date' => [
            'label' => 'Next Followup Date',
            'type' => 'date',
            'group' => 'dealinformation',
        ],
        'fct_partner_id' => [
            'label' => 'Facturino Partner ID',
            'type' => 'string',
            'group' => 'dealinformation',
        ],
        // Commission tracking properties
        'fct_total_revenue' => [
            'label' => 'Total Revenue Generated',
            'type' => 'number',
            'group' => 'dealinformation',
        ],
        'fct_commission_rate' => [
            'label' => 'Commission Rate (%)',
            'type' => 'number',
            'group' => 'dealinformation',
        ],
        'fct_commission_earned' => [
            'label' => 'Commission Earned',
            'type' => 'number',
            'group' => 'dealinformation',
        ],
        'fct_commission_paid' => [
            'label' => 'Commission Paid',
            'type' => 'number',
            'group' => 'dealinformation',
        ],
        'fct_commission_pending' => [
            'label' => 'Commission Pending',
            'type' => 'number',
            'group' => 'dealinformation',
        ],
        // Activity tracking properties
        'fct_invoice_count' => [
            'label' => 'Invoice Count',
            'type' => 'number',
            'group' => 'dealinformation',
        ],
        'fct_total_invoiced' => [
            'label' => 'Total Invoiced',
            'type' => 'number',
            'group' => 'dealinformation',
        ],
        'fct_last_login_date' => [
            'label' => 'Last Login',
            'type' => 'date',
            'group' => 'dealinformation',
        ],
        'fct_last_activity_date' => [
            'label' => 'Partner Last Activity',
            'type' => 'date',
            'group' => 'dealinformation',
        ],
        'fct_partner_status' => [
            'label' => 'Partner Status',
            'type' => 'string',
            'group' => 'dealinformation',
        ],
        'fct_health_score' => [
            'label' => 'Health Score (0-100)',
            'type' => 'number',
            'group' => 'dealinformation',
        ],
        'fct_company_count' => [
            'label' => 'Companies Managed',
            'type' => 'number',
            'group' => 'dealinformation',
        ],
        'fct_facturino_url' => [
            'label' => 'View in Facturino',
            'type' => 'string',
            'group' => 'dealinformation',
        ],
        'fct_last_sync_date' => [
            'label' => 'Last Sync Date',
            'type' => 'date',
            'group' => 'dealinformation',
        ],
    ];

    /**
     * Custom properties for contacts (legacy - kept for backwards compatibility).
     *
     * @var array<string, array{label: string, type: string, group: string}>
     */
    protected array $contactProperties = [
        'facturino_lead_id' => [
            'label' => 'Facturino Lead ID',
            'type' => 'string',
            'group' => 'contactinformation',
        ],
        'facturino_source' => [
            'label' => 'Facturino Source',
            'type' => 'string',
            'group' => 'contactinformation',
        ],
        'facturino_source_url' => [
            'label' => 'Facturino Source URL',
            'type' => 'string',
            'group' => 'contactinformation',
        ],
        'facturino_tags' => [
            'label' => 'Facturino Tags',
            'type' => 'string',
            'group' => 'contactinformation',
        ],
        'facturino_last_email_template' => [
            'label' => 'Facturino Last Email Template',
            'type' => 'string',
            'group' => 'contactinformation',
        ],
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(HubSpotService $hubSpotService): int
    {
        $this->info('HubSpot Setup for Facturino');
        $this->line('============================');
        $this->newLine();

        // Check configuration
        if (!$this->checkConfiguration($hubSpotService)) {
            return self::FAILURE;
        }

        // Test connection
        if (!$this->testConnection($hubSpotService)) {
            return self::FAILURE;
        }

        if ($this->option('test')) {
            $this->info('Connection test successful. Use without --test to run full setup.');
            return self::SUCCESS;
        }

        // Create or get pipeline
        $pipelineData = $this->setupPipeline($hubSpotService);

        if (!$pipelineData) {
            $this->error('Failed to set up pipeline. Aborting.');
            return self::FAILURE;
        }

        // Create company properties
        $this->createCompanyProperties($hubSpotService);

        // Create deal properties
        $this->createDealProperties($hubSpotService);

        // Create contact properties (legacy)
        $this->createContactProperties($hubSpotService);

        // Output .env configuration
        $this->outputEnvConfiguration($pipelineData);

        $this->newLine();
        $this->info('HubSpot setup completed successfully!');

        return self::SUCCESS;
    }

    /**
     * Check if configuration is present.
     *
     * @param HubSpotService $hubSpotService
     * @return bool
     */
    protected function checkConfiguration(HubSpotService $hubSpotService): bool
    {
        if (!$hubSpotService->isConfigured()) {
            $this->error('HUBSPOT_PRIVATE_APP_TOKEN is not configured.');
            $this->line('Please set this in your .env file:');
            $this->line('  HUBSPOT_PRIVATE_APP_TOKEN=pat-na1-xxxxxxxx');
            return false;
        }

        $this->info('Configuration check passed.');
        $this->newLine();

        return true;
    }

    /**
     * Test the API connection.
     *
     * @param HubSpotService $hubSpotService
     * @return bool
     */
    protected function testConnection(HubSpotService $hubSpotService): bool
    {
        $this->line('Testing HubSpot API connection...');

        try {
            $response = $hubSpotService->testConnection();

            $this->info('Connection successful!');
            $this->line('  Portal ID: ' . ($response['portalId'] ?? 'Unknown'));
            $this->line('  UI Domain: ' . ($response['uiDomain'] ?? 'Unknown'));
            $this->newLine();

            return true;

        } catch (\Exception $e) {
            $this->error('Connection failed: ' . $e->getMessage());
            Log::error('HubSpot setup connection failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Set up the pipeline.
     * Uses the existing default pipeline (HubSpot Free only allows 1 pipeline).
     *
     * @param HubSpotService $hubSpotService
     * @return array|null Pipeline data with stages, or null on failure
     */
    protected function setupPipeline(HubSpotService $hubSpotService): ?array
    {
        $this->line('Setting up pipeline stages...');
        $this->line('  (HubSpot Free tier: using existing default pipeline)');

        // Get the default (existing) pipeline - HubSpot Free only allows 1
        $pipeline = $hubSpotService->getDefaultPipeline('deals');

        if (!$pipeline) {
            $this->error('  [FAIL] No deal pipeline found in HubSpot');
            return null;
        }

        $pipelineId = $pipeline['id'];
        $pipelineLabel = $pipeline['label'] ?? 'Unknown';
        $existingStages = $pipeline['stages'] ?? [];

        $this->info("  Found pipeline: \"{$pipelineLabel}\" (ID: {$pipelineId})");
        $this->line('  Existing stages: ' . count($existingStages));

        // Map existing stages by label (case-insensitive)
        $existingByLabel = [];
        foreach ($existingStages as $stage) {
            $label = strtolower($stage['label'] ?? '');
            $existingByLabel[$label] = $stage;
        }

        // Create or update stages
        $stageIds = [];
        $created = 0;
        $skipped = 0;

        foreach ($this->pipelineStages as $label => [$displayOrder, $probability, $isClosed]) {
            $labelLower = strtolower($label);

            // Check if stage already exists
            if (isset($existingByLabel[$labelLower])) {
                $stageId = $existingByLabel[$labelLower]['id'];
                $this->line("  [SKIP] Stage \"{$label}\" exists (ID: {$stageId})");
                $stageIds[$label] = $stageId;
                $skipped++;
                continue;
            }

            // Create new stage
            $stageData = [
                'label' => $label,
                'displayOrder' => $displayOrder,
                'metadata' => [
                    'probability' => $probability,
                ],
            ];

            if ($isClosed === true) {
                $stageData['metadata']['isClosed'] = 'true';
            }

            $stageId = $hubSpotService->createPipelineStage($pipelineId, $stageData);

            if ($stageId) {
                $this->info("  [OK] Created stage \"{$label}\" (ID: {$stageId})");
                $stageIds[$label] = $stageId;
                $created++;
            } else {
                $this->error("  [FAIL] Could not create stage \"{$label}\"");
            }
        }

        $this->newLine();
        $this->line("Stages: {$created} created, {$skipped} already exist");
        $this->newLine();

        // Refresh pipeline to get all stage IDs
        $updatedPipeline = $hubSpotService->getDefaultPipeline('deals');
        if (!$updatedPipeline) {
            return null;
        }

        return [
            'pipeline_id' => $pipelineId,
            'stages' => $this->mapStagesToIds($updatedPipeline['stages'] ?? []),
        ];
    }

    /**
     * Map stage labels to IDs.
     *
     * @param array $stages Stages from HubSpot API
     * @return array Map of internal key => stage ID
     */
    protected function mapStagesToIds(array $stages): array
    {
        $stageMap = [];

        foreach ($stages as $stage) {
            $label = $stage['label'] ?? '';
            $id = $stage['id'] ?? '';

            // Find matching internal key
            foreach ($this->stageKeyMap as $key => $expectedLabel) {
                if (strcasecmp($label, $expectedLabel) === 0) {
                    $stageMap[$key] = $id;
                    break;
                }
            }
        }

        return $stageMap;
    }

    /**
     * Create custom properties for companies.
     *
     * @param HubSpotService $hubSpotService
     * @return void
     */
    protected function createCompanyProperties(HubSpotService $hubSpotService): void
    {
        $this->line('Creating company custom properties...');

        $force = $this->option('force');
        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->companyProperties as $propertyName => $config) {
            // Check if already exists
            if (!$force && $hubSpotService->propertyExists('companies', $propertyName)) {
                $this->line("  [SKIP] {$propertyName} (already exists)");
                $skipped++;
                continue;
            }

            $success = $hubSpotService->createProperty(
                'companies',
                $propertyName,
                $config['label'],
                $config['type'],
                $config['group']
            );

            if ($success) {
                $this->info("  [OK] {$propertyName}");
                $created++;
            } else {
                // Check if it's because property already exists
                if ($hubSpotService->propertyExists('companies', $propertyName)) {
                    $this->line("  [SKIP] {$propertyName} (already exists)");
                    $skipped++;
                } else {
                    $this->error("  [FAIL] {$propertyName}");
                    $failed++;
                }
            }
        }

        $this->newLine();
        $this->line("Company properties: {$created} created, {$skipped} skipped, {$failed} failed");
        $this->newLine();
    }

    /**
     * Create custom properties for deals.
     *
     * @param HubSpotService $hubSpotService
     * @return void
     */
    protected function createDealProperties(HubSpotService $hubSpotService): void
    {
        $this->line('Creating deal custom properties...');

        $force = $this->option('force');
        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->dealProperties as $propertyName => $config) {
            // Check if already exists
            if (!$force && $hubSpotService->propertyExists('deals', $propertyName)) {
                $this->line("  [SKIP] {$propertyName} (already exists)");
                $skipped++;
                continue;
            }

            $success = $hubSpotService->createProperty(
                'deals',
                $propertyName,
                $config['label'],
                $config['type'],
                $config['group']
            );

            if ($success) {
                $this->info("  [OK] {$propertyName}");
                $created++;
            } else {
                // Check if it's because property already exists
                if ($hubSpotService->propertyExists('deals', $propertyName)) {
                    $this->line("  [SKIP] {$propertyName} (already exists)");
                    $skipped++;
                } else {
                    $this->error("  [FAIL] {$propertyName}");
                    $failed++;
                }
            }
        }

        $this->newLine();
        $this->line("Deal properties: {$created} created, {$skipped} skipped, {$failed} failed");
        $this->newLine();
    }

    /**
     * Create custom properties for contacts (legacy).
     *
     * @param HubSpotService $hubSpotService
     * @return void
     */
    protected function createContactProperties(HubSpotService $hubSpotService): void
    {
        $this->line('Creating contact custom properties...');

        $force = $this->option('force');
        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->contactProperties as $propertyName => $config) {
            // Check if already exists
            if (!$force && $hubSpotService->propertyExists('contacts', $propertyName)) {
                $this->line("  [SKIP] {$propertyName} (already exists)");
                $skipped++;
                continue;
            }

            $success = $hubSpotService->createProperty(
                'contacts',
                $propertyName,
                $config['label'],
                $config['type'],
                $config['group']
            );

            if ($success) {
                $this->info("  [OK] {$propertyName}");
                $created++;
            } else {
                // Check if it's because property already exists
                if ($hubSpotService->propertyExists('contacts', $propertyName)) {
                    $this->line("  [SKIP] {$propertyName} (already exists)");
                    $skipped++;
                } else {
                    $this->error("  [FAIL] {$propertyName}");
                    $failed++;
                }
            }
        }

        $this->newLine();
        $this->line("Contact properties: {$created} created, {$skipped} skipped, {$failed} failed");
        $this->newLine();
    }

    /**
     * Output .env configuration for pipeline and stages.
     *
     * @param array $pipelineData Pipeline data with stages
     * @return void
     */
    protected function outputEnvConfiguration(array $pipelineData): void
    {
        $this->newLine();
        $this->info('========================================');
        $this->info('Add these to your .env file:');
        $this->info('========================================');
        $this->newLine();

        $this->line('# HubSpot Pipeline Configuration');
        $this->line("HUBSPOT_PIPELINE_ID={$pipelineData['pipeline_id']}");

        $stages = $pipelineData['stages'] ?? [];

        if (!empty($stages['new_lead'])) {
            $this->line("HUBSPOT_STAGE_NEW_LEAD={$stages['new_lead']}");
        }
        if (!empty($stages['emailed'])) {
            $this->line("HUBSPOT_STAGE_EMAILED={$stages['emailed']}");
        }
        if (!empty($stages['followup_due'])) {
            $this->line("HUBSPOT_STAGE_FOLLOWUP_DUE={$stages['followup_due']}");
        }
        if (!empty($stages['interested'])) {
            $this->line("HUBSPOT_STAGE_INTERESTED={$stages['interested']}");
        }
        if (!empty($stages['invite_sent'])) {
            $this->line("HUBSPOT_STAGE_INVITE_SENT={$stages['invite_sent']}");
        }
        if (!empty($stages['partner_active'])) {
            $this->line("HUBSPOT_STAGE_PARTNER_ACTIVE={$stages['partner_active']}");
        }
        if (!empty($stages['lost'])) {
            $this->line("HUBSPOT_STAGE_LOST={$stages['lost']}");
        }

        $this->newLine();
        $this->info('========================================');
    }
}

// CLAUDE-CHECKPOINT
