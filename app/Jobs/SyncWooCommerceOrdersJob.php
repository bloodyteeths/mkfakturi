<?php

namespace App\Jobs;

use App\Services\WooCommerce\WooCommerceSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued job for syncing WooCommerce orders to Facturino invoices.
 *
 * Dispatched on a schedule (configurable: 15min/1h/4h) or manually.
 */
class SyncWooCommerceOrdersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;

    public $backoff = [60, 300, 900];

    public $timeout = 120;

    protected int $companyId;

    protected ?string $since;

    public function __construct(int $companyId, ?string $since = null)
    {
        $this->companyId = $companyId;
        $this->since = $since;
        $this->onQueue('integrations');
    }

    public function handle(): void
    {
        Log::info('SyncWooCommerceOrdersJob: Starting sync', [
            'company_id' => $this->companyId,
            'since' => $this->since,
            'attempt' => $this->attempts(),
        ]);

        // Load company WooCommerce settings
        $settings = DB::table('company_settings')
            ->where('company_id', $this->companyId)
            ->whereIn('option', [
                'woocommerce_store_url',
                'woocommerce_consumer_key',
                'woocommerce_consumer_secret',
                'woocommerce_tax_mapping',
                'woocommerce_default_payment_method_id',
            ])
            ->pluck('value', 'option')
            ->toArray();

        if (empty($settings['woocommerce_store_url']) || empty($settings['woocommerce_consumer_key'])) {
            Log::info('SyncWooCommerceOrdersJob: WooCommerce not configured, skipping', [
                'company_id' => $this->companyId,
            ]);

            return;
        }

        $syncSettings = [
            'store_url' => $settings['woocommerce_store_url'],
            'consumer_key' => $settings['woocommerce_consumer_key'],
            'consumer_secret' => $settings['woocommerce_consumer_secret'] ?? '',
            'tax_mapping' => json_decode($settings['woocommerce_tax_mapping'] ?? '{}', true),
            'default_payment_method_id' => $settings['woocommerce_default_payment_method_id'] ?? null,
        ];

        $syncService = WooCommerceSyncService::fromCompanySettings($this->companyId, $syncSettings);

        $result = $syncService->syncOrders($this->since);

        Log::info('SyncWooCommerceOrdersJob: Sync completed', [
            'company_id' => $this->companyId,
            'synced' => $result['synced'],
            'skipped' => $result['skipped'],
            'errors' => $result['errors'],
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SyncWooCommerceOrdersJob: Job failed after all retries', [
            'company_id' => $this->companyId,
            'error' => $exception->getMessage(),
        ]);
    }
}
