<?php

namespace App\Http\Controllers\V1\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class FeatureFlagsController extends Controller
{
    /**
     * List of all available feature flags with metadata.
     */
    private const FEATURE_FLAGS = [
        'accounting-backbone' => [
            'key' => 'accounting_backbone',
            'name' => 'Accounting Backbone',
            'description' => 'Enables IFRS-compliant accounting reports (Trial Balance, Balance Sheet, Income Statement)',
            'critical' => false,
        ],
        'migration-wizard' => [
            'key' => 'migration_wizard',
            'name' => 'Migration Wizard',
            'description' => 'Enables Excel-based data import wizard for migrating from other systems',
            'critical' => false,
        ],
        'psd2-banking' => [
            'key' => 'psd2_banking',
            'name' => 'PSD2 Banking',
            'description' => 'Enables PSD2 bank integration for automatic transaction import',
            'critical' => false,
        ],
        'partner-portal' => [
            'key' => 'partner_portal',
            'name' => 'Partner Portal',
            'description' => 'Enables accountant/partner console for managing multiple companies',
            'critical' => false,
        ],
        'advanced-payments' => [
            'key' => 'advanced_payments',
            'name' => 'Advanced Payments',
            'description' => 'Enables CASYS payment links and advanced payment processing',
            'critical' => false,
        ],
        'redis-queues' => [
            'key' => 'redis_queues',
            'name' => 'Redis Queues',
            'description' => 'Use Redis-backed queues instead of database/sync (requires Redis service)',
            'critical' => false,
        ],
        'mcp-ai-tools' => [
            'key' => 'mcp_ai_tools',
            'name' => 'MCP AI Tools',
            'description' => 'Enables AI-powered financial insights and automation tools',
            'critical' => false,
        ],
        'monitoring' => [
            'key' => 'monitoring',
            'name' => 'Monitoring',
            'description' => 'Enables system monitoring and performance tracking',
            'critical' => false,
        ],
    ];

    /**
     * Get all feature flags with their current status.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('manage settings');

        $flags = [];

        foreach (self::FEATURE_FLAGS as $flag => $metadata) {
            $dbKey = 'feature_flag.'.$metadata['key'];
            $dbValue = Setting::getSetting($dbKey);

            // If DB value exists, use it; otherwise fall back to config
            $enabled = $dbValue !== null
                ? filter_var($dbValue, FILTER_VALIDATE_BOOLEAN)
                : config('features.'.$metadata['key'].'.enabled', false);

            $flags[] = [
                'flag' => $flag,
                'key' => $metadata['key'],
                'name' => $metadata['name'],
                'description' => $metadata['description'],
                'critical' => $metadata['critical'],
                'enabled' => $enabled,
            ];
        }

        return response()->json([
            'success' => true,
            'flags' => $flags,
        ]);
    }

    /**
     * Toggle a specific feature flag.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(Request $request, string $flag)
    {
        $this->authorize('manage settings');

        // Validate that the flag exists
        if (! isset(self::FEATURE_FLAGS[$flag])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid feature flag',
            ], 404);
        }

        $metadata = self::FEATURE_FLAGS[$flag];
        $dbKey = 'feature_flag.'.$metadata['key'];

        // Get current value
        $currentValue = Setting::getSetting($dbKey);
        $currentEnabled = $currentValue !== null
            ? filter_var($currentValue, FILTER_VALIDATE_BOOLEAN)
            : config('features.'.$metadata['key'].'.enabled', false);

        // Toggle the value
        $newValue = ! $currentEnabled;

        // Save to database
        Setting::setSetting($dbKey, $newValue ? '1' : '0');

        // Log the change
        Log::info('Feature flag toggled', [
            'flag' => $flag,
            'key' => $metadata['key'],
            'previous_value' => $currentEnabled,
            'new_value' => $newValue,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email,
        ]);

        // Clear config cache to reflect changes
        Artisan::call('config:clear');

        return response()->json([
            'success' => true,
            'flag' => $flag,
            'enabled' => $newValue,
            'message' => $newValue
                ? "Feature '{$metadata['name']}' has been enabled"
                : "Feature '{$metadata['name']}' has been disabled",
        ]);
    }
}

// CLAUDE-CHECKPOINT
