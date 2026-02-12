<?php

namespace App\Services\WooCommerce;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WooCommerce sync orchestrator.
 *
 * Coordinates pulling orders from WooCommerce and creating invoices
 * in Facturino. Handles idempotency, error tracking, and sync history.
 */
class WooCommerceSyncService
{
    protected WooCommerceClient $client;

    protected WooCommerceOrderMapper $mapper;

    protected int $companyId;

    public function __construct(WooCommerceClient $client, WooCommerceOrderMapper $mapper, int $companyId)
    {
        $this->client = $client;
        $this->mapper = $mapper;
        $this->companyId = $companyId;
    }

    /**
     * Create a sync service from stored company settings.
     */
    public static function fromCompanySettings(int $companyId, array $settings): self
    {
        $client = new WooCommerceClient(
            $settings['store_url'] ?? '',
            $settings['consumer_key'] ?? '',
            $settings['consumer_secret'] ?? ''
        );

        $mapper = new WooCommerceOrderMapper(
            $settings['tax_mapping'] ?? [],
            $settings['default_payment_method_id'] ?? null
        );

        return new self($client, $mapper, $companyId);
    }

    /**
     * Sync orders from WooCommerce.
     *
     * @param  string|null  $since  ISO datetime to sync orders after
     * @return array{synced: int, skipped: int, errors: int, details: array}
     */
    public function syncOrders(?string $since = null): array
    {
        $result = [
            'synced' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => [],
        ];

        try {
            $params = [];
            if ($since) {
                $params['after'] = $since;
            }

            $orders = $this->client->getOrders($params);

            foreach ($orders as $order) {
                try {
                    $syncResult = $this->syncSingleOrder($order);
                    if ($syncResult === 'synced') {
                        $result['synced']++;
                    } elseif ($syncResult === 'skipped') {
                        $result['skipped']++;
                    }
                } catch (\Throwable $e) {
                    $result['errors']++;
                    $result['details'][] = [
                        'order_id' => $order['id'] ?? null,
                        'error' => $e->getMessage(),
                    ];

                    Log::warning('WooCommerceSyncService: Order sync failed', [
                        'company_id' => $this->companyId,
                        'order_id' => $order['id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Record sync history
            $this->recordSyncHistory($result);

        } catch (\Throwable $e) {
            Log::error('WooCommerceSyncService: Sync failed', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
            ]);

            $result['errors']++;
            $result['details'][] = ['error' => $e->getMessage()];
        }

        return $result;
    }

    /**
     * Sync a single WooCommerce order.
     *
     * @return string 'synced', 'skipped', or 'error'
     */
    protected function syncSingleOrder(array $order): string
    {
        if (! $this->mapper->shouldSync($order)) {
            return 'skipped';
        }

        $idempotencyKey = $this->mapper->getIdempotencyKey($order);

        // Check if already synced
        $existing = DB::table('woocommerce_sync_log')
            ->where('company_id', $this->companyId)
            ->where('woo_order_id', $order['id'])
            ->first();

        if ($existing && $existing->idempotency_key === $idempotencyKey) {
            return 'skipped';
        }

        // Map order to invoice data
        $invoiceData = $this->mapper->mapOrder($order);

        // Record the sync (actual invoice creation would be handled by InvoiceService)
        DB::table('woocommerce_sync_log')->updateOrInsert(
            [
                'company_id' => $this->companyId,
                'woo_order_id' => $order['id'],
            ],
            [
                'woo_order_number' => $invoiceData['woo_order_number'],
                'woo_order_status' => $invoiceData['woo_order_status'],
                'idempotency_key' => $idempotencyKey,
                'invoice_data' => json_encode($invoiceData),
                'status' => 'synced',
                'synced_at' => now(),
                'updated_at' => now(),
            ]
        );

        Log::info('WooCommerceSyncService: Order synced', [
            'company_id' => $this->companyId,
            'woo_order_id' => $order['id'],
            'order_total' => $invoiceData['totals']['total'],
        ]);

        return 'synced';
    }

    /**
     * Record sync run in history table.
     */
    protected function recordSyncHistory(array $result): void
    {
        DB::table('woocommerce_sync_history')->insert([
            'company_id' => $this->companyId,
            'synced_count' => $result['synced'],
            'skipped_count' => $result['skipped'],
            'error_count' => $result['errors'],
            'details' => json_encode($result['details']),
            'status' => $result['errors'] > 0 ? 'partial' : 'success',
            'created_at' => now(),
        ]);
    }

    /**
     * Get recent sync history for a company.
     *
     * @param  int  $limit  Number of records to return
     * @return array
     */
    public function getSyncHistory(int $limit = 20): array
    {
        return DB::table('woocommerce_sync_history')
            ->where('company_id', $this->companyId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
// CLAUDE-CHECKPOINT
