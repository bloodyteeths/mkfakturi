<?php

namespace App\Http\Controllers\V1\Admin\Integration;

use App\Http\Controllers\Controller;
use App\Jobs\SyncWooCommerceOrdersJob;
use App\Services\WooCommerce\WooCommerceClient;
use App\Services\WooCommerce\WooCommerceSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WooCommerce integration settings and sync controller.
 */
class WooCommerceController extends Controller
{
    /**
     * GET /api/v1/woocommerce/settings
     *
     * Retrieve WooCommerce settings for the current company.
     */
    public function getSettings(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        $settings = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->whereIn('option', [
                'woocommerce_store_url',
                'woocommerce_consumer_key',
                'woocommerce_consumer_secret',
                'woocommerce_auto_sync',
                'woocommerce_sync_frequency',
                'woocommerce_tax_mapping',
                'woocommerce_default_payment_method_id',
            ])
            ->pluck('value', 'option')
            ->toArray();

        // Mask sensitive fields
        if (! empty($settings['woocommerce_consumer_key'])) {
            $settings['woocommerce_consumer_key'] = substr($settings['woocommerce_consumer_key'], 0, 8).'...';
        }
        if (! empty($settings['woocommerce_consumer_secret'])) {
            $settings['woocommerce_consumer_secret'] = '••••••••';
        }

        return response()->json(['data' => $settings]);
    }

    /**
     * POST /api/v1/woocommerce/settings
     *
     * Save WooCommerce settings for the current company.
     */
    public function saveSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_url' => 'required|url|max:500',
            'consumer_key' => 'required|string|max:200',
            'consumer_secret' => 'required|string|max:200',
            'auto_sync' => 'boolean',
            'sync_frequency' => 'in:15,60,240,0',
            'tax_mapping' => 'nullable|array',
            'default_payment_method_id' => 'nullable|integer',
        ]);

        $companyId = $request->header('company');

        $settingsMap = [
            'woocommerce_store_url' => $validated['store_url'],
            'woocommerce_consumer_key' => $validated['consumer_key'],
            'woocommerce_consumer_secret' => $validated['consumer_secret'],
            'woocommerce_auto_sync' => $validated['auto_sync'] ?? false ? '1' : '0',
            'woocommerce_sync_frequency' => $validated['sync_frequency'] ?? '60',
            'woocommerce_tax_mapping' => json_encode($validated['tax_mapping'] ?? []),
            'woocommerce_default_payment_method_id' => $validated['default_payment_method_id'] ?? null,
        ];

        foreach ($settingsMap as $option => $value) {
            DB::table('company_settings')->updateOrInsert(
                ['company_id' => $companyId, 'option' => $option],
                ['value' => $value]
            );
        }

        Log::info('WooCommerceController: Settings saved', ['company_id' => $companyId]);

        return response()->json(['message' => 'Settings saved']);
    }

    /**
     * POST /api/v1/woocommerce/test-connection
     *
     * Test the WooCommerce store connection.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_url' => 'required|url',
            'consumer_key' => 'required|string',
            'consumer_secret' => 'required|string',
        ]);

        $client = new WooCommerceClient(
            $validated['store_url'],
            $validated['consumer_key'],
            $validated['consumer_secret']
        );

        $result = $client->testConnection();

        return response()->json($result);
    }

    /**
     * POST /api/v1/woocommerce/sync
     *
     * Trigger a manual sync of WooCommerce orders.
     */
    public function syncNow(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        dispatch(new SyncWooCommerceOrdersJob((int) $companyId));

        return response()->json([
            'message' => 'Sync job dispatched',
        ], 202);
    }

    /**
     * GET /api/v1/woocommerce/sync-history
     *
     * Get recent sync history for the current company.
     */
    public function syncHistory(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        $history = DB::table('woocommerce_sync_history')
            ->where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json(['data' => $history]);
    }
}
// CLAUDE-CHECKPOINT
