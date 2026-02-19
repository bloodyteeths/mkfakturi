<?php

namespace Modules\Mk\Services\FiscalDevices;

use App\Contracts\FiscalDeviceDriver;
use App\Exceptions\FiscalDeviceException;
use Illuminate\Support\Facades\Log;

/**
 * Daisy FX Fiscal Printer Driver
 *
 * Supports Daisy FX 1200 and FX 1300 fiscal printers — the most popular
 * fiscal printers in North Macedonia, sold by Fiditek (Скопје).
 *
 * Connection types:
 * - TCP/IP on configurable port (default 4999) — most common in restaurants, supermarkets
 * - RS232 serial (9600 baud, 8N1) — legacy installations
 *
 * Protocol details:
 * - Binary framing: STX (0x02) + LEN + SEQ + CMD + DATA + BCC + ETX (0x03)
 * - Commands: 0x30 (open receipt), 0x31 (sale item), 0x35 (close receipt),
 *   0x40 (status), 0x45 (daily Z-report)
 * - Device auto-sends receipt data to UJP via built-in GPRS terminal
 *
 * NOTE: Actual protocol implementation pending — requires Daisy SDK access
 * and physical test device. This driver provides the architectural scaffold.
 */
class DaisyDriver implements FiscalDeviceDriver
{
    private ?string $ipAddress = null;

    private int $port = 4999;

    private ?string $serialNumber = null;

    private ?string $serialPort = null;

    private string $connectionType = 'tcp';

    private bool $connected = false;

    public function connect(array $config): bool
    {
        $this->connectionType = $config['connection_type'] ?? 'tcp';
        $this->serialNumber = $config['serial_number'] ?? null;

        if ($this->connectionType === 'serial') {
            $this->serialPort = $config['serial_port'] ?? null;

            if (! $this->serialPort) {
                throw new FiscalDeviceException(
                    'Serial port path is required for Daisy FX (e.g. /dev/ttyUSB0 or COM3)',
                    'daisy',
                    $this->serialNumber
                );
            }

            Log::info('Daisy FX: Connecting via serial', [
                'port' => $this->serialPort,
                'serial' => $this->serialNumber,
            ]);
        } else {
            $this->ipAddress = $config['ip_address'] ?? null;
            $this->port = $config['port'] ?? 4999;

            if (! $this->ipAddress) {
                throw new FiscalDeviceException(
                    'IP address is required for Daisy FX TCP/IP connection',
                    'daisy',
                    $this->serialNumber
                );
            }

            Log::info('Daisy FX: Connecting via TCP/IP', [
                'ip' => $this->ipAddress,
                'port' => $this->port,
                'serial' => $this->serialNumber,
            ]);
        }

        // TODO: Implement actual TCP socket / serial connection when SDK is available
        $this->connected = true;

        return true;
    }

    public function sendInvoice(array $invoiceData): array
    {
        $this->ensureConnected();

        Log::info('Daisy FX: Sending invoice', [
            'items' => count($invoiceData['items'] ?? []),
            'total' => $invoiceData['total'] ?? 0,
        ]);

        // TODO: Implement when Daisy protocol specs are available
        // Steps: 1) Open receipt (CMD 0x30), 2) Send items (CMD 0x31),
        //        3) Close receipt (CMD 0x35), 4) Parse response
        throw new FiscalDeviceException(
            'Daisy FX protocol not yet implemented — awaiting UJP device API specs',
            'daisy',
            $this->serialNumber
        );
    }

    public function getStatus(): array
    {
        $this->ensureConnected();

        // TODO: Implement CMD 0x40 (status request) when SDK is available
        return [
            'connected' => $this->connected,
            'paper' => true,
            'fiscal_memory' => 'unknown',
            'last_receipt' => null,
            'errors' => [],
            'driver' => 'daisy',
            'device' => 'Daisy FX 1200/1300',
            'serial' => $this->serialNumber,
            'connection_type' => $this->connectionType,
            'note' => 'Status check stub — full implementation pending UJP specs',
        ];
    }

    public function getLastReceipt(): array
    {
        $this->ensureConnected();

        throw new FiscalDeviceException(
            'Daisy FX getLastReceipt not yet implemented',
            'daisy',
            $this->serialNumber
        );
    }

    public function getDailyReport(): array
    {
        $this->ensureConnected();

        // TODO: Implement CMD 0x45 (Z-report) when SDK is available
        throw new FiscalDeviceException(
            'Daisy FX getDailyReport not yet implemented',
            'daisy',
            $this->serialNumber
        );
    }

    public function disconnect(): void
    {
        $this->connected = false;
        Log::info('Daisy FX: Disconnected', ['serial' => $this->serialNumber]);
    }

    public function getDriverName(): string
    {
        return 'daisy';
    }

    private function ensureConnected(): void
    {
        if (! $this->connected) {
            throw new FiscalDeviceException(
                'Not connected to Daisy FX. Call connect() first.',
                'daisy',
                $this->serialNumber
            );
        }
    }
}
