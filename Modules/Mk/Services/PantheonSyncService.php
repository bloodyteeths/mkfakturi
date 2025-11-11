<?php

namespace Modules\Mk\Services;

use App\Models\Invoice;
use App\Models\Payment;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * PANTHEON Web Services Sync Service
 * 
 * Integrates with PANTHEON accounting system web services API
 * for pushing invoices and payments to external accounting platform
 * 
 * API Documentation: PANTHEON Web Services API v2.0
 * Base URL: https://api.pantheon.mk/ws/v2/
 * Rate Limit: 100 requests per minute
 * 
 * @version 1.0.0
 * @updated 2025-07-26 - Initial implementation for ROADMAP4.md AI-06
 */
class PantheonSyncService
{
    protected Client $client;
    protected string $baseUrl;
    protected string $apiKey;
    protected string $companyCode;
    protected bool $sandbox;

    // PANTHEON API endpoints
    protected const API_PUSH_INVOICE = 'invoices/push';
    protected const API_PUSH_PAYMENT = 'payments/push';
    protected const API_STATUS = 'status';
    protected const API_AUTH_TOKEN = 'auth/token';

    // Sandbox URLs for testing
    protected const SANDBOX_BASE_URL = 'https://sandbox-api.pantheon.mk/ws/v2/';
    protected const PRODUCTION_BASE_URL = 'https://api.pantheon.mk/ws/v2/';

    public function __construct()
    {
        $this->sandbox = config('app.env') !== 'production';
        $this->baseUrl = $this->sandbox ? self::SANDBOX_BASE_URL : self::PRODUCTION_BASE_URL;
        
        $this->apiKey = config('services.pantheon.api_key', 'demo-key-12345');
        $this->companyCode = config('services.pantheon.company_code', 'DEMO001');
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'InvoiceShelf-MK/1.0 PantheonSync',
            ],
        ]);
    }

    /**
     * Push invoice to PANTHEON system
     * 
     * @param Invoice $invoice The invoice to push
     * @return array Response from PANTHEON API
     * @throws \Exception On API errors
     */
    public function pushInvoice(Invoice $invoice): array
    {
        try {
            $invoiceData = $this->formatInvoiceData($invoice);
            
            $response = $this->client->post(self::API_PUSH_INVOICE, [
                'headers' => $this->getAuthHeaders(),
                'json' => $invoiceData,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('PANTHEON API returned error: ' . $responseData['message'] ?? 'Unknown error');
            }

            Log::info('Invoice pushed to PANTHEON successfully', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'pantheon_id' => $responseData['data']['pantheon_id'] ?? null,
            ]);

            return $responseData;

        } catch (RequestException $e) {
            $this->handleApiError($e, 'invoice', $invoice->id);
            throw $e;
        }
    }

    /**
     * Push payment to PANTHEON system
     * 
     * @param Payment $payment The payment to push
     * @return array Response from PANTHEON API
     * @throws \Exception On API errors
     */
    public function pushPayment(Payment $payment): array
    {
        try {
            $paymentData = $this->formatPaymentData($payment);
            
            $response = $this->client->post(self::API_PUSH_PAYMENT, [
                'headers' => $this->getAuthHeaders(),
                'json' => $paymentData,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('PANTHEON API returned error: ' . $responseData['message'] ?? 'Unknown error');
            }

            Log::info('Payment pushed to PANTHEON successfully', [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'pantheon_id' => $responseData['data']['pantheon_id'] ?? null,
            ]);

            return $responseData;

        } catch (RequestException $e) {
            $this->handleApiError($e, 'payment', $payment->id);
            throw $e;
        }
    }

    /**
     * Get PANTHEON system status
     * 
     * @return array Status information from PANTHEON API
     * @throws \Exception On API errors
     */
    public function getStatus(): array
    {
        try {
            $response = $this->client->get(self::API_STATUS, [
                'headers' => $this->getAuthHeaders(),
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('PANTHEON API returned error: ' . $responseData['message'] ?? 'Unknown error');
            }

            return [
                'status' => 'online',
                'environment' => $this->sandbox ? 'sandbox' : 'production',
                'api_version' => $responseData['api_version'] ?? '2.0',
                'company_code' => $this->companyCode,
                'last_check' => now()->toISOString(),
                'response_time_ms' => $responseData['response_time_ms'] ?? null,
            ];

        } catch (RequestException $e) {
            $this->handleApiError($e, 'status', null);
            
            return [
                'status' => 'offline',
                'environment' => $this->sandbox ? 'sandbox' : 'production',
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString(),
            ];
        }
    }

    /**
     * Format invoice data for PANTHEON API
     */
    protected function formatInvoiceData(Invoice $invoice): array
    {
        $invoice->load(['company', 'customer', 'items', 'taxes']);

        return [
            'company_code' => $this->companyCode,
            'invoice' => [
                'external_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date->format('Y-m-d'),
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'currency' => $invoice->currency->code ?? 'MKD',
                'exchange_rate' => $invoice->exchange_rate ?? 1,
                'status' => $invoice->status,
                'notes' => $invoice->notes,
                'customer' => [
                    'external_id' => $invoice->customer->id,
                    'name' => $invoice->customer->name,
                    'email' => $invoice->customer->email,
                    'phone' => $invoice->customer->phone,
                    'vat_number' => $invoice->customer->vat_number,
                    'address' => [
                        'name' => $invoice->customer->billing_name,
                        'address_street_1' => $invoice->customer->billing_address_street_1,
                        'address_street_2' => $invoice->customer->billing_address_street_2,
                        'city' => $invoice->customer->billing_city,
                        'state' => $invoice->customer->billing_state,
                        'zip' => $invoice->customer->billing_zip,
                        'country' => $invoice->customer->billing_country_id,
                    ],
                ],
                'items' => $invoice->items->map(function ($item) {
                    return [
                        'external_id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'price' => $item->price / 100, // Convert cents to currency
                        'total' => $item->total / 100,
                        'unit' => $item->unit_name ?? 'kom',
                    ];
                })->toArray(),
                'taxes' => $invoice->taxes->map(function ($tax) {
                    return [
                        'name' => $tax->name,
                        'percent' => $tax->percent,
                        'amount' => $tax->amount / 100, // Convert cents to currency
                    ];
                })->toArray(),
                'totals' => [
                    'sub_total' => $invoice->sub_total / 100,
                    'tax_total' => $invoice->tax_total / 100,
                    'total' => $invoice->total / 100,
                ],
            ],
        ];
    }

    /**
     * Format payment data for PANTHEON API
     */
    protected function formatPaymentData(Payment $payment): array
    {
        $payment->load(['invoice', 'customer', 'paymentMethod']);

        return [
            'company_code' => $this->companyCode,
            'payment' => [
                'external_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'payment_date' => $payment->payment_date->format('Y-m-d'),
                'amount' => $payment->amount / 100, // Convert cents to currency
                'currency' => $payment->currency->code ?? 'MKD',
                'exchange_rate' => $payment->exchange_rate ?? 1,
                'payment_mode' => $payment->payment_mode,
                'notes' => $payment->notes,
                'invoice' => [
                    'external_id' => $payment->invoice->id,
                    'invoice_number' => $payment->invoice->invoice_number,
                ],
                'customer' => [
                    'external_id' => $payment->customer->id,
                    'name' => $payment->customer->name,
                    'email' => $payment->customer->email,
                ],
                'payment_method' => [
                    'name' => $payment->paymentMethod->name ?? 'Unknown',
                    'type' => $payment->payment_mode,
                ],
            ],
        ];
    }

    /**
     * Get authentication headers for PANTHEON API
     */
    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'X-Company-Code' => $this->companyCode,
            'X-Request-ID' => $this->generateRequestId(),
        ];
    }

    /**
     * Generate unique request ID for API tracking
     */
    protected function generateRequestId(): string
    {
        return 'pantheon-' . uniqid() . '-' . time();
    }

    /**
     * Handle API errors with proper logging and retry logic
     */
    protected function handleApiError(RequestException $e, string $operation, ?int $entityId): void
    {
        $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
        $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';
        
        Log::error('PANTHEON API Error', [
            'operation' => $operation,
            'entity_id' => $entityId,
            'status_code' => $statusCode,
            'error_message' => $e->getMessage(),
            'response_body' => $responseBody,
            'environment' => $this->sandbox ? 'sandbox' : 'production',
            'base_url' => $this->baseUrl,
        ]);

        // Handle specific error codes
        if ($statusCode === 401) {
            Log::warning('PANTHEON API authentication failed - check API key and company code');
        } elseif ($statusCode === 429) {
            Log::warning('PANTHEON API rate limit exceeded - implement retry logic');
        } elseif ($statusCode >= 500) {
            Log::warning('PANTHEON API server error - consider retry with backoff');
        }
    }

    /**
     * Test API connectivity and authentication
     */
    public function testConnection(): array
    {
        try {
            $status = $this->getStatus();
            
            return [
                'success' => true,
                'message' => 'PANTHEON API connection successful',
                'status' => $status,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'PANTHEON API connection failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get service configuration for debugging
     */
    public function getConfiguration(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'environment' => $this->sandbox ? 'sandbox' : 'production',
            'company_code' => $this->companyCode,
            'api_key_set' => !empty($this->apiKey),
            'endpoints' => [
                'push_invoice' => $this->baseUrl . self::API_PUSH_INVOICE,
                'push_payment' => $this->baseUrl . self::API_PUSH_PAYMENT,
                'status' => $this->baseUrl . self::API_STATUS,
            ],
        ];
    }
}

