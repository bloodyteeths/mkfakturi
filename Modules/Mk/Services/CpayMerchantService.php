<?php

namespace Modules\Mk\Services;

use App\Models\CompanySetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Per-Merchant CPay (CASYS) Payment Service
 *
 * Unlike the platform-level CpayDriver, this service uses per-company
 * CASYS credentials stored in CompanySettings. Each merchant enters
 * their own merchant_id, merchant_name, and auth_key.
 *
 * CPay API: https://www.cpay.com.mk
 * Uses MD5 checksum with field-count + field-names + lengths + values + auth_key.
 */
class CpayMerchantService
{
    const CPAY_PAYMENT_URL = 'https://www.cpay.com.mk/client/Page/default.aspx?xml_id=/mk-MK/.loginToPay/.simple/';

    /**
     * Check if a company has CASYS configured.
     */
    public function isConfigured(int $companyId): bool
    {
        $merchantId = CompanySetting::getSetting('pos_casys_merchant_id', $companyId);
        $authKey = CompanySetting::getSetting('pos_casys_auth_key', $companyId);

        return ! empty($merchantId) && ! empty($authKey);
    }

    /**
     * Generate a CPay payment checkout URL for a given amount.
     *
     * @param  int  $companyId  The company requesting payment
     * @param  int  $amountCents  Amount in cents (e.g. 15000 = 150.00 MKD)
     * @param  string  $orderId  Unique order/reference ID (e.g. POS-SALE-xxx or INV-xxx)
     * @param  string  $description  Payment description shown to customer
     * @return array  Contains 'checkout_url', 'form_fields', 'order_id'
     *
     * @throws \Exception If CASYS is not configured for this company
     */
    public function createCheckout(int $companyId, int $amountCents, string $orderId, string $description = ''): array
    {
        $merchantId = CompanySetting::getSetting('pos_casys_merchant_id', $companyId);
        $merchantName = CompanySetting::getSetting('pos_casys_merchant_name', $companyId) ?: 'Merchant';
        $authKey = CompanySetting::getSetting('pos_casys_auth_key', $companyId);

        if (empty($merchantId) || empty($authKey)) {
            throw new \Exception('CASYS credentials not configured. Go to POS Settings to enter your Merchant ID and Auth Key.');
        }

        $amountDecimal = number_format($amountCents / 100, 2, '.', '');

        // Success/fail callback URLs
        $baseUrl = config('app.url');
        $okUrl = $baseUrl . '/webhooks/cpay/merchant/ok?order_id=' . urlencode($orderId) . '&company_id=' . $companyId;
        $failUrl = $baseUrl . '/webhooks/cpay/merchant/fail?order_id=' . urlencode($orderId) . '&company_id=' . $companyId;

        // Build form fields per CPay specification
        $fields = [
            'AmountToPay' => (string) $amountCents,
            'PayToMerchant' => $merchantId,
            'MerchantName' => $merchantName,
            'AmountCurrency' => 'MKD',
            'Details1' => mb_substr($description ?: 'Payment', 0, 100),
            'Details2' => $orderId,
            'PaymentOKURL' => $okUrl,
            'PaymentFailURL' => $failUrl,
            'OriginalAmount' => $amountDecimal,
            'FirstName' => '',
            'LastName' => '',
            'Email' => '',
            'Address' => '',
            'City' => '',
            'Zip' => '',
            'Country' => 'MK',
            'Telephone' => '',
        ];

        // Calculate checksum per CPay spec
        $checksum = $this->calculateChecksum($fields, $authKey);
        $fields['CheckSumHeader'] = $checksum['header'];
        $fields['CheckSum'] = $checksum['hash'];

        // Store pending payment for status polling
        Cache::put("cpay_merchant_{$orderId}", [
            'company_id' => $companyId,
            'amount' => $amountCents,
            'status' => 'pending',
            'created_at' => now()->toISOString(),
        ], now()->addHours(2));

        Log::info('CPay merchant checkout created', [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'amount' => $amountDecimal,
        ]);

        return [
            'checkout_url' => self::CPAY_PAYMENT_URL,
            'form_fields' => $fields,
            'order_id' => $orderId,
        ];
    }

    /**
     * Calculate CPay MD5 checksum.
     *
     * Algorithm:
     * 1. Count fields, list names comma-separated
     * 2. Calculate UTF-8 byte length of each value, zero-padded to 3 digits
     * 3. Header = fieldCount + fieldNames + lengths
     * 4. Hash = MD5(header + allValues + authKey)
     */
    protected function calculateChecksum(array $fields, string $authKey): array
    {
        $fieldNames = array_keys($fields);
        $fieldValues = array_values($fields);
        $fieldCount = count($fieldNames);

        // Build lengths string (3-digit zero-padded UTF-8 length of each value)
        $lengths = '';
        foreach ($fieldValues as $val) {
            $lengths .= sprintf('%03d', mb_strlen((string) $val, 'UTF-8'));
        }

        // Header: field count + comma-separated names + lengths
        $header = $fieldCount . implode(',', $fieldNames) . $lengths;

        // Hash input: header + all values concatenated + auth key
        $hashInput = $header;
        foreach ($fieldValues as $val) {
            $hashInput .= (string) $val;
        }
        $hashInput .= $authKey;

        $hash = strtoupper(md5($hashInput));

        return [
            'header' => $header,
            'hash' => $hash,
        ];
    }

    /**
     * Handle CPay merchant payment callback.
     *
     * Called when CPay redirects to PaymentOKURL or PaymentFailURL.
     * Updates the cached payment status.
     */
    public function handleCallback(string $orderId, bool $success, array $callbackData = []): void
    {
        $cacheKey = "cpay_merchant_{$orderId}";
        $pending = Cache::get($cacheKey);

        if (! $pending) {
            Log::warning('CPay merchant callback for unknown order', [
                'order_id' => $orderId,
                'data' => $callbackData,
            ]);
            return;
        }

        $pending['status'] = $success ? 'completed' : 'failed';
        $pending['callback_data'] = $callbackData;
        $pending['completed_at'] = now()->toISOString();

        // Keep for 1 hour after completion (for status polling)
        Cache::put($cacheKey, $pending, now()->addHour());

        Log::info('CPay merchant callback processed', [
            'order_id' => $orderId,
            'company_id' => $pending['company_id'],
            'status' => $pending['status'],
        ]);
    }

    /**
     * Check payment status by order ID.
     */
    public function getPaymentStatus(string $orderId): ?array
    {
        return Cache::get("cpay_merchant_{$orderId}");
    }

    /**
     * Generate a QR code data URI for a CPay checkout URL.
     *
     * Since CPay uses form POST (not GET redirect), the QR encodes
     * a hosted intermediate page that auto-submits the form.
     * For simplicity, we encode the checkout URL with query params
     * that our frontend can use to build and submit the form.
     */
    public function generateQrDataUri(array $checkoutData, int $size = 300): string
    {
        $qrService = app(QrCodeService::class);

        // Encode a URL that contains the order ID for the customer's browser
        // The customer scans this, which opens the CPay payment page
        $baseUrl = config('app.url');
        $payUrl = $baseUrl . '/pay/cpay/' . urlencode($checkoutData['order_id']);

        return $qrService->getDataUri($payUrl, 'svg', $size);
    }
}

// CLAUDE-CHECKPOINT
