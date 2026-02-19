<?php

namespace Modules\Mk\Services\FiscalDevices;

use App\Contracts\FiscalDeviceDriver;
use App\Exceptions\FiscalDeviceException;
use App\Services\FiscalDevices\ErpNetFpClient;
use Illuminate\Support\Facades\Log;

/**
 * ErpNet.FP Fiscal Device Driver
 *
 * Implements FiscalDeviceDriver via the ErpNet.FP REST API sidecar.
 * ErpNet.FP handles all vendor-specific protocols (Daisy, David, Expert, etc.)
 * so this driver delegates all operations to the sidecar's HTTP API.
 *
 * The driver auto-discovers the printer by serial number from the
 * ErpNet.FP printer list. The sidecar must be running and the device
 * must be physically connected (USB/serial/TCP) to the sidecar host.
 *
 * Tax groups for Macedonian VAT:
 *   А = 18% (standard), Б = 5% (reduced), В = 10% (restaurant), Г = 0% (exempt)
 */
class ErpNetFpDriver implements FiscalDeviceDriver
{
    private ?string $printerId = null;

    private ?string $serialNumber = null;

    private bool $connected = false;

    private ?ErpNetFpClient $client = null;

    /** Macedonian VAT rate to fiscal tax group mapping */
    private const TAX_GROUPS = [
        18 => 'А',   // Standard rate
        5 => 'Б',    // Reduced rate
        10 => 'В',   // Restaurant rate (P7-01)
        0 => 'Г',    // Exempt / zero-rate
    ];

    public function connect(array $config): bool
    {
        $this->serialNumber = $config['serial_number'] ?? null;
        $this->client = app(ErpNetFpClient::class);

        if (! $this->serialNumber) {
            throw new FiscalDeviceException(
                'Serial number is required for ErpNet.FP printer discovery',
                'erpnet-fp'
            );
        }

        // Check if sidecar is reachable
        if (! $this->client->isHealthy()) {
            throw new FiscalDeviceException(
                'ErpNet.FP sidecar is not reachable. Ensure the service is running.',
                'erpnet-fp',
                $this->serialNumber
            );
        }

        // Find printer by serial number
        $printer = $this->client->findPrinterBySerial($this->serialNumber);

        if (! $printer) {
            throw new FiscalDeviceException(
                "No printer found with serial number '{$this->serialNumber}'. Check device connection to ErpNet.FP sidecar.",
                'erpnet-fp',
                $this->serialNumber
            );
        }

        $this->printerId = $printer['id'];
        $this->connected = true;

        Log::info('ErpNet.FP: Connected to printer', [
            'printer_id' => $this->printerId,
            'serial' => $this->serialNumber,
            'model' => $printer['model'] ?? 'unknown',
        ]);

        return true;
    }

    public function sendInvoice(array $invoiceData): array
    {
        $this->ensureConnected();

        $receiptData = $this->mapInvoiceToReceipt($invoiceData);

        Log::info('ErpNet.FP: Sending invoice', [
            'printer_id' => $this->printerId,
            'items' => count($receiptData['items']),
            'unique_sale_number' => $receiptData['uniqueSaleNumber'],
        ]);

        $response = $this->client->printReceipt($this->printerId, $receiptData);

        if (! ($response['ok'] ?? false)) {
            $messages = $response['messages'] ?? [];
            $errorMsg = ! empty($messages) ? implode('; ', $messages) : 'Unknown printer error';

            throw new FiscalDeviceException(
                "ErpNet.FP receipt failed: {$errorMsg}",
                'erpnet-fp',
                $this->serialNumber
            );
        }

        return [
            'fiscal_id' => $response['fiscalMemorySerialNumber'] ?? '',
            'receipt_number' => $response['receiptNumber'] ?? '',
            'raw_response' => json_encode($response),
            'receipt_date' => $response['receiptDateTime'] ?? now()->toIso8601String(),
        ];
    }

    public function getStatus(): array
    {
        $this->ensureConnected();

        $status = $this->client->getPrinterStatus($this->printerId);
        $info = $this->client->getPrinter($this->printerId);

        $isOk = $status['ok'] ?? false;
        $messages = $status['messages'] ?? [];

        return [
            'connected' => true,
            'paper' => $isOk,
            'fiscal_memory' => $info['fiscalMemorySerialNumber'] ?? 'unknown',
            'last_receipt' => $status['receiptNumber'] ?? null,
            'errors' => $isOk ? [] : $messages,
            'driver' => 'erpnet-fp',
            'device' => $info['model'] ?? 'ErpNet.FP Printer',
            'serial' => $this->serialNumber,
            'connection_type' => 'erpnet-fp',
            'firmware' => $info['firmwareVersion'] ?? null,
            'device_date_time' => $status['deviceDateTime'] ?? null,
        ];
    }

    public function getLastReceipt(): array
    {
        $this->ensureConnected();

        // ErpNet.FP status includes the last receipt info
        $status = $this->client->getPrinterStatus($this->printerId);

        return [
            'receipt_number' => $status['receiptNumber'] ?? '',
            'amount' => 0, // ErpNet.FP status doesn't include amounts
            'vat_amount' => 0,
            'fiscal_id' => $status['fiscalMemorySerialNumber'] ?? '',
            'timestamp' => $status['deviceDateTime'] ?? '',
        ];
    }

    public function getDailyReport(): array
    {
        $this->ensureConnected();

        Log::info('ErpNet.FP: Requesting Z-report', [
            'printer_id' => $this->printerId,
            'serial' => $this->serialNumber,
        ]);

        $response = $this->client->printZReport($this->printerId);

        if (! ($response['ok'] ?? false)) {
            $messages = $response['messages'] ?? [];
            $errorMsg = ! empty($messages) ? implode('; ', $messages) : 'Z-report failed';

            throw new FiscalDeviceException(
                "ErpNet.FP Z-report failed: {$errorMsg}",
                'erpnet-fp',
                $this->serialNumber
            );
        }

        return [
            'total_amount' => $response['totalAmount'] ?? 0,
            'total_vat' => $response['totalVat'] ?? 0,
            'receipt_count' => $response['receiptCount'] ?? 0,
            'report_number' => $response['reportNumber'] ?? '',
            'raw_response' => json_encode($response),
        ];
    }

    public function disconnect(): void
    {
        $this->connected = false;
        $this->printerId = null;
        Log::info('ErpNet.FP: Disconnected', ['serial' => $this->serialNumber]);
    }

    public function getDriverName(): string
    {
        return 'erpnet-fp';
    }

    /**
     * Map Facturino invoice data to ErpNet.FP receipt format.
     *
     * @param  array  $invoiceData  Expected keys: items, total, payment_method, operator, invoice_number
     * @return array ErpNet.FP receipt payload
     */
    protected function mapInvoiceToReceipt(array $invoiceData): array
    {
        $items = [];
        foreach ($invoiceData['items'] ?? [] as $item) {
            $vatRate = (int) ($item['tax_rate'] ?? $item['vat_rate'] ?? 18);
            $taxGroup = self::TAX_GROUPS[$vatRate] ?? self::TAX_GROUPS[18];

            $items[] = [
                'text' => mb_substr($item['name'] ?? $item['description'] ?? 'Item', 0, 36),
                'taxGroup' => $taxGroup,
                'unitPrice' => (float) ($item['price'] ?? $item['unit_price'] ?? 0),
                'quantity' => (float) ($item['quantity'] ?? 1),
            ];
        }

        $paymentType = match ($invoiceData['payment_method'] ?? 'cash') {
            'card', 'credit_card', 'debit_card' => 'card',
            'transfer', 'bank_transfer', 'wire' => 'check',
            default => 'cash',
        };

        return [
            'uniqueSaleNumber' => $this->generateUniqueSaleNumber($invoiceData),
            'items' => $items,
            'payments' => [
                [
                    'paymentType' => $paymentType,
                    'amount' => (float) ($invoiceData['total'] ?? 0),
                ],
            ],
            'operator' => $invoiceData['operator'] ?? '1',
        ];
    }

    /**
     * Generate a Unique Sale Number (USN) per Macedonian fiscal regulations.
     *
     * Format: NNNNNNNN-PPPP-SSSSSSS
     *   N = operator EIK (company tax number, 8 chars)
     *   P = POS terminal number (4 digits, zero-padded)
     *   S = sequential receipt number (7 digits, zero-padded)
     */
    protected function generateUniqueSaleNumber(array $invoiceData): string
    {
        $eik = str_pad(substr($invoiceData['company_eik'] ?? '00000000', 0, 8), 8, '0', STR_PAD_LEFT);
        $pos = str_pad(substr($invoiceData['pos_number'] ?? '0001', 0, 4), 4, '0', STR_PAD_LEFT);
        $seq = str_pad(substr($invoiceData['invoice_number'] ?? '0000001', 0, 7), 7, '0', STR_PAD_LEFT);

        return "{$eik}-{$pos}-{$seq}";
    }

    private function ensureConnected(): void
    {
        if (! $this->connected || ! $this->printerId) {
            throw new FiscalDeviceException(
                'Not connected to ErpNet.FP printer. Call connect() first.',
                'erpnet-fp',
                $this->serialNumber
            );
        }
    }
}
