<?php

namespace App\Services\FiscalDevices;

use App\Exceptions\FiscalDeviceException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ErpNet.FP REST API Client
 *
 * HTTP client for the ErpNet.FP fiscal printer server.
 * ErpNet.FP is an open-source .NET application that handles
 * all vendor-specific protocols (Daisy, David, Expert, etc.)
 * and exposes a unified REST API over HTTP.
 *
 * GitHub: https://github.com/erpnet/ErpNet.FP
 *
 * API endpoints:
 *   GET  /printers                      — list discovered printers
 *   GET  /printers/{id}                 — printer info
 *   GET  /printers/{id}/status          — printer status (paper, fiscal memory, etc.)
 *   POST /printers/{id}/receipt         — print fiscal receipt
 *   POST /printers/{id}/receipt/reversal — print reversal/refund receipt
 *   GET  /printers/{id}/zreport         — daily Z-report (end of day)
 *   GET  /printers/{id}/xreport         — X-report (interim, non-resetting)
 *
 * Deployment: Docker sidecar on the same Railway private network.
 * URL: http://erpnet-fp:8001 (internal) or configurable via env.
 */
class ErpNetFpClient
{
    protected string $baseUrl;

    protected int $timeout;

    protected int $connectTimeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('mk.fiscal_devices.erpnet_fp.base_url', 'http://erpnet-fp:8001'), '/');
        $this->timeout = (int) config('mk.fiscal_devices.erpnet_fp.timeout', 15);
        $this->connectTimeout = (int) config('mk.fiscal_devices.erpnet_fp.connect_timeout', 5);
    }

    /**
     * List all discovered fiscal printers.
     *
     * @return array List of printer objects with id, uri, serialNumber, model, etc.
     */
    public function listPrinters(): array
    {
        return $this->get('/printers');
    }

    /**
     * Get printer info by ID.
     *
     * @param  string  $printerId  ErpNet.FP printer identifier (e.g., "dt517985")
     * @return array Printer details: uri, serialNumber, fiscalMemorySerialNumber, model, firmwareVersion
     */
    public function getPrinter(string $printerId): array
    {
        return $this->get("/printers/{$printerId}");
    }

    /**
     * Get printer status.
     *
     * @param  string  $printerId  ErpNet.FP printer identifier
     * @return array Status: ok, messages (warnings/errors), deviceDateTime, serialNumber
     */
    public function getPrinterStatus(string $printerId): array
    {
        return $this->get("/printers/{$printerId}/status");
    }

    /**
     * Print a fiscal receipt.
     *
     * @param  string  $printerId  ErpNet.FP printer identifier
     * @param  array  $receiptData  Receipt payload:
     *   - uniqueSaleNumber: string (format: "DT279013-0001-0000001" — NNNNNNNN-PPPP-SSSSSSS)
     *   - items: array of {text, taxGroup, unitPrice, quantity, ?discount}
     *   - payments: array of {paymentType, amount}
     *   - ?operator: string (operator name/id)
     * @return array Response: ok, receiptNumber, receiptDateTime, fiscalMemorySerialNumber, messages
     */
    public function printReceipt(string $printerId, array $receiptData): array
    {
        return $this->post("/printers/{$printerId}/receipt", $receiptData);
    }

    /**
     * Print a reversal (refund) receipt.
     *
     * @param  string  $printerId  ErpNet.FP printer identifier
     * @param  array  $reversalData  Reversal payload:
     *   - uniqueSaleNumber: string
     *   - receiptNumber: string (original receipt to reverse)
     *   - receiptDateTime: string (original receipt date)
     *   - fiscalMemorySerialNumber: string
     *   - reason: string (reversal reason)
     *   - items: same format as printReceipt
     *   - payments: same format as printReceipt
     * @return array Response: ok, receiptNumber, messages
     */
    public function printReversalReceipt(string $printerId, array $reversalData): array
    {
        return $this->post("/printers/{$printerId}/receipt/reversal", $reversalData);
    }

    /**
     * Print daily Z-report (closes the fiscal day, resets daily counters).
     *
     * @param  string  $printerId  ErpNet.FP printer identifier
     * @return array Z-report data: ok, reportNumber, totals, messages
     */
    public function printZReport(string $printerId): array
    {
        return $this->get("/printers/{$printerId}/zreport");
    }

    /**
     * Print X-report (interim report, does NOT reset counters).
     *
     * @param  string  $printerId  ErpNet.FP printer identifier
     * @return array X-report data
     */
    public function printXReport(string $printerId): array
    {
        return $this->get("/printers/{$printerId}/xreport");
    }

    /**
     * Health check — verifies the ErpNet.FP sidecar is reachable.
     *
     * @return bool True if sidecar responds
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout($this->connectTimeout)
                ->connectTimeout($this->connectTimeout)
                ->get("{$this->baseUrl}/printers");

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('ErpNet.FP: Health check failed', [
                'base_url' => $this->baseUrl,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Find a printer by serial number.
     *
     * @param  string  $serialNumber  The fiscal device serial number
     * @return array|null Printer data or null if not found
     */
    public function findPrinterBySerial(string $serialNumber): ?array
    {
        $printers = $this->listPrinters();

        foreach ($printers as $id => $printer) {
            $printerSerial = $printer['serialNumber'] ?? $printer['fiscalMemorySerialNumber'] ?? null;
            if ($printerSerial && strcasecmp($printerSerial, $serialNumber) === 0) {
                return array_merge(['id' => $id], $printer);
            }
        }

        return null;
    }

    /**
     * Send a GET request to the ErpNet.FP server.
     */
    protected function get(string $path): array
    {
        try {
            $url = "{$this->baseUrl}{$path}";

            $response = Http::timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->accept('application/json')
                ->get($url);

            if (! $response->successful()) {
                Log::error('ErpNet.FP: API error', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new FiscalDeviceException(
                    "ErpNet.FP API error: HTTP {$response->status()} — {$response->body()}",
                    'erpnet-fp'
                );
            }

            return $response->json() ?? [];
        } catch (FiscalDeviceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('ErpNet.FP: Connection error', [
                'url' => "{$this->baseUrl}{$path}",
                'error' => $e->getMessage(),
            ]);

            throw new FiscalDeviceException(
                "ErpNet.FP unreachable at {$this->baseUrl}: {$e->getMessage()}",
                'erpnet-fp'
            );
        }
    }

    /**
     * Send a POST request to the ErpNet.FP server.
     */
    protected function post(string $path, array $data): array
    {
        try {
            $url = "{$this->baseUrl}{$path}";

            Log::info('ErpNet.FP: POST request', [
                'url' => $url,
                'data_keys' => array_keys($data),
            ]);

            $response = Http::timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->accept('application/json')
                ->post($url, $data);

            if (! $response->successful()) {
                Log::error('ErpNet.FP: API error', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new FiscalDeviceException(
                    "ErpNet.FP API error: HTTP {$response->status()} — {$response->body()}",
                    'erpnet-fp'
                );
            }

            $result = $response->json() ?? [];

            Log::info('ErpNet.FP: POST response', [
                'url' => $url,
                'ok' => $result['ok'] ?? null,
                'receipt_number' => $result['receiptNumber'] ?? null,
            ]);

            return $result;
        } catch (FiscalDeviceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('ErpNet.FP: Connection error', [
                'url' => "{$this->baseUrl}{$path}",
                'error' => $e->getMessage(),
            ]);

            throw new FiscalDeviceException(
                "ErpNet.FP unreachable at {$this->baseUrl}: {$e->getMessage()}",
                'erpnet-fp'
            );
        }
    }
}
// CLAUDE-CHECKPOINT
