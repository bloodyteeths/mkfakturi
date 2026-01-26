<?php

namespace Modules\Mk\Bitrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Bitrix\Services\Bitrix24ApiClient;

/**
 * BitrixSetupCommand
 *
 * Sets up Bitrix24 CRM with required custom fields and lead statuses.
 * Idempotent - skips existing fields and statuses unless --force is used.
 *
 * Usage:
 *   php artisan bitrix:setup          - Full setup
 *   php artisan bitrix:setup --test   - Dry run, test connection only
 *   php artisan bitrix:setup --force  - Recreate all fields and statuses
 */
class BitrixSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitrix:setup
                            {--test : Dry run - test connection only}
                            {--force : Force recreate all fields and statuses}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up Bitrix24 CRM with custom fields and lead statuses for Facturino outreach';

    /**
     * Custom user fields to create.
     *
     * @var array<string, array{type: string, label: string}>
     */
    protected array $customFields = [
        'FCT_SOURCE' => [
            'type' => 'string',
            'label' => 'Facturino Source',
        ],
        'FCT_SOURCE_URL' => [
            'type' => 'url',
            'label' => 'Facturino Source URL',
        ],
        'FCT_CITY' => [
            'type' => 'string',
            'label' => 'City',
        ],
        'FCT_TAGS' => [
            'type' => 'string',
            'label' => 'Tags',
        ],
        'FCT_FACTURINO_PARTNER_ID' => [
            'type' => 'integer',
            'label' => 'Facturino Partner ID',
        ],
        'FCT_LAST_POSTMARK_MESSAGE_ID' => [
            'type' => 'string',
            'label' => 'Last Postmark Message ID',
        ],
    ];

    /**
     * Lead statuses to create.
     *
     * @var array<string, array{name: string, sort: int}>
     */
    protected array $leadStatuses = [
        'NEW' => [
            'name' => 'New',
            'sort' => 10,
        ],
        'UC_EMAILED' => [
            'name' => 'Emailed',
            'sort' => 20,
        ],
        'UC_FOLLOWUP' => [
            'name' => 'Follow-up',
            'sort' => 30,
        ],
        'UC_INTERESTED' => [
            'name' => 'Interested',
            'sort' => 40,
        ],
        'UC_INVITE_SENT' => [
            'name' => 'Invite Sent',
            'sort' => 50,
        ],
        'UC_PARTNER_CREATED' => [
            'name' => 'Partner Created',
            'sort' => 60,
        ],
        'UC_ACTIVE' => [
            'name' => 'Active',
            'sort' => 70,
        ],
        'JUNK' => [
            'name' => 'Lost',
            'sort' => 80,
        ],
    ];

    /**
     * Existing user fields in Bitrix24.
     */
    protected array $existingFields = [];

    /**
     * Existing lead statuses in Bitrix24.
     */
    protected array $existingStatuses = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Bitrix24ApiClient $bitrixClient): int
    {
        $this->info('Bitrix24 Setup for Facturino');
        $this->line('============================');
        $this->newLine();

        // Check configuration
        if (!$this->checkConfiguration()) {
            return self::FAILURE;
        }

        // Test connection
        if (!$this->testConnection($bitrixClient)) {
            return self::FAILURE;
        }

        if ($this->option('test')) {
            $this->info('Connection test successful. Use without --test to run full setup.');
            return self::SUCCESS;
        }

        // Load existing fields and statuses
        $this->loadExistingData($bitrixClient);

        // Create custom fields
        $this->createCustomFields($bitrixClient);

        // Create lead statuses
        $this->createLeadStatuses($bitrixClient);

        $this->newLine();
        $this->info('Bitrix24 setup completed successfully!');

        return self::SUCCESS;
    }

    /**
     * Check if configuration is present.
     *
     * @return bool
     */
    protected function checkConfiguration(): bool
    {
        $webhookUrl = config('bitrix.webhook_base_url');

        if (empty($webhookUrl)) {
            $this->error('BITRIX24_WEBHOOK_BASE_URL is not configured.');
            $this->line('Please set this in your .env file:');
            $this->line('  BITRIX24_WEBHOOK_BASE_URL=https://xxx.bitrix24.com/rest/1/xxxxxx/');
            return false;
        }

        $this->info('Configuration check passed.');
        $this->line("  Webhook URL: {$webhookUrl}");
        $this->newLine();

        return true;
    }

    /**
     * Test the API connection.
     *
     * @param Bitrix24ApiClient $bitrixClient
     * @return bool
     */
    protected function testConnection(Bitrix24ApiClient $bitrixClient): bool
    {
        $this->line('Testing Bitrix24 API connection...');

        try {
            $response = $bitrixClient->request('profile');

            if (isset($response['result'])) {
                $this->info('Connection successful!');
                $this->line('  Portal: ' . ($response['result']['PERSONAL_CITY'] ?? 'Unknown'));
                $this->line('  User: ' . ($response['result']['NAME'] ?? 'Unknown'));
                $this->newLine();
                return true;
            }

            $this->error('Invalid API response.');
            return false;

        } catch (\Exception $e) {
            $this->error('Connection failed: ' . $e->getMessage());
            Log::error('Bitrix24 setup connection failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Load existing fields and statuses from Bitrix24.
     *
     * @param Bitrix24ApiClient $bitrixClient
     * @return void
     */
    protected function loadExistingData(Bitrix24ApiClient $bitrixClient): void
    {
        $this->line('Loading existing configuration...');

        // Load user fields
        try {
            $fields = $bitrixClient->getUserFields();
            foreach ($fields as $field) {
                $fieldName = $field['FIELD_NAME'] ?? '';
                if (str_starts_with($fieldName, 'UF_CRM_')) {
                    $shortName = substr($fieldName, 7); // Remove 'UF_CRM_' prefix
                    $this->existingFields[$shortName] = $field;
                }
            }
            $count = count($this->existingFields);
            $this->line("  Found {$count} existing custom fields.");
        } catch (\Exception $e) {
            $this->warn('Could not load existing fields: ' . $e->getMessage());
        }

        // Load lead statuses
        try {
            $statuses = $bitrixClient->getLeadStatuses();
            foreach ($statuses as $status) {
                $statusId = $status['STATUS_ID'] ?? '';
                $this->existingStatuses[$statusId] = $status;
            }
            $count = count($this->existingStatuses);
            $this->line("  Found {$count} existing lead statuses.");
        } catch (\Exception $e) {
            $this->warn('Could not load existing statuses: ' . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Create custom user fields.
     *
     * @param Bitrix24ApiClient $bitrixClient
     * @return void
     */
    protected function createCustomFields(Bitrix24ApiClient $bitrixClient): void
    {
        $this->line('Creating custom user fields...');

        $force = $this->option('force');
        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->customFields as $fieldName => $config) {
            $fullFieldName = 'UF_' . $fieldName;

            // Check if already exists
            if (isset($this->existingFields[$fieldName]) && !$force) {
                $this->line("  [SKIP] {$fullFieldName} (already exists)");
                $skipped++;
                continue;
            }

            try {
                $success = $bitrixClient->createUserField(
                    $fieldName,
                    $config['type']
                );

                if ($success) {
                    $this->info("  [OK] {$fullFieldName}");
                    $created++;
                } else {
                    $this->error("  [FAIL] {$fullFieldName}");
                    $failed++;
                }
            } catch (\Exception $e) {
                // Field might already exist (race condition or partial setup)
                if (str_contains($e->getMessage(), 'FIELD_NAME_USED')) {
                    $this->line("  [SKIP] {$fullFieldName} (already exists)");
                    $skipped++;
                } else {
                    $this->error("  [FAIL] {$fullFieldName}: " . $e->getMessage());
                    $failed++;
                }
            }
        }

        $this->newLine();
        $this->line("Custom fields: {$created} created, {$skipped} skipped, {$failed} failed");
        $this->newLine();
    }

    /**
     * Create lead statuses.
     *
     * @param Bitrix24ApiClient $bitrixClient
     * @return void
     */
    protected function createLeadStatuses(Bitrix24ApiClient $bitrixClient): void
    {
        $this->line('Creating lead statuses...');

        $force = $this->option('force');
        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->leadStatuses as $statusId => $config) {
            // Skip built-in statuses (NEW, JUNK, etc.)
            if (in_array($statusId, ['NEW', 'JUNK', 'PROCESSED']) && !$force) {
                $this->line("  [SKIP] {$statusId} (built-in status)");
                $skipped++;
                continue;
            }

            // Check if custom status already exists
            if (isset($this->existingStatuses[$statusId]) && !$force) {
                $this->line("  [SKIP] {$statusId} (already exists)");
                $skipped++;
                continue;
            }

            // Only create UC_ prefixed custom statuses
            if (!str_starts_with($statusId, 'UC_')) {
                $skipped++;
                continue;
            }

            try {
                $success = $bitrixClient->createLeadStatus(
                    $statusId,
                    $config['name'],
                    $config['sort']
                );

                if ($success) {
                    $this->info("  [OK] {$statusId} ({$config['name']})");
                    $created++;
                } else {
                    $this->error("  [FAIL] {$statusId}");
                    $failed++;
                }
            } catch (\Exception $e) {
                // Status might already exist
                if (str_contains($e->getMessage(), 'STATUS_ID_ALREADY_EXISTS') ||
                    str_contains($e->getMessage(), 'duplicate')) {
                    $this->line("  [SKIP] {$statusId} (already exists)");
                    $skipped++;
                } else {
                    $this->error("  [FAIL] {$statusId}: " . $e->getMessage());
                    $failed++;
                }
            }
        }

        $this->newLine();
        $this->line("Lead statuses: {$created} created, {$skipped} skipped, {$failed} failed");
    }
}

// CLAUDE-CHECKPOINT
