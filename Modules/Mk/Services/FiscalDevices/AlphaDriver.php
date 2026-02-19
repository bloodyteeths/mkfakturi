<?php

namespace Modules\Mk\Services\FiscalDevices;

use App\Contracts\FiscalDeviceDriver;
use App\Exceptions\FiscalDeviceException;
use Illuminate\Support\Facades\Log;

/**
 * Alpha Fiscal Cash Register Driver
 *
 * Standard fiscal cash register sold in North Macedonia by Fiditek.
 * Suitable for stores, pharmacies, boutiques, cosmetics shops, offices,
 * services, and cafes.
 *
 * Features:
 * - Full-size keypad and display
 * - Built-in GPRS terminal (auto-sends to UJP)
 * - Crypto module + SIM card + SD card
 *
 * Connection types:
 * - RS232 serial (primary)
 * - TCP/IP via LAN adapter
 *
 * NOTE: Actual protocol implementation pending — requires physical test device.
 */
class AlphaDriver implements FiscalDeviceDriver
{
    private ?string $ipAddress = null;

    private int $port = 4999;

    private ?string $serialNumber = null;

    private ?string $serialPort = null;

    private string $connectionType = 'serial';

    private bool $connected = false;

    public function connect(array $config): bool
    {
        $this->connectionType = $config['connection_type'] ?? 'serial';
        $this->serialNumber = $config['serial_number'] ?? null;

        if ($this->connectionType === 'serial') {
            $this->serialPort = $config['serial_port'] ?? null;

            if (! $this->serialPort) {
                throw new FiscalDeviceException(
                    'Serial port path is required for Alpha (e.g. /dev/ttyUSB0 or COM3)',
                    'alpha',
                    $this->serialNumber
                );
            }

            Log::info('Alpha: Connecting via serial', [
                'port' => $this->serialPort,
                'serial' => $this->serialNumber,
            ]);
        } else {
            $this->ipAddress = $config['ip_address'] ?? null;
            $this->port = $config['port'] ?? 4999;

            if (! $this->ipAddress) {
                throw new FiscalDeviceException(
                    'IP address is required for Alpha TCP/IP connection',
                    'alpha',
                    $this->serialNumber
                );
            }

            Log::info('Alpha: Connecting via TCP/IP', [
                'ip' => $this->ipAddress,
                'port' => $this->port,
                'serial' => $this->serialNumber,
            ]);
        }

        $this->connected = true;

        return true;
    }

    public function sendInvoice(array $invoiceData): array
    {
        $this->ensureConnected();

        throw new FiscalDeviceException(
            'Alpha protocol not yet implemented — awaiting UJP device API specs',
            'alpha',
            $this->serialNumber
        );
    }

    public function getStatus(): array
    {
        $this->ensureConnected();

        return [
            'connected' => $this->connected,
            'paper' => true,
            'fiscal_memory' => 'unknown',
            'last_receipt' => null,
            'errors' => [],
            'driver' => 'alpha',
            'device' => 'Alpha',
            'serial' => $this->serialNumber,
            'connection_type' => $this->connectionType,
            'note' => 'Status check stub — full implementation pending UJP specs',
        ];
    }

    public function getLastReceipt(): array
    {
        $this->ensureConnected();

        throw new FiscalDeviceException(
            'Alpha getLastReceipt not yet implemented',
            'alpha',
            $this->serialNumber
        );
    }

    public function getDailyReport(): array
    {
        $this->ensureConnected();

        throw new FiscalDeviceException(
            'Alpha getDailyReport not yet implemented',
            'alpha',
            $this->serialNumber
        );
    }

    public function disconnect(): void
    {
        $this->connected = false;
        Log::info('Alpha: Disconnected', ['serial' => $this->serialNumber]);
    }

    public function getDriverName(): string
    {
        return 'alpha';
    }

    private function ensureConnected(): void
    {
        if (! $this->connected) {
            throw new FiscalDeviceException(
                'Not connected to Alpha. Call connect() first.',
                'alpha',
                $this->serialNumber
            );
        }
    }
}
