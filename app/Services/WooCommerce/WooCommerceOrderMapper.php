<?php

namespace App\Services\WooCommerce;

use Illuminate\Support\Facades\Log;

/**
 * Maps WooCommerce orders to Facturino invoice data structures.
 *
 * Handles:
 * - Customer data mapping (name, address, tax ID)
 * - Line item mapping (products, quantities, prices, tax)
 * - Tax class → Facturino tax type mapping
 * - Currency handling
 */
class WooCommerceOrderMapper
{
    protected array $taxMapping;

    protected ?int $defaultPaymentMethodId;

    public function __construct(array $taxMapping = [], ?int $defaultPaymentMethodId = null)
    {
        $this->taxMapping = $taxMapping;
        $this->defaultPaymentMethodId = $defaultPaymentMethodId;
    }

    /**
     * Map a WooCommerce order to Facturino invoice data.
     *
     * @param  array  $order  WooCommerce order data from the API
     * @return array Invoice-compatible data array
     */
    public function mapOrder(array $order): array
    {
        return [
            'woo_order_id' => $order['id'],
            'woo_order_number' => $order['number'] ?? $order['id'],
            'woo_order_status' => $order['status'] ?? 'processing',
            'customer' => $this->mapCustomer($order),
            'items' => $this->mapLineItems($order['line_items'] ?? []),
            'totals' => [
                'subtotal' => $order['total'] ?? '0',
                'tax' => $order['total_tax'] ?? '0',
                'discount' => $order['discount_total'] ?? '0',
                'shipping' => $order['shipping_total'] ?? '0',
                'total' => $order['total'] ?? '0',
            ],
            'currency' => $order['currency'] ?? 'MKD',
            'payment_method' => $order['payment_method'] ?? null,
            'payment_method_title' => $order['payment_method_title'] ?? null,
            'order_date' => $order['date_created'] ?? now()->toIso8601String(),
            'notes' => $order['customer_note'] ?? null,
        ];
    }

    /**
     * Map WooCommerce customer/billing data to Facturino customer fields.
     */
    protected function mapCustomer(array $order): array
    {
        $billing = $order['billing'] ?? [];

        $name = trim(($billing['company'] ?? '') ?: (($billing['first_name'] ?? '').' '.($billing['last_name'] ?? '')));

        return [
            'name' => $name,
            'email' => $billing['email'] ?? null,
            'phone' => $billing['phone'] ?? null,
            'address_street_1' => $billing['address_1'] ?? null,
            'address_street_2' => $billing['address_2'] ?? null,
            'city' => $billing['city'] ?? null,
            'zip' => $billing['postcode'] ?? null,
            'state' => $billing['state'] ?? null,
            'country_id' => $this->mapCountryCode($billing['country'] ?? 'MK'),
        ];
    }

    /**
     * Map WooCommerce line items to Facturino invoice items.
     */
    protected function mapLineItems(array $lineItems): array
    {
        return array_map(function ($item) {
            $taxClass = $item['tax_class'] ?? 'standard';
            $taxTypeId = $this->taxMapping[$taxClass] ?? null;

            return [
                'name' => $item['name'] ?? 'Product',
                'description' => null,
                'quantity' => (float) ($item['quantity'] ?? 1),
                'price' => (float) ($item['price'] ?? 0),
                'total' => (float) ($item['total'] ?? 0),
                'tax_total' => (float) ($item['total_tax'] ?? 0),
                'tax_class' => $taxClass,
                'tax_type_id' => $taxTypeId,
                'sku' => $item['sku'] ?? null,
                'woo_product_id' => $item['product_id'] ?? null,
            ];
        }, $lineItems);
    }

    /**
     * Map ISO country code to Facturino country ID.
     */
    protected function mapCountryCode(string $isoCode): ?int
    {
        // MK = Macedonia (id depends on seeded countries table)
        $map = [
            'MK' => 129, // North Macedonia
        ];

        return $map[$isoCode] ?? null;
    }

    /**
     * Check if an order should be synced (based on status).
     */
    public function shouldSync(array $order): bool
    {
        $syncStatuses = ['processing', 'completed', 'on-hold'];

        return in_array($order['status'] ?? '', $syncStatuses);
    }

    /**
     * Generate a unique idempotency key for an order.
     */
    public function getIdempotencyKey(array $order): string
    {
        return 'woo_order_'.$order['id'].'_'.$order['date_modified'];
    }
}
